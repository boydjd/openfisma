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
 * Test_Application_Models_FindingTable 
 * 
 * @uses Test_Case_Unit
 * @package Test 
 * @copyright (c) Endeavor Systems, Inc. 2011 {@link http://www.endeavorsystems.com}
 * @author Duy K. Bui <duy.bui@endeavorsystems.com>
 * @license http://www.openfisma.org/content/license GPLv3
 */
class Test_Application_Models_FindingTable extends Test_Case_Unit
{
    /**
     * Make sure Index Chunk Size = 10
     * 
     * @access public
     * @return void
     * @author Josh Boyd <joshua.boyd@endeavorsystems.com>
     */
    public function testGetIndexChunkSizeIs10()
    {
        $this->assertEquals(10, FindingTable::getIndexChunkSize());
    }
    
    /**
     * Check if getSearchableFields() returns a not-empty array
     *
     * @access public
     * @return void
     */
    public function testGetSearchableFields()
    {
        $searchableFields = Doctrine::getTable('Finding')->getSearchableFields();
        $this->assertTrue(is_array($searchableFields));
        $this->assertNotEmpty($searchableFields);
    }
    
    /**
     * test getOrganizationIds()
     * 
     * @return void
     * @todo reduce the amount of faking
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

        $orgId = FindingTable::getOrganizationIds();
        $this->assertEquals(0, count($orgId));

        $orgId = FindingTable::getOrganizationIds();
        $this->assertEquals(1, count($orgId));
        $this->assertEquals('id', $orgId[0]['id']);
        CurrentUser::setInstance(null);
    }
    
    /**
     * testGetAclFields 
     * 
     * @access public
     * @return void
     */
    public function testGetAclFields()
    {
        $this->assertTrue(is_array(Doctrine::getTable('Finding')->getAclFields()));
    }
}
