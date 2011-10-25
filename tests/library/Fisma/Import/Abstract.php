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
 * test /library/Fisma/Import/Abstract.php
 *
 * @author     Duy K. Bui <duy.bui@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2011 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Test
 * @subpackage Test_Library
 * @require    AbstractDummy
 */
class Test_Library_Fisma_Import_Abstract extends Test_Case_Unit
{
    /*
     * load the Dummy helper
     * @return void
     */
    public function setup()
    {
        require_once(realpath(dirname(__FILE__) . '/AbstractDummy.php'));
    }

    /**
     * test constructor
     * @return void
     */
    public function testConstructor()
    {
        $sampleInitialValues = array(
            'orgSystemId' => '10',
            'networkId' => '20',
            'filePath' => 'data/uploads'
        );
        $importDummy = new Test_Library_Fisma_Import_AbstractDummy($sampleInitialValues);
        $this->assertEquals($sampleInitialValues['orgSystemId'], $importDummy->getOrgSystemId());
        $this->assertEquals($sampleInitialValues['networkId'], $importDummy->getNetworkId());
        $this->assertEquals($sampleInitialValues['filePath'], $importDummy->getFilePath());
    }

    /**
     * test get and set Error()
     * @return void
     */
    public function testError()
    {
        $importDummy = new Test_Library_Fisma_Import_AbstractDummy();
        $sampleErrors = array('error1');
        //setError method is actually and addError method
        $importDummy->setError('error1');
        $this->assertEquals($sampleErrors, $importDummy->getErrors());
    }

    /**
     * test getNumSuppressed()
     * @return void
     */
    public function testGetNumSuppressed()
    {
        $importDummy = new Test_Library_Fisma_Import_AbstractDummy();
        $this->assertEquals(0, $importDummy->getNumSuppressed()); //default value = 0
        $importDummy->parse(); //set _numSuppressed to 1
        $this->assertEquals(1, $importDummy->getNumSuppressed());
    }

    /**
     * test getNumImported()
     * @return void
     */
    public function testGetNumImported()
    {
        $importDummy = new Test_Library_Fisma_Import_AbstractDummy();
        $this->assertEquals(0, $importDummy->getNumImported()); //default value = 0
        $importDummy->parse(); //set _numImported to 1
        $this->assertEquals(1, $importDummy->getNumImported());
    }
}
