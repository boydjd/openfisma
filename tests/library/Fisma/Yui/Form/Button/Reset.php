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
 * test /library/Fisma/Yui/Form/Button/Reset.php
 *
 * @author     Duy K. Bui <duy.bui@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2011 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Test
 * @subpackage Test_Library
 */
class Test_Library_Fisma_Yui_Form_Button_Reset extends Test_Case_Unit
{
    /**
     * test renderSelf
     * don't mess with the spacing or tests might fail
     * @return void
     */
    public function testRenderSelf()
    {
        $name = 'ResetButton';
        $render = "<input id='$name' type='reset' name='$name' value='$name'>
                    <script type='text/javascript'>
                    YAHOO.util.Event.onDOMReady(function () {
                        var $name = new YAHOO.widget.Button(\"$name\");
                    });
                    </script>";
        $testButton = new Fisma_Yui_Form_Button_Reset($name);
        $this->assertEquals($render, $testButton->renderSelf());
    }
}

