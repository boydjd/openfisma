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
     * Get the specific form of the subject model
     */
    public function getForm() 
    {
        $form = Fisma_Form_Manager::loadForm('account');
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
        $checkboxMatrix = $form->getElement('checkboxMatrix');
        foreach ($organizationTree as $organization) {
            $checkboxMatrix->addCheckbox($organization['id'], 
                                         $organization['name'],
                                         $organization['level']);
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
        $form->removeElement('account');
        $form->removeElement('password');
        $form->removeElement('confirmPassword');
        $form->removeElement('checkAccount');
        $form->removeElement('generate_password');
        $form->removeElement('role');
        $form->removeElement('locked');
        $form->removeElement('checkboxMatrix');
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
            /** @todo english */
            throw new Fisma_Exception('Invalid parameter expecting a Record model');
        }
        $values = $form->getValues();
        $roleId = $values['role'];
        /** @todo Transaction */
        $subject->merge($values);
        $ret = $subject->trySave();
        $q = Doctrine_Query::create()
            ->delete('UserRole')
            ->addWhere('userId = ?', $subject->id);
        $deleted = $q->execute();
        $userRole = new UserRole;
        $userRole->userId = $subject->id;
        $userRole->roleId = $roleId;
        $userRole->save();
        return $ret;
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
        return $form;
    }

    /**
     * Display the user's "Edit Profile" page and handle its updating
     */
    public function profileAction()
    {
        $form = $this->_getProfileForm();
        $user = Doctrine::getTable('User')->find($this->_me->id);

        if ($this->_request->isPost()) {
            $post = $this->_request->getPost();
            if ($form->isValid($post)) {
                $user->merge($form->getValues());
                try {
                    $user->save();
                    $message = "Your profile modified successfully."; 
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
        // Load the change password file
        $form = Fisma_Form_Manager::loadForm('change_password');
        $form = Fisma_Form_Manager::prepareForm($form);

        $this->view->requirements =  $this->_getPasswordRequirements();

        if ($this->_request->isPost()) {
            $post   = $this->_request->getPost();
            $form->getElement('oldPassword')->addValidator(new Fisma_Form_Validator_PasswdMatch());
            $form->getElement('newPassword')->addValidator(new Fisma_Form_Validator_Password());

            if ($form->isValid($post)) {
                $user = Doctrine::getTable('User')->find($this->_me->id);
                $user->password = $post['newPassword'];
                try {
                    $user->save();
                    /** @todo english */
                    $message = "Your password modified successfully."; 
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
                $modifyValues = $user->getModified();

                $user->unlink('Events');
                $user->link('Events', $postEvents);
                $user->save();
                Doctrine_Manager::connection()->commit();

                /** @todo english */
                $message = "Notification events modified successfully.";
                if ($modifyValues['notifyEmail']) {
                    $mail = new Fisma_Mail();
                    $mail->validateEmail($user, $modifyValues['notifyEmail']);
                    $message .= " And a validation email has sent to your new notify email, " . 
                        "you will not receive the follow events notifications until you validate it.";
                }
                $model   = self::M_NOTICE;
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
}
