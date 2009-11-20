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
 * A YUI button
 * 
 * @author     Mark E. Haase <mhaase@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2009 (http://www.endeavorsystems.com)
 * @license    http://www.openfisma.org/content/license
 * @package    Fisma
 * @subpackage Fisma_Yui
 * @version    $Id$
 */
class Fisma_Yui_Form_Button extends Zend_Form_Element_Submit
{
    /**
     * When this element is expressed as a string, it renders itself as a convenience. This allows the element to
     * be used as a parameter to echo, print, or string interpolation expressions.
     */              
    function __toString() 
    {
        return $this->renderSelf();
    }
    
    /**
     * A default implementation of render() that creates a standard button. This is overridden in subclasses to 
     * implement more unique button types.
     * 
     * @return string
     */              
    function renderSelf() 
    {
        $disabled = $this->readOnly ? 'disabled' : '';
        $obj = json_encode($this->getAttrib('onClickArgument'));
        $render = "<input type=\"button\" id=\"{$this->getName()}\" value=\"{$this->getLabel()}\" $disabled>
                   <script type='text/javascript'>
                       YAHOO.util.Event.onDOMReady(function() {
                           var button = new YAHOO.widget.Button('{$this->getName()}', 
                               {
                                   onclick: {fn: {$this->getAttrib('onClickFunction')}, obj: {$obj}}
                               }
                           );";
        $image = $this->getAttrib('imageSrc');
        if (isset($image)) {
           $render .= "button._button.style.background = 'url($image) 10% 50% no-repeat';\n";
           $render .= "button._button.style.paddingLeft = '3em';\n";
        }
        $render .= "})</script>";
        return $render;
    } 
}
