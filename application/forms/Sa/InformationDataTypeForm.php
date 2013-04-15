<?php
/**
 * Copyright (c) 2013 Endeavor Systems, Inc.
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
 * Handles SA / Information Data Type
 *
 * @author     Duy K. Bui <duy.bui@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2013 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Form
 */
class Sa_InformationDataTypeForm extends Fisma_Zend_Form_Default
{
    public function _init()
    {
        $this->_table = Doctrine::getTable('InformationDataType');

        $this->_inputs['category'] = new Zend_Form_Element_Text('category');
        $this->_inputs['category']->setOptions(array(
            'required' => true,
            'attribs' => array(
                'autofocus' => true
            )
        ));

        $this->_inputs['subcategory'] = new Zend_Form_Element_Text('subcategory');
        $this->_inputs['subcategory']->setOptions(array(
            'required' => true
        ));

        $this->_inputs['catalogId'] = new Zend_Form_Element_Select('catalogId');
        $this->_inputs['catalogId']->setOptions(array(
            'required' => true
        ));
        $this->_inputs['catalogId']->addMultiOptions(
            Doctrine::getTable('InformationDataTypeCatalog')->listAll()->toKeyValueArray('id', 'name')
        );

        $this->_inputs['description'] = new Zend_Form_Element_Textarea('description');
        $this->_inputs['description']->setOptions(array(
            'required' => false,
            'attribs' => array(
                'class' => 'ckeditor'
            )
        ));

        $threatArrays = array('LOW' => 'LOW', 'MODERATE' => 'MODERATE', 'HIGH' => 'HIGH');
        $this->_inputs['defaultConfidentiality'] = new Fisma_Zend_Form_Element_ConstantText('defaultConfidentiality');
        $this->_inputs['confidentiality'] = new Zend_Form_Element_Select('confidentiality');
        $this->_inputs['confidentiality']->setOptions(array(
            'required' => true
        ));
        $this->_inputs['confidentiality']->addMultiOptions($threatArrays);


        $this->_inputs['defaultIntegrity'] = new Fisma_Zend_Form_Element_ConstantText('defaultIntegrity');
        $this->_inputs['integrity'] = new Zend_Form_Element_Select('integrity');
        $this->_inputs['integrity']->setOptions(array(
            'required' => true
        ));
        $this->_inputs['integrity']->addMultiOptions($threatArrays);


        $this->_inputs['defaultAvailability'] = new Fisma_Zend_Form_Element_ConstantText('defaultAvailability');
        $this->_inputs['availability'] = new Zend_Form_Element_Select('availability');
        $this->_inputs['availability']->setOptions(array(
            'required' => true
        ));
        $this->_inputs['availability']->addMultiOptions($threatArrays);
    }
}
