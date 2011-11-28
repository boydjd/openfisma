<?php
/**
 * Copyright (c) 2011 Endeavor Systems, Inc.
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

require_once(realpath(dirname(__FILE__) . '/../../../Case/Unit.php'));

/**
 * test /library/Fisma/Yui/TabView.php
 *
 * @author     Duy K. Bui <duy.bui@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2011 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Test
 * @subpackage Test_Library
 */
class Test_Library_Fisma_Yui_TabView extends Test_Case_Unit
{
    /**
     * test constructors
     *
     * @return void
     */
    public function testConstructor()
    {
        $tabViewId = 'Test_TabView';
        $tabView = new Fisma_Yui_TabView($tabViewId);
        $this->assertEquals($tabViewId, $tabView->getTabViewId());
        $this->assertNotNull($tabView->getObjectId());

        $objectId = 'a34ef3';
        $tabView = new Fisma_Yui_TabView($tabViewId, $objectId);
        $this->assertEquals($objectId, $tabView->getObjectId());

        $this->setExpectedException('Fisma_Zend_Exception', 'TabView ID must be set to non-empty value');
        $tabView = new Fisma_Yui_TabView(null);
    }

    /**
     * test add() / toString() method
     *
     * @return void
     */
    public function testAdd()
    {
        $tabView = new Fisma_Yui_TabView('Test_TabView');
        $name = 'tab1';
        $url = '/tab1';
        $tabView->addTab($name, $url);

        // instead of adding getter for TabView::_tabs, get it natively through MockLayout -> MockView
        $mockLayout = new TabViewMockLayout();
        $tabPage = array(
            'id' => $name,
            'name' => $name,
            'url' => $url,
            'active' => 'false'
        );
        $tabView->render($mockLayout);
        $tabs = $mockLayout->view->tabs;

        $this->assertEquals('TabView_Test_TabView_SelectedTab', $tabs['selectedTabCookie']);
        $this->assertEquals(0, $tabs['objectId']);
        $this->assertEquals('TabView_Test_TabView_ObjectId', $tabs['objectIdCookie']);
        $this->assertEquals(1, count($tabs['tabs']));
        $this->assertEquals($tabPage, $tabs['tabs'][0]);
        $this->assertEquals('TabView_Test_TabView_TabViewContainer', $tabs['tabViewContainer']);
        $this->assertEquals('yui/tab-view.phtml', $mockLayout->view->viewscript);
        $this->assertEquals('default', $mockLayout->view->option);
    }

    /**
     * test the default __tostring()
     *
     * @return void
     */
    public function testToString()
    {
        $tabView = new Fisma_Yui_TabView('Test_TabView');        
        $this->setExpectedException('Fisma_Zend_Exception', $tabView::LAYOUT_NOT_INSTANTIATED_ERROR);
        $tabView->__tostring();
    }
}
/**
 * a mock object mimicking Zend_Layout behaviors
 *
 * the purpose of using a mocklayout that provides a mockview (instead of providing the mockview directly)
 * is to separate the statement ZendLayout::getInstance()->getView();
 * into 2 independently testable $layout = ZendLayout::getInstance() and $view = $layout->getView();
 */
class TabViewMockLayout
{
    public $view;
    public function __construct()
    {
        $this->view = new TabViewMockView();
    }
    public function getView()
    {
        return $this->view;
    }
}
class TabViewMockView
{
    public $tabs;
    public $viewscript;
    public $option;

    public function __construct()
    {
        $this->tabs = array();
    }   

    public function partial($viewscript, $option, $tabs)
    {       
        $this->viewscript = $viewscript;
        $this->option = $option;
        $this->tabs = $tabs;
        return 'this is a dummy view';
    }   
}

