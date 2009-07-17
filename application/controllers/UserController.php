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
 * @author    Ryan Yang <ryan@users.sourceforge.net>
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/mw/index.php?title=License
 * @version   $Id$
 * @package   Controller
 */
 
/**
 * CRUD for Account manipulation
 *
 * @package   Controller
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/mw/index.php?title=License
 */
class UserController extends BaseController
{
    protected $_modelName = 'User';


    /**
     * init() - Initialize internal members.
     */
    public function init()
    {
        parent::init();
        $this->_user = new User();
        $ajaxContext = $this->_helper->getHelper('AjaxContext');
        $ajaxContext->addActionContext('checkaccount', 'html')
                    ->initContext();
    }
    
    /**
     * Get the specific form of the subject model
     */
    public function getForm() 
    {
        $form = Fisma_Form_Manager::loadForm('account');
        if (in_array($this->_request->getActionName(), array('create', 'edit'))) {
            if ('create' == $this->_request->getActionName()) {
                $form->getElement('password')->setRequired(true);
            }
            $this->view->requirements =  $this->_getPasswordRequirements();
        }
        $roles  = Doctrine_Query::create()
                    ->select('*')
                    ->from('Role')
                    ->execute();
        foreach ($roles as $role) {
            $form->getElement('role')->addMultiOptions(array($role->id => $role->name));
        }
        $organizationTreeObject = Doctrine::getTable('Organization')->getTree();
        $q = Doctrine_Query::create()
                ->select('o.id, o.name, o.level')
                ->from('Organization o');
        $organizationTreeObject->setBaseQuery($q);
        $organizationTree = $organizationTreeObject->fetchTree();
        if ($organizationTree) {
            $orgs = $form->getElement('organizations');
            foreach ($organizationTree as $organization) {
                $orgs->addCheckbox($organization['id'], 
                                             $organization['name'],
                                             $organization['level']);
            }
        }
        if (Configuration::getConfig('auth_type') == 'database') {
            $form->removeElement('checkAccount');
        } else {
            $form->removeElement('generate_password');
        }
        
        $form = Fisma_Form_Manager::prepareForm($form);
        return $form;
    }

    /**
     * Returns the standard form for reading, and updating
     * the current user's profile.
     *
     * @return Zend_Form
     *
     * @todo This function is not named correctly
     */
    private function _getProfileForm()
    {
        $form = Fisma_Form_Manager::loadForm('account');
        $form->removeElement('username');
        $form->removeElement('password');
        $form->removeElement('confirmPassword');
        $form->removeElement('checkAccount');
        $form->removeElement('generate_password');
        $form->removeElement('role');
        $form->removeElement('locked');
        $form->removeElement('organizations');
        return $form;
    }


    /** 
     * Set the Roles, organization relation before save the model
     *
     * @param Zend_Form $form
     * @param Doctrine_Record|null $subject
     * @return Doctrine_Record
     */
    protected function saveValue($form, $subject=null)
    {
        if (is_null($subject)) {
            $subject = new $this->_modelName();
        } elseif (!($subject instanceof Doctrine_Record)) {
            throw new Fisma_Exception('Invalid parameter, expected a Doctrine_Model');
        }
        $values = $form->getValues();
        if (empty($values['password'])) {
            unset($values['password']);
        }
        $subject->merge($values);
        $subject->unlink('Roles');
        $subject->link('Roles', $values['role']);

        $subject->unlink('Organizations');
        $subject->link('Organizations', $values['organizations']);
        $subject->save();
    }

    /**
     * Get the Roles and the organization from the model and assign them to the form
     *
     * @param Doctrine_Record|null $subject
     * @param Zend_Form $form
     * @return Doctrine_Record
     */
    protected function setForm($subject, $form)
    {
        $roleId = $subject->Roles[0]->id;
        $form->setDefaults($subject->toArray());
        $form->getElement('role')->setValue($roleId);
        $orgs = $subject->Organizations;
        $orgIds = array();
        foreach ($orgs as $o) {
            $orgIds[] = $o->id;
        }
        $form->getElement('organizations')->setValue($orgIds);
        return $form;
    }

