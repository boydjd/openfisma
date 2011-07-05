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

require_once(realpath(dirname(__FILE__) . '/../../../../FismaUnitTest.php'));

/**
 * Tests for the Fisma_Doctrine_Validator_Phone class
 * 
 * @author     Andrew Reeves <andrew.reeves@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2010 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Test
 * @subpackage Test_Fisma_Doctrine_Validator
 */
class Test_Library_Fisma_Doctrine_Validator_Phone extends Test_FismaUnitTest
{
    /**
     * Test that the validate() method correctly matches only valid phone numbers.
     */
    public function testValidate()
    {
        $validator = new Fisma_Doctrine_Validator_Phone();
        $this->assertTrue($validator->validate(''));
        $this->assertTrue($validator->validate('(321) 321-4321'));
        $this->assertFalse($validator->validate('3213214321'));
        $this->assertFalse($validator->validate('321-321-4321'));
        $this->assertFalse($validator->validate('321.321.4321'));
        $this->assertFalse($validator->validate('(xyz) 123-0987'));
    }
}
