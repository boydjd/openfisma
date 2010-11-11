<?php
/**
 * Copyright (c) 2010 Endeavor Systems, Inc.
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
 * Fisma_Zend_Form_Manager_Securityauthorization 
 * 
 * @uses Fisma_Zend_Form_Manager_Abstract
 * @package Fisma_Zend_Form_Manager 
 * @copyright (c) Endeavor Systems, Inc. 2010 {@link http://www.endeavorsystems.com}
 * @author Andrew Reeves <andrew.reeves@endeavorsystems.com> 
 * @license http://www.openfisma.org/content/license GPLv3
 */
class Fisma_Zend_Form_Manager_Securityauthorization extends Fisma_Zend_Form_Manager_Abstract
{
    /**
     * prepareForm 
     * 
     * @return void
     */
    public function prepareForm()
    {
        $form = $this->getForm();

        $systems = $this->_me->getSystemsByPrivilege('SecurityAuthorization', 'read');
        $selectArray = $this->_view->systemSelect($systems);
        $form->getElement('sysOrgId')->addMultiOptions($selectArray);
        
        $saTable = Doctrine::getTable('SecurityAuthorization');

        $impactDef = $saTable->getColumnDefinition('impact');
        $impactKeys = $impactDef['values'];
        $impactNames = array_map('ucfirst', array_map('strtolower', $impactKeys));
        $impacts = array_combine($impactKeys, $impactNames);
        $form->getElement('impact')->addMultiOptions($impacts);

        $statusDef = $saTable->getColumnDefinition('status');
        $statusKeys = $statusDef['values'];
        $statusNames = array_map('ucfirst', array_map('strtolower', $statusKeys));
        $statuses = array_combine($statusKeys, $statusNames);
        $form->getElement('status')->addMultiOptions($statuses);

        /*
         * differences in how the form is displayed in different actions
         * 'view' is always readOnly, so it doesn't need to be explicit here
         */
        switch ($this->_request->getActionName()) {
            case 'create':
                // impact and status should not appear
                $form->removeElement('impact');
                $form->removeElement('status');
                break;
            case 'edit':
                // once an SA is created, the system and impact are ineditable
                $form->getElement('sysOrgId')->readOnly = true;
                $form->getElement('sysOrgId')->setRequired(false)->setIgnore(true);
                $form->getElement('impact')->readOnly = true;
                $form->getElement('impact')->setRequired(false)->setIgnore(true);
                break;
        }

        $this->setForm($form);
    }
}
