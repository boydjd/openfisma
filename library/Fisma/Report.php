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
 * @author     Mark E. Haase
 * @copyright  (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    PACKAGE
 * @subpackage SUBPACKAGE
 * @version    $Id$
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
     * The column names for this report
     */
    private $_columnNames;
    
    /**
     * Rectangular data for this report
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
     * Columns accessor
     */
    public function getColumns()
    {
        return $this->_columns;
    }
    
    /**
     * Columns mutator
     * 
     * @param array $columns
     */
    public function setColumns($columns)
    {
        $this->_columns = $columns;
        
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
        $this->_data = $data;
        
        return $this;
    }
}
