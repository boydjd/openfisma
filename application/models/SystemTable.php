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
    /**
     * Implement the interface for Searchable
     */
    public function getSearchableFields()
    {
        return array (
            'nickname' => array(
                'initiallyVisible' => true,
                'label' => 'Nickname',
                'join' => array(
                    'model' => 'Organization',
                    'relation' => 'Organization',
                    'field' => 'nickname'
                ),
                'sortable' => true,
                'type' => 'text'
            ),
            'name' => array(
                'initiallyVisible' => true,
                'label' => 'Name',
                'join' => array(
                    'model' => 'Organization',
                    'relation' => 'Organization',
                    'field' => 'name'
                ),
                'sortable' => true,
                'type' => 'text'
            ),
            'description' => array(
                'initiallyVisible' => false,
                'label' => 'Description',
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
                'label' => 'SDLC Phase',
                'sortable' => true,
                'type' => 'enum'
            ),
            'confidentiality' => array(
                'enumValues' => $this->getEnumValues('confidentiality'),
                'initiallyVisible' => true,
                'label' => 'Confidentiality',
                'sortable' => true,
                'type' => 'enum'
            ),
            'confidentialityDescription' => array(
                'initiallyVisible' => false,
                'label' => 'Confidentiality Description',
                'sortable' => true,
                'type' => 'text'
            ),
            'integrity' => array(
                'enumValues' => $this->getEnumValues('integrity'),
                'initiallyVisible' => true,
                'label' => 'Integrity',
                'sortable' => true,
                'type' => 'enum'
            ),
            'integrityDescription' => array(
                'initiallyVisible' => false,
                'label' => 'Integrity Description',
                'sortable' => true,
                'type' => 'text'
            ),
            'availability' => array(
                'enumValues' => $this->getEnumValues('availability'),
                'initiallyVisible' => true,
                'label' => 'Availability',
                'sortable' => true,
                'type' => 'enum'
            ),
            'availabilityDescription' => array(
                'initiallyVisible' => false,
                'label' => 'Availability Description',
                'sortable' => true,
                'type' => 'text'
            ),
            'fipsCategory' => array(
                'enumValues' => $this->getEnumValues('fipsCategory'),
                'initiallyVisible' => true,
                'label' => 'FIPS 199 Category',
                'sortable' => true,
                'type' => 'enum'
            ),
            'controlledBy' => array(
                'enumValues' => $this->getEnumValues('controlledBy'),
                'initiallyVisible' => false,
                'label' => 'Controlled By',
                'sortable' => true,
                'type' => 'enum'
            ),
            'securityAuthorizationDt' => array(
                'initiallyVisible' => false,
                'label' => 'Security Authorization Date',
                'sortable' => true,
                'type' => 'date'
            ),
            'contingencyPlanTestDt' => array(
                'initiallyVisible' => false,
                'label' => 'Contingency Plan Test Date',
                'sortable' => true,
                'type' => 'date'
            ),
            'controlAssessmentDt' => array(
                'initiallyVisible' => false,
                'label' => 'Control Self-assessment Date',
                'sortable' => true,
                'type' => 'date'
            ),
            'hasFiif' => array(
                'enumValues' => $this->getEnumValues('hasFiif'),
                'initiallyVisible' => false,
                'label' => 'Contains FIIF',
                'sortable' => true,
                'type' => 'enum'
            ),
            'hasPii' => array(
                'enumValues' => $this->getEnumValues('hasPii'),
                'initiallyVisible' => false,
                'label' => 'Contains PII',
                'sortable' => true,
                'type' => 'enum'
            ),
            'piaRequired' => array(
                'enumValues' => $this->getEnumValues('piaRequired'),
                'initiallyVisible' => false,
                'label' => 'PIA Required',
                'sortable' => true,
                'type' => 'enum'
            ),
            'piaUrl' => array(
                'initiallyVisible' => false,
                'label' => 'PIA URL',
                'sortable' => true,
                'type' => 'text'
            ),
            'sornRequired' => array(
                'enumValues' => $this->getEnumValues('sornRequired'),
                'initiallyVisible' => false,
                'label' => 'SORN Required',
                'sortable' => true,
                'type' => 'enum'
            ),
            'sornUrl' => array(
                'enumValues' => $this->getEnumValues('sornUrl'),
                'initiallyVisible' => false,
                'label' => 'SORN URL',
                'sortable' => true,
                'type' => 'text'
            ),
            'uniqueProjectId' => array(
                'initiallyVisible' => false,
                'label' => 'Unique Project Identifier',
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
