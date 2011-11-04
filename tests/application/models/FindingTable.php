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
 * @author Josh Boyd <joshua.boyd@endeavorsystems.com> 
 * @license http://www.openfisma.org/content/license GPLv3
 */
class Test_Application_Models_FindingTable extends Test_Case_Unit
{
    
    public function testGetIndexChunkSizeIs10()
    {
        $this->assertEquals(10, FindingTable::getIndexChunkSize());
    }
    
    /*
     * Check if getSearchableFields() returns a not-empty array
     *
     * @access public
     * @return void
     */
    public function testGetSearchableFields()
    {
        $this->assertTrue(class_exists('FindingTable'));
        try {
            $searchableFields = Doctrine::getTable('Finding')->getSearchableFields();
        } catch (Exception $e) {
            $this->markTestSkipped('This test must be run alone due to dynamic class loading problem.');
        }
        $this->assertTrue(is_array($searchableFields));
        $this->assertNotEmpty($searchableFields);
    }
    
    /**
     * test getOrganizationIds()
     * 
     * @return void
     */
    public function testGetOrganizationIds()
    {
        $orgArray = array(0 => array('id' => 'id'));
        $mockOrg = $this->getMock('Doctrine_Query', array('toKeyValueArray'));
        $mockOrg->expects($this->exactly(2))
                ->method('toKeyValueArray')
                ->with('id', 'id')
                ->will($this->onConsecutiveCalls(null, $orgArray));
        $user =  $this->getMock('User', array('getOrganizationsByPrivilege'));
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
        $this->assertTrue(is_array(FindingTable::getAclFields()));
    }
}
