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

require_once(realpath(dirname(__FILE__) . '/../../FismaUnitTest.php'));

/**
 * Tests for CPE (Common Platform Enumeration) class
 * 
 * @author     Mark E. Haase
 * @copyright  (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Test
 * @subpackage Test_Library
 */
class Test_Library_Fisma_Cpe extends Test_FismaUnitTest
{
    /**
     * Test proper handling of URL encoded CPEs
     */
    public function testUrlEncodedCpe()
    {
        // Encode some random chacters that are specifically mentioned in the CPE spec.
        $urlEncoding = rawurlencode(':/?'); 
        $cpeString = "cpe:/h:redhat:$urlEncoding:3:sp1:ultimate:en";
        
        $cpe = new Fisma_Cpe($cpeString);
        
        $this->assertEquals('h', $cpe->part);
        $this->assertEquals('redhat', $cpe->vendor);
        $this->assertEquals(':/?', $cpe->product);
        $this->assertEquals(3, $cpe->version);
        $this->assertEquals('sp1', $cpe->update);
        $this->assertEquals('ultimate', $cpe->edition);
        $this->assertEquals('en', $cpe->language);
    }
    
    /**
     * Negative test for CPE which doesn't have correct preamble
     * 
     * @expectedException Fisma_Cpe_Exception
     */
    public function testIncorrectPreamble()
    {
        // It should always start with 'cpe:/'
        $cpeString = 'http://google.com';
        
        $cpe = new Fisma_Cpe($cpeString);
    }
    
    /**
     * Negative test for invalid CPE part
     * 
     * @expectedException Fisma_Cpe_Exception
     */
    public function testInvalidPart()
    {
        // 'x' is not a legal part specifier
        $cpeString = 'cpe:/x:apple_computer:experimental_table:1.0';
        
        $cpe = new Fisma_Cpe($cpeString);
    }
    
    /**
     * Negative test for a CPE that has too many parts
     * 
     * @expectedException Fisma_Cpe_Exception
     */
    public function testCpeHasTooManyParts()
    {
        // The CPE can have at most 8 parts
        $cpeString = 'cpe:/o:1:2:3:4:5:6:7';
        
        $cpe = new Fisma_Cpe($cpeString);
    }

    public function testGetCpeName()
    {
        $cpeString = 'cpe:/h:readhat' . rawurlencode(':/?') . ':3:sp1:ultimate:en';
        $cpe = new Fisma_Cpe($cpeString);
        $this->assertEquals($cpeString, $cpe->cpeName);
    }

    public function testGetNull()
    {
        $cpeString = 'cpe:/h:readhat' . rawurlencode(':/?') . ':3:sp1:ultimate:en';
        $cpe = new Fisma_Cpe($cpeString);
        $this->assertNull($cpe->nullValue);
    }
}
