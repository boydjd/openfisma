<?php
/**
 * Copyright (c) 2012 Endeavor Systems, Inc.
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
 * Custom form validator for maxium upload file size element.
 *
 * @author     Mark Ma <mark.maendeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2010 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Fisma
 * @subpackage Fisma_Zend_Validate
 */
class Fisma_Zend_Form_Validate_MaxUploadFilesize extends Zend_Validate_Abstract
{
    const INVALID        = 'maxUploadFilesizeInvalid';
    const INVALID_FORMAT = 'maxUploadFilesizeInvalidFormat';
    const INVALID_SIZE = 'maxUploadFilesizeInvalidNumber';

    /**
     * Validation failure message template definitions
     *
     * @var array
     */
    protected $_messageTemplates = array(
        self::INVALID        => "Invalid maxium upload file size.",
        self::INVALID_FORMAT => "Invalid maxium upload file size, expected 'M' or 'K' at the end of number."
    );

    /**
     * Defined by Zend_Validate_Interface
     *
     * Returns true if $value is a valid phone number of the
     * format integer + K or M
     *
     * @param  string $value
     * @return boolean
     */
    public function isValid($value)
    {
        if (!is_string($value) || !is_numeric(substr($value, 0, -1))) {
            $this->_error(self::INVALID);
            return false;
        }

        if (strtoupper(substr($value, -1)) != 'M' && strtoupper(substr($value, -1)) !='K') {
            $this->_error(self::INVALID_FORMAT);
            return false;
        }

        $setting = Fisma_String::convertFilesizeToInteger($value);
        $maxSize = Fisma_String::convertFilesizeToInteger(ini_get('upload_max_filesize'));
 
        if ($setting > $maxSize) {
            $this->_messageTemplates[self::INVALID_SIZE] = 'Invalid maxium upload file size, should be less than '
                                                           . ini_get('upload_max_filesize');
            $this->_error(self::INVALID_SIZE);
            return false;
        }

        return true;
    }
}
