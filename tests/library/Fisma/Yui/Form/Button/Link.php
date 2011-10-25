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
 * test /library/Fisma/Yui/Form/Button/Link.php
 *
 * @author     Duy K. Bui <duy.bui@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2011 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Test
 * @subpackage Test_Library
 */
class Test_Library_Fisma_Yui_Form_Button_Link extends Test_Case_Unit
{
    /**
     * test renderSelf()
     * don't reindent / mess with spacing, you've been warned
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
            $onClickRender .= "onclick: {fn:$onClickFunction";
            if (!empty($onClickArgument)) {
                $onClickRender .= ", obj: \"$onClickArgument\"";
            }
            $onClickRender .= "},\n";
        }
        $target = '_self';
        $targetRender = '';
        if (!empty($target)) {
            $targetRender = 'target: "' . $target . '",';
        }
        $name = 'TestLink';
        $label = 'Test Link';
        $href = '/test/';

        $fullrender = "<span id='$name'></span>
                   <script type='text/javascript'>
                        YAHOO.util.Event.onDOMReady(function() {
                            var button = new YAHOO.widget.Button({
                                 type: \"link\",";
        $barerender = $fullrender;
        $fullrender .= "
                                 label: \"$label\",
                                 href: \"$href\",
                                 id: \"{$name}Button\",
                                 $onClickRender
                                 $targetRender
                                 disabled: $disabled,
                                 container: \"$name\"
                            });
                        ";
        $barerender .= "
                                 label: \"\",
                                 href: \"\",
                                 id: \"{$name}Button\",
                                 
                                 
                                 disabled: $disabled,
                                 container: \"$name\"
                            });
                        ";

        $image = '/resources/icons/test';
        $fullrender .= "button._button.style.background = 'url($image) 10% 50% no-repeat';\n";
        $fullrender .= "button._button.style.paddingLeft = '3em';\n";
    
        $fullrender .= "\n});</script>";
        $barerender .= "\n});</script>";

        $button = new Fisma_Yui_Form_Button_Link($name);
        $this->assertEquals($barerender, $button->renderSelf());

        $options = array(
            readOnly => $readOnly,
            onClickFunction => $onClickFunction,
            onClickArgument => $onClickArgument,
            target => $target,
            imageSrc => $image,
            value => $label,
            href => $href
        );
        $button = new Fisma_Yui_Form_Button_Link($name, $options);
        $this->assertEquals($fullrender, $button->renderSelf());
    }
}

