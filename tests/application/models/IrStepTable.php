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

require_once(realpath(dirname(__FILE__) . '/../../Case/Unit.php'));

/**
 * Test_Application_Models_IrStepTable
 * 
 * @uses Test_Case_Unit
 * @package Test 
 * @copyright (c) Endeavor Systems, Inc. 2011 {@link http://www.endeavorsystems.com}
 * @author Duy K. Bui <duy.bui@endeavorsystems.com> 
 * @license http://www.openfisma.org/content/license GPLv3
 */
class Test_Application_Models_IrStepTable extends Test_Case_Unit
{
       /**
     * Test the query built for _openGap()
     *
     * @return void
     */
    public function testOpenGapQuery()
    {
        $query = Doctrine::getTable('IrStep')->openGapQuery(1, 1)->getDql();
        $expectedQuery = 'SET irstep.cardinality = irstep.cardinality + 1';
        $this->assertContains($expectedQuery, $query);
    }

    /**
     * Test the query built for _closeGap()
     *
     * @return void
     */
    public function testCloseGapQuery()
    {
        $query = Doctrine::getTable('IrStep')->closeGapQuery(1, 1)->getDql();
        $expectedQuery = 'SET irstep.cardinality = irstep.cardinality - 1';
        $this->assertContains($expectedQuery, $query);
    }
}
