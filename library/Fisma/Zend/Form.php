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
 * Extends Zend_Form by adding a property called "readOnly". When a form is marked as read only, the decorator can 
 * render the form differently (i.e. render the form without actual form controls, or with disabled form controls.) 
 * 
 * @author     Mark E. Haase <mhaase@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Fisma
 * @subpackage Fisma_Zend_Form
 */
class Fisma_Zend_Form extends Zend_Form
{
    /**
     * Indicates if the form is "read only". If so, then the form controls are disabled when rendering.
     * 
     * @var boolean
     */
    private $_readOnly = false;
    
    /**
     * Overrides the parent function to set the readOnly attribute on all child form elements if the
     * form itself is marked as readOnly. It calls the parent implementation after doing this.
     * 
     * @param Zend_View_Interface $view Provided for compatibility
     * @return string The rendered form in HTML
     */
    function render(Zend_View_Interface $view = null) 
    {
        if ($this->isReadOnly()) {
            foreach ($this->getElements() as $element) {
                $element->readOnly = true;
            }
        }
        
        return parent::render();
    }
   
    /**
     * Returns true if the form is marked as read only.
     * 
     * @return boolean True if the form is readonly, false otherwise
     */ 
    function isReadOnly() 
    {
        return $this->_readOnly;
    }

    /**
     * Sets the readOnly attribute for this form.
     * 
     * @param boolean $value The specified boolean which indicates if the form readonly to set
     * @return void
     * @throws Fisma_Zend_Exception if the specified value to set is not type boolean
     */
    function setReadOnly($value) 
    {
        if (is_bool($value)) {
            $this->_readOnly = $value;
        } else {
            throw new Fisma_Zend_Exception("Invalid type for '$value'. Expected a boolean.");
        }
    }
}
