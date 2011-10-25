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

require_once(realpath(dirname(__FILE__) . '/../../../Case/Unit.php'));

/**
 * test /library/Fisma/Js/SwitchButton.php
 *
 * @author     Duy K. Bui <duy.bui@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2011 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Test
 * @subpackage Test_Library
 */
class Test_Library_Fisma_Js_SwitchButton extends Test_Case_Unit
{

    /**
     * test customized toString
     */
    public function testToString()
    {
        /**
         * the following strings are copied character-by-character from source file
         * tests would fail should spacing be messed with
         * 
         * recommend: extract HTML code to external XML or similar editable resources
         * notice: SwitchButton->__tostring() *assumes* that when $callback is provided, so is $payload
         */
        $preRender = "<div id='defaultSwitchButton'></div>
                   <script type='text/javascript'>
                       YAHOO.util.Event.onDOMReady(function () {
                           var switchButton = new Fisma.SwitchButton(";
        $postRender = ");
                       });
                   </script>";

        //test full Arguments
        //arguments extracted from default values in SwitchButtonDummy
        $fullArguments = "'defaultSwitchButton', false, 'buttonClick', \"garbagePayload\"";
        $sampleButton = new Fisma_Js_SwitchButton('defaultSwitchButton', false, 'buttonClick', 'garbagePayload');
        $this->assertEquals($preRender . $fullArguments . $postRender, $sampleButton->__tostring());

        //test bare Arguments
        //arguments extracted from default values in SwitchButtonDummy
        $bareArguments = "'defaultSwitchButton', false";
        $sampleButton = new Fisma_Js_SwitchButton('defaultSwitchButton', false, null, null);
        $this->assertEquals($preRender . $bareArguments . $postRender, $sampleButton->__tostring());
    }
}
