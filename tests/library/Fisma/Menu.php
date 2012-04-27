<?php
/**
 * Copyright (c) 2012 Endeavor Systems, Inc.
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

require_once(realpath(dirname(__FILE__) . '/../../Case/Database.php'));

/**
 * test /library/Fisma/Menu.php
 *
 * @author     Mark Ma <mark.ma@reyosoft.com>
 * @copyright  (c) Endeavor Systems, Inc. 2012 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Test
 * @subpackage Test_Library
 */
class Test_Library_Fisma_Menu extends Test_Case_Database
{

    /**
     * setUp
     *
     * @access public
     * @return void
     */
    public function setUp()
    {
        Fisma::setConfiguration(new Fisma_Configuration_Array(), true);
        Fisma::configuration()->setConfig('auth_type', 'database');
    }

     /**
     * Testing the menu items with root user.
     *
     * @return void
     */
    public function testMenuWithRootUser()
    {
        $user = $this->getMock('Mock_Blank', array('acl'));
        $user->expects($this->any())
             ->method('acl')
             ->will($this->returnValue(new Fisma_Zend_Acl('user_root')));

        $menu = Fisma_Menu::getMainMenu($user)->getMenus();

        // Should have all menu items. 
        $this->assertEquals('Dashboard', $menu[0]->text); 
        $this->assertEquals('Findings', $menu[1]->text); 
        $this->assertEquals('Vulnerabilities', $menu[2]->text); 
        $this->assertEquals('System Inventory', $menu[3]->text); 
        $this->assertEquals('Incidents', $menu[4]->text); 
        $this->assertEquals('Administration', $menu[5]->text); 
        $this->assertEquals('User Preferences', $menu[6]->text); 
        $this->assertEquals('Debug', $menu[7]->text); 

        // Should have all the submenu items.
        $this->assertEquals('Summary', $menu[1]->submenu['itemdata'][0][0]->text); 
        $this->assertEquals('Administration', $menu[1]->submenu['itemdata'][2][2]->text); 
        $this->assertEquals('Search', $menu[2]->submenu['itemdata'][0][0]->text); 
        $this->assertEquals('Reports', $menu[2]->submenu['itemdata'][2][1]->text); 
        $this->assertEquals('Assets', $menu[3]->submenu['itemdata'][0][0]->text); 
        $this->assertEquals('Controls', $menu[3]->submenu['itemdata'][1][1]->submenu['itemdata'][0][0]->text); 
        $this->assertEquals('Dashboard', $menu[4]->submenu['itemdata'][1][0]->text); 
        $this->assertEquals('E-mail', $menu[5]->submenu['itemdata'][0][0]->text); 
        $this->assertEquals('Change Password', $menu[6]->submenu['itemdata'][0][0]->text); 
    }

     /**
     * Testing the menu items with default user
     *
     * @return void
     */
    public function testMenuWithDefaultUser()
    {
        $user = $this->getMock('Mock_Blank', array('acl'));
        $user->expects($this->any())
             ->method('acl')
             ->will($this->returnValue(new Fisma_Zend_Acl('defaultUser')));

        // Default user has no privilege, so, it should have only two menu items. 
        $menu = Fisma_Menu::getMainMenu($user)->getMenus();
        $this->assertEquals('User Preferences', $menu[0]->text); 
        $this->assertEquals('Debug', $menu[1]->text); 
        $this->assertNull($menu[2]->text); 
    }

     /**
     * Testing the menu items with auth type of LDAP
     *
     * @return void
     */
    public function testMenuWithLDAP()
    {
        Fisma::setConfiguration(new Fisma_Configuration_Array(), true);
        Fisma::configuration()->setConfig('auth_type', 'ldap');

        $user = $this->getMock('Mock_Blank', array('acl'));
        $user->expects($this->any())
             ->method('acl')
             ->will($this->returnValue(new Fisma_Zend_Acl('user_root')));

        $menu = Fisma_Menu::getMainMenu($user)->getMenus();

        // LDAP menu item should show
        $this->assertEquals('Administration', $menu[5]->text); 
        $this->assertEquals('LDAP', $menu[5]->submenu['itemdata'][0][2]->text); 
    }
}

