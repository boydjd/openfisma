<?php
/**
 * Copyright (c) 2011 Endeavor Incidents, Inc.
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
 * Test_Application_Models_IncidentTable
 * 
 * @uses Test_Case_Unit
 * @package Test 
 * @copyright (c) Endeavor Incidents, Inc. 2011 {@link http://www.endeavorsystems.com}
 * @author Duy K. Bui <duy.bui@endeavorsystems.com>
 * @license http://www.openfisma.org/content/license GPLv3
 */
class Test_Application_Models_IncidentTable extends Test_Case_Unit
{
    /*
     * Check if getSearchableFields() returns a not-empty array
     *
     * @access public
     * @return void
     */
    public function testGetSearchableFields()
    {
        $searchableFields = Doctrine::getTable('Incident')->getSearchableFields();
        $this->assertTrue(is_array($searchableFields));
        $this->assertNotEmpty($searchableFields);
    }
    
    /**
     * Test the query with and without permission constraint
     *
     * @return void
     */
    public function testGetUserIncidentQuery()
    {
        $user = new User();
        
        $acl = new Fisma_Zend_Acl('root');
        $elevatedQuery = IncidentTable::getUserIncidentQuery($user, $acl)->getDql();
        $this->assertContains('FROM Incident i', $elevatedQuery);
        
        $acl = new Fisma_Zend_Acl('mple');
        $limitedQuery = IncidentTable::getUserIncidentQuery($user, $acl)->getDql();
        $this->assertContains('LEFT JOIN', $limitedQuery);
    }
    
    /**
     * Test getAclFields() with different users
     *
     * @return void
     */
    public function testAclFields()
    {
        $user = $this->getMock('Mock_Blank', array('acl'));
        $user->expects($this->exactly(2))->method('acl')
             ->will($this->onConsecutiveCalls(
                 new Fisma_Zend_Acl('sample'),
                 new Fisma_Zend_Acl('user_root')
             ));
        CurrentUser::setInstance($user);        
        
        $incidentTable = Doctrine::getTable('Incident');
        $limitedAclFields = $incidentTable->getAclFields();
        $this->assertGreaterThanOrEqual(1, count($limitedAclFields));
        
        $elevatedAclFields = $incidentTable->getAclFields();
        $this->assertEquals(0, count($elevatedAclFields));
        CurrentUser::setInstance(null);
    }
    
    /**
     * Test the query built for getIncidentIds
     *
     * @return void
     */
    public function testGetIncidentIdsQuery()
    {
        $user = new User();
        $query = Doctrine::getTable('Incident')->getIncidentIdsQuery()->getDql();
        $expectedQuery = 'FROM IrIncidentUser INDEXBY incidentId WHERE userId = ?';
        $this->assertContains($expectedQuery, $query);
    }
    
    /**
     * Test the execution in getIncidentIds
     *
     * @return void
     * @deprecated pending on the removal of source method
     */
    public function testGetIncidentIds()
    {
        $mockQuery = $this->getMock('Mock_Blank', array('execute'));
        $mockQuery->expects($this->once())->method('execute')
                  ->will($this->returnValue(array(
                      '13' => 'Incident 13 Resultset',
                      '27' => 'Incident 27 Resultset'
                  )));
        $incidentIds = IncidentTable::getIncidentIds($mockQuery);
        $this->assertEquals(array('13', '27'), $incidentIds);
    }
}
