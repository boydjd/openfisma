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
 * A lightweight representation of a column in a report
 *
 * @author     Mark E. Haase
 * @copyright  (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Fisma
 * @subpackage Fisma_Report
 */
class Fisma_Report_Column
{
    /**
     * Name of this column
     *
     * @var string
     */
    private $_name;

    /**
     * Whether this column is sortable or not
     *
     * @var bool
     */
    private $_isSortable;

    /**
     * The name of the javascript/YUI formatter function for this column
     *
     * @var string
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
     * Whether this column is hidden or not
     *
     * @var bool
     */
    private $_isHidden;

    /**
     * Constructor
     *
     * @param string $name Name of column
     * @param string $sortable Whether column should be sortable (only applies in some reporting contexts)
     * @param string $formatter Name of a Javascript/YUI formatter function
     * @param mixed $formatterParams A scalar or array of parameters passed to the formatter
     * @param string $hidden Whether column should be hidden
     * @param string $parser The name of a parser to use (mainly applicable to YUI)
     */
    public function __construct($name, 
                                $sortable = false, 
                                $formatter = null, 
                                $formatterParams = null, 
                                $hidden = false, 
                                $parser = 'string')
    {
        $this->_name = $name;
        $this->_isSortable = $sortable;
        $this->_formatter = $formatter;
        $this->_formatterParameters = $formatterParams;
        $this->_isHidden = $hidden;
        $this->_parser = $parser;
    }

    /**
     * Returns true if this column is sortable, false otherwise
     *
     * @return bool
     */
    public function isSortable()
    {
        return $this->_isSortable;
    }

    /**
     * Accessor for $_name
     *
     * @return string
     */
    public function getName()
    {
        return $this->_name;
    }

    /**
     * Accessor for $_formatter
     *
     * @return string
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
     * Returns true if this column is hidden, false otherwise
     *
     * @return bool
     */
    public function isHidden()
    {
        return $this->_isHidden;
    }
}
