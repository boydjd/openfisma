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
 * Test suite for /library/Fisma/Cli/EcdNotifier.php. Due to the use of Zend_Console_Getopt, this test must be run
 *  without any options for phpunit.
 *
 * @author     Duy K. Bui <duy.bui@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2011 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Test
 * @subpackage Test_Library
 */
class Test_Library_Fisma_Cli_EcdNotifier extends Test_Case_Unit
{
    /**
     * Test the main function
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
        $query->expects($this->any())->method('execute')->will($this->returnValue($findings));

        $notify = $this->getMock('Fisma_Cli_EcdNotifier', array('getQuery', 'notify'));
        $notify->setLog($this->getMock("Zend_Log"));
        $notify->expects($this->any())->method('getQuery')->will($this->returnValue($query));
        $notify->expects($this->exactly(count($findings)))->method('notify');
        Fisma::initialize(Fisma::RUN_MODE_TEST);
        $notify->run();

    }

    /**
     * Test the query to get expiringFindings
     *
     * @return void
     */
    public function testQuery()
    {
        $notify = new Fisma_Cli_EcdNotifier();
        $notify->setLog($this->getMock("Zend_Log"));
        $query = $notify->getQuery()->getDql();
        $conditions = 'WHERE f.status != ? AND f.currentEcd IN (?, ?, ?, ?)';
        $this->assertContains($conditions, $query);
    }
}

