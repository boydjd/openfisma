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
 * Test_Application_Models_SecurityControlCatalogTable
 * 
 * @uses Test_Case_Unit
 * @package Test 
 * @copyright (c) Endeavor Systems, Inc. 2011 {@link http://www.endeavorsystems.com}
 * @author Josh Boyd <joshua.boyd@endeavorsystems.com> 
 * @license http://www.openfisma.org/content/license GPLv3
 */
class Test_Application_Models_SecurityControlCatalogTable extends Test_Case_Unit
{
    /**
     * test the query built by getCatalogsQuery()
     * 
     * @return void
     */
    public function testGetCatalogsQuery()
    {
        $expectedQuery = 'SELECT s.id AS s__0, s.name AS s__1 FROM security_control_catalog s ORDER BY s.name';
        $query = SecurityControlCatalogTable::getCatalogsQuery();
        $this->assertEquals($expectedQuery, $query->getSql());
    }

    /**
     * test the execution of getCatalogs()
     * 
     * @return void
     * @deprecated pending the removal of source method
     */
    public function testGetCatalogs()
    {
        $mockQuery = $this->getMock('Doctrine_Query', array('execute'));
        $mockQuery->expects($this->once())
                  ->method('execute');
        SecurityControlCatalogTable::getCatalogs($mockQuery);
    }
}

