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
 * A YUI button
 * 
 * @author     Mark E. Haase <mhaase@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Fisma
 * @subpackage Fisma_Yui
 */
class Fisma_Yui_Form_Button extends Zend_Form_Element_Submit
{
    /**
     * The YUI button type. This can be changed in subclasses to quickly create a new type of button.
     * 
     * @string
     */
    protected $_yuiButtonType = 'button';

    /**
     * When this element is expressed as a string, it renders itself as a convenience. This allows the element to
     * be used as a parameter to echo, print, or string interpolation expressions.
     * 
     * @return string The string expressed YUI button element
     */
    function __toString() 
    {
        return $this->renderSelf();
    }
    
    /**
     * A default implementation of render() that creates a standard button. This is overridden in subclasses to 
     * implement more unique button types.
     * 
     * @return string The HTML expressed YUI button element
     */
    function renderSelf() 
    {
        $disabled = $this->readOnly ? 'disabled' : '';
        $checked = $this->getAttrib('checked') ? ('checked: true,') : '';
        
        $value = $this->getValue() ? $this->getValue() : $this->getLabel();
        $obj = json_encode($this->getAttrib('onClickArgument'));
        
        $render = "<input type=\"{$this->_yuiButtonType}\" id=\"{$this->getName()}\" value=\"{$value}\" $disabled>
                   <script type='text/javascript'>
                       YAHOO.util.Event.onDOMReady(function() {
                           var button = new YAHOO.widget.Button('{$this->getName()}', 
                               {
                                   $checked
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
