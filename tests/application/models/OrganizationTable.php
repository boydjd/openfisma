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
 * Test_Application_Models_OrganizationTable 
 * 
 * @uses Test_Case_Unit
 * @package Test 
 * @copyright (c) Endeavor Systems, Inc. 2011 {@link http://www.endeavorsystems.com}
 * @author Josh Boyd <joshua.boyd@endeavorsystems.com> 
 * @license http://www.openfisma.org/content/license GPLv3
 */
class Test_Application_Models_OrganizationTable extends Test_Case_Unit
{
    /**
     * testGetAclFields 
     * 
     * @access public
     * @return void
     */
    public function testGetAclFields()
    {
        $this->assertTrue(is_array(OrganizationTable::getAclFields()));
    }

    /**
     * testGetSearchIndexQuery 
     * 
     * @access public
     * @return void
     */
    public function testGetSearchIndexQuery()
    {
        $sampleQ = Doctrine_Query::create()->from('System s');
        $q = OrganizationTable::getSearchIndexQuery($sampleQ, array('OrganizationType' => 'organization_type'));
        $this->assertEquals('Doctrine_Query', get_class($q));
        $this->assertEquals(" FROM System s WHERE organization_type.nickname <> ?", $q->getDql());
    }
    
    /**
     * Check if getOrganizationIds() returns correctly formatted array
     * 
     * @return void
     */
    public function testGetOrganizationIds()
    {
        $orgArray = array(0 => array('id' => 'id'));
        $mockOrg = $this->getMock('Mock_Blank', array('toKeyValueArray'));
        $mockOrg->expects($this->exactly(2))
                ->method('toKeyValueArray')
                ->with('id', 'id')
                ->will($this->onConsecutiveCalls(null, $orgArray));
        $user =  $this->getMock('Mock_Blank', array('getOrganizationsByPrivilege'));
        $user->expects($this->exactly(2))
             ->method('getOrganizationsByPrivilege')
             ->will($this->returnValue($mockOrg));
        CurrentUser::setInstance($user);

        $orgId = OrganizationTable::getOrganizationIds();
        $this->assertEquals(0, count($orgId));

        $orgId = OrganizationTable::getOrganizationIds();
        $this->assertEquals(1, count($orgId));
        $this->assertEquals('id', $orgId[0]['id']);
        CurrentUser::setInstance(null);
    }
    
    /**
     * Test the join part of the query
     *
     * @return void
     */
    public function testGetUsersAndRolesByOrganizationIdQuery()
    {
        $query = OrganizationTable::getUsersAndRolesByOrganizationIdQuery(0)->getDql();
        $expectedQuery = 'FROM User u '
                        .'LEFT JOIN u.UserRole ur '
                        .'LEFT JOIN ur.UserRoleOrganization uro '
                        .'LEFT JOIN ur.Role r '
                        .'LEFT JOIN uro.Organization o';
        $this->assertContains($expectedQuery, $query);
    }
    
    /**
     * Test the join and condition part of the query
     *
     * @return void
     */
    public function testGetSystemsLikeNameQuery()
    {
        $query = OrganizationTable::getSystemsLikeNameQuery('test')->getDql();
        $expectedQuery = 'FROM Organization o LEFT JOIN o.System s WHERE o.name LIKE ?';
        $this->assertContains($expectedQuery, $query);
    }
}
