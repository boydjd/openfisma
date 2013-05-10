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
 * Handles SA / Information Data Type Catalog
 *
 * @author     Duy K. Bui <duy.bui@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2013 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Form
 */
class Sa_InformationDataTypeCatalogForm extends Fisma_Zend_Form_Default
{
    public function _init()
    {
        $this->_table = Doctrine::getTable('InformationDataTypeCatalog');

        $this->_inputs['name'] = new Zend_Form_Element_Text('name');
        $this->_inputs['name']->setOptions(array(
            'required' => true,
            'attribs' => array(
                'autofocus' => true
            )
        ));

        $this->_inputs['description'] = new Zend_Form_Element_Textarea('description');
        $this->_inputs['description']->setOptions(array(
            'required' => false,
            'attribs' => array(
                'class' => 'ckeditor'
            )
        ));
    }
}
