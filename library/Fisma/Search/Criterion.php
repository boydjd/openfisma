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
 * A single piece of criteria used in an advanced search
 *
 * @author     Mark E. Haase <mhaase@endeavorystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2010 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Fisma
 * @subpackage Fisma_Search
 */
class Fisma_Search_Criterion
{
    /**
     * A list of valid operators that are allowed in search criteria
     *
     * This mirrors operators defined in Fisma/Search/CriteriaDefinition.js
     *
     * @var array
     */
    static private $_validOperators = array(
        'booleanYes',
        'booleanNo',
        'dateAfter',
        'dateBefore',
        'dateBetween',
        'dateDay',
        'dateThisMonth',
        'dateThisYear',
        'dateToday',
        'enumIs',
        'enumIsNot',
        'enumIn',
        'enumNotIn',
        'floatBetween',
        'floatGreaterThan',
        'floatLessThan',
        'integerBetween',
        'integerDoesNotEqual',
        'integerEquals',
        'integerGreaterThan',
        'integerLessThan',
        'organizationSubtree',
        'organizationChildren',
        'systemAggregationSubtree',
        'textContains',
        'textDoesNotContain',
        'textExactMatch',
        'textNotExactMatch',
        'unspecified'
    );

    /**
     * The field which this criterion operates on
     *
     * @var string
     */
    private $_field;

    /**
     * The name of the operator which this criterion uses
     *
     * @var string
     */
    private $_operator;

    /**
     * The operands (arguments) to the operator for this criterion
     *
     * @var array Array of mixed (strings and ints, typically)
     */
    private $_operands;

    /**
     * Constructor
     *
     * @param string $field
     * @param string $operator
     * @param array $operands
     * @throws  Fisma_Search_Exception
     */
    public function __construct($field, $operator, $operands)
    {
        $this->setField($field);
        $this->setOperator($operator);
        $this->setOperands($operands);
    }

    /**
     * Accessor for $_field
     *
     * @return string
     */
    public function getField()
    {
        return $this->_field;
    }

    /**
     * Accessor for $_operands
     *
     * @return array Array of mixed (strings and ints, typically)
     */
    public function getOperands()
    {
        return $this->_operands;
    }

    /**
     * Accessor for $_operator
     *
     * @return string
     */
    public function getOperator()
    {
        return $this->_operator;
    }

    /**
     * Mutator for $_field
     *
     * @param string $field
     */
    public function setField($field)
    {
        $this->_field = $field;
    }

    /**
     * Mutator for $_operands
     *
     * @param array Array of mixed (strings and ints, typically) $operands
     */
    public function setOperands($operands)
    {
        $this->_operands = $operands;
    }

    /**
     * Mutator for $_operator
     *
     * @param string $operator
     * @throws  Fisma_Search_Exception
     */
    public function setOperator($operator)
    {
        if (!in_array($operator, self::$_validOperators)) {
            throw new Fisma_Search_Exception("Invalid search criterion operator: $operator");
        }

        $this->_operator = $operator;
    }
}
