<?php
/**
 * Copyright (c) 2008 Endeavor Systems, Inc.
 *
 * This file is part of OpenFISMA.
 *
 * OpenFISMA is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * OpenFISMA is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with OpenFISMA.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author    Mark E. Haase <mhaase@endeavorsystems.com>
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/mw/index.php?title=License
 * @version   $Id: RiskAssessment.class.php 863 2008-09-09 21:17:03Z mehaase $
 */

/**
 * An implementation of a MITRE Common Platform Enumeration (CPE) data type,
 * version 2.1
 * @link http://cpe.mitre.org
 *
 * @package   Local
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/mw/index.php?title=License
 */
class Cpe
{
    /**
     * Raw CPE data
     */
    private $_cpeName;

    /**
     * Data parsed from the CPE.
     */
    private $_cpeDetails = array();

    /**
     * Unreserved characters (URc) that are permissible in a CPE component. All other characters must be URL encoded.
     * This is used in the regex to match component names.
     */
    const UNRESERVED_CHARACTERS = '[-A-Za-z0-9~\_.]';
    
    /**
     * __construct() - Create a new instance
     *
     * @param string $cpeName The raw cpe-item text.
     */
    public function __construct($cpeName)
    {
        $this->_cpeName = $cpeName;

        // Rename the UNRESERVED_CHARACTERS constant to something shorter to make the regex more readable.
        $urc = self::UNRESERVED_CHARACTERS;

        // Parse out the components of the CPE name
        $components = array();
        if (!preg_match("/cpe:\/($urc*):?($urc*):?($urc*):?($urc*):?($urc*):?($urc*):?($urc*)/",
                        $cpeName,
                        $components)) {
            throw new Exception_InvalidFileFormat("CPE item is not formatted correctly: \"$cpeName\"");
        }

        // Name the components (see CPE specification)
        $this->_cpeDetails['part']     = urldecode($components[1]);
        $this->_cpeDetails['vendor']   = urldecode($components[2]);
        $this->_cpeDetails['product']  = urldecode($components[3]);
        $this->_cpeDetails['version']  = urldecode($components[4]);
        $this->_cpeDetails['update']   = urldecode($components[5]);
        $this->_cpeDetails['edition']  = urldecode($components[6]);
        $this->_cpeDetails['language'] = urldecode($components[7]);
        
        // Discard any empty components
        foreach ($this->_cpeDetails as $key => $value) {
            if (empty($value)) {
                unset($this->_cpeDetails[$key]);
            }
        }
    }

    /**
     * __get() - Return the raw CPE name or one of the parsed CPE components
     *
     * @param
     */
    public function __get($fieldName)
    {
        if ($fieldName == 'cpeName') {
            return $this->_cpeName;
        } elseif (isset($this->_cpeDetails[$fieldName])) {
            // Do some pretty formatting for field values. E.g. turn "database_server" into "Database Server"
            return ucwords(str_replace('_', ' ', $this->_cpeDetails[$fieldName]));
        } else {
            return null;
        }
    }
}
