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
 * test suite for /library/Fisma/OrganizationTypeTable.php
 *
 * @author     Duy K. Bui <duy.bui@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2011 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Test
 * @subpackage Test_Library
 */
class Test_Application_Models_OrganizationTypeTable extends Test_Case_Unit
{
    /**
     * Test getSearchableFields()
     * @return void
     */
    public function testGetSearchableFields()
    {
        $searchableFields = OrganizationTypeTable::getSearchableFields();
        $this->assertTrue(is_array($searchableFields));
        $this->assertEquals(5, count($searchableFields));
    }
    
    /**
     * Test if getAclFields() returns the expected type
     *
     * @return void
     */
    public function testGetAclFields()
    {
        $this->assertTrue(is_array(OrganizationTypeTable::getAclFields()));
    }
    
    /**
     * Test the query modification in getSearchIndexQuery()
     *
     * @return void
     */
    public function testGetSearchIndexQuery()
    {
        $relationAliases = array('OrganizationType' => 'ot');
        $baseQuery = $this->getMock('Doctrine_Query');
        $baseQuery->expects($this->once())->method('where')->with('ot.nickname <> ?', 'system');
        OrganizationTypeTable::getSearchIndexQuery($baseQuery, $relationAliases);
    }
    
    /**
     * Test getOrganizationTypeArray()
     * 
     * @return void
     * @todo check database
     */    
    public function testGetOrganizationTypeArray()
    {
        //$orgTypeTable = Doctrine::getTable('OrganizationType');
        //$orgTypeTable->getOrganizationTypeArray(false);
    }
}

