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
 * An abstraction for constructing a query based on specific, pre-defined criteria that are applied on a per-field
 * basis.
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
     * A list of valid operators that are allowed in search criteria
     * 
     * This mirrors operators defined in Fisma/Search/Criteria.js
     * 
     * @var array
     */
    static private $_validOperators = array(
        'beginsWith',
        'contains',
        'endsWith',
        'is'
    );

    /**
     * Used for iterator interface
     */
    private $_iteratorIndex;
    
    /**
     * A numerically-indexed array of criteria. 
     * 
     * See addCriterion() for description of the objects in this array.
     * 
     * @var array
     */
    private $_criteria;
    
    /**
     * Add search criteria for a specific field
     * 
     * @param string $fieldName Name of the field that the criteria applies to
     * @param string $operator The name of the operator to be applied (must be in the $_validOperators list)
     * @param string $operand The value that is applied by the operator to the specified field
     */
    public function add($fieldName, $operator, $operand)
    {
        if (in_array($operator, self::$_validOperators)) {
            $newCriterion = new stdClass;
            
            $newCriterion->fieldName = $fieldName;
            $newCriterion->operator = $operator;
            $newCriterion->operand = $operand;
            
            $this->_criteria[] = $newCriterion;
        } else {
            throw new Fisma_Search_Exception("Invalid search criterion operator: " . $operator);
        }
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
