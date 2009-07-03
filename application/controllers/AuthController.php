<?php
/**
 * Copyright (c) 2008 Endeavor Systems, Inc.
 *
 * This file is part of OpenFISMA.
 *
 * OpenFISMA is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * OpenFISMA is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with OpenFISMA.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @package   Controller
 * @author    Jim Chen <xhorse@users.sourceforge.net>
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/mw/index.php?title=License
 * @version   $Id$
 */

/**
 * Handles CRUD for Authentication objects.
 *
 * @package   Controller
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/mw/index.php?title=License
 */
class AuthController extends MessageController
{
    /**
     * The message displayed to the user when their e-mail address needs validation.
     */
    const VALIDATION_MESSAGE = "<br />Because you changed your e-mail address, we have sent you a confirmation message.
                                <br />You will need to confirm the validity of your new e-mail address before you will
                                receive any e-mail notifications.";

    /**
     * Handling user login
     * 
     * The login process verifies the credential provided by the user. The authentication can
     * be performed against the database or LDAP provider, according to the application's 
     * configuration. Also, it enforces the security policies set by the
     * application.
     */
    public function loginAction()
    {
        $this->_helper->layout->setLayout('login');
        $username = $this->getRequest()->getPost('username');
        $password = $this->getRequest()->getPost('userpass');

        // If the username isn't passed in the post variables, then just display
        // the login screen without any further processing.
        if ( empty($username) ) {
            return $this->render();
        }
        
        // Attempt login. Display any authentication exceptions back to the user
        try {
            $user = Doctrine::getTable('User')->findOneByUsername($username);
            
            // If the user name isn't found, then display an error message
            if (!$user) {
                // Notice that we don't tell the user whether the username is correct or not.
                // This is a security feature to prevent bruteforcing usernames.
                throw new Zend_Auth_Exception("Incorrect username or password");                
            }
            
            $user->getTable()->getRecordListener()->get('BaseListener')->setOption('disabled', true);
            // Authenticate this user based on their password
            $authType = Configuration::getConfig('auth_type');
            // The root user is always authenticated against the database.
            if ($username == 'root') {
                $authType = 'database';
            }

            // Any policy effect the authentication result will go inside the Auth_Adapter
            if ($authType == 'ldap') {
                // Handle LDAP authentication 
                $config = new Config();
                $data = $config->getLdap();
                $authAdapter = new Zend_Auth_Adapter_Ldap($data, $username, $password);
            } else if ($authType == 'database') {
                // Handle database authentication 
                $authAdapter = new Fisma_Auth_Adapter_Doctrine($user);
                $authAdapter->setCredential($password);
            }

            $auth = Zend_Auth::getInstance();
            $auth->setStorage(new Fisma_Auth_Storage_Session());
            $authResult = $auth->authenticate($authAdapter); 
            
            if (!$authResult->isValid()) {
                throw new Zend_Auth_Exception("Incorrect username or password");
            }
            
            // Set cookie for 'column manager' to control the columns visible on the search page
            // Persistent cookies are prohibited on U.S. government web servers by federal law. 
            // This cookie will expire at the end of the session.
            setcookie(User::SEARCH_PREF_COOKIE, $user->searchColumnsPref, false, '/');

            $passExpirePeriod = Configuration::getConfig('pass_expire');
            // Check whether the user's password is about to expire (for database authentication only)
            if ('database' == Configuration::getConfig('auth_type')) {
                $passWarningPeriod = Configuration::getConfig('pass_warning');
                $passWarningTs = new Zend_Date($user->passwordTs, 'Y-m-d');
                $passWarningTs->add($passExpirePeriod - $passWarningPeriod, Zend_Date::DAY);
                $now = Zend_Date::now();
                if ($now->isLater($passWarningTs)) {
                    $leaveDays = $passWarningPeriod - $now->sub($passWarningTs, Zend_Date::DAY);
                    $message = "Your password will expire in $leaveDays days,"
                               . " you should change it now.";
                    $this->message($message, self::M_WARNING);
                    // redirect back to password change action
                    $this->_forward('user', 'Panel', null, array('sub'=>'password'));
                    return;
                }
            }
            
            // Check if the user is using the system standard hash function
            if (Configuration::getConfig('hash_type') != $user->hashType) {
                $message = 'This version of the application uses an improved password storage scheme.'
                         . ' You will need to change your password in order to upgrade your account.';
                $this->message($message, self::M_WARNING);
                $this->_helper->_actionStack('header', 'Panel');
                $this->_forward('password', 'User');
                return;
            }
            
            // Check to see if the user needs to review the rules of behavior.
            // If they do, then send them to that page. Otherwise, send them to
            // the dashboard.
            $nextRobReview = new Zend_Date($user->lastRob);
            $nextRobReview->add(Configuration::getConfig('rob_duration'), Zend_Date::DAY);
            if (is_null($user->lastRob) || $nextRobReview->isEarlier(new Zend_Date())) {
                $this->_helper->layout->setLayout('notice');
                return $this->render('rule');
            }
            
            // Finally, if the user has passed through all of this, 
            // send them to their original requested page or dashboard otherwise
            $redirectInfo = new Zend_Session_Namespace('redirect_page');
            if (isset($redirectInfo->page) && !empty($redirectInfo->page)) {
                $path = $redirectInfo->page;
                unset($redirectInfo->page);
                $this->_response->setRedirect($path);
            } else {
                $this->_forward('index', 'Panel');
            }
        } catch(Zend_Auth_Exception $e) {
            // If any Auth exceptions are caught during login, 
            // then return to the login screen and display the message
            $this->view->assign('error', $e->getMessage());
        }
    }

    /**
     * Close out the current user's session.
     */
    public function logoutAction() {
        $user = User::currentUser();
        if (!empty($user)) {
            $user->logout();
        }
        $this->_forward('login');
    }

    /**
     * privacyAction() - Display the system's privacy policy.
     *
     * @todo the business logic is stored in the view instead of the controller
     */
    public function privacyAction()
    {
    }

    /**
     * robAction() - Display the system's Rules Of Behavior.
     *
     * @todo the business logic is stored in the view instead of the controller
     * @todo rename this function to rulesOfBehaviorAction -- that name is
     * easier to understand
     */
    public function robAction()
    {
    }


    /**
     * Validate the user's e-mail change.
     *
     * @todo Cleanup this method: comments and formatting
     */
    public function emailvalidateAction()
    {
        $userId = $this->_request->getParam('id');
        $code   = $this->_request->getParam('code');
        $error  = true;

        $user   = Doctrine::getTable('User')->find($userId);
        if (!empty($user)) {
            if ($user->validateEmail($code)) {
                /** @todo english, also see the follow */
                $message =  'Your e-mail address has been validated. You may close this window ' .
                  'or click <a href="/">here</a> to enter ' . Configuration::getConfig('system_name');
                $error = false;
            }
        }
        
        if ($error) {
            $message = "Error: Your e-mail address can not be confirmed. Please contact an administrator.";
        }
        $this->view->msg = $message;
    }
}