    /**
     * Display the user's "Edit Profile" page and handle its updating
     */
    public function profileAction()
    {
        $form = $this->_getProfileForm();
        //$user = Doctrine::getTable('User')->find($this->_me->id);
        $user = $this->_me;
        if ($this->_request->isPost()) {
            $post = $this->_request->getPost();
            if ($form->isValid($post)) {
                $user->merge($form->getValues());
                try {
                    Doctrine_Manager::connection()->beginTransaction();
                    $modified = $user->getModified();
                    $user->save();
                    Doctrine_Manager::connection()->commit();
                    $message = "Profile updated successfully"; 
                    if (isset($modified['email'])) {
                        $mail = new Fisma_Mail();
                        if ($mail->validateEmail($user, $modified['email'])) {
                            $message .= ", and a validation email has been sent to your new e-mail address.";
                        } 
                    }
                    $model   = self::M_NOTICE;
                } catch (Doctrine_Exception $e) {
                    Doctrine_Manager::connection()->rollback();
                    $message = $e->getMessage();
                    $model   = self::M_WARNING;
                }
            } else {
                $errorString = Fisma_Form_Manager::getErrors($form);
                $message     = "Unable to update profile:<br>" . $errorString;
                $model       = self::M_WARNING;
            }
            $this->message($message, $model);
        } else {
            $form->setDefaults($user->toArray());
        }
        $this->view->form    = Fisma_Form_Manager::prepareForm($form);
    }

    /**
     * Change user's password
     */
    public function passwordAction()
    {
        // This action isn't allowed unless the system's authorization is based on the database
        if ('database' != Configuration::getConfig('auth_type')) {
            throw new Fisma_Exception('Password action is not allowed when the authentication type is not "database"');
        }
        
        // Load the change password file
        $form = Fisma_Form_Manager::loadForm('change_password');
        $form = Fisma_Form_Manager::prepareForm($form);

        $this->view->requirements =  $this->_getPasswordRequirements();
        $post   = $this->_request->getPost();

        if ($post['oldPassword']) {

            if ($form->isValid($post)) {
                $user = Doctrine::getTable('User')->find($this->_me->id);
                $user->password = $post['newPassword'];
                try {
                    $user->save();
                    $message = "Password updated successfully."; 
                    $model   = self::M_NOTICE;
                } catch (Doctrine_Exception $e) {
                    Doctrine_Manager::connection()->rollback();
                    $message = $e->getMessage();
                    $model   = self::M_WARNING;
                }
            } else {
                $errorString = Fisma_Form_Manager::getErrors($form);
                $message     = "Unable to change password:<br>" . $errorString;
                $model       = self::M_WARNING;
            }
            $this->message($message, $model);
        }
        $this->view->form    =  $form;
    }

    /**
     *  Set user's notification policy
     */
    public function notificationAction()
    {
        $user = Doctrine::getTable('User')->find($this->_me->id);

        if ($this->_request->isPost()) {
            //@todo check injection
            $user->notifyFrequency = $this->_request->getParam('notify_frequency');
            $user->notifyEmail     = $this->_request->getParam('notify_email');

            $postEvents = $this->_request->getPost('existEvents');
            try {
                Doctrine_Manager::connection()->beginTransaction();
                $modified = $user->getModified();

                $user->unlink('Events');
                $user->link('Events', $postEvents);
                $user->getTable()->getRecordListener()->get('BaseListener')->setOption('disabled', true);
                $user->save();
                Doctrine_Manager::connection()->commit();

                $message = "Notification events modified successfully";
                $model   = self::M_NOTICE;
                if ($modified['notifyEmail']) {
                    $mail = new Fisma_Mail();
                    if ($mail->validateEmail($user, $modified['notifyEmail'])) {
                        $message .= ", and a validation email has sent to your new notify email";
                    } else {
                        $message .= ", but the validation e-mail could not be sent to your new address.";
                        $model = self::M_WARNING;
                    }
                }
            } catch (Doctrine_Exception $e) {
                Doctrine_Manager::connection()->rollback();
                $message = $e->getMessage();
                $model   = self::M_WARNING;
            }
            $this->message($message, $model);
        }

        $this->view->me = $user;
    }


