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
 * An interface for any object which searchable that provides an abstraction for getting information about how 
 * that object can be searched.
 * 
 * The Doctrine YAML file is not expressive enough to document the required search metadata, and it has some
 * unexpected behaviors. (E.g. an actAs clause will overwrite any previous column definition.)
 * 
 * @author     Mark E. Haase <mhaase@endeavorystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2010 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Fisma
 * @subpackage Fisma_Search
 */
interface Fisma_Search_Searchable
{
    /**
     * Return an array of fields (and definitions) which are searchable
     * 
     * @return array
     */
    public function getSearchableFields();
    
    /**
     * Return an array of fields which are used to test access control
     * 
     * Each key is the name of a field and each value is a callback function which provides a list of values to match
     * against that field.
     * 
     * @return array
     */
    public function getAclFields();
}
