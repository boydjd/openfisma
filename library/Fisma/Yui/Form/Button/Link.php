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
 * A YUI button that acts as a link (anchor tag: <a>)
 * 
 * @author     Mark E. Haase <mhaase@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Fisma
 * @subpackage Fisma_Yui
 */
class Fisma_Yui_Form_Button_Link extends Fisma_Yui_Form_Button
{
    /**
     * Instead of overriding render(), renderSelf() can be called by the decorator to build the input.
     * This saves the trouble of creating a separate view helper and allows the element to simply draw
     * itself.
     * 
     * @return string The HTML snippet of the rendered YUI link button
     */
    function renderSelf() 
    {
        // When readOnly, we need to pass the configuration item "disabled: true" to the YUI button constructor
        $disabled = $this->readOnly ? 'true' : 'false';
        // merge the part of onclick event
        $onClickFunction = $this->getAttrib('onClickFunction');
        $onClickArgument = $this->getAttrib('onClickArgument');
        $onClickRender = '';
        if (!empty($onClickFunction)) {
            $onClickRender .= ", onclick: {fn:$onClickFunction";
            if (!empty($onClickArgument)) {
                $onClickRender .= ", obj: \"$onClickArgument\"";
            }
            $onClickRender .= "},\n";
        }
        $target = $this->getAttrib('target');
        $targetRender = '';
        if (!empty($target)) {
            $targetRender = 'target: "' . $target . '",';
        }
        $render = "<span id='{$this->getName()}'></span>
                   <script type='text/javascript'>
                        YAHOO.util.Event.onDOMReady(function() {
                            var button = new YAHOO.widget.Button({  
                                 type: \"link\",  
                                 label: \"{$this->getValue()}\",  
                                 href: \"{$this->getAttrib('href')}\",
                                 id: \"{$this->getName()}Button\",
                                 $onClickRender
                                 $targetRender
                                 disabled: $disabled,
                                 container: \"{$this->getName()}\"
                            });
                        ";
        $image = $this->getAttrib('imageSrc');
        if (isset($image)) {
            $render .= "button._button.style.background = 'url($image) 10% 50% no-repeat';\n";
            $render .= "button._button.style.paddingLeft = '3em';\n";
        }
        $render .= "\n});</script>";
        return $render;
    }
}
