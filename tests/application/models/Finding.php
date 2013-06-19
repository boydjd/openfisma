<?php
/**
 * Copyright (c) 2008 Endeavor Systems, Inc.
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
 * Unit tests for the finding model
 *
 * @author     Mark E. Haase
 * @copyright  (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Test
 * @subpackage Test_Model
 */
class Test_Application_Models_Finding extends Test_Case_Unit
{
    /**
     * Original ECD cannot be set directly
     *
     * @return void
     * @expectedException Fisma_Zend_Exception
     */
    public function testOriginalEcdCannotBeSet()
    {
        $finding = new Finding();

        $finding->originalEcd = Zend_Date::now();
    }

    /**
     * Test if the ECD lock affects synchronization of the original and current ECD correctly
     *
     * @return void
     * @throws PHPUnit_Framework_ExpectationFailedException if assertion fails
     */
    public function testEcdLockSyncsEcds()
    {
        $finding = new Finding();

        // Setting the current ECD should synchronize the original ECD
        $date1 = Zend_Date::now();
        $finding->currentEcd = $date1;

        $this->assertEquals($date1, $finding->currentEcd);
        $this->assertEquals($date1, $finding->originalEcd);

        // When locked, the original ECD does not synchronize with the current ECD
        $date2 = clone $date1;
        $date2->addDay(1);

        $finding->ecdLocked = true;
        $finding->currentEcd = $date2;

        $this->assertEquals($date2, $finding->currentEcd);
        $this->assertNotEquals($date2, $finding->originalEcd);
    }

    /**
     * Test residual risk calculation
     */
    public function testResidualRiskCalculation()
    {
        $finding = new Finding();

        // If no threat, then there is no risk
        $this->assertNull($finding->calculateResidualRisk(null, null));

        // If no countermeasures, then risk=threat
        $this->assertEquals('HIGH', $finding->calculateResidualRisk('HIGH', null));

        // Nine cases where risk is mapped to threat and countermeasures
        $this->assertEquals('LOW', $finding->calculateResidualRisk('HIGH', 'HIGH'));
        $this->assertEquals('MODERATE', $finding->calculateResidualRisk('HIGH', 'MODERATE'));
        $this->assertEquals('HIGH', $finding->calculateResidualRisk('HIGH', 'LOW'));

        $this->assertEquals('LOW', $finding->calculateResidualRisk('MODERATE', 'HIGH'));
        $this->assertEquals('MODERATE', $finding->calculateResidualRisk('MODERATE', 'MODERATE'));
        $this->assertEquals('MODERATE', $finding->calculateResidualRisk('MODERATE', 'LOW'));

        $this->assertEquals('LOW', $finding->calculateResidualRisk('LOW', 'HIGH'));
        $this->assertEquals('LOW', $finding->calculateResidualRisk('LOW', 'MODERATE'));
        $this->assertEquals('LOW', $finding->calculateResidualRisk('LOW', 'LOW'));
    }

    /**
     * Test residual risk calculation error
     *
     * @expectedException Fisma_Zend_Exception
     */
    public function testResidualRiskCalculationError()
    {
        $finding = new Finding();

        $finding->calculateResidualRisk('Bogus', 'Parameters');
    }

    /**
     * Test the residual risk field
     */
    public function testResidualRisk()
    {
        $finding = new Finding();

        // Test with non-null countermeasures
        $finding->threatLevel = 'HIGH';
        $finding->countermeasuresEffectiveness = 'HIGH';
        $this->assertEquals('LOW', $finding->residualRisk);
    }

    /**
     * Test the residual risk field
     */
    public function testResidualRiskNullCountermeasures()
    {
        $finding = new Finding();

        // Test null countermeasures
        $finding->threatLevel = 'HIGH';
        $this->assertEquals('HIGH', $finding->residualRisk);
    }
}
