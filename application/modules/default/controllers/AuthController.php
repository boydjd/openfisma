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
     * @GETAllowed
     *
     * @return void
     */
    public function loginAction()
    {
        // initialize reverse proxy options
        $reverseProxyOptions = $this->getInvokeArg('bootstrap')->getOption('reverse_proxy_auth');
        $reverseProxyEnabled = isset($reverseProxyOptions['enable']) && $reverseProxyOptions['enable'];
        $reverseProxyHttpHeader = isset($reverseProxyOptions['http_header'])
            ? $reverseProxyOptions['http_header']
            : 'username';
        $reverseProxyAllowStandardLogin = isset($reverseProxyOptions['allow_standard_login'])
            ? $reverseProxyOptions['allow_standard_login']
            : false;

        $this->_helper->layout->setLayout('login');
        $username = $this->getRequest()->getPost('username');
        $password = $this->getRequest()->getPost('userpass');

        if ($reverseProxyEnabled) {
            $serverKey = 'HTTP_' . strtoupper(strtr($reverseProxyHttpHeader, '-', '_'));
            if (isset($_SERVER[$serverKey])) {
                $username = $_SERVER[$serverKey];
            } else if (!$reverseProxyAllowStandardLogin) {
                throw new Fisma_Zend_Exception_User('Use of standard login form disabled.');
            } else { // using standard login
                $reverseProxyEnabled = false;
            }
        }

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
            $user->checkAccountLock(false, $reverseProxyEnabled);

            // Perform authentication
            $auth = Zend_Auth::getInstance();
            $auth->setStorage(new Fisma_Zend_Auth_Storage_Session());
            if ($reverseProxyEnabled) {
                $auth->getStorage()->write($user);
            } else {
                $authAdapter = $this->getAuthAdapter($user, $password);
                $authResult = $auth->authenticate($authAdapter);

                // Generate log entries and notifications
                if (!$authResult->isValid()) {
                    $user->getAuditLog()->write("Failed login ({$_SERVER['REMOTE_ADDR']})");
                    Notification::notify('ACCOUNT_LOGIN_FAILURE', $user, $user);
                    Notification::notify('USER_LOGIN_FAILURE', $user, $user, array('userId' => $user->id));
                    throw new Zend_Auth_Exception(self::CREDENTIAL_ERROR_MESSAGE);
                }
            }

            $msgs = $this->_getFailedLoginMessage($user);
            if (!empty($msgs)) {
                $this->view->priorityMessenger($msgs);
            }

            $user->login();
            Notification::notify('ACCOUNT_LOGIN_SUCCESS', $user, $user);
            Notification::notify('USER_LOGIN_SUCCESS', $user, $user, array('userId' => $user->id));
            $user->getAuditLog()->write("Logged in ({$_SERVER['REMOTE_ADDR']})");
            if ($user->timezoneAuto) {
                $user->timezone = $this->getRequest()->getParam('timezoneOffset');
                $user->save();
            }

            // Register rulesOfBehavior forced action so that user can't view other pages
            // until Rob is accepted
            if ($this->_checkUserRulesOfBehavior($user)) {
                $forward = array("module" => 'default', "controller" => 'User', "action" => 'accept-rob');
                $this->_helper->ForcedAction->registerForcedAction($user->id, 'rulesOfBehavior', $forward);
            }

            // Check whether the user's password is about to expire (for database authentication only)
            if (!$reverseProxyEnabled
                && ('database' == Fisma::configuration()->getConfig('auth_type') || 'root' == $user->username)
            ) {

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
                $this->_redirect('/index/home');
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
     * @GETAllowed
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
     * @GETAllowed
     * @return void
     */
    public function logoutAction()
    {
        $currentUser = CurrentUser::getInstance();

        if ($currentUser) {
            $currentUser->getAuditLog()->write('Logged out');
            $currentUser->clearViewAs();
        }

        $auth = Zend_Auth::getInstance();
        $auth->setStorage(new Fisma_Zend_Auth_Storage_Session());
        $auth->clearIdentity();
        $this->_redirect('/auth/login');

    }

    /**
     * Forget username, password, or request unlock
     *
     * @GETAllowed
     * @return void
     */
    public function recoverAction()
    {
        $this->_helper->layout->setLayout('login');
        if ($this->getRequest()->isPost()) {
            $username = $this->getRequest()->getPost('username');
            $email = $this->getRequest()->getPost('email');
            $recover = $this->getRequest()->getPost('recover');

            $sender = Fisma::configuration()->getConfig('sender');
            $method = Fisma::configuration()->getConfig('auth_type');
            $systemName = Fisma::configuration()->getConfig('system_name');
            $contactName = Fisma::configuration()->getConfig('contact_name');
            $contactEmail = Fisma::configuration()->getConfig('contact_email');
            $contactSubject =Fisma::configuration()->getConfig('contact_subject');

            switch ($recover) {
                case 'password':
                    if ($user = Doctrine::getTable('User')->findOneByUsername($username)) {
                        if ($user->lockType == 'manual') {
                            $this->view->error = "Account $user->username has been disabled.";
                        } else {
                            // @TODO: Reset password if not using ldap
                            // if ($method === 'ldap') {
                                $options = array('user' => $user);
                                $mail = new Mail();

                                $mail->sender        = $sender;
                                $mail->senderName    = $systemName;
                                $mail->recipient     = $contactEmail;
                                $mail->recipientName = $contactName;
                                $mail->subject       = "$contactSubject ($recover)";
                                $this->view->error   = "Administrator has been contacted.";
                            /*} else {
                                $this->view->error = "Password has been reset for $username";
                            }*/
                        }
                    } else {
                        $this->view->error = "User $username not found.";
                    }
                    break;
                case 'unlock':
                    if ($user = Doctrine::getTable('User')->findOneByUsername($username)) {
                        if ($user->lockType == 'manual') {
                            $this->view->error = "Account $user->username has been disabled.";
                        } else if (!$user->locked) {
                            $this->view->error = "Account $user->username is not locked.";
                        } else {
                            $options = array('user' => $user);
                            $mail = new Mail();

                            $mail->sender        = $sender;
                            $mail->senderName    = $systemName;
                            $mail->recipient     = $contactEmail;
                            $mail->recipientName = $contactName;
                            $mail->subject       = "$contactSubject ($recover)";
                            $this->view->error   = "Administrator has been contacted.";
                        }
                    } else {
                        $this->view->error = "User $username not found.";
                    }
                    break;
                case 'username':
                    if ($user = Doctrine::getTable('User')->findOneByEmail($email)) {
                        if ($user->lockType == 'manual') {
                            $this->view->error = "This account has been disabled.";
                        } else {
                            $options = array('user' => $user, 'systemName' => $systemName);
                            $mail = new Mail();

                            $mail->sender        = $sender;
                            $mail->senderName    = $systemName;
                            $mail->recipient     = $email;
                            $mail->recipientName = $user->displayName;
                            $mail->subject       = "[{$systemName}] Your account information";
                            $this->view->error   = "Account info will be sent to <{$email}>.";
                        }
                    } else {
                        $this->view->error = "$email is not associated with a registered user.";
                    }
                    break;
                case 'request':
                    $options = array('email' => $email);
                    $mail = new Mail();

                    $mail->sender        = $sender;
                    $mail->senderName    = $systemName;
                    $mail->recipient     = $contactEmail;
                    $mail->recipientName = $contactName;
                    $mail->subject       = "$contactSubject ($recover)";
                    $this->view->error   = "Administrator has been contacted.";
                    break;
            }
            if (isset($mail)) {
                try {
                    $mail->mailTemplate($recover, $options);
                    $handler = (isset($mailHandler)) ? $mailHandler : new Fisma_MailHandler_Immediate();
                    $handler->setMail($mail)->send();
                } catch (Zend_Mail_Exception $e) {
                    throw new Fisma_Zend_Exception($e);
                }
            }
        }
    }

    /**
     * Display the system's privacy policy.
     *
     * @GETAllowed
     * @return void
     * @todo the business logic is stored in the view instead of the controller
     */
    public function privacyAction()
    {
    }

    /**
     * Display the system's Rules Of Behavior.
     *
     * @GETAllowed
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
     * @GETAllowed
     * @return void
     */
    public function refreshSessionAction()
    {
        $this->view->result = new Fisma_AsyncResponse();
        $this->view->result->succeed();
    }

    /**
     * Get the failed login messages.
     *
     * @param user object
     * @return array The messages are shown at the message box.
     */
    private function _getFailedLoginMessage($user)
    {
        $msgs = array();
        if ('database' == Fisma::configuration()->getConfig('auth_type') && $user->failureCount > 0) {
            $attempt = (1==$user->failureCount) ? 'attempt' : 'attempts';
            $be = (1==$user->failureCount) ? 'was' : 'were';
            $warningMsg = "There $be " . $user->failureCount . " bad login $attempt since your last login.";
            $msgs[] = array('warning' => $warningMsg);
        }

       return $msgs;
    }
}
