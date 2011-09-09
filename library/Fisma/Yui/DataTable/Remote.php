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

/**
 * A PHP wrapper for a YUI DataTable which has a remote data source (i.e. a URL which is fetched by XHR)
 * 
 * The purpose of this class is to provide a consistent way of using the YUI table, so this class automatically
 * provides functionality such as paging, dynamic data requests, row highlighting, and column sorting. (These
 * functions are mandatory and cannot be turned off. For simpler tables, there is a "local" version of this class.)
 * 
 * Optional functionality includes: row click events
 * 
 * @author     Mark E. Haase
 * @copyright  (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Fisma
 * @subpackage Fisma_Yui
 */
class Fisma_Yui_DataTable_Remote extends Fisma_Yui_DataTable_Abstract
{
    /**
     * The base URL from which this table fetches its data
     * 
     * @var string
     */
    protected $_dataUrl;
    
    /**
     * The name of the JSON variable that contains the table data sent in the response
     * 
     * @var string
     */
    protected $_resultVariable;

    /**
     * The maximum number of rows to display at a single time.
     * 
     * @var int
     */
    protected $_rowCount;

    /**
     * The column which is initially sorted in this table
     * 
     * This column is determined by the initial query's sort order, i.e. the ORDER BY clause. The column name specified
     * must match the column name return in the hydrated result set.
     * 
     * @var string
     */
    protected $_initialSortColumn;
    
    /**
     * Set to true if this column is sorted ascending, false if sorted descending, or null if no sort applied
     * 
     * @var bool
     */
    protected $_sortAscending;
    
    /**
     * This URL is the base URL when the user clicks a row and is redirected to another URL.
     * 
     * @var string
     */
    protected $_clickEventBaseUrl;

    /**
     * The value of this variable in the clicked row is appended to the URL which the user is redirected to.
     * 
     * @var string
     */
    protected $_clickEventVariableName;

    /**
     * A javascript function that will handle click events. 
     * 
     * @var string
     */
    protected $_clickEventHandler;

    /**
     * The name of a Javascript function which can build POST requests for this data table's data source
     * 
     * @var string
     */
    protected $_requestConstructor;
    
    /**
     * A function which is called by YUI after the table is rendered (after data updates, for example)
     * 
     * @var string
     */
    protected $_renderEventFunction;
    
    /**
     * If true, the table will not automatically fetch data and data must be fetched programmatically. If false, the
     * table will automatically make an initial data request to the $_dataUrl.
     * 
     * @var bool
     */
    protected $_deferData = false;    

    /**
     * If set, the data table reference will be assigned to a global variable matching this name.
     * 
     * @var string
     */
    protected $_globalVariableName = null;
    
    /**
     * Render the datatable with HTML and/or Javascript
     * 
     * @return string
     */
    public function render()
    {
        $this->_validate();
        
        $view = Zend_Layout::getMvcInstance()->getView();

        return $view->partial('yui/data-table-remote.phtml', 'default', $this->getProperties());
    }

    /**
     * Retrieve an array of table properties used in rendering.
     *
     * @return array Rendering properties
     */
    public function getProperties()
    {
        $uniqueId = uniqid();
               
        $data = array(
            'clickEventBaseUrl' => $this->_clickEventBaseUrl,
            'clickEventHandler' => $this->_clickEventHandler,
            'clickEventVariableName' => $this->_clickEventVariableName,
            'columns' => $this->getColumns(),
            'columnDefinitions' => $this->_getYuiColumnDefinitions(),
            'containerId' => $uniqueId . "_container",
            'dataUrl' => $this->_dataUrl,
            'deferData' => $this->_deferData,
            'initialSortColumn' => $this->_initialSortColumn,
            'globalVariableName' => $this->_globalVariableName,
            'renderEventFunction' => $this->_renderEventFunction,
            'requestConstructor' => $this->_requestConstructor,
            'resultVariable' => $this->_resultVariable,
            'rowCount' => $this->_rowCount,
            'sortDirection' => ($this->_sortAscending ? 'asc' : 'desc')
        );

        return $data;
    }

