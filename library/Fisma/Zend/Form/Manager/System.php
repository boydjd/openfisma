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
 * Fisma_Zend_Form_Manager_System
 *
 * @uses Fisma_Zend_Form_Manager_Abstract
 * @package Fisma_Zend_Form_Manager
 * @copyright (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @author Josh Boyd <joshua.boyd@endeavorsystems.com>
 * @license http://www.openfisma.org/content/license GPLv3
 */
class Fisma_Zend_Form_Manager_System extends Fisma_Zend_Form_Manager_Abstract
{
    /**
     * prepareForm
     *
     * @access public
     * @return void
     */
    public function prepareForm()
    {
        $form = $this->getForm();

        $organizationTreeObject = Doctrine::getTable('Organization')->getTree();
        $q = CurrentUser::getInstance()->getOrganizationsByPrivilegeQuery('organization', 'read');
        $q->orderBy($q->getRootAlias() . '.lft');
        $organizationTreeObject->setBaseQuery($q);
        $organizationTree = $organizationTreeObject->fetchTree();
        $form->getElement('cloneOrganizationId')->addMultiOptions(array(null => null));

        if (!empty($organizationTree)) {
            foreach ($organizationTree as $organization) {
                $value = $organization['id'];
                $text = str_repeat('--', $organization['level']) . $organization['name'];
                $form->getElement('parentOrganizationId')->addMultiOptions(array($value => $text));
                $form->getElement('cloneOrganizationId')->addMultiOptions(array($value => $text));
            }
        }

        $systemTable = Doctrine::getTable('System');

        $enumFields = array('confidentiality', 'integrity', 'availability', 'sdlcPhase');
        foreach ($enumFields as $field) {
            $array = $systemTable->getEnumValues($field);
            $form->getElement($field)->addMultiOptions(array_combine($array, $array));
        }

        $this->setForm($form);
    }
}
