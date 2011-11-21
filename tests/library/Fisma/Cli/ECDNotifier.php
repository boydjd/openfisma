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
 * Test suite for /library/Fisma/Cli/ECDNotifier.php
 *
 * @author     Duy K. Bui <duy.bui@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2011 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Test
 * @subpackage Test_Library
 */
class Test_Library_Fisma_Cli_ECDNotifier extends Test_Case_Unit
{
    /**
     * test the main function
     * @return void
     */
    public function testRun()
    {
        $finding1 = $this->getMock('Mock_Blank');
        $finding1->currentEcd = Fisma::now();
        $finding2 = $this->getMock('Mock_Blank');
        $finding2->currentEcd = Fisma::now();
        $finding3 = $this->getMock('Mock_Blank');
        $finding3->currentEcd = Fisma::now();
        $finding4 = $this->getMock('Mock_Blank');
        $finding4->currentEcd = Fisma::now();

        $findings = array($finding1, $finding2, $finding3, $finding4);

        $query = $this->getMock('Mock_Blank', array('execute'));
        $query->expects($this->once())->method('execute')->will($this->returnValue($findings));

        $notify = $this->getMock('Fisma_Cli_ECDNotifier', array('getQuery', 'notify'));
        $notify->expects($this->once())->method('getQuery')->will($this->returnValue($query));
        $notify->expects($this->exactly(4))->method('notify');

        $notify->run();

    }

    /**
     * Test the query to get expiringFindings
     * 
     * @return void
     */
    public function testQuery()
    {
        $notify = new Fisma_Cli_ECDNotifier();
        $query = $notify->getQuery()->getDql();
        $conditions = 'WHERE f.status != ? AND f.currentEcd IN (?, ?, ?, ?)';
        $this->assertContains($conditions, $query);
    }
}

