<?php
/**
 * Copyright (c) 2011 Endeavor Systems, Inc.
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

require_once(realpath(dirname(__FILE__) . '/../../../Case/Unit.php'));

/**
 * test /library/Fisma/Search/Criterion.php
 *
 * @author     Duy K. Bui <duy.bui@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2011 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Test
 * @subpackage Test_Library
 */
class Test_Library_Fisma_Search_Criterion extends Test_Case_Unit
{
    /**
     * test constructor and accessors
     * @return void
     */
    public function testConstructorAndAccessors()
    {
        $field = 'id';
        $operands = array(5, 10);
        $operator = 'integerBetween';
        $criterion = new Fisma_Search_Criterion($field, $operator, $operands);
        $this->assertEquals($field, $criterion->getField());
        $this->assertEquals($operator, $criterion->getOperator());
        $this->assertEquals($operands, $criterion->getOperands());
    }

    /**
     * test exception when attempting to set unsupported operator
     * @return void
     */
    public function testUnsupportedOperator()
    {
        $this->setExpectedException('Fisma_Search_Exception', 'Invalid search criterion operator: ');
        $criterion = new Fisma_Search_Criterion('id', 'isASolutionToBinaryEquation', array(1, 3, -4));
    }
}

