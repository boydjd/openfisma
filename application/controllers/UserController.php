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
 * @package   Controller
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
     * A session cookie that stores a bitmap of which columns the user wants to view on the search page. 
     */
    const COOKIE_NAME = 'search_columns_pref';
    
    /**
     * init() - Initialize internal data structures.
     */         
    public function init()
    {
        $this->_user = new User();
        $this->_me = Zend_Auth::getInstance()->getIdentity();
    }

    /**
     * Handling user login
     * 
     * The login process verifies the credential provided by the user. The authentication can
     * be performed against the database or LDAP provider, 
     * according to the application's configuration. Also, it enforces the security policies set by the
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
        
        // Attempt login. Display any authorization exceptions back to the user
        try {
            $user = User::login($username, $password);
            
            // Check whether the user's password is about to expire
            $passExpirationPeriod = Configuration::getConfig('pass_expire');
            $passWarningPeriod = Configuration::getConfig('pass_warning');
            $passWarningTs = new Zend_Date($user->passwordTs);
            $passWarningTs->sub($passExpirationPeriod - $passWarningPeriod, Zend_Date::DAY);
            if ($passWarningTs->isEarlier(new Zend_Date())) {
                $message = "Your password will expire in $leaveDays days, you should change it now.";
                $model = self::M_WARNING;
                $this->message($message, $model);
                // redirect back to password change action
                $this->_helper->_actionStack('header', 'Panel');
                $this->_forward('password');
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
            
            // Finally, if the user has passed through all of this, send them to their original requested
            // page. If they don't have a requested page, send them to the main dashboard.
            $redirectInfo = new Zend_Session_Namespace('redirect_page');
            if (isset($redirectInfo->page) && !empty($redirectInfo->page)) {
                $path = $redirectInfo->page;
                unset($redirectInfo->page);
                $this->_response->setRedirect($path);
            }
            $this->_forward('index', 'Panel');
        } catch(Zend_Auth_Exception $e) {
            // If any Auth exceptions are caught during login, then return to the login screen
            // and display the message
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
     * logoutAction() - Close out the current user's session.
     */
    public function logoutAction() {
        if (!empty($this->_me)) {
            /** @doctrine fix logging */
            //$this->_user->log('LOGOUT', $this->_me->id, 'Success');
            //$notification = new Notification();
            //$notification->add(Notification::ACCOUNT_LOGOUT, null, "User: {$this->_me->account}");
            Zend_Auth::getInstance()->clearIdentity();
        }
        $this->_forward('login');
    }

    /**
     * getProfileForm() - Returns the standard form for reading, and updating
     * the current user's profile.
     *
     * @return Zend_Form
     *
     * @todo This function is not named correctly
     */
    public function getProfileForm()
    {
        $form = Fisma_Form_Manager::loadForm('account');
        $form->removeElement('account');
        $form->removeElement('password');
        $form->removeElement('confirmPassword');
        $form->removeElement('checkaccount');
        $form->removeElement('generate_password');
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
        $form = $this->getProfileForm();
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
        $this->view->assign('form', Fisma_Form_Manager::prepareForm($form));

    }

    /**
     * passwordAction() - Display the change password page
     */
    public function passwordAction()
    {
        // Load the change password file
        $passwordForm = Fisma_Form_Manager::loadForm('change_password');
        $passwordForm = Fisma_Form_Manager::prepareForm($passwordForm);

        // Prepare the password requirements explanation:
        $requirements[] = "Length must be between "
        . Configuration::getConfig('pass_min')
        . " and "
        . Configuration::getConfig('pass_max')
        . " characters long.";
        if (Configuration::getConfig('pass_uppercase') == 1) {
            $requirements[] = "Must contain at least 1 upper case character (A-Z)";
        }
        if (Configuration::getConfig('pass_lowercase') == 1) {
            $requirements[] = "Must contain at least 1 lower case character (a-z)";
        }
        if (Configuration::getConfig('pass_numerical') == 1) {
            $requirements[] = "Must contain at least 1 numeric digit (0-9)";
        }
        if (Configuration::getConfig('pass_special') == 1) {
            $requirements[] = htmlentities("Must contain at least 1 special character (!@#$%^&*-=+~`_)");
        }

        $this->view->assign('requirements', $requirements);
        $this->view->assign('form', $passwordForm);
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
        unset($profileData['save']);

        if ($formValid) {
            $result = $this->_user->find($this->_me->id);
            $originalEmail = $result->current()->email;
            $notifyEmail = $result->current()->notify_email;
            $ret = $this->_user->update($profileData, 'id = '.$this->_me->id);
            if ($ret == 1) {
                $this->_user
                ->log('ACCOUNT_MODIFICATION',
                $this->_me->id,
                "User Account {$this->_me->account} Successfully Modified");
                $msg = "Profile modified successfully.";

                if ($originalEmail != $profileData['email']
                && empty($notifyEmail)) {
                    $this->_user->update(array('email_validate'=>0), 'id = '.$this->_me->id);
                    $result = $this->emailvalidate($this->_me->id, $profileData['email'], 'update');
                    if (true == $result) {
                        $msg .= self::VALIDATION_MESSAGE;
                    }
                }
                $this->view->setScriptPath(Fisma_Controller_Front::getPath('application') . '/views/scripts');
                $this->message($msg, self::M_NOTICE);
            } else {
                $this->message("Unable to update account. ($ret)",
                self::M_WARNING);
            }
        } else {
            $errorString = Fisma_Form_Manager::getErrors($form);
            $this->message("Unable to update account:<br>" . $errorString, self::M_WARNING);
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
            $result = $this->emailvalidate($this->_me->id, $data['notify_email'], 'update');
            if (true == $result) {
                $msg .= self::VALIDATION_MESSAGE;
            }
        }

        $this->view->setScriptPath(Fisma_Controller_Front::getPath('application') . '/views/scripts');
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
        $userRow = $this->_user->find($this->_me->id)->current();
        if ('save' == $req->getParam('s')) {
            $post = $req->getPost();
            $passwordForm = Fisma_Form_Manager::loadForm('change_password');
            $passwordForm = Fisma_Form_Manager::prepareForm($passwordForm);
            $oldPassword = $passwordForm->getElement('oldPassword');
            $oldPassword->addValidator(new Fisma_Form_Validator_PasswdMatch($userRow));
            $password = $passwordForm->getElement('newPassword');
            $password->addValidator(new Fisma_Form_Validator_Password($userRow));
            $formValid = $passwordForm->isValid($post);
            if (!$formValid) {
                $errorString = Fisma_Form_Manager::getErrors($passwordForm);
                // Error message
                $msg = "Unable to change password:<br>".$errorString;
                $model = self::M_WARNING;
            } else {
                $newPass = $this->_user->digest($req->newPassword);
                $historyPass = $userRow->historyPassword;
                $count = substr_count($historyPass, ':');
                if (3 == $count) {
                    $historyPass = substr($historyPass, 0, -strlen(strrchr($historyPass, ':')));
                }
                $historyPass = ':' . $userRow->password . $historyPass;
                $now = date('Y-m-d H:i:s');
                $data = array(
                'password' => $newPass,
                'hash'     => Configuration::getConfig('encrypt'),
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
        $this->_forward('password');
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
