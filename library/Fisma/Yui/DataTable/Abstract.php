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
 * An abstract class that provides a basic wrapper for a YUI data table
 * 
 * @author     Mark E. Haase
 * @copyright  (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Fisma
 * @subpackage Fisma_Yui
 */
abstract class Fisma_Yui_DataTable_Abstract
{
    /**
     * An array of Fisma_Yui_DataTable_Column
     * 
     * @var array
     */
    private $_columns;
    
    /**
     * Abstract method for rendering the table
     * 
     * @return string
     */
    abstract public function render();

    /**
     * Add a column to this table
     * 
     * Fluent interface
     * 
     * @param Fisma_Yui_DataTable_Column $column
     */
    public function addColumn(Fisma_Yui_DataTable_Column $column)
    {
        $this->_columns[] = $column;
        
        return $this;
    }

    /**
     * Get array of table columns
     * 
     * @return array Array of Fisma_Yui_DataTable_Column
     */
    public function getColumns()
    {
        return $this->_columns;
    }

    /**
     * Construct a YUI representation of the column definitions
     */
    protected function _getYuiColumnDefinitions()
    {
        $columnDefinitions = array();
        
        foreach ($this->getColumns() as $column) {
                        
            // These keys are always defined
            $columnDefinition = array(
                'key' => $column->getName(),
                'label' => $column->getLabel(),
                'sortable' => $column->getSortable(),
                'hidden' => $column->getHidden()
            );

            // Add the formatter key only if the column has a formatter
            $formatter = $column->getFormatter();
            if ($formatter) {
                $columnDefinition['formatter'] = $column->getFormatter();
                $columnDefinition['formatterParameters'] = $column->getFormatterParameters();
            }

            $columnDefinitions[] = $columnDefinition;
        }
                
        return $columnDefinitions;
    }

    /**
     * Implement magic function by calling render()
     */
    public function __toString()
    {
        return $this->render();
    }
}
