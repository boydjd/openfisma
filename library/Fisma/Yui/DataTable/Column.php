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
 * @version    $Id$
 */
class Fisma_Yui_DataTable_Column
{
    /**
     * The logical (human-friendly) name for this column. It is displayed in the table header.
     * 
     * @var string
     */
    private $_name;
    
    /**
     * Whether this column is sortable or not
     * 
     * @var bool
     */
    private $_sortable;
    
    /**
     * Create a column with a human-friendly name
     * 
     * @param string $name
     * @param bool $sortable
     */
    public function __construct($name, $sortable)
    {
        $this->_name = $name;
        $this->_sortable = $sortable;
    }
    
    /**
     * Getter for logical name
     * 
     * @return string
     */
    public function getName()
    {
        return $this->_name;
    }

    /**
     * True if this column is sortable, false otherwise
     */
    public function getSortable()
    {
        return $this->_sortable;
    }
}
