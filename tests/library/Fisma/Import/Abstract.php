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
 */
class Test_Library_Fisma_Import_Abstract extends Test_Case_Unit
{
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
        $importDummy = new Fisma_Import_Dummy($sampleInitialValues);
        $this->assertEquals($importDummy->_orgSystemId, $sampleInitialValues['orgSystemId']);
        $this->assertEquals($importDummy->_networkId, $sampleInitialValues['networkId']);
        $this->assertEquals($importDummy->_filePath, $sampleInitialValues['filePath']);
    }

    /**
     * test get and set Error()
     */
    public function testError()
    {
        $importDummy = new Fisma_Import_Dummy();
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
        $importDummy = new Fisma_Import_Dummy();
        $this->assertEquals($importDummy->_numSuppressed, $importDummy->getNumSuppressed());
    }

    /**
     * test getNumImported()
     */
    public function testGetNumImported()
    {
        $importDummy = new Fisma_Import_Dummy();
        $this->assertEquals($importDummy->_numImported, $importDummy->getNumImported());
    }
}

/**
 * Dummy class to wrap Fisma_Import_Abstract
 * @author     Duy K. Bui <duy.bui@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2011 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Test
 * @subpackage Test_Library
 */
//the require statement below is a quick-and-dirty workaround for phpunit
require_once(realpath(dirname(__FILE__) . '/../../../../library/Fisma/Import/Abstract.php'));
class Fisma_Import_Dummy extends Fisma_Import_Abstract
{
    //Publicize everything to test
    public $_errors = array();                                                                                      
    public $_orgSystemId;                                                                                           
    public $_networkId;                                                                                             
    public $_filePath;                                                                                              
    public $_numImported = 0;                                                                                       
    public $_numSuppressed = 0;
    //lazy constructor
    public function __construct($values = null)
    {
        if(isset($values)) {
            parent::__construct($values);
        }
        else {
            $this->_orgSystemId = 0;
            $this->_networkId = 0;
            $this->_filePath = '';
        }
    } 
    public function _setError($err)                                                                                 
    {                                                                                                                  
        parent::_setError($err);                                                                                       
    }
    public function parse()
    {
        return true;
    }
}
