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

require_once(realpath(dirname(__FILE__) . '/../FismaUnitTest.php'));

/**
 * Tests for Fisma_String
 * 
 * @author     Mark E. Haase
 * @copyright  (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Test
 * @subpackage Test_Fisma
 * @version    $Id$
 */
class Test_Fisma_String extends Test_FismaUnitTest
{
    /**
     * Test randomness of string generation
     */
    public function testRandomStringRandomness()
    {
        $this->assertNotEquals(Fisma_String::random(10), Fisma_String::random(10));
    }

    /**
     * Test random string only uses default allowed characters
     */   
    public function testRandomStringDefaultAllowedCharacters()
    {
        $this->assertRegExp('([A-Z,a-z,0-9]*)', Fisma_String::random(10));
    }

    /**
     * Test random string only uses allow characters
     */
    public function testRandomStringAllowedCharacters()
    {
        $this->assertEquals(Fisma_String::random(2, 'AA'), 'AA');
    }

    /**
     * Test random string is the requested length
     */
    public function testRandomStringLength()
    {
        $this->assertEquals(strlen(Fisma_String::random(22)), 22);
    }
}
