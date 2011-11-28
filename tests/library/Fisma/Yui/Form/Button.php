<?php
/**
 * Copyright (c) 2011 Endeavor Systems, Inc.
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

require_once(realpath(dirname(__FILE__) . '/../../../../Case/Unit.php'));

/**
 * test /library/Fisma/Yui/Form/Button.php
 *
 * @author     Duy K. Bui <duy.bui@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2011 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Test
 * @subpackage Test_Library
 */
class Test_Library_Fisma_Yui_Form_Button extends Test_Case_Unit
{
    /**
     * test renderSelf()
     *
     * please maintain spacing *strictly*
     * @return void
     */
    public function testRenderSelf()
    {
        $readOnly = false;
        $onClickFunction = 'test_on_click';
        $onClickArgument = '{msg:test}';

        $disabled = $this->readOnly ? 'disabled' : '';
        // merge the part of onclick event
        $onClickRender = '';
        if (!empty($onClickFunction)) {
            $onClickRender .= "onclick: {fn:$onClickFunction";
            if (!empty($onClickArgument)) {
                $onClickRender .= ", obj: \"$onClickArgument\"";
            }
            $onClickRender .= "},\n";
        }
        
        $name = 'TestLink';
        $label = 'Test Link';
        
        $fullrender = "<input type=\"button\" id=\"$name\" value=\"$label\" $disabled>
                   <script type='text/javascript'>
                       YAHOO.util.Event.onDOMReady(function() {
                           var button = new YAHOO.widget.Button('$name', 
                               {
                                   $checked
                                   onclick: {fn: {$onClickFunction}, obj: \"$onClickArgument\"}
                               }
                           );";

        $image = '/resources/icons/test';
        $fullrender .= "button._button.style.background = 'url($image) 10% 50% no-repeat';\n";
        $fullrender .= "button._button.style.paddingLeft = '3em';\n";
    
        $fullrender .= "})</script>";

        $options = array(
            readOnly => $readOnly,
            onClickFunction => $onClickFunction,
            onClickArgument => $onClickArgument,
            target => $target,
            imageSrc => $image,
            label => $label,
        );
        $button = new Fisma_Yui_Form_Button($name, $options);
        $this->assertEquals($fullrender, $button->__toString());
    }
}

