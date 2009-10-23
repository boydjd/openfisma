<?php
/**
 * Copyright (c) 2008 Endeavor Systems, Inc.
 *
 * This file is part of OpenFISMA.
 *
 * OpenFISMA is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * OpenFISMA is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with OpenFISMA.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author    Mark E. Haase <mhaase@endeavorsystems.com>
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/content/license
 * @package   Model
 */

require_once(realpath(dirname(__FILE__) . '/../FismaUnitTest.php'));

/**
 * Unit tests for the finding model
 *
 * @package   Test
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/content/license
 */
class Test_Model_Finding extends Test_FismaUnitTest
{
    /**
     * New findings default to NEW status
     */
    public function testNewFindingStatusIsNew()
    {
        $finding = new Finding();
        $this->assertEquals('NEW', $finding->status);
    }
    
    /**
     * New finding has due date 30 days in the future
     */
    public function testNewFindingHasThirtyDayDueDate()
    {
        $finding = new Finding();
        
        $expectedDueDate = Zend_Date::now();
        $expectedDueDate->addDay(30);
        
        $this->assertEquals($expectedDueDate->toString('Y-m-d'), $finding->nextDueDate);
    }
    
    /**
     * Setting the mitigation type on a NEW finding changes the status to DRAFT
     */
    public function testSettingMitigationTypeOnNewFindingChangesStatusToDraft()
    {
        $finding = new Finding();
        
        $finding->type = 'CAP';
        
        $this->assertEquals('DRAFT', $finding->status);
    }
    
    /**
     * Test that the mitigation type cannot be set and then unset
     * 
     * @expectedException
     */
    public function testMitigationTypeCannotBeUnset()
    {
        $finding = new Finding();
        
        $finding->type = 'CAP';
        unset($finding->type);
    }
    
    /**
     * Test ECD business logic and locking rules
     */
    public function testEcdChangeAndLocking()
    {
        $finding = new Finding();

        // Create sample ECDs
        $ecd1 = Zend_Date::now();
        $ecd1->addMonth(3);

        $ecd2 = Zend_Date::now();
        $ecd2->addMonth(6);
        
        // Setting the ECD should update the original and current fields
        $finding->currentEcd = $ecd1->toString('Y-m-d');
        $this->assertEquals($ecd1->toString('Y-m-d'), $finding->currentEcd);
        $this->assertEquals($ecd1->toString('Y-m-d'), $finding->originalEcd);
        
        // Now change finding status to EN. This should lock the original ECD
        $finding->status = 'EN';
        $finding->currentEcd = $ecd2->toString('Y-m-d');
        $this->assertEquals($ecd2->toString('Y-m-d'), $finding->currentEcd);
        $this->assertEquals($ecd1->toString('Y-m-d'), $finding->originalEcd);
    }
    
    /**
     * Test that original ECD can't be set directly
     * 
     * @expectedException Fisma_Exception
     */
    public function testOriginalEcdCantBeSetDirectly()
    {
        $finding = new Finding();

        // 6 months in the future is picked arbitrarily:
        $ecd = Zend_Date::now();
        $ecd->addMonth(6);
        
        $finding->setOriginalEcd($ecd->toString('Y-m-d'));
    }
}