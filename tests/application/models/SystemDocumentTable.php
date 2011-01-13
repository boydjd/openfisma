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

require_once(realpath(dirname(__FILE__) . '/../../FismaUnitTest.php'));

/**
 * Test_Application_Models_SystemDocumentTable 
 * 
 * @uses Test_FismaUnitTest
 * @package Test_ 
 * @copyright (c) Endeavor Systems, Inc. 2011 {@link http://www.endeavorsystems.com}
 * @author Josh Boyd <joshua.boyd@endeavorsystems.com> 
 * @license http://www.openfisma.org/content/license GPLv3
 */
class Test_Application_Models_SystemDocumentTable extends Test_FismaUnitTest
{
    /**
     * testGetSearchableFields 
     * 
     * @access public
     * @return void
     */
    public function testGetSearchableFields()
    {
        $searchableFields = SystemDocumentTable::getSearchableFields();

        $this->assertTrue(is_array($searchableFields));
        $this->assertEquals(8, count($searchableFields));
    }

    /**
     * testGetAclFields 
     * 
     * @access public
     * @return void
     */
    public function testGetAclFields()
    {
        $this->assertTrue(is_array(SystemDocumentTable::getAclFields()));
    }
}
