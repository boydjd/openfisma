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
 * test suite for /library/Fisma/Format/Section.php
 *
 * @author     Duy K. Bui <duy.bui@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2011 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Test
 * @subpackage Test_Library
 */
class Test_Library_Fisma_Format_Section extends Test_Case_Unit
{
    /**
     * test function startSection()
     * recommended: extarct hard-coded html out of the library
     * @return void
     */
    public function testStartSection()
    {
        $anchorName = 'UnitTest';
        $anchorText = 'Unit Testing';
        $editableTarget = 'Test_Mode';
        $openTag = '<div class=\'sectionHeader\'><span ';
        $closeTag = '</span></div><div class=\'section\'>';

        $bareReturn = $openTag . '>' . $anchorText . $closeTag;
        $this->expectOutputString($bareReturn);
        Fisma_Format_Section::startSection($anchorText);

        $withAnchorName = $openTag . '><a name=\'' . $anchorName . '\'>' . $anchorText . '</a>' . $closeTag;
        //cascading the old output
        $this->expectOutputString($bareReturn . $withAnchorName);
        Fisma_Format_Section::startSection($anchorText, null, $anchorName);

        $withEditableTarget = $openTag . 'class=\'editable\' target=\'' . $editableTarget . '\'>' . $anchorText . $closeTag;
        //cascading the old outputs
        $this->expectOutputString($bareReturn . $withAnchorName . $withEditableTarget);
        Fisma_Format_Section::startSection($anchorText, $editableTarget);

        $fullReturn = $openTag . 'class=\'editable\' target=\'' . $editableTarget . '\'><a name=\'' . $anchorName . '\'>' . $anchorText . '</a>' . $closeTag;
        $this->expectOutputString($bareReturn . $withAnchorName . $withEditableTarget . $fullReturn);
        Fisma_Format_Section::startSection($anchorText, $editableTarget, $anchorName);
    }

    /**
     * test function stopSection()
     * well, there's nothing to test at all
     * recommended: use a XML or any other externally editable input for HTML code
     *
     * @return void
     */
    public function testStopSection()
    {
        $this->expectOutputString('<div class=\'clear\'></div></div>'."\n");
        Fisma_Format_Section::stopSection();
    }
}

