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
 * Fisma_Zend_Form_Manager_Organization_Converttosystem 
 * 
 * @uses Fisma_Zend_Form_Manager_Abstract
 * @package 
 * @copyright (c) Endeavor Systems, Inc. 2011 {@link http://www.endeavorsystems.com}
 * @author Mark Ma <mark.ma@reyosoft.com> 
 * @license http://www.openfisma.org/content/license GPLv3
 */
class Fisma_Zend_Form_Manager_System_Converttoorganization extends Fisma_Zend_Form_Manager_Abstract
{
    /**
     * prepareForm 
     * 
     * @return void
     */
    public function prepareForm()
    {
        $form = $this->getForm();

        $organizationTypes = Doctrine::getTable('OrganizationType')->getOrganizationTypeArray(false);

        $form->getElement('orgType')->setMultiOptions($organizationTypes);
        $this->setForm($form);
    }
}
