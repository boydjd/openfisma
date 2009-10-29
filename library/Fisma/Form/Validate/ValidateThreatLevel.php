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
 */

/**
 * Fisma_Form_Validate_ValidateThreatLevel
 * 
 * @uses Zend
 * @uses _Validate_Abstract
 * @package Validate
 * @version $Id$
 * @copyright (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com})
 * @author Jackson Yang <yangjianshan@users.sourceforge.net>
 * @license {@link http://www.openfisma.org/content/license}
 */
class Fisma_Form_Validate_ValidateThreatLevel extends Zend_Validate_Abstract
{
    const THREATLEVEL = 'threatlevel';    

    protected $_reservedThreatLevel = array(
                ""         => "",
                "LOW"      => "LOW",
                "MODERATE" => "MODERATE",
                "HIGH"     => "HIGH"
    );

    protected $_messageTemplates = array(
        self::THREATLEVEL => "invalid threat level."
    );

    /**
     * isValid 
     * 
     * @param string $value 
     * @access public
     * @return boolean 
     */
    public function isValid($value)
    {
        $this->_setValue($value);

        if (!array_key_exists($value, $this->_reservedThreatLevel)) {
            $this->_error(self::THREATLEVEL);
            return false;
        }

        return true;
    }
}
