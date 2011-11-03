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
 * Test_Application_Models_Network
 * 
 * @uses Test_Case_Unit
 * @package Test 
 * @copyright (c) Endeavor Systems, Inc. 2011 {@link http://www.endeavorsystems.com}
 * @author Josh Boyd <joshua.boyd@endeavorsystems.com> 
 * @license http://www.openfisma.org/content/license GPLv3
 */
class Test_Application_Models_Network extends Test_Case_Unit
{
    /**
     * Test query for preDelete()
     *
     * @return void
     */
    public function testActiveAssetsQuery()
    {
        $network = new Network();
        $query = $network->activeAssetsQuery()->getSql();
        $expectedQuery = 'SELECT a.id AS a__id FROM asset a WHERE a.networkid = ?';
        $this->assertEquals($expectedQuery, $query);
    }
    
    /**
     * Test implementation of ON_DELETE constraint
     *
     * @return void
     */
    public function testPreDelete()
    {
        $mockQuery = $this->getMock('Doctrine_Query', array('count'));
        $mockQuery->expects($this->exactly(2))->method('count')->will($this->onConsecutiveCalls(0, 1));
        
        $network = new Network();
        $network->preDelete(null, $mockQuery);
        
        $this->setExpectedException('Fisma_Zend_Exception_User');
        $network->preDelete(null, $mockQuery);
    }
}
