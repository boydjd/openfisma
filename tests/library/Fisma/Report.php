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
 * Tests for the generic reporting abstraction class
 * 
 * @author     Mark E. Haase
 * @copyright  (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Test
 * @subpackage Test_Fisma
 */
class Test_Library_Fisma_Report extends Test_FismaUnitTest
{
    /**
     * Test report title
     */
    public function testReportTitle()
    {
        $report = new Fisma_Report();
        
        $report->setTitle('The Colbert Report');
        
        $this->assertEquals('The Colbert Report', $report->getTitle());
    }
    
    /**
     * Test report columns
     */
    public function testReportColumns()
    {
        $report = new Fisma_Report();
        
        // No columns by default
        $this->assertEquals(0, count($report->getColumns()));
        
        // Add two columns
        $report->addColumn(new Fisma_Report_Column('Column 1'));
        $report->addColumn(new Fisma_Report_Column('Column 2'));
        
        // Report now has two columns
        $this->assertEquals(2, count($report->getColumns()));
        
        // Should be able to get column names an an array
        $expectedNames = array('Column 1', 'Column 2');
        $this->assertEquals($expectedNames, $report->getColumnNames());
    }
    
    /**
     * Test report data
     */
    public function testReportData()
    {
        $report = new Fisma_Report();
        
        $data = array(
            array(1, 2, 3),
            array(4, 5, 6)
        );
        
        $report->setData($data);
        
        $this->assertEquals($data, $report->getData());
    }

    /**
     * testNullReportData 
     * 
     * @expectedException Fisma_Zend_Exception
     */
    public function testNullReportData()
    {
        $report = new Fisma_Report();
        $report->setData(null);
    }
}
