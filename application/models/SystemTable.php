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
 * SystemTable
 *
 * @uses Fisma_Doctrine_Table
 * @package Model
 * @copyright (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @author Josh Boyd <joshua.boyd@endeavorsystems.com>
 * @license http://www.openfisma.org/content/license GPLv3
 */
class SystemTable extends Fisma_Doctrine_Table implements Fisma_Search_Searchable
{
    protected $_customLogicalNames = array(
        'name' => 'Name',
        'nickname' => 'Nickname',
        'parentNickname' => 'Parent',
        'description' => 'Description'
    );

    /**
     * Implement the interface for Searchable
     */
    public function getSearchableFields()
    {
        return array (
            'nickname' => array(
                'initiallyVisible' => true,
                'join' => array(
                    'model' => 'Organization',
                    'relation' => 'Organization',
                    'field' => 'nickname'
                ),
                'sortable' => true,
                'type' => 'text',
                'formatter' => 'Fisma.TableFormat.recordLink',
                'formatterParameters' => array(
                    'prefix' => '/system/view/id/'
                )
            ),
            'name' => array(
                'initiallyVisible' => true,
                'join' => array(
                    'model' => 'Organization',
                    'relation' => 'Organization',
                    'field' => 'name'
                ),
                'sortable' => true,
                'type' => 'text'
            ),
            'pocUser' => array(
                'initiallyVisible' => false,
                'label' => 'Organization_Point_of_Contact',
                'join' => array(
                    'model' => 'User',
                    'relation' => 'Organization.Poc',
                    'field' => 'displayName'
                ),
                'sortable' => true,
                'type' => 'text'
            ),
            'description' => array(
                'initiallyVisible' => false,
                'join' => array(
                    'model' => 'Organization',
                    'relation' => 'Organization',
                    'field' => 'description'
                ),
                'sortable' => false,
                'type' => 'text'
            ),
            'type' => array(
                'initiallyVisible' => true,
                'label' => 'Type',
                'sortable' => true,
                'join' => array(
                    'model' => 'SystemType',
                    'relation' => 'SystemType',
                    'field' => 'nickname'
                ),
                'type' => 'text'
            ),
            'sdlcPhase' => array(
                'enumValues' => $this->getEnumValues('sdlcPhase'),
                'initiallyVisible' => true,
                'sortable' => true,
                'type' => 'enum'
            ),
            'fipsCategory' => array(
                'enumValues' => $this->getEnumValues('fipsCategory'),
                'initiallyVisible' => true,
                'sortable' => true,
                'type' => 'enum'
            ),
            'fismaReportable' => array(
                'initiallyVisible' => false,
                'sortable' => true,
                'type' => 'boolean'
            ),
            'controlledBy' => array(
                'enumValues' => $this->getEnumValues('controlledBy'),
                'initiallyVisible' => false,
                'sortable' => true,
                'type' => 'enum'
            ),
            'securityAuthorizationDt' => array(
                'initiallyVisible' => false,
                'sortable' => true,
                'type' => 'date'
            ),
            'nextSecurityAuthorizationDt' => array(
                'initiallyVisible' => false,
                'sortable' => true,
                'type' => 'date'
            ),
            'contingencyPlanTestDt' => array(
                'initiallyVisible' => false,
                'sortable' => true,
                'type' => 'date'
            ),
            'controlAssessmentDt' => array(
                'initiallyVisible' => false,
                'sortable' => true,
                'type' => 'date'
            ),
            'hasFiif' => array(
                'enumValues' => $this->getEnumValues('hasFiif'),
                'initiallyVisible' => false,
                'sortable' => true,
                'type' => 'enum'
            ),
            'hasPii' => array(
                'enumValues' => $this->getEnumValues('hasPii'),
                'initiallyVisible' => false,
                'sortable' => true,
                'type' => 'enum'
            ),
            'piaRequired' => array(
                'enumValues' => $this->getEnumValues('piaRequired'),
                'initiallyVisible' => false,
                'sortable' => true,
                'type' => 'enum'
            ),
            'piaUrl' => array(
                'initiallyVisible' => false,
                'sortable' => true,
                'type' => 'text'
            ),
            'sornRequired' => array(
                'enumValues' => $this->getEnumValues('sornRequired'),
                'initiallyVisible' => false,
                'sortable' => true,
                'type' => 'enum'
            ),
            'sornUrl' => array(
                'enumValues' => $this->getEnumValues('sornUrl'),
                'initiallyVisible' => false,
                'sortable' => true,
                'type' => 'text'
            ),
            'uniqueProjectId' => array(
                'initiallyVisible' => false,
                'sortable' => true,
                'type' => 'text'
            ),
            'organizationId' => array(
                'hidden' => true,
                'join' => array(
                    'model' => 'Organization',
                    'relation' => 'Organization',
                    'field' => 'id'
                ),
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
        // This class re-uses the ACL ID provider from Organization Table (since the access control is identical)
        return array('organizationId' => 'OrganizationTable::getOrganizationIds');
    }

    /**
     * Provide ID list for ACL filter
     *
     * @return array
     */
    public static function getSystemIds()
    {
        $systemIds = CurrentUser::getInstance()
                     ->getSystemsByPrivilege('organization', 'read')
                     ->toKeyValueArray('systemId', 'systemId');

        return array_keys($systemIds);
    }
}
