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
 * OrganizationTable
 *
 * @uses Fisma_Doctrine_Table
 * @package Model
 * @copyright (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @author Josh Boyd <joshua.boyd@endeavorsystems.com>
 * @license http://www.openfisma.org/content/license GPLv3
 */
class OrganizationTable extends Fisma_Doctrine_Table implements Fisma_Search_Searchable,
                                                                Fisma_Search_CustomIndexBuilder_Interface
{
    /**
     * Implement the interface for Searchable
     */
    public function getSearchableFields()
    {
        // The org type should show all values *except* system
        $orgTypeEnumValues = $this->getEnumValues('orgType');
        unset($orgTypeEnumValues[array_search('system', $orgTypeEnumValues)]);

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
            'createdTs' => array(
                'initiallyVisible' => false,
                'label' => 'Creation Date',
                'sortable' => true,
                'type' => 'datetime'
            ),
            'modifiedTs' => array(
                'initiallyVisible' => false,
                'label' => 'Modification Date',
                'sortable' => true,
                'type' => 'datetime'
            ),
            'orgType' => array(
                'enumValues' => $orgTypeEnumValues,
                'initiallyVisible' => true,
                'label' => 'Type',
                'sortable' => true,
                'type' => 'enum'
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
        return array('id' => 'OrganizationTable::getOrganizationIds');
    }

    /**
     * Provide ID list for ACL filter
     * 
     * @return array
     */
    static function getOrganizationIds()
    {
        $currentUser = CurrentUser::getInstance();
 
        // the ID list would contain the systems in the disposal phase 
        $organizationIds = $currentUser->getOrganizationsByPrivilege('organization', 'read', true)
                                       ->toKeyValueArray('id', 'id');

        return $organizationIds;
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
        $baseTableAlias = $relationAliases['Organization'];

        return $baseQuery->where("$baseTableAlias.orgType <> ?", 'system');
    }

    /**
     * A callback for lucene searches that involve searching an organization subtree
     *
     * Known implementers: FindingTable
     *
     * @param string $parentOrganization The nickname of the root node of the subtree to return
     * @return array An array of organization IDs in the subtree
     */
    static function getOrganizationSubtreeIds($parentOrganization)
    {
        $organization = Doctrine::getTable('Organization')->findOneByNickname($parentOrganization);

        // If the parent node isn't found, then return an impossible condition to prevent matching any objects
        if (!$organization) {
            return array(0);
        }

        $idQuery = Doctrine_Query::create()
                   ->select('id')
                   ->from('Organization')
                   ->where('lft >= ? AND rgt <= ?', array($organization->lft, $organization->rgt))
                   ->setHydrationMode(Doctrine::HYDRATE_SCALAR);

        $idResult = $idQuery->execute();

        $ids = array();
        
        foreach ($idResult as $row) {
            foreach ($row as $column => $value) {
                $ids[] = $value;
            }
        }
        
        return $ids;
    }
}
