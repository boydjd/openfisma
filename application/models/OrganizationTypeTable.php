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
 * OrganizationTypeTable
 *
 * @uses Fisma_Doctrine_Table
 * @package Model
 * @copyright (c) Endeavor Systems, Inc. 2011 {@link http://www.endeavorsystems.com}
 * @author Mark Ma <mark.ma@reyosoft.com>
 * @license http://www.openfisma.org/content/license GPLv3
 */

class OrganizationTypeTable extends Fisma_Doctrine_Table implements Fisma_Search_Searchable,
                                                                Fisma_Search_CustomIndexBuilder_Interface
{
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
            'nickname' => array(
                'initiallyVisible' => true,
                'label' => 'Nickname',
                'sortable' => true,
                'type' => 'text'
            ),
            'description' => array(
                'initiallyVisible' => true,
                'label' => 'Description',
                'sortable' => false,
                'type' => 'text'
            ),
            'id' => array(
                'hidden' => true,
                'type' => 'integer'
            )
        );
    }

    /**
     * Return a list of fields which are used for access control
     *
     * @return array
     */
    public function getAclFields()
    {
        return array();
    }

    /**
     * Modifies the search index collection query to filter out system objects
     *
     * @param Doctrine_Query $baseQuery
     * @param array $relationAliases An array that maps relation names to table aliases in the query
     * @return Doctrine_Query
     */
    public function getSearchIndexQuery(Doctrine_Query $baseQuery, $relationAliases)
    {
        // Table aliases are generated from doctrine metadata (without user input) and are safe to interpolate
        $baseTableAlias = $relationAliases['OrganizationType'];
        return $baseQuery->where("$baseTableAlias.nickname <> ?", 'system');
    }

    /**
     * Return an array of organization type which its keys are ids and value are nickname
     *
     * @param boolean $includeSystem
     * @return Organization type array with key is id and value is nickname
     */
    public function getOrganizationTypeArray()
    {
        $orgTypeArray = array();
        $orgTypes = $this->findAll(Doctrine::HYDRATE_ARRAY);
        foreach ($orgTypes as $orgType) {
            if ('System' == $orgType['name']) {
                continue;
            }

            $orgTypeArray[$orgType['id']] = $orgType['name'];
        }

        natcasesort($orgTypeArray);
        return $orgTypeArray;
    }
}
