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
class Test_Library_Fisma_Yui_DataTable_Remote extends Test_Case_Unit
{
    /*
     * test render()
     * @return void
     */
    public function testRender()
    {
        $table = new Fisma_Yui_DataTable_Remote();
        $column = new Fisma_Yui_DataTable_Column('Column 1', true, 'Fisma.DataTable.Html', null);

        $table->addColumn($column, true);
        $this->assertEquals(1, count($table->getColumns()));

        $table->setDataUrl('')
              ->setDeferData(false)
              ->setRenderEventFunction('')
              ->setResultVariable('')                                                       
              ->setRowCount(0)
              ->setInitialSortColumn('')
              ->setRequestConstructor('')
              ->setSortAscending(true)
              ->setClickEventBaseUrl('')
              ->setClickEventVariableName('');

        $layout = new RemoteTableMockLayout();
        $this->assertEquals('this is a dummy view', $table->render($layout));

        $this->assertEquals('', $layout->view->data['clickEventBaseUrl']);
        $this->assertEquals('', $layout->view->data['clickEventVariableName']);
        $this->assertEquals('', $layout->view->data['dataUrl']);
        $this->assertFalse($layout->view->data['deferData']);
        $this->assertEquals('', $layout->view->data['initialSortColumn']);
        $this->assertEquals('', $layout->view->data['renderEventFunction']);
        $this->assertEquals('', $layout->view->data['requestConstructor']);
        $this->assertEquals('', $layout->view->data['resultVariable']);
        $this->assertEquals(0, $layout->view->data['rowCount']);
        $this->assertEquals('asc', $layout->view->data['sortDirection']);
        $this->assertEquals(1, count($layout->view->data['columns']));
        $this->assertEquals(1, count($layout->view->data['columnDefinitions']));

        $this->setExpectedException('Fisma_Zend_Exception', Fisma_Yui_DataTable_Remote::LAYOUT_NOT_INSTANTIATED_ERROR);
        $table->__tostring();
    }

    //phpunit returns true for all assertions after setExpectedException
    public function testValidate_1()
    {
        $table = new Fisma_Yui_DataTable_Remote();
        $this->setExpectedException('PHPUnit_Framework_Error', '_dataUrl cannot be null when rendering a remote table.');
        $table->render();
    }
    public function testValidate_2()
    {
        $table = new Fisma_Yui_DataTable_Remote();
        $table->setDataUrl('')
              ->setResultVariable('')
              ->setRowCount(0)
              ->setInitialSortColumn('')
              ->setSortAscending(true);        
        $this->setExpectedException('PHPUnit_Framework_Error', 'Table must contain at least one column.');
        $table->render();

    }
}
class RemoteTableMockLayout
{
    public $view;

    public function __construct()
    {
        $this->view = new RemoteTableMockView();
    }

    public function getView()
    {
        return $this->view;
    }
}
class RemoteTableMockView
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
