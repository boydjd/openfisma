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
 * Class description
 *
 * @author     Duy K. Bui <duy.bui@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2011 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Test
 * @subpackage Test_Library
 */
class Test_Library_Fisma_ZfDebug_Plugin_YuiLogging extends Test_Case_Unit
{
    /**
     * test getTab method
     */
    public function testGetTab()
    {
        $samplePlugin = new Fisma_ZfDebug_Plugin_YuiLogging();
        $this->assertEquals(Fisma_ZfDebug_Plugin_YuiLogging::TAB_NAME, $samplePlugin->getTab());
    }

    /**
     * test getIconData method
     */
    public function testGetIconData()
    {
        $samplePlugin = new Fisma_ZfDebug_Plugin_YuiLogging();
        $this->assertEquals(Fisma_ZfDebug_Plugin_YuiLogging::ICON_MIME.';'.Fisma_ZfDebug_Plugin_YuiLogging::ICON_DATA, $samplePlugin->getIconData());
    }

    /**
     * test whether getIdentifier() return the classname
     */
    public function testGetIdentifier()
    {
        $samplePlugin = new Fisma_ZfDebug_Plugin_YuiLogging();
        $this->assertEquals(get_class($samplePlugin), $samplePlugin->getIdentifier());
    }

    /**
     * test whether getPanel() use the correct view script
     * @recommend: use a constant or variable to hold the path instead of hard-coding
     */
    public function testGetPanel()
    {
        $samplePlugin = new Fisma_ZfDebug_Plugin_YuiLogging();

        //test with default view
        $this->assertEquals($samplePlugin::LAYOUT_NOT_INSTANTIATED_ERROR, $samplePlugin->getPanel());
        //test with mock view
        $this->assertEquals('debug/zfdebug-yui-logging-tab.phtml', $samplePlugin->getPanel(new MockLayout()));
    }
}
class MockLayout
{
    public function getView()
    {
        return new MockView();
    }
}
class MockView
{
    public function partial($string)
    {
        return $string;
    }
}
