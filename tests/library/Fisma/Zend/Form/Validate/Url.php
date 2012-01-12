<?php
/**
 * Copyright (c) 2012 Endeavor Systems, Inc.
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

require_once(realpath(dirname(__FILE__) . '/../../../../../Case/Unit.php'));

/**
 * Tests for the Fisma_Zend_Form_Validate_Url class
 * 
 * @author     Mark Ma <mark.ma@reyosoft.com>
 * @copyright  (c) Endeavor Systems, Inc. 2012 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Test
 * @subpackage Test_Fisma_Zend_Form_Validate
 */
class Test_Library_Fisma_Zend_Form_Validate_Url extends Test_Case_Unit
{
    /**
     * Test that the isValid() method correctly.
     * @return void
     */
    public function testIsValid()
    {
        $validator = new Fisma_Zend_Form_Validate_Url();
 
        //not required -> true
        $this->assertTrue($validator->isValid(''));
 
        //functional true
        $this->assertTrue($validator->isValid('https://www.example.com'));
        $this->assertTrue($validator->isValid('http://www.example.com:8888'));
 
        //function falses
        $this->assertFalse($validator->isValid('www.example.com'));
    }
}
