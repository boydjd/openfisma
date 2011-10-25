<?php
/**
 * Copyright (c) 2008 Endeavor Systems, Inc.
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

/**
 ** Dummy class to wrap Fisma_Import_Abstract
 ** @author     Duy K. Bui <duy.bui@endeavorsystems.com>
 ** @copyright  (c) Endeavor Systems, Inc. 2011 {@link http://www.endeavorsystems.com}
 ** @license    http://www.openfisma.org/content/license GPLv3
 ** @package    Test
 ** @subpackage Test_Library
 **/

class Test_Library_Fisma_Import_AbstractDummy extends Fisma_Import_Abstract
{
    /**
     * lazy constructor
     * @return void
     */
    public function __construct($values = null)
    {
        if(isset($values)) {
            parent::__construct($values);
        } else {
            $this->_orgSystemId = 0;
            $this->_networkId = 0;
            $this->_filePath = '';
        }
    }
    /*
     * public access to parent's protected method
     * @param array err
     * @return void
     */
    public function setError($err)
    {
        parent::_setError($err);
    }
    /*
     * simple implementation of parent's abstract method
     * used for testing getNumImported & getNumSuppressed
     * @return void
     */
    public function parse()
    {
        $this->_numImported = 1;
        $this->_numSuppressed = 1;       
    }
    /*
     * public accessors to parent's protected member
     * @return String
     */
    public function getOrgSystemId()
    {
        return $this->_orgSystemId;
    }
    /*
     * public accessors to parent's protected member
     * @return String
     */
    public function getNetworkId()
    {
        return $this->_networkId;
    }
    /*
     * public accessors to parent's protected member
     * @return String
     */
    public function getFilePath()
    {
        return $this->_filePath;
    }

}
