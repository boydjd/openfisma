<?php
/**
 * Copyright (c) 2011 Endeavor Systems, Inc.
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
 * Fisma_Zend_Controller_Action_Helper_OrganizationType 
 * 
 * @uses Zend_Controller_Action_Helper_Abstract
 * @package Fisma_Zend_Controller_Action_Helper 
 * @copyright (c) Endeavor Systems, Inc. 2011 {@link http://www.endeavorsystems.com}
 * @author Mark Ma <mark.ma@reyosoft.com> 
 * @license http://www.openfisma.org/content/license GPLv3
 */
class Fisma_Zend_Controller_Action_Helper_OrganizationType extends Zend_Controller_Action_Helper_Abstract
{
    /**
     * A helper function to create the objects required to render the organization type filter form
     * 
     * @return Zend_Form
     */
    public function getFilterForm($includeNone = true)
    {
        $organizationTypes = Doctrine::getTable('OrganizationType')->getOrganizationTypeArray(false);

        if ($includeNone) {
            $organizationList = array('none' => 'None') + array_map('ucwords', $organizationTypes);
        } else {
            $organizationList = array_map('ucwords', $organizationTypes);
        }

        // Set up the filter options 
        $filterForm = Fisma_Zend_Form_Manager::loadForm('organization_type_filter');

        $filterForm->getElement('orgTypeFilter')->setMultiOptions($organizationList);

        $filterForm->getElement('orgTypeFilter')
                    ->setMultiOptions($organizationList);

        $filterForm->setDefaults($this->getRequest()->getParams());
         
        $filterForm->setDecorators(
            array(
                'FormElements',
                array('HtmlTag', array('tag' => 'span')),
                'Form'
            )
        );

        $filterForm->setElementDecorators(array('ViewHelper', 'Label'));
        
        return $filterForm;
    }

    /**
     * A helper function to get organization type id.
     *
     * @param $userId
     * @namespace 
     * @includNone default is true
     * @return $id organization type id 
     */
    public function getOrganizationTypeId($userId, $namespace, $includeNone = true)
    {
        $orgTypeStorage = Doctrine::getTable('Storage')->getUserIdAndNamespaceQuery($userId, $namespace)
                             ->fetchOne();               
 
        // Check the parameter firstly, then storage table. If both empty, then return either 'none' if 
        // $includNone is true or the first record id in the organization type table.
        if ($this->getRequest()->getParam('orgTypeId')) {
            $orgTypeId = $this->getRequest()->getParam('orgTypeId');
        } elseif (!empty($orgTypeStorage)) {
            $orgTypeId = $orgTypeStorage->data['orgType'];
        } else {
            if ($includeNone) {
                $orgTypeId = 'none';
            } else {
                $organizationTypes = Doctrine::getTable('OrganizationType')->getOrganizationTypeArray(false);
                $orgTypeIds = array_keys($organizationTypes);
                $orgTypeId = array_shift($orgTypeIds);
            }
        }
        return $orgTypeId;
    }
}
