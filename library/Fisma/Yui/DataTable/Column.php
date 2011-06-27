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
 * A wrapper for YUI's data table column
 * 
 * @author     Mark E. Haase
 * @copyright  (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Fisma
 * @subpackage Fisma_Yui
 */
class Fisma_Yui_DataTable_Column
{
    /**
     * The logical (human-friendly) name for this column. It is displayed in the table header.
     * 
     * Also, if _name is not specified, then _label will be used to automatically generate a _name value.
     * 
     * @var string
     */
    private $_label;
    
    /**
     * Whether this column is sortable or not
     * 
     * @var bool
     */
    private $_sortable;
    
    /**
     * A formatter for this column
     * 
     * @var Fisma_Yui_DataTable_ColumnFormatter
     */
    private $_formatter;

    /**
     * An array of parameters passed to the formatter function
     *
     * @var array
     */
    private $_formatterParameters;

    /**
     * A parser for this column
     * 
     * @var string
     */
    private $_parser;

    /**
     * A javascript-friendly name for this column. If not specified, this is automatically derived from the _label.
     * 
     * @var string
     */
    private $_name;

    /**
     * Whether this column is hidden or not
     * 
     * @var bool
     */
    private $_hidden;

    /**
     * Create a column with a human-friendly name
     * 
     * @param string $label A human-friendly label for this column
     * @param bool $sortable
     * @param Fisma_Yui_DataTable_ColumnFormatter $formatter
     * @param mixed $formatterParams A scalar or array of parameters passed to the formatter
     * @param string $name A javascript-friendly name. If not specified, then it is derived from the label.
     * @param bool $hidden Whether column should be hidden
     * @param string $parser The name of a javascript parser function to use on this column.
     */
    public function __construct($label, 
                                $sortable, 
                                $formatter = null, 
                                $formatterParams = null, 
                                $name = null, 
                                $hidden = false,
                                $parser = 'string')
    {
        $this->_label = $label;
        $this->_sortable = $sortable;
        $this->_formatter = $formatter;
        $this->_formatterParameters = $formatterParams;
        $this->_hidden = $hidden;
        $this->_parser = $parser;
        
        if (is_null($name)) {
            $this->_name = Fisma_String::convertToJavascriptName($label);
        } else {
            $this->_name = $name;
        }
    }
    
    /**
     * Getter for label
     * 
     * @return string
     */
    public function getLabel()
    {
        return $this->_label;
    }

    /**
     * True if this column is sortable, false otherwise
     */
    public function getSortable()
    {
        return $this->_sortable;
    }
    
    /**
     * Accessor for $_formatter
     * 
     * @return Fisma_Yui_DataTable_ColumnFormatter
     */
    public function getFormatter()
    {
        return $this->_formatter;
    }

    /**
     * Accessor for $_formatterParameters
     *
     * @return string
     */
    public function getFormatterParameters()
    {
        return $this->_formatterParameters;
    }

    /**
     * Accessor for $_parser
     * 
     * @return string
     */
    public function getParser()
    {
        return $this->_parser;
    }

    /**
     * Accessor for name
     * 
     * @return string
     */
    public function getName()
    {
        return $this->_name;
    }

    /**
     * True if this column is hidden, false otherwise
     * 
     * @return bool
     */
    public function getHidden()
    {
        return $this->_hidden;
    }
}
