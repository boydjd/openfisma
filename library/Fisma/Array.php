<?php
/**
 * Copyright (c) 2011 Endeavor Systems, Inc.
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
 * A class that contains helper functions for working with arrays.
 *
 * @author     Mark E. Haase <mhaase@endeavorystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2011 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Fisma
 */
class Fisma_Array
{
    /**
     * Group a nested array by a key that each nested value contains.
     *
     * See the unit test for an example of how this works.
     *
     * @param array $linearArray
     * @param string $groupKey
     * @param string $condenseKey
     * @return array
     */
    static function groupByKey($linearArray, $groupKey, $condenseKey = null)
    {
        $groupedArray = array();

        foreach ($linearArray as $innerKey => $innerValue) {
            if (!is_array($innerValue)) {
                throw new Fisma_Zend_Exception("The value is not an array.");
            }

            $group = $innerValue[$groupKey];
            unset($innerValue[$groupKey]);

            if (!$group) {
                throw new Fisma_Zend_Exception("The group key is not set.");
            }

            if (!isset($groupedArray[$group])) {
                $groupedArray[$group] = array();
            }

            $groupedArray[$group][] = ($condenseKey ? $innerValue[$condenseKey] : $innerValue);
        }

        return $groupedArray;
    }
}
