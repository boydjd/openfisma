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
 * Class description
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
    public function setup()
    {
        require_once(realpath(dirname(__FILE__) . '/AbstractDummy.php'));
    }

    /**
     * test constructor
     */
    public function testConstructor()
    {
        $sampleInitialValues = array(
            'orgSystemId' => '10',
            'networkId' => '20',
            'filePath' => 'data/uploads'
        );
        $importDummy = new Test_Library_Fisma_Import_AbstractDummy($sampleInitialValues);
        $this->assertEquals($importDummy->_orgSystemId, $sampleInitialValues['orgSystemId']);
        $this->assertEquals($importDummy->_networkId, $sampleInitialValues['networkId']);
        $this->assertEquals($importDummy->_filePath, $sampleInitialValues['filePath']);
    }

    /**
     * test get and set Error()
     */
    public function testError()
    {
        $importDummy = new Test_Library_Fisma_Import_AbstractDummy();
        $this->assertEquals($importDummy->_errors, $importDummy->getErrors());

        $sampleErrors = array('error1');
        $importDummy->_setError('error1');
        $this->assertEquals($sampleErrors, $importDummy->_errors);
    }

    /**
     * test getNumSuppressed()
     */
    public function testGetNumSuppressed()
    {
        $importDummy = new Test_Library_Fisma_Import_AbstractDummy();
        $this->assertEquals($importDummy->_numSuppressed, $importDummy->getNumSuppressed());
    }

    /**
     * test getNumImported()
     */
    public function testGetNumImported()
    {
        $importDummy = new Test_Library_Fisma_Import_AbstractDummy();
        $this->assertEquals($importDummy->_numImported, $importDummy->getNumImported());
    }
}
