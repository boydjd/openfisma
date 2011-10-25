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

require_once(realpath(dirname(__FILE__) . '/../../../../../Case/Unit.php'));

/**
 * this test suite is quite useless because it has to extract HTML hard-coded from source file
 * letter by letter, space by space
 * having an external viewscript / template / whatever would be a bless
 *
 * @author     Duy K. Bui <duy.bui@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2011 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Test
 * @subpackage Test_Library
 */
class Test_Library_Fisma_Yui_Form_Button_Submit extends Test_Case_Unit
{
    /**
     * test renderSelf()
     * the following method require MANUAL spacing, don't mess with it, please.
     * @return void
     */
    public function testRenderSelf()
    {
        $readOnly = false;
        $onClickFunction = 'test_on_click';
        $onClickArgument = '{msg:test}';

        $disabled = $this->readOnly ? 'true' : 'false';
        // merge the part of onclick event
        $onClickRender = '';
        if (!empty($onClickFunction)) {
            $onClickRender .= ", onclick: {fn:$onClickFunction";
            if (!empty($onClickArgument)) {
                $onClickRender .= ", obj: \"$onClickArgument\"";
            }
            $onClickRender .= "}";
        }
        
        $name = 'TestLink';
        $label = 'Test Link';
        
        $fullrender = "<span id=\"{$name}Container\"></span>
                   <script type='text/javascript'>
                       YAHOO.util.Event.onDOMReady(function() {
                           var $name = new YAHOO.widget.Button({
                               type: \"submit\",";
        $barerender = $fullrender;

        $image = '/resources/icons/test';
        $imagerender = "$name._button.style.background = 'url($image) 10% 50% no-repeat';\n";
        $imagerender .= "$name._button.style.paddingLeft = '3em';\n";
        $fullrender .= "
                               label: \"$name\",
                               id: \"$name\",
                               name: \"$name\",
                               value: \"$name\",
                               container: \"{$name}Container\",
                               disabled: $disabled
                               $onClickRender
                           });
                           $imagerender
                       ";
        $barerender .= "
                               label: \"$name\",
                               id: \"$name\",
                               name: \"$name\",
                               value: \"$name\",
                               container: \"{$name}Container\",
                               disabled: $disabled
                               
                           });
                           
                       ";

   
        $fullrender .= "});</script>";
        $barerender .= "});</script>";

        $button = new Fisma_Yui_Form_Button_Submit($name);
        $this->assertEquals($barerender, $button->renderSelf());

        $options = array(
            readOnly => $readOnly,
            onClickFunction => $onClickFunction,
            onClickArgument => $onClickArgument,
            target => $target,
            imageSrc => $image,
            value => $label,
        );
        $button = new Fisma_Yui_Form_Button_Submit($name, $options);
        $this->assertEquals($fullrender, $button->renderSelf());
    }
}

