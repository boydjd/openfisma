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

require_once(realpath(dirname(__FILE__) . '/../../../FismaUnitTest.php'));

/**
 * Test the generic report column class
 * 
 * @author     Mark E. Haase
 * @copyright  (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Test
 * @subpackage Test_Fisma
 */
class Test_Library_Fisma_Report_Column extends Test_FismaUnitTest
{
    /**
     * This class is very basic
     */
    public function testConstructor()
    {
        $column1 = new Fisma_Report_Column('Name');
        
        $this->assertEquals('Name', $column1->getName());
        $this->assertFalse($column1->isSortable());
        $this->assertNull($column1->getFormatter());
        
        $column2 = new Fisma_Report_Column('Stuff', true, 'FormatFunction');
        
        $this->assertEquals('Stuff', $column2->getName());
        $this->assertTrue($column2->isSortable());
        $this->assertEquals('FormatFunction', $column2->getFormatter());
    }
}
