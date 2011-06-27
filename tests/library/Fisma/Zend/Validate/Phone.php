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
 * Tests for the Fisma_Zend_Validate_Phone class
 * 
 * @author     Andrew Reeves <andrew.reeves@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2010 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Test
 * @subpackage Test_Fisma_Zend_Validate
 */
class Test_Library_Fisma_Zend_Validate_Phone extends Test_FismaUnitTest
{
    /**
     * Test that the isValid() method correctly matches only valid phone numbers.
     */
    public function testIsValid()
    {
        $validator = new Fisma_Zend_Validate_Phone();
        $this->assertTrue($validator->isValid(''));
        $this->assertTrue($validator->isValid('(321) 321-4321'));
        $this->assertFalse($validator->isValid('3213214321'));
        $this->assertFalse($validator->isValid('321-321-4321'));
        $this->assertFalse($validator->isValid('321.321.4321'));
        $this->assertFalse($validator->isValid('(xyz) 123-0987'));
    }
}