    /**
     * Get the password complex requirements
     *
     * @return array 
     */
    private function _getPasswordRequirements()
    {
        $requirements[] = "Length must be between "
        . Configuration::getConfig('pass_min_length')
        . " and "
        . Configuration::getConfig('pass_max_length')
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
        return $requirements;
    }

    public function setColumnPreferenceAction()
    {
        $me = Doctrine::getTable('User')->find($this->_me->id);
        $me->searchColumnsPref = $_COOKIE['search_columns_pref'];
        $me->getTable()->getRecordListener()->setOption('disabled', true);
        $me->save();
        $this->_helper->layout->setLayout('ajax');
        $this->_helper->viewRenderer->setNoRender();
    }
    
    /**
     * store user last accept rob
     * create a audit event
     */
    public function acceptRobAction() {
        $user = User::currentUser();
        $user->getTable()->getRecordListener()->get('BaseListener')->setOption('disabled', true);
        $user->lastRob = Fisma::now();
        $user->save();
        $this->_forward('index', 'Panel');
    }
    
    /**
     * generate a password that meet the application's password complexity requirements.
     */
    public function generatePasswordAction()
    {
        $passLengthMin = Configuration::getConfig('pass_min_length');
        $passLengthMax = Configuration::getConfig('pass_max_length');
        $passNum = Configuration::getConfig('pass_numerical');
        $passUpper = Configuration::getConfig('pass_uppercase');
        $passLower = Configuration::getConfig('pass_lowercase');
        $passSpecial = Configuration::getConfig('pass_special');
        
        $flag = 0;
        $password = "";
        $length = rand($passLengthMin ? $passLengthMin : 1, $passLengthMax);
        if (true == $passUpper) {
            $possibleCharactors[] = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
            $flag++;
        }
        if (true == $passLower) {
            $possibleCharactors[] = "abcdefghijklmnopqrstuvwxyz";
            $flag++;
        }
        if (true == $passNum) {
            $possibleCharactors[] = "0123456789";
            $flag++;
        }
        if (true == $passSpecial) {
            $possibleCharactors[] = "!@#$%^&*()_+=-`~\|':;?><,.[]{}/";
            $flag++;
        }

        while (strlen($password) < $length) {
            if (0 == $flag) {
                $password .= rand(0, 9);
            } else {
                foreach ($possibleCharactors as $row) {
                    if (strlen($password) < $length) {
                        $password .= substr($row, (rand()%(strlen($row))), 1);
                    }
                }
            }
        }
        echo $password;
        $this->_helper->layout->disableLayout(true);
        $this->_helper->viewRenderer->setNoRender();
    }
    
    /**
     * checkaccountAction() - Check to see if the specified LDAP
     * distinguished name (Account) exists in the system's specified LDAP directory.
     * @todo code finish this function later
     */
    public function checkAccountAction()
    {
        Fisma_Acl::requirePrivilege('user', 'read');
        $ldapConfig = new LdapConfig();
        $data = $ldapConfig->getLdaps();
        $account = $this->_request->getParam('account');
        $msg = '';
        if (count($data) == 0) {
            $type = 'warning';
            // to do Engilish
            $msg .= "Ldap doesn't exist or no data";
        }
        foreach ($data as $opt) {
            $srv = new Zend_Ldap($opt);
            try {
                $type = 'message';
                $dn = $srv->getCanonicalAccountName($account,
                            Zend_Ldap::ACCTNAME_FORM_DN); 
                $msg = "$account exists, the dn is: $dn";
            } catch (Zend_Ldap_Exception $e) {
                $type = 'warning';
                // The expected error is LDAP_NO_SUCH_OBJECT, meaning that the
                // DN does not exist.
                if ($e->getErrorCode() ==
                    Zend_Ldap_Exception::LDAP_NO_SUCH_OBJECT) {
                    $msg = "$account does NOT exist";
                } else {
                    $msg .= 'Unknown error while checking Account: '
                          . $e->getMessage();
                }
            }
        }
        echo json_encode(array('msg' => $msg, 'type' => $type));
        $this->_helper->layout->setLayout('ajax');
        $this->_helper->viewRenderer->setNoRender();
    }
}
