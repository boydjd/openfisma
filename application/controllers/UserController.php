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
 * @author    Jim Chen <xhorse@users.sourceforge.net>
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/mw/index.php?title=License
 * @version   $Id$
 */

/**
 * Handles CRUD for "user" objects.
 *
 * @package   Controller
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/mw/index.php?title=License
 */
class UserController extends MessageController
{
    /**
     * The current user for this session.
     *
     * @var User
     */
    private $_user = null;
    
    /**
     * The Zend_Auth identity corresponding to the current user.
     *
     * @var Zend_Auth
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
        $this->_me = Zend_Auth::getInstance()->getIdentity();
    }
    
    /**
     * loginAction() - Handles user login, verifying the password, etc.
     */
    public function loginAction()
    {
        $req = $this->getRequest();
        $username = $req->getPost('username');
        $password = $req->getPost('userpass');
        
        // If the username isn't passed in the post variables, then just display
        // the login screen without any further processing.
        $this->_helper->layout->setLayout('login');
        if ( empty($username) ) {
            return $this->render();
        }

        try {
            /**
             * @todo Fix this SQL injection
             */
            $whologin = $this->_user->fetchRow("account = '$username'");

            // If the username isn't found, throw an exception
            if (empty($whologin)) {
                $this->_user->log(User::LOGINFAILURE, '',
                    "This username does not exist: ".$username);
                throw new Zend_Auth_Exception("Incorrect username or password");
            }
            
            $whologin = $whologin->toArray();

            // If the account is locked...
            // (due to manual lock, expired account, password errors, etc.)
            if ( ! $whologin['is_active'] && 'database' == Config_Fisma::readSysConfig('auth_type')) {
                $unlockEnabled = Config_Fisma::readSysConfig('unlock_enabled');
                if (1 == intval($unlockEnabled)) {
                    $unlockDuration = Config_Fisma::readSysConfig('unlock_duration');

                    // If the system administrator has elected to have accounts
                    // unlock automatically, then calculate how much time is
                    // left on the lock.
                    $now = new Zend_Date();
                    $terminationTs = new Zend_Date($whologin['termination_ts']);
                    $terminationTs->add($unlockDuration, Zend_Date::SECOND);
                    $unlockRemaining = clone $terminationTs;
                    $unlockRemaining->sub($now);
                    $minutesRemaining = ceil($unlockRemaining->getTimestamp() / 60);

                    if ($terminationTs->isEarlier($now)) {
                        $updateData =
                        $this->_user->update(array('is_active'=>1,
                                                   'failure_count'=>0,
                                                   'termination_ts'=>NULL),
                                                   'id  = '.$whologin['id']);
                    } else {
                        throw new Zend_Auth_Exception('Your user account has been locked due to'
                                                    . Config_Fisma::readSysConfig("failure_threshold")
                                                    . ' or more unsuccessful login attempts. Your account will be'
                                                    . " unlocked in $minutesRemaining minutes. Please try again at that"
                                                    . 'time.');
                    }
                } else {
                    // If accounts are not unlocked automatically on this
                    // system, then let the user know that they need to contact
                    // the administrator.
                    throw new Zend_Auth_Exception('Your account has been locked due to '
                                                . Config_Fisma::readSysConfig("failure_threshold")
                                                . ' or more unsuccessful login attempts. Please contact the'
                                                . ' <a href="mailto:'
                                                . Config_Fisma::readSysConfig('contact_email')
                                                . '">Administrator</a>.');
                }
            }

            // Proceed through authorization based on the configured mechanism
            // (LDAP, Database, etc.)
            $authType = Config_Fisma::readSysConfig('auth_type');
            $auth = Zend_Auth::getInstance();
            $result = $this->authenticate($authType, $username, $password);
            
            if (!$result->isValid()) {
                $this->_user->log(User::LOGINFAILURE,
                                  $whologin['id'],
                                  'Password Error');
                $notification = new Notification();
                $notification->add(Notification::ACCOUNT_LOGIN_FAILURE,
                                   null,
                                   "User: {$whologin['account']}");

                if ($whologin['failure_count'] >= Config_Fisma::readSysConfig('failure_threshold') - 1) {
                    $this->_user->log(User::TERMINATION,
                                      $whologin['id'],
                                      'Account locked');
                    $notification = new Notification();
                    $notification->add(Notification::ACCOUNT_LOCKED,
                                       null,
                                       "User: {$whologin['account']}");
                    throw new Zend_Auth_Exception('Your account has been locked due to '
                                                . Config_Fisma::readSysConfig("failure_threshold")
                                                . ' or more unsuccessful login attempts. Please contact the'
                                                . ' <a href="mailto:'
                                                . Config_Fisma::readSysConfig('contact_email')
                                                . '">Administrator</a>.');
                }
                
                throw new Zend_Auth_Exception("Incorrect username or password");
            }
            
            // At this point, the user is authenticated.
            // Now check if the account has expired.
            $_me = (object)$whologin;
            $period = Config_Fisma::readSysConfig('max_absent_time');
            $deactiveTime = new Zend_Date();
            $deactiveTime->sub($period, Zend_Date::DAY);
            $lastLogin = new Zend_Date($whologin['last_login_ts'],
                                       'YYYY-MM-DD HH-MI-SS');

            if ( !$lastLogin->equals(new Zend_Date('0000-00-00 00:00:00')) && $lastLogin->isEarlier($deactiveTime) ) {
                throw new Zend_Auth_Exception("Your account has been locked because you have not logged in for $period"
                                            . "or more days. Please contact the <a href=\"mailto:"
                                            . Config_Fisma::readSysConfig('contact_email')
                                            . '">Administrator</a>.');
            }
            
            // If we get this far, then the login is totally successful.
            $this->_user->log(User::LOGIN, $_me->id, "Success");
            $notification = new Notification();
            $notification->add(Notification::ACCOUNT_LOGIN_SUCCESS,
                               $whologin['account'],
                               "UserId: {$whologin['id']}");

            // Initialize the Access Control
            $nickname = $this->_user->getRoles($_me->id);
            foreach ($nickname as $n) {
                $_me->roleArray[] = $n['nickname'];
            }
            if (empty( $_me->roleArray )) {
                $_me->roleArray[] = $_me->account . '_r';
            }
            $_me->systems = $this->_user->getMySystems($_me->id);
            
            // Set up the session timeout
            $store = $auth->getStorage();
            $exps = new Zend_Session_Namespace($store->getNamespace());
            $exps->setExpirationSeconds(Config_Fisma::readSysConfig('expiring_seconds'));
            $store->write($_me);
            
            // Check to see if the user needs to review the rules of behavior.
            // If they do, then send them to that page. Otherwise, send them to
            // the dashboard.
            $nextRobReview = new Zend_Date($whologin['last_rob'], 'Y-m-d');
            $nextRobReview->add(Config_Fisma::readSysConfig('rob_duration'), Zend_Date::DAY);
            $now = new Zend_Date();
            if ($now->isEarlier($nextRobReview)) {
                $this->_forward('index', 'Panel');
            } else {
                $this->_helper->layout->setLayout('notice');
                return $this->render('rule');
            }
        } catch(Zend_Auth_Exception $e) {
            $this->view->assign('error', $e->getMessage());
            $this->render();
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
        $this->_user->log(User::ROB_ACCEPT, $this->_me->id, 'accept ROB');
        $this->_forward('index', 'Panel');
    }

    /**
     * logoutAction() - Close out the current user's session.
     */
    public function logoutAction() {
        if (!empty($this->_me)) {
            $this->_user->log(User::LOGOUT, $this->_me->id, $this->_me->account . ' logout');
            $notification = new Notification();
            $notification->add(Notification::ACCOUNT_LOGOUT, null, "User: {$this->_me->account}");
            Zend_Auth::getInstance()->clearIdentity();
        }
        $this->_forward('login');
    }

    /**
     * getprofileForm() - Returns the standard form for reading, and updating
     * the current user's profile.
     *
     * @return Zend_Form
     *
     * @todo This function is not named correctly
     */
    public function getprofileForm()
    {
        $form = Form_Manager::loadForm('account');
        $form->removeElement('account');
        $form->removeElement('password');
        $form->removeElement('confirmPassword');
        $form->removeElement('ldap_dn');
        $form->removeElement('checkdn');
        $form->removeElement('role');
        $form->removeElement('is_active');
        return $form;
    }

    /**
     * profileAction() - Display the user's "Edit Profile" page.
     *
     * @todo Cleanup this method: comments and formatting
     */
    public function profileAction()
    {
        // Profile Form
        $form = $this->getprofileForm();
        $query = $this->_user
                      ->select()->setIntegrityCheck(false)
                      ->from('users',
                             array('name_last',
                                   'name_first',
                                   'phone_office',
                                   'phone_mobile',
                                   'email',
                                   'title'))
                      ->where('id = ?', $this->_me->id);
        $userProfile = $this->_user->fetchRow($query)->toArray();
        $form->setAction("/panel/user/sub/updateprofile");
        $form->setDefaults($userProfile);
        $this->view->assign('form', Form_Manager::prepareForm($form));

        $this->render();
    }

    /**
     * passwordAction() - Display the change password page
     */
    public function passwordAction()
    {
        // Load the change password file
        $passwordForm = Form_Manager::loadForm('change_password');
        $passwordForm = Form_Manager::prepareForm($passwordForm);
        
        // Prepare the password requirements explanation:
        $requirements[] = "Length must be between "
                        . Config_Fisma::readSysConfig('pass_min')
                        . " and "
                        . Config_Fisma::readSysConfig('pass_max')
                        . " characters long.";
        if (Config_Fisma::readSysConfig('pass_uppercase') == 1) {
            $requirements[] = "Must contain at least 1 upper case character (A-Z)";
        }
        if (Config_Fisma::readSysConfig('pass_lowercase') == 1) {
            $requirements[] = "Must contain at least 1 lower case character (a-z)";
        }
        if (Config_Fisma::readSysConfig('pass_numerical') == 1) {
            $requirements[] = "Must contain at least 1 numeric digit (0-9)";
        }
        if (Config_Fisma::readSysConfig('pass_special') == 1) {
            $requirements[] = htmlentities("Must contain at least 1 special character (!@#$%^&*-=+~`_)");
        }

        $this->view->assign('requirements', $requirements);
        $this->view->assign('form', $passwordForm);
        
        $this->render();
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

        $this->render();
    }

    /**
     * updateprofileAction() - Handle any edits to a user's profile settings.
     *
     * @todo Cleanup this method: comments and formatting
     * @todo This method is named incorrectly
     */
    public function updateprofileAction()
    {
        // Load the account form in order to perform validations.
        $form = $this->getProfileForm();
        $formValid = $form->isValid($_POST);
        $profileData = $form->getValues();
        unset($profileData['submit']);

        if ($formValid) {
            $result = $this->_user->find($this->_me->id);
            $originalEmail = $result->current()->email;
            $notifyEmail = $result->current()->notify_email;
            $ret = $this->_user->update($profileData, 'id = '.$this->_me->id);
            if ($ret == 1) {
                $this->_user
                     ->log(User::MODIFICATION, $this->_me->id, "{$this->_me->account} Profile Modified");
                $msg = "Profile modified successfully.";

                if ($originalEmail != $profileData['email']
                    && empty($notifyEmail)) {
                    $this->_user->update(array('email_validate'=>0), 'id = '.$this->_me->id);
                    $this->emailvalidate($this->_me->id, $profileData['email'], 'update');
                    $msg .= self::VALIDATION_MESSAGE;
                }
                $this->view->setScriptPath(APPLICATION_PATH . '/views/scripts');
                $this->message($msg, self::M_NOTICE);
            } else {
                $this->message("Unable to update account. ($ret)",
                    self::M_WARNING);
            }
        } else {
            /**
             * @todo this error display code needs to go into the decorator,
             * but before that can be done, the function it calls needs to be
             * put in a more convenient place
             */
            $errorString = '';
            foreach ($form->getMessages() as $field => $fieldErrors) {
                if (count($fieldErrors > 0)) {
                    foreach ($fieldErrors as $error) {
                        $label = $form->getElement($field)->getLabel();
                        $errorString .="$label: $error<br>";
                    }
                }
            }

            $this->message("Unable to update account:<br>" . addslashes($errorString), self::M_WARNING);
        }
        $this->_forward('profile');
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
            $this->_emailvalidate($this->_me->id, $data['notify_email'], 'update');
            $msg .= self::VALIDATION_MESSAGE;
        }

        $this->view->setScriptPath(APPLICATION_PATH . '/views/scripts');
        $this->message($msg, $model);
        $this->_forward('notifications');
    }

        
    /**
     * pwdchangeAction() - Handle any edits to a user's profile settings.
     *
     * @todo Cleanup this method: comments and formatting
     * @todo This method is named incorrectly
     */
    public function pwdchangeAction()
    {
        $req = $this->getRequest();
        if ('save' == $req->getParam('s')) {
            $post = $req->getPost();
            $passwordForm = Form_Manager::loadForm('change_password');
            $passwordForm = Form_Manager::prepareForm($passwordForm);
            $password = $passwordForm->getElement('newPassword');
            $password->addValidator(new Form_Validator_Password());
            $formValid = $passwordForm->isValid($post);
            if (!$formValid) {
                /**
                * @todo this error display code needs to go into the decorator,
                * but before that can be done, the function it calls needs to be
                * put in a more convenient place
                */
                $errorString = '';
                foreach ($passwordForm->getMessages() as $field => $fieldErrors) {
                    if (count($fieldErrors>0)) {
                        foreach ($fieldErrors as $error) {
                            $label = $passwordForm->getElement($field)->getLabel();
                            $errorString .= "$label: $error<br>";
                        }
                    }
                }
                // Error message
                $msg = "Unable to change password:<br>".$errorString;
                $model = self::M_WARNING;
            } else {
                $newPass = Config_Fisma::encrypt($req->newPassword);
                $ret = $this->_user->find($this->_me->id)->current();
                $historyPass = $ret->historyPassword;
                if (strpos($historyPass, $ret->password) > 0) {
                    $historyPass = ':' . $newPass . $historyPass;
                } else {
                    $historyPass = ':' . $newPass . ':' . $ret->password . $historyPass;
                }
                $historyPass = substr($historyPass, 0, 99);
                $now = date('Y-m-d H:i:s');
                $data = array(
                    'password' => $newPass,
                    'history_password' => $historyPass,
                    'password_ts' => $now
                );
                $result = $this->_user->update($data,
                    'id = ' . $this->_me->id);
                if (!$result) {
                    $msg = 'Failed to change the password';
                    $model = self::M_WARNING;
                } else {
                    $msg = 'Password changed successfully';
                    $model = self::M_NOTICE;
                }
            }
            $this->message($msg, $model);
        }
        $this->_helper->actionStack('header', 'Panel');
        $this->_forward('password');
    }
    
