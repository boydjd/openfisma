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
 * DocumentTypeTable 
 * 
 * @uses Fisma_Doctrine_Table
 * @package Model 
 * @copyright (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @author Josh Boyd <joshua.boyd@endeavorsystems.com> 
 * @license http://www.openfisma.org/content/license GPLv3
 */
class DocumentTypeTable extends Fisma_Doctrine_Table implements Fisma_Search_Searchable,
                                                                Fisma_Search_CustomIndexBuilder_Interface
{
    /**
     * Return the count of required document types
     * 
     * @return integer
     */
    public function getRequiredDocTypeCount()
    {
        $requiredDocTypeQuery = Doctrine_Query::create()
                                ->from('DocumentType')
                                ->where('required = ?', true);

        return $requiredDocTypeQuery->count();
    }

    /**
     * Return a list of required document types
     * 
     * @return Doctrine_Query
     */
    public function getAllRequiredDocumentTypeQuery()
    {
        $requiredDocumentTypeQuery = Doctrine_Query::create()
                                         ->from('DocumentType dt')
                                         ->where('dt.required = ?', true);

        return $requiredDocumentTypeQuery;
    }

    /**
     * Implement the interface for Searchable
     */
    public function getSearchableFields()
    {
        return array (
            'name' => array(
                'initiallyVisible' => true,
                'label' => 'Name',
                'sortable' => true,
                'type' => 'text'
            ),
            'required' => array(
                'enumValues' => array('yes', 'no'),
                'initiallyVisible' => true,
                'label' => 'Required',
                'sortable' => true,
                'type' => 'enum'
            )
        );
    }

    /**
     * Document type model uses default access control (return empty array)
     *
     * @return array
     */
    public function getAclFields()
    {
        return array();
    }
    
    /**
     * Modifies the search index collection query to convert the boolean value to a string
     * 
     * @param Doctrine_Query $baseQuery
     * @param array $relationAliases An array that maps relation names to table aliases in the query
     * @return Doctrine_Query
     */
    public function getSearchIndexQuery(Doctrine_Query $baseQuery, $relationAliases)
    {
        // Table aliases are generated from doctrine metadata (without user input) and are safe to interpolate
        $baseTableAlias = $relationAliases['DocumentType'];

        return $baseQuery->select("$baseTableAlias.id AS id")
                         ->addSelect("$baseTableAlias.name AS name")
                         ->addSelect("IF($baseTableAlias.required = 1, 'yes', 'no') AS required");
    }
}
