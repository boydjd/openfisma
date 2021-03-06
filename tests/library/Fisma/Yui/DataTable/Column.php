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

require_once(realpath(dirname(__FILE__) . '/../../../../Case/Unit.php'));

/**
 * Tests for YUI data table columns
 * 
 * @author     Mark E. Haase
 * @copyright  (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Test
 * @subpackage Test_Library
 */
class Test_Library_Fisma_Yui_DataTable_Column extends Test_Case_Unit
{
    /**
     * Test the constructor and accessors for the table column class
     * @return void
     */
    public function testCreateColumn()
    {
        $column1 = new Fisma_Yui_DataTable_Column('Column 1', true);
        
        $this->assertEquals('Column 1', $column1->getLabel());
        $this->assertNotContains(' ', $column1->getName());
        $this->assertTrue($column1->getSortable());
        
        $formatParams = array(
            'width' => 'auto',
            'css' => 'none'
        );
        $column2 = new Fisma_Yui_DataTable_Column('Column 2', false, 'Fisma.DataTable.Bare', $formatParams,
                                                  'Test_Column_2', true, 'script');

        $this->assertEquals('Column 2', $column2->getLabel());
        $this->assertEquals("Fisma.DataTable.Bare", $column2->getFormatter());
        $this->assertFalse($column2->getSortable());
        $this->assertEquals('Test_Column_2', $column2->getName());
        $this->assertEquals($formatParams, $column2->getFormatterParameters());
        $this->assertEquals('script', $column2->getParser());
        $this->assertTrue($column2->getHidden());
    }
}
