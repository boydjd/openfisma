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
     * Current User
     * @var User
     */
    private $_me = null;

    /**
     * The message displayed to the user when their e-mail address needs validation.
     */
    const VALIDATION_MESSAGE = "<br />Because you changed your e-mail address, we have sent you a confirmation message.
                                <br />You will need to confirm the validity of your new e-mail address before you will
                                receive any e-mail notifications.";
    
    /**
     * init() - Initialize internal data structures.
     */         
    public function init()
    {
        $this->_user = new User();
        $this->_me = User::currentUser();
    }

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
                /** @doctrine fix the logging function */
                //$this->_user->log('LOGINFAILURE', '', 'Failure');
                // Notice that we don't tell the user whether the username is correct or not.
                // This is a security feature to prevent bruteforcing usernames.
                throw new Zend_Auth_Exception("Incorrect username or password");                
            }
            
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
                $authAdapter = new Fisma_Auth_Adapter_Doctrine($user, 'username', 'password');
                $authAdapter->setCredential($password);
            }

            $auth = Zend_Auth::getInstance();
            $authResult = $auth->authenticate($authAdapter);
            
            if ($authResult->isValid()) {
                // Set up the session timeout for the authentication token
                $authSession = new Zend_Session_Namespace(
                    Zend_Auth::getInstance()->getStorage()->getNamespace()
                );
                $authSession->setExpirationSeconds(
                    Configuration::getConfig('session_inactivity_period') * 60
                );
                $authSession->currentUser = $user;
            } else {
                /** @doctrine fix logging */
                //$this->_user->log('LOGINFAILURE',$whologin['id'],'Failure');
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
                $passWarningTs = new Zend_Date($user->passwordTs);
                $passWarningTs->add($passExpirePeriod - $passWarningPeriod, Zend_Date::DAY);
                $now = Zend_Date::now();
                if ($now->isLater($passWarningTs)) {
                    $leaveDays = $passWarningPeriod - $now->sub($passWarningTs, Zend_Date::DAY);
                    $message = "Your password will expire in $leaveDays days,"
                               . " you should change it now.";
                    $this->message($message, self::M_WARNING);
                    // redirect back to password change action
                    $this->_helper->_actionStack('header', 'Panel');
                    $this->_forward('password');
                }
            }
            
            // Check if the user is using the system standard hash function
            if (Configuration::getConfig('hash_type') != $user->hashType) {
                $message = 'This version of the application uses an improved password storage scheme.'
                         . ' You will need to change your password in order to upgrade your account.';
                $this->message($message, self::M_WARNING);
                $this->_helper->_actionStack('header', 'Panel');
                $this->_forward('password');
            }
            
            // Check to see if the user needs to review the rules of behavior.
            // If they do, then send them to that page. Otherwise, send them to
            // the dashboard.
            $nextRobReview = new Zend_Date($user->lastRob);
            $nextRobReview->add(Configuration::getConfig('rob_duration'), Zend_Date::DAY);
            if ($nextRobReview->isEarlier(new Zend_Date())) {
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
     * store user last accept rob
     * create a audit event
     */
    public function acceptrobAction() {
        $now = new Zend_Date();
        $nowSqlString = $now->toString('Y-m-d H:i:s');
        $this->_user->update(array('last_rob'=>$nowSqlString), 'id = '.$this->_me->id);
        $this->_user->log('ROB_ACCEPT', $this->_me->id, 'Digitally accepted the Rules of Behavior');
        $this->_forward('index', 'Panel');
    }

    /**
     * Close out the current user's session.
     */
    public function logoutAction() {
        if (!empty($this->_me)) {
            $this->_me->logout();
        }
        unset($this->_me);
        $this->_forward('login');
    }

    /**
     * notificationsAction() - Display the user's "Edit Profile" page.
     *
     * @todo Cleanup this method: comments and formatting
     */
    public function notificationsAction()
    {
        // assign notification events
        $event = new Event();

        $ret = $this->_user->find($this->_me->id);
        $this->view->notify_frequency = $ret->current()->notify_frequency;
        $this->view->notify_email = $ret->current()->notify_email;
        $allEvent = $event->getUserAllEvents($this->_me->id);
        $enabledEvent = $event->getEnabledEvents($this->_me->id);

        $this->view->availableList = array_diff($allEvent, $enabledEvent);
        $this->view->enableList = array_intersect($allEvent, $enabledEvent);
    }

    /**
     * savenotifyAction() - Handle any edits to a user's notification settings.
     *
     * @todo Cleanup this method: comments and formatting
     * @todo This method is named incorrectly
     */
    public function savenotifyAction()
    {
        $event = new Event();
        $data = $this->_request->getPost();
        $row = $this->_user->find($this->_me->id);
        $originalEmail = $row->current()->notify_email;

        if (!isset($data['enableEvents'])) {
            $data['enableEvents'] = array();
        }
        $event->saveEnabledEvents($this->_me->id, $data['enableEvents']);
        $notifyData = array('notify_frequency' => $data['notify_frequency'],
        'notify_email' => $data['notify_email']);
        $ret = $this->_user->update($notifyData, "id = " . $this->_me->id);
        if ($ret > 0 || 0 == $ret) {
            $msg = "Notification events modified successfully.";
            $model = self::M_NOTICE;
        } else {
            $msg = "Failed to update the notification events.";
            $model = self::M_WARNING;
        }


        if ($originalEmail != $data['notify_email'] && $data['notify_email'] != '') {
            $this->_user
            ->update(array('email_validate'=>0), 'id = ' . $this->_me->id);
            $result = $this->emailvalidate($this->_me->id, $data['notify_email'], 'update');
            if (true == $result) {
                $msg .= self::VALIDATION_MESSAGE;
            }
        }

        $this->view->setScriptPath(Fisma::getPath('application') . '/views/scripts');
        $this->message($msg, $model);
        $this->_forward('notifications');
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
     * emailvalidateAction() - Validate the user's e-mail change.
     *
     * @todo Cleanup this method: comments and formatting
     */
    public function emailvalidateAction()
    {
        $userId = $this->_request->getParam('id');
        $ret = $this->_user->find($userId);
        $userEmail = $ret->current()->email;
        $notifyEmail = $ret->current()->notify_email;
        $email = !empty($notifyEmail)?$notifyEmail:$userEmail;
        $query = $this->_user
        ->getAdapter()
        ->select()
        ->from('validate_emails', 'validate_code')
        ->where('user_id = ?', $userId)
        ->where('email = ?', $email)
        ->order('id DESC');
        $ret = $this->_user->getAdapter()->fetchRow($query);
        if ($this->_request->getParam('code') == $ret['validate_code']) {
            $this->_user->getAdapter()->delete('validate_emails', 'user_id = '.$userId);
            $this->_user->update(array('email_validate'=>1), 'id = '.$userId);
            $msg = "Your e-mail address has been validated. You may close this window or click <a href='http://"
            . $_SERVER['HTTP_HOST']
            . "'>here</a> to enter "
            . Configuration::getConfig('system_name')
            . '.';
        } else {
            $msg = "Error: Your e-mail address can not be confirmed. Please contact an administrator.";
        }
        $this->view->msg = $msg;
    }
    
    
    /**
     * This function is callback function.
     * When you selected a option, 
     * the values of options is not only saved in cookie
     * but also saved in database.
     * This part deals saving in database.
     * 
     */
    public function setColumnPreferenceAction()
    {
        $user = new User();
        $user->setColumnPreference($this->_me->id, $_COOKIE[self::COOKIE_NAME]);
        $this->_helper->layout->setLayout('ajax');
        $this->_helper->viewRenderer->setNoRender();
    }
}
