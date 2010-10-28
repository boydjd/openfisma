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
 * This interface defines a method for Fisma_Doctrine_Table instances to tweak or override the default doctrine queries
 * used to populate the search index.
 * 
 * @author     Mark E. Haase <mhaase@endeavorsystems.com>
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Fisma
 * @subpackage Fisma_Search
 */
interface Fisma_Search_CustomIndexBuilder_Interface
{
    /**
     * The implementer should return a Doctrine_Query which, upon execution, will return array-hydrated data for
     * all indexable fields on all indexable records.
     *
     * For convenience, the default Doctrine_Query is passed in. The implementer can tweak this query and return it,
     * or the implementer can instantiate a new query and do something entirely different. (The latter case is more
     * likely to break the indexer, however, so be careful.)
     * 
     * @param Doctrine_Query $baseQuery
     * @param array $relationAliases An array that maps relation names to table aliases in the query
     * @return Doctrine_Query
     */
    public function getSearchIndexQuery(Doctrine_Query $baseQuery, $relationAliases);
}

