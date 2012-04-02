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
 * Tests for the system model
 *
 * @author     Mark E. Haase
 * @copyright  (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Test
 * @subpackage Test_Model
 */
class Test_Application_Models_System extends Test_Case_Unit
{
    /**
     * FIPS category is the high water mark of CIA
     *
     * @return void
     * @throws PHPUnit_Framework_ExpectationFailedException if assertion fails
     */
    public function testFipsCategoryIsHighWaterMarkOfCia()
    {
        $system = new System();

        $system->confidentiality = System::CIA_MODERATE;
        $system->integrity = System::CIA_LOW;
        $system->availability = System::CIA_HIGH;
        $this->assertEquals(System::CIA_HIGH, $system->fipsCategory);

        $system->availability = System::CIA_LOW;
        $this->assertEquals(System::CIA_MODERATE, $system->fipsCategory);

        $system->confidentiality = System::CIA_LOW;
        $this->assertEquals(System::CIA_LOW, $system->fipsCategory);
    }

    /**
     * If one, but not all, CIA values are set, then FIPS security category should still be defined
     *
     * @return void
     * @throws PHPUnit_Framework_ExpectationFailedException if assertion fails
     */
    public function testFipsCategoryDefinedIfAnyCiaDefined()
    {
        $system = new System();

        $system->confidentiality = System::CIA_LOW;
        $this->assertEquals(System::CIA_LOW, $system->fipsCategory);
    }

    /**
     * Confidentiality can be N/A but FIPS category is still defined
     *
     * @return void
     * @throws PHPUnit_Framework_ExpectationFailedException if assertion fails
     */
    public function testFipsCategoryWhenConfidentialityIsNa()
    {
        $system = new System();

        $system->confidentiality = System::CIA_NA;
        $system->integrity = System::CIA_LOW;
        $system->availability = System::CIA_MODERATE;
        $this->assertEquals(System::CIA_MODERATE, $system->fipsCategory);
    }

    /**
     * If all CIA values are null, than FIPS category is null
     *
     * @return void
     * @throws PHPUnit_Framework_ExpectationFailedException if assertion fails
     */
    public function testFipsCategoryIsNullWhenCiaIsNull()
    {
        $system = new System();

        $this->assertNull($system->fipsCategory);
    }

    /**
     * FIPS category cannot be set directly
     *
     * @return void
     * @expectedException Fisma_Zend_Exception
     */
    public function testCannotSetFipsCategoryDirectly()
    {
        $system = new System();

        $system->fipsCategory = System::CIA_MODERATE;
    }

    /**
     * Exhibit 53 Unique Project Id has automatic formatting
     *
     * @return void
     * @throws PHPUnit_Framework_ExpectationFailedException if assertion fails
     */
    public function testUpiFormatting()
    {
        $system = new System();

        // Should automatically hyphenate
        $system->uniqueProjectId = '0123456789ABCDEFG';
        $this->assertEquals('012-34-56-78-9A-BCDE-FG', $system->uniqueProjectId);

        // If the UPI is short, then it should pad out to fit the format
        $system->uniqueProjectId = '0123456789';
        $this->assertEquals('012-34-56-78-90-0000-00', $system->uniqueProjectId);
    }

    /**
     * testGetSdlcPhaseLabelForInitiation
     *
     * @access public
     * @return void
     */
    public function testGetSdlcPhaseLabelForInitiation()
    {
        $system = new System();

        $system->sdlcPhase = 'initiation';

        $this->assertEquals('Initiation', $system->getSdlcPhaseLabel());
    }

    /**
     * testGetSdlcPhaseLabelForDevelopment
     *
     * @access public
     * @return void
     */
    public function testGetSdlcPhaseLabelForDevelopment()
    {
        $system = new System();

        $system->sdlcPhase = 'development';

        $this->assertEquals('Development/Acquisition', $system->getSdlcPhaseLabel());
    }

    /**
     * testGetSdlcPhaseLabelForImplementation
     *
     * @access public
     * @return void
     */
    public function testGetSdlcPhaseLabelForImplementation()
    {
        $system = new System();

        $system->sdlcPhase = 'implementation';

        $this->assertEquals('Implementation/Assessment', $system->getSdlcPhaseLabel());
    }

    /**
     * testGetSdlcPhaseLabelForOperations
     *
     * @access public
     * @return void
     */
    public function testGetSdlcPhaseLabelForOperations()
    {
        $system = new System();

        $system->sdlcPhase = 'operations';

        $this->assertEquals('Operations/Maintenance', $system->getSdlcPhaseLabel());
    }

    /**
     * testGetSdlcPhaseLabelForDisposal
     *
     * @access public
     * @return void
     */
    public function testGetSdlcPhaseLabelForDisposal()
    {
        $system = new System();

        $system->sdlcPhase = 'disposal';

        $this->assertEquals('Disposal', $system->getSdlcPhaseLabel());
    }

    /**
     * testGetSdlcPhaseMap
     *
     * @access public
     * @return void
     */
    public function testGetSdlcPhaseMap()
    {
        $system = new System();

        $this->assertTrue(is_array($system->getSdlcPhaseMap()));
    }

    /**
     * testCalculateThreatLikelihood
     *
     * @access public
     * @return void
     * @dataProvider threatLikelihoodProvider
     */
    public function testCalculateThreatLikelihood($expected, $threat, $countermeasure)
    {
        $system = new System();

        $this->assertEquals($expected, $system->calculateThreatLikelihood($threat, $countermeasure));
    }

    /**
     * threatLikelihoodProvider
     *
     * @static
     * @access public
     * @return void
     */
    static public function threatLikelihoodProvider()
    {
        return array(
            array('expected' => 'HIGH', 'threat' => 'HIGH', 'countermeasure' => 'LOW'),
            array('expected' => 'MODERATE', 'threat' => 'HIGH', 'countermeasure' => 'MODERATE'),
            array('expected' => 'LOW', 'threat' => 'HIGH', 'countermeasure' => 'HIGH'),
            array('expected' => 'MODERATE', 'threat' => 'MODERATE', 'countermeasure' => 'LOW'),
            array('expected' => 'MODERATE', 'threat' => 'MODERATE', 'countermeasure' => 'MODERATE'),
            array('expected' => 'LOW', 'threat' => 'MODERATE', 'countermeasure' => 'HIGH'),
            array('expected' => 'LOW', 'threat' => 'LOW', 'countermeasure' => 'LOW'),
            array('expected' => 'LOW', 'threat' => 'LOW', 'countermeasure' => 'MODERATE'),
            array('expected' => 'LOW', 'threat' => 'LOW', 'countermeasure' => 'HIGH')
        );
    }

    /**
     * Test calcMin()
     *
     * @return void
     */
    public function testCalcMin()
    {
        $system = new System();
        $this->assertEquals('LOW', $system->calcMin('LOW', 'HIGH'));
    }

    /**
     * Test getName()
     *
     * @return void
     */
    public function testGetName()
    {
        $system = new System();
        $system->Organization->nickname = 'org_nick';
        $system->Organization->name = 'org_name';
        $this->assertEquals('org_nick - org_name', $system->getName());
    }

    /**
     * Test getOrganizationDependencyId()
     *
     * @return void
     */
    public function testGetOrganizationDependencyId()
    {
        $system = new System();
        $system->Organization->id = 1;
        $this->assertEquals(1, $system->getOrganizationDependencyId());
    }

    /**
     * Test the implementation of ON_DELETE constraint
     *
     * @return void
     */
    public function testPreDelete()
    {
        $system = new System();
        $system->preDelete(null);

        $mockIncident = $this->getMock('Mock_Blank', array('set'));
        $system->Organization->Incidents[] = $mockIncident;

        $this->setExpectedException('Fisma_Zend_Exception_User');
        $system->preDelete(null);
    }
}
