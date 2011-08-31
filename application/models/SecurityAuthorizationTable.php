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
 * SecurityAuthorizationTable 
 * 
 * @uses Fisma_Doctrine_Table
 * @package Model 
 * @copyright (c) Endeavor Systems, Inc. 2010 {@link http://www.endeavorsystems.com}
 * @author Andrew Reeves <andrew.reeves@endeavorsystems.com> 
 * @license http://www.openfisma.org/content/license GPLv3
 */

/**
 */
class SecurityAuthorizationTable extends Fisma_Doctrine_Table implements Fisma_Search_Searchable
{
    /**
     * Return an array of fields (and definitions) which are searchable
     * 
     * @return array
     */
    public function getSearchableFields()
    {
        return array (
            'id' => array(
                'initiallyVisible' => true,
                'label' => 'ID',
                'sortable' => true,
                'type' => 'integer'
            ),
            'organization' => array(
                'initiallyVisible' => true,
                'extraCriteria' => array(
                    'organizationSubtree' => array(
                        'idField' => 'responsibleOrganizationId',
                        'idProvider' => 'OrganizationTable::getOrganizationSubtreeIds',
                        'label' => 'Organizational Unit',
                        'renderer' => 'text',
                        'query' => 'oneInput',
                    )
                ),
                'label' => 'System',
                'join' => array(
                    'model' => 'Organization',
                    'relation' => 'Organization', 
                    'field' => 'nickname'
                ),
                'sortable' => true,
                'type' => 'text'
            ),
            'createdTs' => array(
                'initiallyVisible' => true,
                'label' => 'Created',
                'sortable' => true,
                'type' => 'date'
            ), 
            'status' => array(
               'initiallyVisible' => true,
                'label' => 'Status',
                'sortable' => true,
                'type' => 'text'
            ),
            'result' => array(
                'initiallyVisible' => true,
                'label' => 'Result',
                'sortable' => true,
                'type' => 'text'
            )
        );
    }

    /**
     * Return an array of fields which are used to test access control
     * 
     * Each key is the name of a field and each value is a callback function which provides a list of values to match
     * against that field.
     * 
     * @return array
     */
    public function getAclFields()
    {
        return array();
    }

}
