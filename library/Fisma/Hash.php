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
 * Produces a variety of hashes
 * 
 * @author     Mark E. Haase
 * @copyright  (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Fisma
 * @subpackage Fisma_Hash
 */
class Fisma_Hash
{
    /**
     * This is a static class that cannot be instantiated
     * 
     * @return void
     */
    private function __construct()
    {
        ;
    }
    
    /**
     * Return the requested hash
     * 
     * @param string $data The specified data to be hashed
     * @param string $hashType The specified hash algorithm
     * @return string The hash code of the requested data
     * @throws Fisma_Zend_Exception if the hash type is not supported
     */
    static public function hash($data, $hashType)
    {
        if ('sha1' == $hashType) {
            return sha1($data);
        } elseif ('md5' == $hashType) {
            return md5($data);
        } elseif ('sha256' == $hashType) {
            return mhash(MHASH_SHA256, $data);
        } else {
            throw new Fisma_Zend_Exception("Unsupported hash type: {$hashType}");
        }
    }
}
