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
 * This is generic encapsulation of a typical report in OpenFISMA.
 * 
 * Reports typically contain a rectangular (or tabular) data set and a set of column headings, and can be rendered
 * into an HTML, Excel, or PDF context.
 * 
 * TO BE CLEAR: This is not in any way related to the quarterly or annual reports which are sent to OMB.
 * 
 * @author     Mark E. Haase
 * @copyright  (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Fisma
 * @subpackage Fisma_Report
 */
class Fisma_Report
{
    /**
     * The title of this report
     * 
     * @var string
     */
    private $_title;
    
    /**
     * An array of Fisma_Report_Column objects
     * 
     * @var array
     */
    private $_columns = array();
    
    /**
     * Rectangular data for this report
     * 
     * @var array
     */
    private $_data;
    
    /**
     * Title accessor
     */
    public function getTitle()
    {
        return $this->_title;
    }
    
    /**
     * Title mutator
     * 
     * Fluent interface
     * 
     * @param string $title
     */
    public function setTitle($title)
    {
        $this->_title = $title;
        
        return $this;
    }
    
    /**
     * Returns an array of Fisma_Report_Column
     * 
     * @return array
     */
    public function getColumns()
    {
        return $this->_columns;
    }
    
    /**
     * Return an array of just the column names, not column objects
     * 
     * @return array
     */
    public function getColumnNames()
    {
        $names = array();
        
        foreach ($this->_columns as $column) {
            $names[] = $column->getName();
        }
        
        return $names;
    }
    
    /**
     * Add a column to the report
     * 
     * @param Fisma_Report_Column $column
     */
    public function addColumn(Fisma_Report_Column $column)
    {
        $this->_columns[] = $column;
        
        return $this;
    }
    
    /**
     * Get report data as rectangular array
     */
    public function getData()
    {
        return $this->_data;
    }
    
    /**
     * Set report data from rectangular array
     * 
     * @param array $data
     */
    public function setData($data)
    {
        if (is_null($data)) {
            throw new Fisma_Zend_Exception("Report data object is null");
        }
        
        $this->_data = $data;
        
        return $this;
    }
}
