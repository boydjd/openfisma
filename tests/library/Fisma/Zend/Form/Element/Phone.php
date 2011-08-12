<?php
/**
 * Copyright (c) 2010 Endeavor Systems, Inc.
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

require_once(realpath(dirname(__FILE__) . '/../../../../../FismaUnitTest.php'));

/**
 * Tests for the Fisma_Zend_Form_Element_Phone class
 * 
 * @author     Andrew Reeves <andrew.reeves@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2010 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Test
 * @subpackage Test_Fisma_Zend_Form_Element
 */
class Test_Library_Fisma_Zend_Form_Element_Phone extends Test_FismaUnitTest
{
    /**
     * Test that the constructor adds the filter and validator
     */
    public function testConstruct()
    {
        $phone = new Fisma_Zend_Form_Element_Phone('phone');

        $hasFilter = false;
        foreach ($phone->getFilters() as $filter) {
            if ($filter instanceOf Fisma_Zend_Filter_Phone) {
                $hasFilter = true;
                break;
            }
        }
        $this->assertTrue($hasFilter, 'No filter after construct.');

        $hasValidator = false;
        foreach ($phone->getValidators() as $validator) {
            if ($validator instanceOf Fisma_Zend_Validate_Phone) {
                $hasValidator = true;
                break;
            }
        }
        $this->assertTrue($hasValidator, 'No validator after construct.');
    }

    /**
     * Test that the clearFilters() method does not remove the Phone filter
     */
    public function testClearFilters()
    {
        $phone = new Fisma_Zend_Form_Element_Phone('phone');
        $phone->clearFilters();

        $hasFilter = false;
        foreach ($phone->getFilters() as $filter) {
            if ($filter instanceOf Fisma_Zend_Filter_Phone) {
                $hasFilter = true;
                break;
            }
        }
        $this->assertTrue($hasFilter, 'No filter after clearFilters when there should be.');
    }
}
