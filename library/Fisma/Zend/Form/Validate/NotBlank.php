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
 * Validate if the specific value empty
 * 
 * @author     Josh Boyd <joshua.boyd@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Fisma
 * @subpackage Fisma_Zend_Form
 * 
 * @uses       Zend_Validate_Abstract
 */
class Fisma_Zend_Form_Validate_NotBlank extends Zend_Validate_Abstract
{
    /**
     * Constant error message key 'blank'
     */
    const NOTBLANK = 'blank';

    /**
     * Error message templates
     * 
     * @var array
     */
    protected $_messageTemplates = array(
        self::NOTBLANK => "cannot be blank."
    );

    /**
     * Check if the specified value is blank
     * 
     * @param string $value The specified to be validated
     * @return boolean True if not blank, false otherwise
     */
    public function isValid($value)
    {
        $this->_setValue($value);
        $validator = new Doctrine_Validator_Notblank();

        if (!$validator->validate($value)) {
            $this->_error();
            return false;
        }

        return true;
    }
}
