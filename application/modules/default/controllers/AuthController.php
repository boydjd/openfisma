<?php
/**
 * Copyright (c) 2008 Endeavor Systems, Inc.
 *
 * This file is part of OpenFISMA.
 *
 * OpenFISMA is free software: you can redistribute it and/or modify it under the terms of the GNU General Public 
 * License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later
 * version.
 *
 * OpenFISMA is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied 
 * warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for more 
 * details.
 *
 * You should have received a copy of the GNU General Public License along with OpenFISMA.  If not, see 
 * {@link http://www.gnu.org/licenses/}.
 */

/**
 * Handles CRUD for Authentication objects.
 *
 * @author     Jim Chen <xhorse@users.sourceforge.net>
 * @copyright  (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Controller
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
     * Initialize internal members.
     * 
     * @return void
     */
    public function init()
    {
        parent::init();
        $this->_helper->contextSwitch()
                      ->addActionContext('refresh-session', 'json')
                      ->initContext();
    }

    /**
     * Handling user login
     * 
     * The login process verifies the credential provided by the user. The authentication can
     * be performed against the database or LDAP provider, according to the application's 
     * configuration. Also, it enforces the security policies set by the
     * application.
     * 
     * @return void
     */
    public function loginAction()
    {
        $this->_helper->layout->setLayout('login');
        $username = $this->getRequest()->getPost('username');
        $password = $this->getRequest()->getPost('userpass');
        
        // Display anonymous reporting button if IR module is enabled
        $incidentModule = Doctrine::getTable('Module')->findOneByName('Incident Reporting');

        $this->view->showReportIncidentButton = ($incidentModule && $incidentModule->enabled);

        $incidentModule = Doctrine::getTable('Module')->findOneByName('Incident Reporting');

        $this->view->showReportIncidentButton = ($incidentModule && $incidentModule->enabled);

        // If the username isn't passed in the post variables, then just display
        // the login screen without any further processing.
        if ( empty($username) ) {
            return $this->render();
        }
        
        // Attempt login. Display any authentication exceptions back to the user
        try {
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
            $expirationPeriod = Fisma::configuration()->getConfig('account_inactivity_period');
            $accountExpiration->addDay($expirationPeriod);
            $now = Zend_Date::now();
            if ($accountExpiration->isEarlier($now)) {
                $user->lockAccount(User::LOCK_TYPE_INACTIVE);
                $reason = $user->getLockReason();
                throw new Fisma_Zend_Exception_AccountLocked("Account is locked ($reason)");
            }

            // Perform authentication
            $auth = Zend_Auth::getInstance();
            $auth->setStorage(new Fisma_Zend_Auth_Storage_Session());
            $authAdapter = $this->getAuthAdapter($user, $password);
            $authResult = $auth->authenticate($authAdapter); 

            // Generate log entries and notifications
            if (!$authResult->isValid()) {
                $user->getAuditLog()->write("Failed login ({$_SERVER['REMOTE_ADDR']})");
                Notification::notify('LOGIN_FAILURE', $user, $user);
                throw new Zend_Auth_Exception(self::CREDENTIAL_ERROR_MESSAGE);
            }
            
            // At this point, authentication is successful. Log in the user to update last login time, last login IP,
            // etc.
            $lastLoginInfo = new Zend_Session_Namespace('last_login_info');
            $lastLoginInfo->lastLoginTs = $user->lastLoginTs;
            $lastLoginInfo->lastLoginIp = $user->lastLoginIp;
            $lastLoginInfo->failureCount = $user->failureCount;
                        
            $user->login();
            Notification::notify('LOGIN_SUCCESS', $user, $user);
            $user->getAuditLog()->write("Logged in ({$_SERVER['REMOTE_ADDR']})");
            
            // Register rulesOfBehavior forced action so that user can't view other pages 
            // until Rob is accepted
            if ($this->_checkUserRulesOfBehavior($user)) {
                $forward = array("module" => 'default', "controller" => 'User', "action" => 'accept-rob');
                $this->_helper->ForcedAction->registerForcedAction($user->id, 'rulesOfBehavior', $forward);
            }            

            // Check whether the user's password is about to expire (for database authentication only)
            if ('database' == Fisma::configuration()->getConfig('auth_type') || 'root' == $user->username) {

                // Check if the user's mustResetPassword flag is set 
                if ($user->mustResetPassword) {
                    $message = ' You will need to change your password.';
                    $this->view->priorityMessenger($message, 'warning');

                    // reset default layout and forward to password change action
                    $this->_helper->layout->setLayout('layout');

                    // Register mustResetPassword forced action so that user can't view other pages 
                    // until password is changed
                    $forward = array("module" => 'default', "controller" => "user", "action" => 'password');
                    $this->_helper->ForcedAction->registerForcedAction($user->id, 'mustResetPassword', $forward);

                    $this->_redirect('/user/password');
                    return;
                }

                $passExpirePeriod = Fisma::configuration()->getConfig('pass_expire');
                $passWarningPeriod = Fisma::configuration()->getConfig('pass_warning');
                $passWarningTs = new Zend_Date($user->passwordTs, Fisma_Date::FORMAT_DATE);
                $passWarningTs->add($passExpirePeriod - $passWarningPeriod, Zend_Date::DAY);
                $now = Zend_Date::now();
                if ($now->isLater($passWarningTs)) {
                    //set the password expiration day, and daysRemaining = expiration date - now
                    $passWarningTs->add($passWarningPeriod, Zend_Date::DAY);
                    $daysRemaining = floor($passWarningTs->sub($now)->toValue() / 86400);
                    $message = "Your password will expire in $daysRemaining days,"
                             . " you should change it now.";
                    $this->view->priorityMessenger($message, 'warning');
                    // reset default layout and forward to password change action
                    $this->_helper->layout->setLayout('layout');
                    $this->_redirect('/user/password');
                    return;
                }

                // Check if the user is using the system standard hash function
                if (Fisma::configuration()->getConfig('hash_type') != $user->hashType) {
                    $message = 'This version of the application uses an improved password storage scheme.'
                             . ' You will need to change your password in order to upgrade your account.';
                    $this->view->priorityMessenger($message, 'warning');
                    // reset default layout
                    $this->_helper->layout->setLayout('layout');
                    $this->_redirect('/user/password');
                    return;
                }

            }
           
            // Finally, if the user has passed through all of this, 
            // send them to their original requested page or dashboard otherwise
            $session = Fisma::getSession();
            if (isset($session->redirectPage) && !empty($session->redirectPage)) {
                $path = $session->redirectPage;
                unset($session->redirectPage);
                $this->_response->setRedirect($path);
            } else {
                $this->_helper->layout->setLayout('layout');
                $this->_redirect('/index/index');
            }
        } catch (Zend_Auth_Exception $zae) {
            // If any Auth exceptions are caught during login, 
            // then return to the login screen and display the message
            $this->view->assign('error', $zae->getMessage());
        } catch (Fisma_Zend_Exception $fze) {
            $userMessage = 'Authentication is not configured correctly. Contact your server administrator.';
            $this->view->assign('error', $userMessage);

            // No stack trace is logged because zend ldap will include the stack trace in its log message
            $logMessage = $fze->getMessage();
            $this->getInvokeArg('bootstrap')->getResource('log')->err($logMessage);
        }
    }

    /**
     * Returns a suitable authentication adapter based on system configuration and current user
     * 
     * @param User $user Authentication adapters may be different for different users
     * @param string $password The corresponding password of the specified user
     * @return Zend_Auth_Adapter_Interface The suitable authentication adapter
     */
    public function getAuthAdapter(User $user, $password)
    {
        // Determine authentication method (based on system configuration, except root is always authenticated against
        // the database)
        $method = Fisma::configuration()->getConfig('auth_type');

        if ('root' == $user->username) {
            $method = 'database';
        }

        // Construct an adapter for the desired authentication method
        switch ($method) {
            case 'ldap':
                $ldapConfig = LdapConfig::getConfig();
                $authAdapter = new Fisma_Zend_Auth_Adapter_Ldap($ldapConfig, $user->username, $password);
                break;
            case 'database':
                $authAdapter = new Fisma_Zend_Auth_Adapter_Doctrine($user, $password);
                break;
            default:
                throw new Zend_Auth_Exception('Invalid authentication method ($method)');
                break;
        }
        
        return $authAdapter;
    }

    /**
     * Close out the current user's session
     * 
     * @return void
     */
    public function logoutAction() 
    {
        $currentUser = CurrentUser::getInstance();
       
        if ($currentUser) {
            $currentUser->getAuditLog()->write('Logged out');
            Notification::notify('LOGOUT', $currentUser, $currentUser);
        }

        $auth = Zend_Auth::getInstance();
        $auth->setStorage(new Fisma_Zend_Auth_Storage_Session());
        $auth->clearIdentity();
        $this->_redirect('/auth/login');

    }

    /**
     * Display the system's privacy policy.
     * 
     * @return void
     * @todo the business logic is stored in the view instead of the controller
     */
    public function privacyAction()
    {
    }

    /**
     * Display the system's Rules Of Behavior.
     * 
     * @return void
     * @todo the business logic is stored in the view instead of the controller
     * @todo rename this function to rulesOfBehaviorAction -- that name is
     * easier to understand
     */
    public function robAction()
    {
    }
    
    /**
     * Check whether user needs to accept rules of behavior
     * 
     * @param user object
     * @return true if user does, otherwise, false 
     */
    private function _checkUserRulesOfBehavior($user)
    {
        $nextRobReview = new Zend_Date($user->lastRob, Zend_Date::ISO_8601);
        $nextRobReview->add(Fisma::configuration()->getConfig('rob_duration'), Zend_Date::DAY);

        if (is_null($user->lastRob) || $nextRobReview->isEarlier(new Zend_Date())) {
            return true;
        }

        return false;
    }

    /**
     * A no-op action to continue the users' session
     *
     * @return void
     */
    public function refreshSessionAction()
    {
        $this->view->result = new Fisma_AsyncResponse();
        $this->view->result->succeed();
    }
}
