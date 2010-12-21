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
 * Fisma_Zend_Form_Manager_Assessmentplanentry
 * 
 * @uses Fisma_Zend_Form_Manager_Abstract
 * @package Fisma_Zend_Form_Manager 
 * @copyright (c) Endeavor Systems, Inc. 2010 {@link http://www.endeavorsystems.com}
 * @author Andrew Reeves <andrew.reeves@endeavorsystems.com> 
 * @license http://www.openfisma.org/content/license GPLv3
 */
class Fisma_Zend_Form_Manager_Assessmentplanentry extends Fisma_Zend_Form_Manager_Abstract
{
    /**
     * prepareForm 
     * 
     * @return void
     */
    public function prepareForm()
    {
        $form = $this->getForm();

        $apeTable = Doctrine::getTable('AssessmentPlanEntry');      

        $statusKeys = $apeTable->getColumnDefinition('status');
        $statusKeys = $statusKeys['values'];
        $statusNames = array_map('ucfirst', array_map('strtolower', $statusKeys));
        $form->getElement('status')->addMultiOptions(array_combine($statusKeys, $statusNames));

        $resultElem = $form->getElement('result');
        $resultElem->addMultiOption('');
        $resultKeys = $apeTable->getColumnDefinition('result');
        $resultKeys = $resultKeys['values'];
        $resultNames = array_map('ucfirst', array_map('strtolower', $resultKeys));
        $resultElem->addMultiOptions(array_combine($resultKeys, $resultNames));

        $ape = $apeTable->find($this->_request->getParam('id'));
        $sa = $ape->SaSecurityControlAggregate->SecurityAuthorization;
        if ($sa->status == 'Assessment Plan') {
            // hide result
            $form->removeElement('status');
            $form->removeElement('result');
        }

        $this->setForm($form);
    }
}
