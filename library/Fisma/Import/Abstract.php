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
 * Abstract definition for Fisma_Import 
 * 
 * @abstract
 * @package Fisma
 * @subpackage Import
 * @version $Id$
 * @copyright (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @author Josh Boyd <joshua.boyd@endeavorsystems.com> 
 * @license http://www.openfisma.org/content/license GPLv3
 */
abstract class Fisma_Import_Abstract
{
    protected $_errors = array();
    protected $_orgSystemId;
    protected $_networkId;
    protected $_filePath;
    protected $_numImported = 0;
    protected $_numSuppressed = 0;

    /**
     * Constructor
     * 
     * @param array $values 
     * @return void
     */
    public function __construct($values)
    {
        $this->_orgSystemId = $values['orgSystemId'];
        $this->_networkId = $values['networkId'];
        $this->_filePath = $values['filePath'];
    }

    /**
     * Return array of errors. 
     * 
     * @access public
     * @return array 
     */
    public function getErrors()
    {
        return $this->_errors;
    }

    /**
     * Add a new error
     * 
     * @param string $err 
     * @return void
     */
    protected function _setError($err)
    {
        $this->_errors[] = $err;
    }

    /**
     * Get number of items imported 
     * 
     * @access public
     * @return int 
     */
    public function getNumImported()
    {
        return $this->_numImported;
    }

    /**
     * Get number of items suppressed 
     * 
     * @access public
     * @return int 
     */
    public function getNumSuppressed()
    {
        return $this->_numSuppressed;
    }

}
