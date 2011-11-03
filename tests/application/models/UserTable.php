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
 * @package Test_ 
 * @copyright (c) Endeavor Systems, Inc. 2011 {@link http://www.endeavorsystems.com}
 * @author Josh Boyd <joshua.boyd@endeavorsystems.com> 
 * @license http://www.openfisma.org/content/license GPLv3
 */
class Test_Application_Models_UserTable extends Test_Case_Unit
{
    /**
     * testGetSearchableFields 
     * 
     * @access public
     * @return void
     */
    public function testGetSearchableFields()
    {
        $this->assertTrue(class_exists('UserTable'));
        try {
            $searchableFields = Doctrine::getTable('User')->getSearchableFields();
        } catch (Exception $e) {
            $this->markTestSkipped('This test must be run alone due to dynamic class loading problem.');
        }
        $this->assertTrue(is_array($searchableFields));
        $this->assertEquals(12, count($searchableFields));
    }
  
    /*
     * testGetAclFields 
     * 
     * @access public
     * @return void
     */
    public function testGetAclFields()
    {
        $this->assertTrue(is_array(UserTable::getAclFields()));
    }
 
    /**
     * Test the join part in the query.
     * 
     * @return void
     */
    public function testGetUserByUserRoleIdQuery()
    {
        $query = UserTable::getUserByUserRoleIdQuery(1)->getSql();
        $expectedQuery = 'FROM user u INNER JOIN user_role u2 ON u.id = u2.userid WHERE u2.userroleid = ?';
        $this->assertContains($expectedQuery, $query);
    }

    /**
     * Test the condition part in the query.
     * 
     * @return void
     */
    public function testGetUsersLikeUsernameQuery()
    {
        $query = UserTable::getUsersLikeUsernameQuery('root')->getSql();
        $expectedQuery = 'FROM user u WHERE u.username LIKE ?';
        $this->assertContains($expectedQuery, $query);
        $this->assertContains('u.locktype is null', $query);
    }
}
