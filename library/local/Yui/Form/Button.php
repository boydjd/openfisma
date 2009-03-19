<?php
/**
 * Copyright (c) 2008 Endeavor Systems, Inc.
 *
 * This file is part of OpenFISMA.
 *
 * OpenFISMA is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * OpenFISMA is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with OpenFISMA.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author    Mark E. Haase <mhaase@endeavorsystems.com>
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/mw/index.php?title=License
 * @version   $Id$
 */

/**
 * A YUI button
 *
 * @package   Yui
 * @subpackage Yui_Form
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/mw/index.php?title=License
 */
class Yui_Form_Button extends Zend_Form_Element
{
    protected $_label;
    protected $_id;
    private $_onClick;    
    
    /**
     * Construct a button
     * 
     * @param string $label Displayed to the user
     * @param string $id Used to represent the element uniquely in the DOM       
     */
    function __construct($label, $id)
    {
        parent::__construct($id);
        $this->_label = str_replace("\"", "\'", $label);
        $this->_id = $id;
    }

    /**
     * When this element is expressed as a string, it renders itself as a convenience. This allows the element to
     * be used as a parameter to echo, print, or string interpolation expressions.
     */              
    function __toString() 
    {
        return $this->render();
    }
    
    /**
     * A default implementation of render() that creates a standard button. This is overridden in subclasses to 
     * implement more unique button types.
     */              
    function render() 
    {
        $disabled = $this->readOnly ? 'disabled' : '';
        $onClick = (!empty($this->_onClick)) ? ",({onclick: \"$this->_onClick()\"" : '';
        $render = "<input type=\"button\" id=\"{$this->_id}\" value=\"$this->_label\" $disabled>
                   <script type='text/javascript'>
                       var {$this->_id} = new YAHOO.widget.Button('$this->_id', 
                           {
                               onclick: {fn: $this->_onClick},
                           }
                       );
                   </script>";
         return $render;
    }
    
    /**
     * Specify the name of a javascript function (without parentheses) to use as the click event handler for this
     * button. Not necessarily supported by all subclasses.
     */              
    function onClick($functionName) {
        $this->_onClick = $functionName;
    }    
}
