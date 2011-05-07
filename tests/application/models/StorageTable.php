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
 * Test_Application_Models_StorageTable
 * 
 * @uses Test_FismaUnitTest
 * @package Test 
 * @copyright (c) Endeavor Systems, Inc. 2011 {@link http://www.endeavorsystems.com}
 * @author Andrew Reeves <andrew.reeves@endeavorsystems.com>
 * @license http://www.openfisma.org/content/license GPLv3
 */
class Test_Application_Models_StorageTable extends Test_FismaUnitTest
{
    /**
     * testClassExists 
     * 
     * @access public
     * @return void
     */
    public function testClassExists()
    {
        $this->assertTrue(class_exists('StorageTable'));
    }

    /**
     * testGetUserIdAndNamespaceQuery
     *
     * @access public
     * @return void
     */
     public function testGetUserIdAndNamespaceQuery()
     {
         $table = Doctrine::getTable('storage');
         $userId = 42;
         $namespace = 'Sample.Namespace';
         $q = $table->getUserIdAndNamespaceQuery($userId, $namespace);
         $this->assertTrue($q instanceof Doctrine_Query);
         $this->assertEquals(' FROM storage WHERE userId = ? AND namespace = ?', $q->getDql());
         $this->assertEquals(array($userId, $namespace), $q->getFlattenedParams());
     }
}
