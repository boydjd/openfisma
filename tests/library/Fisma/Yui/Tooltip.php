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
* test /library/Fisma/Yui/Tooltip.php
*
* @author     Duy K. Bui <duy.bui@endeavorsystems.com>
* @copyright  (c) Endeavor Systems, Inc. 2011 {@link http://www.endeavorsystems.com}
* @license    http://www.openfisma.org/content/license GPLv3
* @package    Test
* @subpackage Test_Library
*/
class Test_Library_Fisma_Yui_Tooltip extends Test_Case_Unit
{
    /**
     * test render
     *
     * please don't mess with spacing for your own good
     * @return void
     */
    public function testRender()
    {
        $id = 'tooltip1';
        $title = 'Test Tooltip';
        $content = 'nothing interesting here';
        $render = "<span id='{$id}Tooltip' class='tooltip'>$title</span><script type='text/javascript'>"
                . "var {$id}TooltipVar = new YAHOO.widget.Tooltip("
                . "\"{$id}TooltipObj\", { context:\"{$id}Tooltip\", "
                . "showdelay: 150, hidedelay: 150, autodismissdelay: 25000, "
                . "text:\"<p>$content</p>\", "
                . 'effect:{effect:YAHOO.widget.ContainerEffect.FADE,duration:0.25}, '
                . 'width: "50%"})</script>';
         
        $tooltip = new Fisma_Yui_Tooltip($id, $title, $content);
        $this->assertEquals($render, $tooltip->__tostring());
    }
}

