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
 * Tests for Fisma_Array.
 *
 * @author     Mark E. Haase <mhaase@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2011 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Test
 * @subpackage Test_Fisma
 */
class Test_Library_Fisma_Array extends Test_Case_Unit
{
    /**
     * Test grouping a nested array by keys.
     */
    public function testGroupByKey()
    {
        $linearArray = array(
            array('groupId' => 'OpenFISMA', 'id' => 1, 'name' => 'Mark'),
            array('groupId' => 'OpenFISMA', 'id' => 2, 'name' => 'Andrew'),
            array('groupId' => 'OpenFISMA', 'id' => 3, 'name' => 'Duy'),
            array('groupId' => 'BD', 'id' => 4, 'name' => 'Max'),
            array('groupId' => 'BD', 'id' => 5, 'name' => 'Craig'),
            array('groupId' => 'APT', 'id' => 6, 'name' => 'Anthony'),
            array('groupId' => 'APT', 'id' => 7, 'name' => 'AJ'),
            array('groupId' => 'APT', 'id' => 8, 'name' => 'Frank')
        );

        $expectedOutput1 = array(
            'OpenFISMA' => array(
                array('id' => 1, 'name' => 'Mark'),
                array('id' => 2, 'name' => 'Andrew'),
                array('id' => 3, 'name' => 'Duy')),
            'BD' => array(
                array('id' => 4, 'name' => 'Max'),
                array('id' => 5, 'name' => 'Craig')),
            'APT' => array(
                array('id' => 6, 'name' => 'Anthony'),
                array('id' => 7, 'name' => 'AJ'),
                array('id' => 8, 'name' => 'Frank'))
        );

        $this->assertEquals($expectedOutput1, Fisma_Array::groupByKey($linearArray, 'groupId'));

        $expectedOutput2 = array(
            'OpenFISMA' => array('Mark', 'Andrew', 'Duy'),
            'BD' => array('Max', 'Craig'),
            'APT' => array('Anthony', 'AJ', 'Frank')
        );

        $this->assertEquals($expectedOutput2, Fisma_Array::groupByKey($linearArray, 'groupId', 'name'));
    }

    /**
     * Test invalid group key.
     *
     * @expectedException Fisma_Zend_Exception
     */
    public function testInvalidGroupKey()
    {
        $linearArray = array(
            array('groupId' => 'OpenFISMA', 'id' => 1, 'name' => 'Mark'),
            array('id' => 2, 'name' => 'Andrew'), // <--- Missing the group key!
            array('groupId' => 'OpenFISMA', 'id' => 3, 'name' => 'Duy')
        );

        Fisma_Array::groupByKey($linearArray, 'groupId');
    }

    /**
     * Test malformed input array.
     *
     * @expectedException Fisma_Zend_Exception
     */
    public function testMalformedInput()
    {
        $linearArray = array(
            array('groupId' => 'OpenFISMA', 'id' => 1, 'name' => 'Mark'),
            5, // <--- This is a scalar, not an array!
            array('groupId' => 'OpenFISMA', 'id' => 2, 'name' => 'Andrew'),
        );

        Fisma_Array::groupByKey($linearArray, 'groupId');
    }
}
