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
 * Test_Application_Models_AssetTable 
 * 
 * @uses Test_Case_Unit
 * @package Test_ 
 * @copyright (c) Endeavor Systems, Inc. 2011 {@link http://www.endeavorsystems.com}
 * @author Josh Boyd <joshua.boyd@endeavorsystems.com> 
 * @license http://www.openfisma.org/content/license GPLv3
 */
class Test_Application_Models_AssetTable extends Test_Case_Unit
{
    /*
     * testGetSearchableFields 
     * 
     * @access public
     * @return void
     */
    public function testGetSearchableFields()
    {
        $this->assertTrue(class_exists('AssetTable'));
        $searchableFields = Doctrine::getTable('Asset')->getSearchableFields();
        $this->assertTrue(is_array($searchableFields));
        $this->assertEquals(10, count($searchableFields));
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

        $orgId = AssetTable::getOrganizationIds();
        $this->assertEquals(0, count($orgId));

        $orgId = AssetTable::getOrganizationIds();
        $this->assertEquals(1, count($orgId));
        $this->assertEquals('id', $orgId[0]['id']);
    }
    /**
     * Test getAclFields() as a static method
     * 
     * @return void
     */
    public function testGetAclFields()
    {
        $user = $this->getMock('User', array('acl'));
        $user->expects($this->exactly(2))
             ->method('acl')
             ->will($this->onConsecutiveCalls(new Fisma_Zend_Acl('defaultUser'), new Fisma_Zend_Acl('root')));
        CurrentUser::setInstance($user);
        $field = AssetTable::getAclFields();
        $this->assertEquals(1, count($field));

        $field = AssetTable::getAclFields();
        $this->assertGreaterThanOrEqual(0, count($field));
    }

    /**
     * revert the states of Static classes to original
     * 
     * @return void
     */
    public static function tearDownAfterClass()
    {
        CurrentUser::setInstance(null);
    }
}
