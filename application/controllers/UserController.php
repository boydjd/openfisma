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
        $checkboxMatrix = new Fisma_Form_Element_CheckboxTree('organizations');
        foreach ($organizationTree as $organization) {
            $checkboxMatrix->addCheckbox($organization['id'], 
                                         $organization['name'],
                                         $organization['level']);
        }
        $form->getDisplayGroup('accountInfo')->addElement($checkboxMatrix);
        $form = Fisma_Form_Manager::prepareForm($form);
        return $form;
    }

    /** 
     * Set the Roles, organization relation before save the model
     *
     * @param Zend_Form $form
     * @param Doctrine_Record|null $subject
     * @return Doctrine_Record
     */
    protected function mergeValue($form, $subject=null)
    {
        if (is_null($subject)) {
            $subject = new $this->_modelName();
        } elseif (!($subject instanceof Doctrine_Record)) {
            /** @todo english */
            throw new Fisma_Exception_General('Invalid parameter expecting a Record model');
        }
        $values = $form->getValues();
        $roleId = $values['role'];
        /** @todo Transaction */
        $q = Doctrine_Query::create()
            ->delete('UserRole')
            ->addWhere('userId = ?', $subject->id);
        $deleted = $q->execute();
        $userRole = new UserRole;
        $userRole->userId = $subject->id;
        $userRole->roleId = $roleId;
        $userRole->save();
        $subject->merge($values);
        return $subject;
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
}
