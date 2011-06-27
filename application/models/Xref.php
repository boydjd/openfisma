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
 * Xref 
 * 
 * @uses BaseXref
 * @package Model 
 * @copyright (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @author Josh Boyd <joshua.boyd@endeavorsystems.com> 
 * @license http://www.openfisma.org/content/license GPLv3
 */
class Xref extends BaseXref
{
    /**
     * Mapping of Xref types to corresponding URLs
     * 
     * Some reference providers do not have an automated system for getting URLs. Those providers are not included
     * in this list.
     * 
     * @see http://cve.mitre.org/data/refs/index.html
     * @var array
     */
    private $_types = array(
        'AIXAPAR' => 'http://www-01.ibm.com/support/docview.wss?uid=1',
        'BID' => 'http://www.securityfocus.com/bid/',
        'OSVDB' => 'http://osvdb.org/show/osvdb/',
        'SECTRACK' => 'http://www.securitytracker.com/id?',
        'SECUNIA' => 'http://secunia.com/advisories/',
        'SREASON' => 'http://securityreason.com/securityalert/',
        'UBUNTU' => 'http://www.ubuntu.com/usn/',
        'XF' => 'http://xforce.iss.net/xforce/xfdb/'
    );
    
    /**
     * Get a URL to this external reference
     * 
     * An Xref value looks like this: OSVDB:1234. In this case, it would link to the OSVDB website using 1234
     * as the object identifier.
     * 
     * This will return null if the Xref type is not recognized.
     * 
     * @return string|null
     */
    public function getUrl()
    {
        $matches = array();
        $url = null;

        if (preg_match('/(\w+?):(.+)/', $this->value, $matches)) {
            
            $type = $matches[1];
            $id = $matches[2];
            
            if (isset($this->_types[$type])) {
                $url = $this->_types[$type] . $id;
            }
        }
        
        return $url;
    }
}
