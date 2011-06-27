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
 * Validate if the MCE editor has content
 *
 * @author     Jim Chen <xhorse@users.sourceforge.net>
 * @copyright  (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Fisma
 * @subpackage Fisma_Zend_Form
 */
class Fisma_Zend_Form_Validate_MceNotEmpty extends Fisma_Zend_Form_Validate_NotBlank
{
    /**
     * Constance error message key 'notempty'
     */
    const NOTEMPTY = "notempty";

    /**
     * Error message templates
     * 
     * @var array
     */
    protected $_messageTemplates = array(
        self::NOTEMPTY => "can't be empty."
    );

    /** 
     * Returns true if the mce editor has none empty value after removing the wrapper tags
     *
     * @param string $value The specified value to be validated
     * @return boolean True if not empty, false otherwise
     */
    public function isValid($value)
    {
        // tags don't count as content
        $value = strip_tags($value);
        $value = html_entity_decode($value);

        // remove all non breaking spaces
        $value = trim($value, "\xA0");

        $validator = new Zend_Validate_NotEmpty();

        if ($validator->isValid($value)) {
            return parent::isValid($value);
        }

        $this->_error(self::NOTEMPTY);
        return false;
    }
}
