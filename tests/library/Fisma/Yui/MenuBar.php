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
 * Class description
 *
 * @author     Duy K. Bui <duy.bui@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2011 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Test
 * @subpackage Test_Library
 * @require    Fisma_Yui_Menu
 */
class Test_Library_Fisma_Yui_MenuBar extends Test_Case_Unit
{
    /**
     * test add() and get() methods
     */
    public function testAdd()
    {
        $dummyMenu = new Fisma_Yui_Menu(null);
        $menuBar = new Fisma_Yui_MenuBar();
        
        $menuBar->add($dummyMenu);
        $menus = $menuBar->getMenus();
        $this->assertEquals(1, count($menus));
        $this->assertEquals($dummyMenu, $menus[0]);

        $this->setExpectedException('Fisma_Zend_Exception', 'Can only add Menus and MenuItems to this class.');
        $menuBar->add($menuBar);
    }
}

