<?php
/**
 * Copyright (c) 2010 Endeavor Systems, Inc.
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
 * Custom form validator for phone number elements.
 *
 * @author     Andrew Reeves <andrew.reeves@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2010 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Fisma
 * @subpackage Fisma_Zend_Validate
 */
class Fisma_Zend_Validate_Phone extends Zend_Validate_Abstract
{
    const INVALID        = 'phoneInvalid';
    const INVALID_FORMAT = 'phoneInvalidFormat';

    /**
     * Validation failure message template definitions
     *
     * @var array
     */
    protected $_messageTemplates = array(
        self::INVALID        => "Invalid variable type.",
        self::INVALID_FORMAT => "Invalid phone number, expected '(###) ###-####'."
    );

    /**
     * Defined by Zend_Validate_Interface
     *
     * Returns true if $value is a valid phone number of the
     * format (###) ###-####
     *
     * @param  string $value
     * @return boolean
     */
    public function isValid($value)
    {
        if (!is_string($value)) {
            $this->_error(self::INVALID);
            return false;
        }

        $this->_setValue($value);

        $validator = new Fisma_Doctrine_Validator_Phone();
        if (!$validator->validate($value)) {
            $this->_error(self::INVALID_FORMAT);
            return false;
        }

        return true;
    }
}