    /**
     * Validate that all of the required parameters for the table object have been set.
     * 
     * Because this is called in render, it can't throw an exception, so it triggers a user error instead.
     */
    protected function _validate()
    {
        $requiredFields = array('_dataUrl', '_resultVariable', '_rowCount', '_initialSortColumn', '_sortAscending');
        
        foreach ($requiredFields as $requiredField) {
            if (is_null($this->$requiredField)) {
                trigger_error("$requiredField cannot be null when rendering a remote table.", E_USER_ERROR);
            }
        }
        
        if (count($this->getColumns()) == 0) {
            trigger_error("Table must contain at least one column.", E_USER_ERROR);
        }
    }
    
    /**
     * Mutator for $_dataUrl
     * 
     * Fluent interface
     * 
     * @param string $dataUrl
     */
    public function setDataUrl($dataUrl)
    {
        $this->_dataUrl = $dataUrl;

        return $this;
    }

    /**
     * Mutator for $_deferData
     * 
     * Fluent interface
     * 
     * @param bool $deferData
     */
    public function setDeferData($deferData)
    {
        $this->_deferData = $deferData;

        return $this;
    }

    /**
     * Mutator for $_renderEventFunction
     * 
     * Fluent interface
     * 
     * @param string $renderEventFunction
     */
    public function setRenderEventFunction($renderEventFunction)
    {
        $this->_renderEventFunction = $renderEventFunction;
        
        return $this;
    }

    /**
     * Mutator for $_resultVariable
     * 
     * Fluent interface
     * 
     * @param string $resultVariable
     */
    public function setResultVariable($resultVariable)
    {
        $this->_resultVariable = $resultVariable;
        
        return $this;
    }

    /**
     * Mutator for $_rowCount
     * 
     * Fluent interface
     * 
     * @param int $rowCount
     */
    public function setRowCount($rowCount)
    {
        $this->_rowCount = $rowCount;
        
        return $this;
    }
    
    /**
     * Mutator for $_initialSortColumn
     * 
     * Fluent interface
     * 
     * @param string $initialSortColumn
     */
    public function setInitialSortColumn($initialSortColumn)
    {
        $this->_initialSortColumn = $initialSortColumn;
        
        return $this;
    }

    /**
     * Mutator for $_requestConstructor
     * 
     * Fluent interface
     * 
     * @param string $requestConstructor
     */
    public function setRequestConstructor($requestConstructor)
    {
        $this->_requestConstructor = $requestConstructor;
        
        return $this;
    }

    /**
     * Mutator for $_sortAscending
     * 
     * Fluent interface
     * 
     * @param bool $sortAscending
     */
    public function setSortAscending($sortAscending)
    {
        $this->_sortAscending = $sortAscending;
        
        return $this;
    }    

    /**
     * Mutator for $_clickEventBaseUrl
     * 
     * Fluent interface
     * 
     * @param string $clickEventBaseUrl
     */
    public function setClickEventBaseUrl($clickEventBaseUrl)
    {
        $this->_clickEventBaseUrl = $clickEventBaseUrl;
        
        return $this;
    }

    /**
     * Mutator for $_clickEventVariableName
     * 
     * Fluent interface
     * 
     * @param string $clickEventVariableName
     */
    public function setClickEventVariableName($clickEventVariableName)
    {
        $this->_clickEventVariableName = $clickEventVariableName;
        
        return $this;
    }

    /**
     * Mutator for $_globalVariableName
     * 
     * Fluent interface
     * 
     * @param string $globalVariableName
     */
    public function setGlobalVariableName($globalVariableName)
    {
        if (empty($globalVariableName)) {
            throw new Fisma_Zend_Exception("Global variable name cannot be null or blank.");
        }

        $this->_globalVariableName = $globalVariableName;
        
        return $this;
    }
    
    /**
     * Mutator for $_clickEventHandler
     * 
     * Fluent interface
     * 
     * @param string $clickEventHandler
     */
    public function setClickEventHandler($clickEventHandler)
    {
        $this->_clickEventHandler = $clickEventHandler;
        
        return $this;
    }
}
