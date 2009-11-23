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
 * <http://www.gnu.org/licenses/>.
 */

/**
 * Extends Zend_Form by adding a property called "readOnly". When a form is marked as read only, the decorator can 
 * render the form differently (i.e. render the form without actual form controls, or with disabled form controls.) 
 * 
 * @author     Mark E. Haase <mhaase@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2009 (http://www.endeavorsystems.com)
 * @license    http://www.openfisma.org/content/license
 * @package    Fisma
 * @subpackage Fisma_Form
 * @version    $Id$
 */
class Fisma_Form extends Zend_Form
{
    /**
     * Indicates if the form is "read only". If so, then the form controls are disabled when rendering.
     */
    private $_readOnly = false;
    
    /**
     * Overrides the parent function to set the readOnly attribute on all child form elements if the
     * form itself is marked as readOnly. It calls the parent implementation after doing this.
     * 
     * @param Zend_View_Interface $view
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
     * @return bool
     */ 
    function isReadOnly() 
    {
        return $this->_readOnly;
    }

    /**
     * Sets the readOnly attribute for this form.
     * 
     * @param bool $value
     */
    function setReadOnly($value) 
    {
        if (is_bool($value)) {
            $this->_readOnly = $value;
        } else {
            throw new Fisma_Exception("Invalid type for '$value'. Expected a boolean.");
        }
    }
}
