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
 * along with OpenFISMA.  If not, see {@link http://www.gnu.org/licenses/}.
 */

/**
 * Fisma_Form_Validate_MaxPasswordLength 
 * 
 * @uses Zend_Validate_Abstract
 * @package Fisma_Form_Validate 
 * @version $Id$
 * @copyright (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com})
 * @author Josh Boyd <joshua.boyd@endeavorsystems.com> 
 * @license {@link http://www.openfisma.org/content/license}
 */
class Fisma_Form_Validate_MaxPasswordLength extends Zend_Validate_Abstract
{
    const MSG_INVALID = 'invalid';

    protected $_messageTemplates = array(
        self::MSG_INVALID => "'%value%' is not greater than the minimum password length."
    );

    /**
     * isValid - Check if the maximum password length is valid 
     * 
     * @param mixed $value
     * @param mixed $context 
     * @access public
     * @return boolean 
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
