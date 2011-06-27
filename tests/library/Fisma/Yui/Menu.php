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

require_once(realpath(dirname(__FILE__) . '/../../../FismaUnitTest.php'));

/**
 * Tests for the wrapper around YUI menu
 *
 * @author     Mark E. Haase
 * @copyright  (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Test
 * @subpackage Test_Fisma
 */
class Test_Library_Fisma_Yui_Menu extends Test_FismaUnitTest
{
    /**
     * Test a basic menu with no submenus or separators
     */
    public function testBasicMenu()
    {
        $menu = new Fisma_Yui_Menu('Test Menu');
        
        $menu->add(new Fisma_Yui_MenuItem('Google', 'http://www.google.com'));
        $menu->add(new Fisma_Yui_MenuItem('Yahoo', 'http://www.yahoo.com'));
        
        // Convert to JSON and then back to PHP array, to make sure public members are exposed correctly
        $menuArray = json_decode(json_encode($menu), true);

        $this->assertEquals('Test Menu', $menuArray['text']);

        // There is only one section in itemdata (because the menu has no separators)
        $itemData = $menuArray['submenu']['itemdata'];

        $this->assertEquals(1, count($itemData));

        // The first section of itemdata contains two menu items
        $this->assertEquals(2, count($itemData[0]));
    
        // Now do assertions on individual menu items
        $this->assertEquals('Google', $itemData[0][0]['text']);
        $this->assertEquals('http://www.google.com', $itemData[0][0]['url']);
        
        $this->assertEquals('Yahoo', $itemData[0][1]['text']);
        $this->assertEquals('http://www.yahoo.com', $itemData[0][1]['url']);
    }
    
    /**
     * Test a menu with a separator
     */
    public function testMenuSeparator()
    {
        $menu = new Fisma_Yui_Menu('Test Menu');
        
        $menu->add(new Fisma_Yui_MenuItem('Google', 'http://www.google.com'));
        $menu->addSeparator();
        $menu->add(new Fisma_Yui_MenuItem('Yahoo', 'http://www.yahoo.com'));
        
        // Convert to JSON and then back to PHP array, to make sure public members are exposed correctly
        $menuArray = json_decode(json_encode($menu), true);
        
        // There are two sections in itemdata (because the menu contains one separator)
        $itemData = $menuArray['submenu']['itemdata'];

        $this->assertEquals(2, count($itemData));

        // Each section one menu item
        $this->assertEquals(1, count($itemData[0]));
        $this->assertEquals(1, count($itemData[1]));
    
        // Now do assertions on individual menu items
        $this->assertEquals('Google', $itemData[0][0]['text']);
        $this->assertEquals('http://www.google.com', $itemData[0][0]['url']);
        
        $this->assertEquals('Yahoo', $itemData[1][0]['text']);
        $this->assertEquals('http://www.yahoo.com', $itemData[1][0]['url']);
    }
    
    /**
     * Test a menu that contains a submenu
     */
    public function testMenuWithSubmenu()
    {
        $menu = new Fisma_Yui_Menu('Test Menu');

        $subMenu = new Fisma_Yui_Menu('Sub Menu');
        $subMenu->add(new Fisma_Yui_MenuItem('Google', 'http://www.google.com'));
        $subMenu->add(new Fisma_Yui_MenuItem('Yahoo', 'http://www.yahoo.com'));
        
        $menu->add($subMenu);
        
        // Convert to JSON and then back to PHP array, to make sure public members are exposed correctly
        $menuArray = json_decode(json_encode($menu), true);
        
        $this->assertEquals('Sub Menu', $menuArray['submenu']['itemdata'][0][0]['text']);
        
        $subMenuItems = $menuArray['submenu']['itemdata'][0][0]['submenu']['itemdata'];
        
        // Now do assertions on individual menu items
        $this->assertEquals('Google', $subMenuItems[0][0]['text']);
        $this->assertEquals('http://www.google.com', $subMenuItems[0][0]['url']);
        
        $this->assertEquals('Yahoo', $subMenuItems[0][1]['text']);
        $this->assertEquals('http://www.yahoo.com', $subMenuItems[0][1]['url']);
    }
}
