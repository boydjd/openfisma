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
 * Zend filter for form elements which reformats a phone number value into a standard format
 *
 * @author     Andrew Reeves <andrew.reeves@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2010 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Fisma
 * @subpackage Fisma_Zend_Filter
 */
class Fisma_Zend_Filter_Phone extends Zend_Filter_Digits
{
    /**
     * Defined by Zend_Filter_Interface
     *
     * Returns the string $value, in a proper phone number format.
     *
     * @param  string $value
     * @return string
     */
    public function filter($value)
    {
        $value = parent::filter($value);
        if (!empty($value)) {
            $first = substr($value, 0, 3);
            $first = $first ? $first : '';
            $second = substr($value, 3, 3);
            $second = $second ? $second : '';
            $third = substr($value, 6);
            $third = $third ? $third : '';

            $value = sprintf('(%s) %s-%s', $first, $second, $third);
        }

        return $value;
    }
}
