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
 * Extends Fisma_Zend_Form and includes custom variations that are usually done in Fisma_Zend_Form_Manager. Forms that
 * extend this class only need to override the _init() method to add elements into $_inputs and set $_table accordingly.
 *
 * @author     Duy K. Bui <duy.bui@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2013 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Fisma
 * @subpackage Fisma_Zend_Form
 */
class Fisma_Zend_Form_Default extends Fisma_Zend_Form
{
    protected $_inputs = array();
    protected $_table;

    protected function _init()
    {
    }

    public function init()
    {
        $this->_init();

        $this->addElements($this->_inputs);
        $this->addDisplayGroup($this->_inputs, 'main');

        $this->setMethod('post');

        $this->setDecorators(
            array(
                new Zend_Form_Decorator_FormElements(),
                new Fisma_Zend_Form_Decorator()
            )
        );

        $this->setDisplayGroupDecorators(
            array(
                new Zend_Form_Decorator_FormElements(),
                new Fisma_Zend_Form_Decorator()
            )
        );

        $this->setElementDecorators(array(new Fisma_Zend_Form_Decorator()));

        foreach ($this->getElements() as $element) {
            // Set label to getLogicalName();
            $element->setLabel($this->_table->getLogicalName($element->getName()));

            // Set tooltip to getComment();
            $tooltip = $this->_table->getComment($element->getName());
            if (!empty($tooltip)) {
                $element->setAttrib('tooltip', $tooltip);
            }

            // By default, all input is trimmed of extraneous white space
            if (!$element->getFilter('StringTrim') && !$element->getFilter('Null')) {
                $element->addFilter('StringTrim');
            }
            // Add decorator for select element
            if ($element->getType() == 'Zend_Form_Element_Select') {
                $element->viewScript = 'yui/select-menu.phtml';
                $element->addDecorator('ViewScript', array('placement' => false));
            }
        }
    }
}
