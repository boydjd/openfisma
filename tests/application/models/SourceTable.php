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
 * @author Josh Boyd <joshua.boyd@endeavorsystems.com> 
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
        $this->assertTrue(class_exists('SourceTable'));
        try {
            $searchableFields = Doctrine::getTable('Source')->getSearchableFields();
        } catch (Exception $e) {
            $this->markTestSkipped('This test must be run alone due to dynamic class loading problem.');
        }
        

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
        $this->assertTrue(is_array(SourceTable::getAclFields()));
    }
    
    /**
     * Test the query built for getSources()
     *
     * @return void
     */
    public function testGetSourcesQuery()
    {
        $query = SourceTable::getSourcesQuery()->getSql();
        $expectedQuery = 'FROM source s ORDER BY s.nickname';
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
        $mockQuery = $this->getMock('Doctrine_Query', array('execute'));
        $mockQuery->expects($this->once())->method('execute');
        SourceTable::getSources($mockQuery);
    }
}
