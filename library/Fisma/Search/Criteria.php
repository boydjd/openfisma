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
 * A collection of Fisma_Search_Criterion objects.
 * 
 * This class supports OpenFISMA's "Advanced Search" mode. It implements the Iterator interface so that criteria can
 * easily be traversed with 'foreach'.
 * 
 * @author     Mark E. Haase <mhaase@endeavorystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2010 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Fisma
 * @subpackage Fisma_Search
 */
class Fisma_Search_Criteria implements Iterator
{
    /**
     * Used for iterator interface
     * 
     * @var int
     */
    private $_iteratorIndex;
    
    /**
     * A numerically-indexed array of Fisma_Search_Criterion. 
     * 
     * @var array
     */
    private $_criteria = array();

    /**
     * Add search criteria for a specific field
     * 
     * @param Fisma_Search_Criterion $criterion
     */
    public function add(Fisma_Search_Criterion $criterion)
    {
        $this->_criteria[] = $criterion;
    }
    
    /**
     * Implement iterator interface
     */
    public function rewind()
    {
        $this->_iteratorIndex = 0;
    }
    
    /**
     * Implement iterator interface
     */
    public function current()
    {
        return $this->_criteria[$this->_iteratorIndex];
    }

    /**
     * Implement iterator interface
     */
    public function key()
    {
        return $this->_iteratorIndex;
    }

    /**
     * Implement iterator interface
     */
    public function next()
    {
        $this->_iteratorIndex++;
    }

    /**
     * Implement iterator interface
     */
    public function valid()
    {
        return array_key_exists($this->_iteratorIndex, $this->_criteria);
    }
}
