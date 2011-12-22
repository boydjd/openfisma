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

require_once(realpath(dirname(__FILE__) . '/../../Case/Unit.php'));

/**
 * Test_Application_Models_UserTable 
 * 
 * @uses Test_Case_Unit
 * @package Test 
 * @copyright (c) Endeavor Systems, Inc. 2011 {@link http://www.endeavorsystems.com}
 * @author Duy K. Bui <duy.bui@endeavorsystems.com>
 * @license http://www.openfisma.org/content/license GPLv3
 */
class Test_Application_Models_UserTable extends Test_Case_Unit
{
    /**
     * Check if getSearchableFields() returns a not-empty array 
     * 
     * @access public
     * @return void
     */
    public function testGetSearchableFields()
    {
        $searchableFields = Doctrine::getTable('User')->getSearchableFields();
        $this->assertTrue(is_array($searchableFields));
        $this->assertNotEmpty($searchableFields);
    }
  
    /*
     * testGetAclFields 
     * 
     * @access public
     * @return void
     */
    public function testGetAclFields()
    {
        $this->assertTrue(is_array(Doctrine::getTable('User')->getAclFields()));
    }
 
    /**
     * Test the join part in the query.
     * 
     * @return void
     */
    public function testGetUserByUserRoleIdQuery()
    {
        $query = Doctrine::getTable('User')->getUserByUserRoleIdQuery(1)->getDql();
        $expectedQuery = 'FROM User u INNER JOIN u.UserRole ur WHERE ur.userroleid = ?';
        $this->assertContains($expectedQuery, $query);
    }

    /**
     * Test the condition part in the query.
     * 
     * @return void
     */
    public function testGetUsersLikeUsernameQuery()
    {
        $query = Doctrine::getTable('User')->getUsersLikeUsernameQuery('root')->getDql();
        $expectedQuery = 'FROM User u WHERE u.username LIKE ?';
        $this->assertContains($expectedQuery, $query);
        $this->assertContains('u.locktype is null', $query);
    }
    
    /**
     * Test the query for getRoles()
     *
     * @return void
     */
    public function testGetRolesQuery()
    {
        $userTable = Doctrine::getTable('User');
        $query = $userTable->getRolesQuery(1)->getDql();
        $expectedQuery = 'FROM User u INNER JOIN u.Roles r WHERE u.id = ?';
        $this->assertContains($expectedQuery, $query);
    }    
}
