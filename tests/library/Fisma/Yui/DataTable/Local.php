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
 * Tests for YUI data table with local data source
 * Employed to test Fisma_Yui_DataTabale_Abstract as well
 * 
 * @author     Mark E. Haase
 * @copyright  (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Test
 * @subpackage Test_Library
 */
class Test_Library_Fisma_Yui_DataTable_Local extends Test_Case_Unit
{
    /**
     * Add columns and get columns from a data table
     * @return void
     */
    public function testAddColumns()
    {
        $table = new Fisma_Yui_DataTable_Local();
        
        // No columns by default
        $this->assertEquals(0, count($table->getColumns()));
            
        // Now add 2 columns
        $table->addColumn(new Fisma_Yui_DataTable_Column('Column 1', true), true)
              ->addColumn(new Fisma_Yui_DataTable_Column('Column 2', true), true);

        $this->assertEquals(2, count($table->getColumns()));
    }

    /**
     * test Fisma_Yui_DataTable_Abstract
     * test Fisma_Yui_DataTable_Local::render() via MockLayout & MockView
     * (using MockView to return data add via Abstract class)
     * @return void
     */
    public function testAbstractAndRender()
    {
        $table = new Fisma_Yui_DataTable_Local();
        $column = new Fisma_Yui_DataTable_Column('Column 1', true, 'Fisma.DataTable.Html', null);
        $table->addColumn($column, true);
        $this->assertEquals(1, count($table->getColumns()));

        $this->assertEquals(Fisma_Yui_DataTable_Local::LAYOUT_NOT_INSTANTIATED_ERROR, $table->__tostring());

        $table->setGroupBy($column);
        $table->addEventListener('mouseover', 'highlightCurrentRows');
        $table->setData('Mock Data');

        $layout = new DataTableMockLayout();
        $this->assertEquals('this is a dummy view', $table->render($layout));
        $this->assertEquals($column, $layout->view->data['groupBy']);
        $this->assertEquals(1, count($layout->view->data['eventListeners']));
        $this->assertEquals('Mock Data', $layout->view->data['data']);
        $this->assertEquals('Fisma.DataTable.Html', $layout->view->data['columnDefinitions'][0]['formatter']);

        $this->assertNull($layout->view->data['columnDefinitions'][0]['formatterParameters']);


    }
}
class DataTableMockLayout
{
    public $view;

    public function __construct()
    {
        $this->view = new DataTableMockView();
    }

    public function getView()
    {
        return $this->view;
    }
}
class DataTableMockView
{
    public $data;
    public $viewscript;
    public $option;

    public function __construct()
    {
        $this->tabs = array();
    }   

    public function partial($viewscript, $option, $data)
    {       
        $this->viewscript = $viewscript;
        $this->option = $option;
        $this->data = $data;
        return 'this is a dummy view';
    }
}
