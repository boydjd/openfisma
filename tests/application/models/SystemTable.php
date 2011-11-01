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

require_once (realpath(dirname(__FILE__) . '/../../Case/Unit.php'));

/**
 * Test_Application_Models_SystemTable
 *
 * @uses Test_Case_Unit
 * @package Test_
 * @copyright (c) Endeavor Systems, Inc. 2011 {@link http://www.endeavorsystems.com}
 * @author Josh Boyd <joshua.boyd@endeavorsystems.com>
 * @license http://www.openfisma.org/content/license GPLv3
 */
class Test_Application_Models_SystemTable extends Test_Case_Unit
{
    /*
     * testGetSearchableFields
     *
     * @access public
     * @return void
     */
    public function testGetSearchableFields()
    {
        $this->assertTrue(class_exists('SystemTable'));
        try {
            $searchableFields = Doctrine::getTable('System')->getSearchableFields();
        } catch (Exception $e) {
            $this->markTestSkipped('This test must be run alone due to dynamic class loading problem.');
        }
        $this->assertTrue(is_array($searchableFields));
        $this->assertEquals(24, count($searchableFields));
    }

    /**
     * test getSystemIds()
     *
     * @return void
     */
    public function testGetSystemIds()
    {
        $orgArray = array('key' => 'value');
        $mockOrg = $this->getMock('Doctrine_Query', array('toKeyValueArray'));
        $mockOrg->expects($this->once())->method('toKeyValueArray')->with('systemId', 'systemId')->will($this->returnValue($orgArray));
        $user = $this->getMock('User', array('getSystemsByPrivilege'));
        $user->expects($this->once())->method('getSystemsByPrivilege')->will($this->returnValue($mockOrg));
        CurrentUser::setInstance($user);

        $orgId = SystemTable::getSystemIds();
        $this->assertEquals(1, count($orgId));
        $this->assertEquals('key', $orgId[0]);
    }

    /**
     * Test getAclFields() as a static method
     *
     * @return void
     */
    public function testGetAclFields()
    {
        $field = SystemTable::getAclFields();
        $this->assertGreaterThanOrEqual(1, count($field));
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
