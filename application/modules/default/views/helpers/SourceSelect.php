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
 * Turn a Doctrine Collection of sources into an HTML select
 *
 * @author     Ben Zheng <benzheng@users.sourceforge.net>
 * @copyright  (c) Endeavor Systems, Inc. 2010 {@link http://www.endeavorsources.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    View_Helper
 */
class View_Helper_SourceSelect extends Zend_View_Helper_Abstract
{
    /**
     * Turn a Doctrine Collection of sources into an HTML select
     *
     * @param Doctrine_Collection $collection The doctrine collection of sources
     * @return array An array where the key/values mirror the select element's key/values
     */
    public function sourceSelect($collection)
    {
        $sources = array();
        
        foreach ($collection as $source) {
            $sources[$source->id] = $source->nickname . ' - ' . $source->name;
        }
                
        return $sources;
    } 
}
