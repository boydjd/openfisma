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
 * An implementation of a MITRE Common Platform Enumeration (CPE) data type, version 2.1
 * 
 * @link http://cpe.mitre.org
 * 
 * @author     Mark E. Haase <mhaase@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Fisma
 * @subpackage Fisma_Cpe
 */
class Fisma_Cpe
{
    /**
     * Raw CPE data
     * 
     * @var string
     */
    private $_cpeName;

    /**
     * Data parsed from the CPE.
     * 
     * @var array
     */
    private $_cpeDetails = array();

    /**
     * Create a new instance
     * 
     * @param string $cpeName The raw cpe-item text
     * @return void
     */
    public function __construct($cpeName)
    {
        $this->_cpeName = $cpeName;

        $this->_parseCpe();
    }

    /**
     * Return the raw CPE name or one of the parsed CPE components
     * 
     * @param string $fieldName The field's name to obtain
     * @return string|null
     */
    public function __get($fieldName)
    {
        if ($fieldName == 'cpeName') {
            return $this->_cpeName;
        } elseif (isset($this->_cpeDetails[$fieldName])) {
            return $this->_cpeDetails[$fieldName];
        } else {
            return null;
        }
    }
    
    /**
     * Parse the instance's CPE string and create/update its CPE data
     * 
     * @throws Fisma_Cpe_Exception if the CPE item is not well-formed
     */
    private function _parseCpe()
    {
        $components = array();
        
        // Remove the 'cpe:/' prefix
        $cpeName = $this->_cpeName;
        if ('cpe:/' == substr($cpeName, 0, 5)) {
            $cpeName = substr($cpeName, 5);
        } else {
            throw new Fisma_Cpe_Exception('CPE does not begin with "cpe:/"');
        }
        
        // Parse remaining identifiers; there cannot be more than 7 of these, per the CPE spec
        $tokens = explode(':', $cpeName);
        if (count($tokens) > 7) {
            throw new Fisma_Cpe_Exception("CPE has too many parts");
        }
        
        // Validate part
        $validParts = array('a', 'h', 'o');
        if (in_array($tokens[0], $validParts)) {
            $this->_cpeDetails['part'] = $tokens[0];
            array_shift($tokens);
        } else {
            throw new Fisma_Cpe_Exception("CPE has an invalid part specifier: '{$tokens[0]}'");
        }
        
        // Assign names to any remaining tokens which are not blank
        $partNames = array('vendor', 'product', 'version', 'update', 'edition', 'language');
        foreach ($tokens as $index => $token) {
            if (!empty($token)) {
                $this->_cpeDetails[$partNames[$index]] = urldecode($token);
            }
        }
    }
}
