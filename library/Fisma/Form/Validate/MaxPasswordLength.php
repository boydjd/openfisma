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
 * Validate that max password length is longer than minimum password length
 *
 * @author     Josh Boyd <joshua.boyd@endeavorsystems.com> 
 * @copyright  (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Fisma
 * @subpackage Fisma_Form
 * @version    $Id$ 
 */
class Fisma_Form_Validate_MaxPasswordLength extends Zend_Validate_Abstract
{
    /**
     * Constant error message key 'invalid'
     */
    const MSG_INVALID = 'invalid';

    /**
     * Error message templates
     * 
     * @var array
     */
    protected $_messageTemplates = array(
        self::MSG_INVALID => "'%value%' is not greater than the minimum password length."
    );

    /**
     * Check if the maximum password length is valid
     * 
     * @param string $value The specified to be validated
     * @param array $context The context array which includes some password complexity requirements
     * @return boolean Ture if not greater than the minimum password length, false otherwise
     * @todo suggest rename class name to Fisma_Form_Validate_MinPasswordLength 
     * since actually following snippet involves 'pass_min_length' but 'pass_max_length'
     */
    public function isValid($value, $context = null)
    {
        $this->_setValue($value);

        if ($value < $context['pass_min_length']) {
            $this->_error();
            return false;
        }

        return true;
    }
}
