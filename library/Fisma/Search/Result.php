<?php
/**
 * Copyright (c) 2010 Endeavor Systems, Inc.
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
 * Represents a search result
 * 
 * @author     Mark E. Haase <mhaase@endeavorystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2010 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Fisma
 * @subpackage Fisma_Search
 */
class Fisma_Search_Result
{
    /**
     * The total number of documents found in the index
     * 
     * @var int
     */
    private $_numberFound;

    /**
     * The number of documents returned in this result (less than or equal to $_numberFound)
     * 
     * @var int
     */
    private $_numberReturned;
    
    /**
     * Rectangular array of search results
     * 
     * Each row represents a document, and each row contains keys that correspond to fields in that row
     * 
     * @var string
     */
    private $_tableData;
    
    /**
     * Create a new search result
     * 
     * @param int $numberFound
     * @param int $numberReturned
     * @param array $tableData
     */
    public function __construct($numberFound, $numberReturned, $tableData)
    {
        $this->_numberFound = $numberFound;
        $this->_numberReturned = $numberReturned;
        $this->_tableData = $tableData;
    }
    
    /**
     * Accessor for $_numberFound
     * 
     * @return int
     */
    public function getNumberFound()
    {
        return $this->_numberFound;
    }

    /**
     * Accessor for $_numberReturned
     * 
     * @return int
     */
    public function getNumberReturned()
    {
        return $this->_numberReturned;
    }

    /**
     * Accessor for $_tableData
     * 
     * @return string
     */
    public function getTableData()
    {
        return $this->_tableData;
    }
}
