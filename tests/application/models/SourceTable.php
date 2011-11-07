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
 * Test_Application_Models_SourceTable 
 * 
 * @uses Test_Case_Unit
 * @package Test 
 * @copyright (c) Endeavor Systems, Inc. 2011 {@link http://www.endeavorsystems.com}
 * @author Duy K. Bui <duy.bui@endeavorsystems.com>
 * @license http://www.openfisma.org/content/license GPLv3
 */
class Test_Application_Models_SourceTable extends Test_Case_Unit
{
    /**
     * Check if getSearchableFields() returns a not-empty array 
     * 
     * @access public
     * @return void
     */
    public function testGetSearchableFields()
    {
        $searchableFields = Doctrine::getTable('Source')->getSearchableFields();
        $this->assertTrue(is_array($searchableFields));
        $this->assertNotEmpty($searchableFields);
    }

    /**
     * testGetAclFields 
     * 
     * @access public
     * @return void
     */
    public function testGetAclFields()
    {
        $this->assertTrue(is_array(Doctrine::getTable('Source')->getAclFields()));
    }
    
    /**
     * Test the query built for getSources()
     *
     * @return void
     */
    public function testGetSourcesQuery()
    {
        $query = Doctrine::getTable('Source')->getSourcesQuery()->getDql();
        $expectedQuery = 'FROM Source s ORDER BY s.nickname';
        $this->assertContains($expectedQuery, $query);
    }
    
    /**
     * Test the execution of the query from getSourcesQuery()
     *
     * @return void
     * @deprecated pending on the removal of source method
     */
    public function testGetSources()
    {
        $mockQuery = $this->getMock('Mock_Blank', array('execute'));
        $mockQuery->expects($this->once())->method('execute');
        SourceTable::getSources($mockQuery);
    }
}
