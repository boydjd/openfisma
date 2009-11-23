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
class AuthController extends Zend_Controller_Action
{
    /**
     * The message displayed to the user when their e-mail address needs validation.
     */
    const VALIDATION_MESSAGE = "<br />Because you changed your e-mail address, we have sent you a confirmation message.
                                <br />You will need to confirm the validity of your new e-mail address before you will
                                receive any e-mail notifications.";
    
    /**
     * The error message displayed when a user's credentials are incorrect
     */
    const CREDENTIAL_ERROR_MESSAGE = "Invalid username or password";

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
            // Honor the session.cookie_secure configuration from Zend_Session. If
            // the user attempts to login over HTTP with session.cookie_secure=true
            // the user will be shown an error message that they must use the system
            // over HTTPS.
            if ( Zend_Session::getOptions('cookie_secure') && 
                !$this->_request->isSecure() 
            ) {
                throw new Zend_Auth_Exception("You must access this application via HTTPS, since secure cookies are"
                                            . " enabled.");
            }
            
            // Verify account exists and is not locked
            $user = Doctrine::getTable('User')->findOneByUsername($username);
            if (!$user) {
                // Notice that we don't tell the user whether the username is correct or not.
                // This is a security feature to prevent bruteforcing usernames.
                throw new Zend_Auth_Exception(self::CREDENTIAL_ERROR_MESSAGE);                
            }
            $user->checkAccountLock();

            // Check if account has expired
            $accountExpiration = new Zend_Date($user->lastLoginTs, Zend_Date::ISO_8601);
            $expirationPeriod = Configuration::getConfig('account_inactivity_period');
            $accountExpiration->addDay($expirationPeriod);
            $now = Zend_Date::now();
            if ($accountExpiration->isEarlier($now)) {
                $user->lockAccount(User::LOCK_TYPE_INACTIVE);
                $reason = $user->getLockReason();
                throw new Fisma_Exception_AccountLocked("Account is locked ($reason)");
            }

            // Perform authentication
            $auth = Zend_Auth::getInstance();
            $auth->setStorage(new Fisma_Auth_Storage_Session());
            $authAdapter = $this->getAuthAdapter($user, $password);
            $authResult = $auth->authenticate($authAdapter); 

            // Generate log entries and notifications
            if (!$authResult->isValid()) {
                $user->log(User::LOGIN_FAILURE, "Login failure");
                Notification::notify('LOGIN_FAILURE', $user, $user);
                throw new Zend_Auth_Exception(self::CREDENTIAL_ERROR_MESSAGE);
            }
            
            // At this point, authentication is successful. Log in the user to update last login time, last login IP,
            // etc.
            $user->login();
            Notification::notify('LOGIN_SUCCESS', $user, $user);
            $user->log(User::LOGIN, "Successful Login");
            
            // Set cookie for 'column manager' to control the columns visible on the search page
            // Persistent cookies are prohibited on U.S. government web servers by federal law. 
            // This cookie will expire at the end of the session.
            Fisma_Cookie::set(User::SEARCH_PREF_COOKIE, $user->searchColumnsPref);

            // Check whether the user's password is about to expire (for database authentication only)
            if ('database' == Configuration::getConfig('auth_type')) {
                $passExpirePeriod = Configuration::getConfig('pass_expire');
                $passWarningPeriod = Configuration::getConfig('pass_warning');
                $passWarningTs = new Zend_Date($user->passwordTs, 'Y-m-d');
                $passWarningTs->add($passExpirePeriod - $passWarningPeriod, Zend_Date::DAY);
                $now = Zend_Date::now();
                if ($now->isLater($passWarningTs)) {
                    $leaveDays = $passWarningPeriod - $now->sub($passWarningTs, Zend_Date::DAY);
                    $message = "Your password will expire in $leaveDays days,"
                             . " you should change it now.";
                    $this->view->priorityMessenger($message, 'warning');
                    // redirect back to password change action
                    $this->_forward('user', 'Panel', null, array('sub'=>'password'));
                    return;
                }
            }
            
            // Check if the user is using the system standard hash function
            if (Configuration::getConfig('hash_type') != $user->hashType) {
                $message = 'This version of the application uses an improved password storage scheme.'
                         . ' You will need to change your password in order to upgrade your account.';
                $this->view->priorityMessenger($message, 'warning');
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
     * Returns a suitable authentication adapter based on system configuration and current user
     * 
     * @param User $user Authentication adapters may be different for different users
     * @param string $password
     * @return Zend_Auth_Adapter_Interface
     */
    public function getAuthAdapter(User $user, $password)
    {
        // Determine authentication method (based on system configuration, except root is always authenticated against
        // the database)
        $method = Configuration::getConfig('auth_type');

        if ('root' == $user->username) {
            $method = 'database';
        }

        // Construct an adapter for the desired authentication method
        switch ($method) {
            case 'ldap':
                $ldapConfig = LdapConfig::getConfig();
                $authAdapter = new Fisma_Auth_Adapter_Ldap($ldapConfig, $user->username, $password);
                break;
            case 'database':
                $authAdapter = new Fisma_Auth_Adapter_Doctrine($user, $password);
                break;
            default:
                throw new Zend_Auth_Exception('Invalid authentication method ($method)');
                break;
        }
        
        return $authAdapter;
    }

    /**
     * Close out the current user's session.
     */
    public function logoutAction() {
        $currentUser = User::currentUser();

        if ($currentUser) {
            Notification::notify('LOGOUT', $currentUser, $currentUser);
            $currentUser->log(User::LOGOUT, 'Log out');
        }

        $auth = Zend_Auth::getInstance();
        $auth->setStorage(new Fisma_Auth_Storage_Session());
        $auth->clearIdentity();
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
