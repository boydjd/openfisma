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

            // If the account is locked...
            // (due to manual lock, expired account, password errors, etc.)
            if ( ! $whologin->is_active ) {
                $unlockEnabled = readSysConfig('unlock_enabled');
                if (1 == intval($unlockEnabled)) {
                    $unlockDuration = readSysConfig('unlock_duration');

                    // If the system administrator has elected to have accounts
                    // unlock automatically, then calculate how much time is
                    // left on the lock.
                    $now = new Zend_Date();
                    $terminationTs = new Zend_Date($whologin->termination_ts);
                    $terminationTs->add($unlockDuration, Zend_Date::SECOND);
                    $unlockRemaining = clone $terminationTs;
                    $unlockRemaining->sub($now);
                    $minutesRemaining =
                        ceil($unlockRemaining->getTimestamp() / 60);

                    if ($terminationTs->isEarlier($now)) {
                        $updateData =
                        $this->_user->update(array('is_active'=>1,
                                                   'failure_count'=>0,
                                                   'termination_ts'=>NULL),
                                                   'id  = '.$whologin->id);
                    } else {
                        throw new Zend_Auth_Exception('Your user account has
                        been locked due to ' .
                        readSysConfig("failure_threshold") .
                        " or more unsuccessful login attempts. Your account will
                        be unlocked in $minutesRemaining minutes. Please try
                        again at that time.");
                    }
                } else {
                    // If accounts are not unlocked automatically on this
                    // system, then let the user know that they need to contact
                    // the administrator.
                    throw new Zend_Auth_Exception('Your account has been locked
                        due to ' .
                        readSysConfig("failure_threshold") .
                        ' or more unsuccessful login attempts. Please contact
                        the <a href="mailto:' .
                        readSysConfig('contact_email') .
                        '">Administrator</a>.');
                }
            }

            // Proceed through authorization based on the configured mechanism
            // (LDAP, Database, etc.)
            $authType = readSysConfig('auth_type');
            $auth = Zend_Auth::getInstance();
            $result = $this->authenticate($authType, $username, $password);
            
            if (!$result->isValid()) {
                $this->_user->log(User::LOGINFAILURE,
                                  $whologin->id,
                                  'Password Error');
                $notification = new Notification();
                $notification->add(Notification::ACCOUNT_LOGIN_FAILURE,
                                   null,
                                   "User: {$whologin->account}");

                if ($whologin->failure_count >=
                    readSysConfig('failure_threshold') - 1) {
                    $this->_user->log(User::TERMINATION,
                                      $whologin->id,
                                      'Account locked');
                    $notification = new Notification();
                    $notification->add(Notification::ACCOUNT_LOCKED,
                                       null,
                                       "User: {$whologin->account}");
                    throw new Zend_Auth_Exception('Your account has been locked
                        due to ' .
                        readSysConfig("failure_threshold") .
                        ' or more unsuccessful login attempts. Please contact
                        the <a href="mailto:' .
                        readSysConfig('contact_email') .
                        '">Administrator</a>.');
                }
                
                throw new Zend_Auth_Exception("Incorrect username or password");
            }
            
            // At this point, the user is authenticated.
            // Now check if the account has expired.
            $_me = (object)($whologin->toArray());
            $period = readSysConfig('max_absent_time');
            $deactiveTime = new Zend_Date();
            $deactiveTime->sub($period, Zend_Date::DAY);
            $lastLogin = new Zend_Date($_me->last_login_ts,
                                       'YYYY-MM-DD HH-MI-SS');

            if ( !$lastLogin->equals(new Zend_Date('0000-00-00 00:00:00')) 
                && $lastLogin->isEarlier($deactiveTime) ) {
                throw new Zend_Auth_Exception("Your account has been locked
                    because you have not logged in for $period or more days.
                    Please contact the <a href=\"mailto:" .
                    readSysConfig('contact_email') .
                    '">Administrator</a>.');
            }
            
            // If we get this far, then the login is totally successful.
            $this->_user->log(User::LOGIN, $_me->id, "Success");
            $notification = new Notification();
            $notification->add(Notification::ACCOUNT_LOGIN_SUCCESS,
                $whologin->account,
                "UserId: {$whologin->id}");

            // Initialize the Access Control
            $nickname = $this->_user->getRoles($_me->id);
            foreach ($nickname as $n) {
                $_me->roleArray[] = $n['nickname'];
            }
            if ( empty( $_me->roleArray ) ) {
                $_me->roleArray[] = $_me->account . '_r';
            }
            $_me->systems = $this->_user->getMySystems($_me->id);
            
            // Set up the session timeout
            $store = $auth->getStorage();
            $exps = new Zend_Session_Namespace($store->getNamespace());
            $exps->setExpirationSeconds(readSysConfig('expiring_seconds'));
            $store->write($_me);
            
            // Check to see if the user needs to review the rules of behavior.
            // If they do, then send them to that page. Otherwise, send them to
            // the dashboard.
            $nextRobReview = new Zend_Date($_me->last_rob, 'Y-m-d');
            $nextRobReview->add(readSysConfig('rob_duration'), Zend_Date::DAY);
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
        $this->_user->update(array('last_rob'=>$nowSqlString),
            'id = '.$this->_me->id);
        $this->_user->log(User::ROB_ACCEPT, $this->_me->id, 'accept ROB');
        $this->_forward('index', 'Panel');
    }

    /**
     * logoutAction() - Close out the current user's session.
     */
    public function logoutAction() {
        if (!empty($this->_me)) {
            $this->_user->log(User::LOGOUT, $this->_me->id,
                $this->_me->account . ' logout');
            $notification = new Notification();
            $notification->add(Notification::ACCOUNT_LOGOUT, null,
                "User: {$this->_me->account}");
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
        $form->removeElement('confirm_password');
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
        $query = $this->_user->select()->setIntegrityCheck(false)
                            ->from('users', array('name_last', 'name_first',
                                'phone_office', 'phone_mobile', 'email',
                                'title'))
                            ->where('id = ?', $this->_me->id);
        $userProfile = $this->_user->fetchRow($query)->toArray();
        $form->setAction("/panel/user/sub/updateprofile");
        $form->setDefaults($userProfile);
        $this->view->assign('form', Form_Manager::prepareForm($form));

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
                $this->_user->log(User::MODIFICATION, $this->_me->id, 
                    "{$this->_me->account} Profile Modified");
                $msg = "Profile modified successfully.";

                if ($originalEmail != $profileData['email']
                    && empty($notifyEmail)) {
                    $this->_user->update(array('email_validate'=>0),
                        'id = '.$this->_me->id);
                    $this->_emailvalidate($this->_me->id,
                        $profileData['email'], 'update');
                    $msg .= "<br />Because you changed your e-mail address, we
                            have sent you a confirmation message.<br />You will
                            need to confirm the validity of your new e-mail
                            address before you will receive any e-mail
                            notifications.";
                }
                $this->view->setScriptPath(VIEWS . '/scripts');
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
            // Error message
            $this->message("Unable to update account:<br>"
                .addslashes($errorString),
                self::M_WARNING);
            // On error, redirect back to the profile action.
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
        $notifyData = array('notify_frequency' =>
                                $data['notify_frequency'],
                            'notify_email' => $data['notify_email']);
        $ret = $this->_user->update($notifyData, "id = ".$this->_me->id);
        if ($ret > 0 || 0 == $ret) {
            $msg = "Notification events modified successfully.";
            $model = self::M_NOTICE;
        } else {
            $msg = "Failed to update the notification events.";
            $model = self::M_WARNING;
        }


        if ( $originalEmail != $data['notify_email']
             && $data['notify_email'] != '' ) {
            $this->_user->update(array('email_validate'=>0),
                        'id = '.$this->_me->id);
            $this->_emailvalidate($this->_me->id,
                        $data['notify_email'], 'update');
            $msg .="<br />Because you changed your notification e-mail address,
                    we have sent you a confirmation message.<br />You will need
                    to confirm the validity of your new e-mail address before
                    you will receive any e-mail notifications.";
        }

        $this->view->setScriptPath(VIEWS . DS . 'scripts');
        $this->message($msg, $model);
        $this->_forward('profile');
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
            $auth = Zend_Auth::getInstance();
            $_me = $auth->getIdentity();
            $id = $_me->id;
            $pwds = $req->getPost('pwd');
            $oldpass = md5($pwds['old']);
            $newpass = md5($pwds['new']);
            $res = $this->_user->find($id)->toArray();
            $password = $res[0]['password'];
            $historyPass = $res[0]['history_password'];
            if ($pwds['new'] != $pwds['confirm']) {
                $msg = 'The new password does not match the confirm password,'
                       .' please try again.';
                $model = self::M_WARNING;
            } else {
                if ($oldpass != $password) {
                    $msg = 'The old password supplied is incorrect,'
                           .' please try again.';
                    $model = self::M_WARNING;
                } else {
                    if (!$this->checkPassword($pwds['new'], 2)) {
                        $msg = 'This password does not meet the password
                               complexity requirements.<br>
Please create a password that adheres to these complexity requirements:<br>
--The password must be at least 8 character long<br>
--The password must contain at least 1 lower case letter (a-z),
 1 upper case letter (A-Z), and 1 digit (0-9)<br>
--The password can also contain National Characters if desired 
(Non-Alphanumeric, !,@,#,$,% etc.)<br>
--The password cannot be the same as your last 3 passwords<br>
--The password cannot contain your first name or last name<br>";';
                        throw new FismaException($msg);
                    } else {
                        if ($newpass == $password) {
                            $msg = 'Your new password cannot be the same as'
                                   .' your old password.';
                            $model = self::M_WARNING;
                        } else {
                            if (strpos($historyPass, $newpass) > 0) {
                                $msg = 'Your password must be different from'
                                    .' the last three passwords you have used.'
                                    .' Please pick a different password.';
                                $model = self::M_WARNING;
                            } else {
                                if (strpos($historyPass, $password) > 0) {
                                    $historyPass = ':' . $newpass
                                        . $historyPass;
                                } else {
                                    $historyPass = ':' . $newpass . ':'
                                        . $password . $historyPass;
                                }
                                $historyPass = substr($historyPass, 0, 99);
                                $now = date('Y-m-d H:i:s');
                                $data = array(
                                    'password' => $newpass,
                                    'history_password' => $historyPass,
                                    'password_ts' => $now
                                );
                                $result = $this->_user->update($data,
                                    'id = ' . $id);
                                if (!$result) {
                                    $msg = 'Failed to change the password';
                                    $model = self::M_WARNING;
                                } else {
                                    $msg = 'Password changed successfully';
                                    $model = self::M_NOTICE;
                                }
                            }
                        }
                    }
                }
            }
            $this->message($msg, $model);
        }
        $this->_helper->actionStack('header', 'Panel');
        $this->_forward('profile');
    }
    
    /**
     * checkPassword() - ??? (Does this implement password complexity? If so,
     * this is deprecated in favor of using a Zend_validator.
     *
     * @todo Cleanup this method: comments and formatting...what does this
     * method do?
     */
    function checkPassword($pass, $level = 1) {
        if ($level > 1) {

            $nameincluded = true;
            // check last name
            if (empty($this->user_name_last)
                || strpos($pass, $this->user_name_last) === false) {
                $nameincluded = false;
            }
            if (!$nameincluded) {
                // check first name
                if (empty($this->user_name_first)
                    || strpos($pass, $this->user_name_first) === false) {
                    $nameincluded = false;
                } else {
                    $nameincluded = true;
                }
            }
            if ($nameincluded) return false; // include first name or last name
            // high level
            if (strlen($pass) < 8) return false;
            // must be include three style among upper case letter,
            // lower case letter, symbol, digit.
            // following rule: at least three type in four type,
            // or symbol and any of other three types
            $num = 0;
            if (preg_match("/[0-9]+/", $pass)) // all are digit
            $num++;
            if (preg_match("/[a-z]+/", $pass)) // all are digit
            $num++;
            if (preg_match("/[A-Z]+/", $pass)) // all are digit
            $num++;
            if (preg_match("/[^0-9a-zA-Z]+/", $pass)) // all are digit
            $num+= 2;
            if ($num < 3) return false;
        } else if ($level == 1) {
            // low level
            if (strlen($pass) < 3) return false;
            // must include three style among upper case letter,
            // lower case letter, symbol, digit.
            // following rule: at least two type in four type
            if (preg_match("/^[0-9]+$/", $pass)) // all are digit
            return false;
            if (preg_match("/^[a-z]+$/", $pass)) // all are lower case letter
            return false;
            if (preg_match("/^[A-Z]+$/", $pass)) // all are upper case letter
            return false;
        }
        return true;
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
            $authAdapter = new Zend_Auth_Adapter_Ldap($data, $username,
                                                      $password);
        } else if ($type == 'database') {
            $authAdapter = new Zend_Auth_Adapter_DbTable($db, 'users',
                                                        'account', 'password');
            $authAdapter->setIdentity($username)->setCredential(md5($password));
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
        $query = $this->_user->getAdapter()->select()
                      ->from('validate_emails', 'validate_code')
                      ->where('user_id = ?', $userId)
                      ->where('email = ?', $email)
                      ->order('id DESC');
        $ret = $this->_user->getAdapter()->fetchRow($query);
        if ($this->_request->getParam('code') == $ret['validate_code']) {
            $this->_user->getAdapter()->delete('validate_emails',
                'user_id = '.$userId);
            $this->_user->update(array('email_validate'=>1), 'id = '.$userId);
            $msg = "Your e-mail address has been validated. You may close this
                    window or click <a href='http://"
                    . $_SERVER['HTTP_HOST']
                    . "'>here</a> to go back to "
                    . readSysConfig('system_name')
                    . '.';
        } else {
            $msg = "Error: Your e-mail address can not be confirmed. Please
                    contact an administrator.";
        }
        $this->view->msg = $msg;
        $this->render();
    }

    /**
     * _emailvalidate() - Validate the user's e-mail change.
     *
     * @todo Cleanup this method: comments and formatting
     * @todo This function is named incorrectly
     */
    protected function _emailvalidate($userId, $email, $type)
    {
        $mail = new Zend_Mail();

        $mail->setFrom(readSysConfig('sender'), readSysConfig('system_name'));
        $mail->addTo($email);
        $mail->setSubject("Email validation");

        $validateCode = md5(rand());
        
        $data = array('user_id'=>$userId, 'email'=>$email,
            'validate_code'=>$validateCode);
        $this->_user->getAdapter()->insert('validate_emails', $data);

        $contentTpl = $this->view->setScriptPath(VIEWS . DS . 'scripts/mail');
        $contentTpl = $this->view;

        $contentTpl->actionType = $type;
        $contentTpl->validateCode = $validateCode;
        $contentTpl->userId = $userId;
        $content = $contentTpl->render('validate.tpl');
        $mail->setBodyText($content);
        $mail->send($this->_getTransport());
    }

    /**
     * _getTransport() - Return the appropriate Zend_Mail_Transport subclass,
     * based on the system's configuration.
     *
     * @return Zend_Mail_Transport_Smtp|Zend_Mail_Transport_Sendmail
     */
    protected function _getTransport()
    {
        $transport = null;
        if ( 'smtp' == readSysConfig('send_type')) {
            $config = array('auth' => 'login',
                'username' => readSysConfig('smtp_username'),
                'password' => readSysConfig('smtp_password'),
                'port' => readSysConfig('smtp_port'));
            $transport = new Zend_Mail_Transport_Smtp(
                readSysConfig('smtp_host'), $config);
        } else {
            $transport = new Zend_Mail_Transport_Sendmail();
        }
        return $transport;
    }
}