    /**
     * authenticate() - Authenticate the user against LDAP or backend database.
     *
     * @param string $type The type of authorization ('ldap' or 'database')
     * @param string $username Username for login
     * @param string $password Password for login
     * @return Zend_Auth_Result
     */
    protected function authenticate($type, $username, $password) {
        $db = Zend_Registry::get('db');

        // The root user is always authenticated against the database.
        if ($username == 'root') {
            $type = 'database';
        }

        // Handle LDAP or database authentication for non-root users.
        if ($type == 'ldap') {
            $config = new Config();
            $data = $config->getLdap();
            $authAdapter = new Zend_Auth_Adapter_Ldap($data, $username, $password);
        } else if ($type == 'database') {
            $authAdapter = new Zend_Auth_Adapter_DbTable($db, 'users', 'account', 'password');
            $encryptPass = Config_Fisma::encrypt($password);
            $authAdapter->setIdentity($username)->setCredential($encryptPass);
        }
        
        $auth = Zend_Auth::getInstance();
        return $auth->authenticate($authAdapter);
    }

    /**
     * privacyAction() - Display the system's privacy policy.
     *
     * @todo the business logic is stored in the view instead of the controller
     */
    public function privacyAction()
    {
        $this->render();
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
        $this->render();
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
                 . "'>here</a> to go back to "
                 . Config_Fisma::readSysConfig('system_name')
                 . '.';
        } else {
            $msg = "Error: Your e-mail address can not be confirmed. Please contact an administrator.";
        }
        $this->view->msg = $msg;
        $this->render();
    }
}
