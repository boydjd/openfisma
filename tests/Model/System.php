<?php
/**
 * Copyright (c) 2008 Endeavor Systems, Inc.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 * @author    Woody lee <woody.li@reyosoft.com>
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/mw/index.php?title=License
 * @version   $Id$
 * @package   Test_System
 */

require_once(realpath(dirname(__FILE__) . '/../FismaUnitTest.php'));

/**
 * Unit tests for the System model
 *
 * @package Test_model
 */
class Test_Model_System extends Test_FismaUnitTest
{
    private $_system = null;

    public function setUp()
    {
        $system = new System();
        $system->mapValue('description');
        $system->mapValue('name');
        $system->mapValue('nickname');
        $this->_system = $system;
    }

    /**
     * Test the method of getting security category level
     * 
     * The security category will be the highest level 
     * among the confidentiality, integrity and availability
     * 
     */
    public function testSecurityCategory()
    {
        $system = new System();
        
        $system->confidentiality = System::CIA_MODERATE;
        $system->integrity = System::CIA_MODERATE;
        $system->availability = System::CIA_LOW;
        $this->assertEquals($system->fipsSecurityCategory(), System::CIA_MODERATE);
        
        $system->confidentiality = System::CIA_HIGH;
        $system->integrity = System::CIA_MODERATE;
        $system->availability = System::CIA_LOW;
        $this->assertEquals($system->fipsSecurityCategory(), System::CIA_HIGH);
        
        $system->confidentiality = System::CIA_LOW;
        $system->integrity = System::CIA_LOW;
        $system->availability = System::CIA_LOW;
        $this->assertEquals($system->fipsSecurityCategory(), System::CIA_LOW);
        
        $system->confidentiality = System::CIA_NA;
        $system->integrity = System::CIA_LOW;
        $system->availability = System::CIA_MODERATE;
        $this->assertEquals($system->fipsSecurityCategory(), System::CIA_MODERATE);
        
    }
}
