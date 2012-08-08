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
                'initiallyVisible' => true,
                'label' => 'Organization Type',
                'join' => array(
                    'model' => 'OrganizationType',
                    'relation' => 'OrganizationType',
                    'field' => 'nickname'
                ),
                'sortable' => true,
                'type' => 'text'
            ),
            'pocUser' => array(
                'initiallyVisible' => false,
                'label' => 'Point Of Contact',
                'join' => array(
                    'model' => 'User',
                    'relation' => 'Poc',
                    'field' => 'displayName'
                ),
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
        $baseTableAlias = $relationAliases['OrganizationType'];
        return $baseQuery->where("$baseTableAlias.nickname <> ?", 'system');
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
        // Since it addes the slashes at searchByCriteria(), so, it needs to remove slashes here.
        $parentOrganization = stripslashes($parentOrganization);

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

    /**
     * A callback for lucene searches that involve searching an organization's immediate children
     *
     * Known implementers: FindingTable
     *
     * @param string $parentOrganization The nickname of the root node of the children to return
     * @return array An array of children organization IDs
     */
    static function getOrganizationChildrenIds($parentOrganization)
    {
        // Since it addes the slashes at searchByCriteria(), so, it needs to remove slashes here.
        $parentOrganization = stripslashes($parentOrganization);

        $organization = Doctrine::getTable('Organization')->findOneByNickname($parentOrganization);

        // If the parent node isn't found, then return an impossible condition to prevent matching any objects
        if (!$organization) {
            return array(0);
        }

        $orgSystems = $organization->getNode()->getChildren();
        $myOrgSystemIds = array($organization->id);
        foreach ($orgSystems as $orgSystem) {
            $myOrgSystemIds[] = $orgSystem['id'];
        }
        return $myOrgSystemIds;
    }

    /**
     * A callback for solr searches that involve searching system aggregation subtree
     *
     * Known implementers: FindingTable
     *
     * @param string $parentOrganization The nickname of the root node of the subtree to return
     * @return array An array of organization IDs in the subtree
     */
    static function getSystemAggregationSubtreeIds($parentOrganization)
    {
        // Since it addes the slashes at searchByCriteria(), so, it needs to remove slashes here.
        $parentOrganization = stripslashes($parentOrganization);

        $organization = Doctrine::getTable('Organization')->findOneByNickname($parentOrganization);

        /*
         * If the parent node isn't found or isn't a system, then return an impossible condition to prevent matching
         * any objects
         */
        if (!$organization || is_null($organization->System)) {
            return array(0);
        }

        /*
         * Since the system aggregation tree is not a nested set, we'll have to traverse it the hard way.
         * We do a breadth-first traversal, pulling each level of the subtree starting with the root node in the first
         * pass.
         * WARNING: The data structure is assumed to be a tree- if it is, instead a graph (containing cycles) this will
         * be an infinite loop.
         */
        $systemIds = array($organization->systemId);
        $currentLevel = $systemIds;
        while (!empty($currentLevel)) {
            $sids = Doctrine_Query::create()
                   ->select('id')
                   ->from('System')
                   ->whereIn('aggregateSystemId', $currentLevel)
                   ->execute()
                   ->toKeyValueArray('id', 'id');
            $systemIds = array_merge($systemIds, $sids);
            $currentLevel = $sids;
        }

        // we need to return the organization ids, not the system ids
        return Doctrine_Query::create()
            ->select('id')
            ->from('Organization')
            ->whereIn('systemId', $systemIds)
            ->execute()
            ->toKeyValueArray('id', 'id');
    }

    /**
     * getUsersAndRolesByOrganizationIdQuery
     *
     * @param mixed $organizationId
     * @access public
     * @return void
     */
    public function getUsersAndRolesByOrganizationIdQuery($organizationId)
    {
        return Doctrine_Query::create()
            ->select('u.id, u.username, r.id, r.nickname')
            ->from('User u')
            ->leftJoin('u.UserRole ur')
            ->leftJoin('ur.UserRoleOrganization uro')
            ->leftJoin('ur.Role r')
            ->leftJoin('uro.Organization o')
            ->where('o.id = ?', $organizationId)
            ->orderBy('r.id, u.username')
            ->setHydrationMode(Doctrine::HYDRATE_SCALAR);
    }

    /**
     * getSystemsLikeNameQuery
     *
     * @param mixed $query
     * @access public
     * @return void
     */
    public function getSystemsLikeNameQuery($query)
    {
        return Doctrine_Query::create()
            ->from('Organization o')
            ->leftJoin('o.System s')
            ->where('o.name LIKE ?', $query . '%')
            ->orWhere('o.nickname LIKE ?', $query . '%');
    }

    /**
     * Get the basic items needed for an organization select UI: id, nickname and name
     *  (systems can be optionally excluded).
     *
     * @param bool $excludeSystem Optional, default to FALSE
     * @return Doctrine_Query
     */
    public function getOrganizationSelectQuery($excludeSystem = false)
    {
        $organizationSelectQuery = Doctrine_Query::create()
            ->select('o.id, o.nickname, o.name')
            ->from('Organization o');
        if ($excludeSystem) {
            $organizationSelectQuery->where('o.systemId IS Null');
        }
        $organizationSelectQuery->orderBy('o.nickname');
        return $organizationSelectQuery;
    }
}
