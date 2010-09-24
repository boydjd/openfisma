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
 * FindingTable 
 * 
 * @uses Fisma_Doctrine_Table
 * @package Model 
 * @copyright (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @author Josh Boyd <joshua.boyd@endeavorsystems.com> 
 * @license http://www.openfisma.org/content/license GPLv3
 */
class FindingTable extends Fisma_Doctrine_Table implements Fisma_Search_Searchable
{
    /**
     * Implement the interface for Searchable
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
                    // This criterion requires the lft and rgt fields of the Organization model to be indexed on finding
                    'organizationSubtree' => array(
                        'callback' => 'OrganizationTable::getOrganizationSubtreeLuceneQuery',
                        'label' => 'Organizational Unit',
                        'renderer' => 'text',
                        'query' => 'oneInput',
                    )
                ),
                'label' => 'Responsible Organization',
                'join' => array(
                    'relation' => 'ResponsibleOrganization', 
                    'field' => 'nickname'
                ),
                'sortable' => true,
                'type' => 'text'
            ),
            'source' => array(
                'initiallyVisible' => true,
                'label' => 'Source',
                'join' => array(
                    'relation' => 'Source', 
                    'field' => 'nickname'
                ),
                'sortable' => true,
                'type' => 'text'
            ),
            'createdTs' => array(
                'initiallyVisible' => false,
                'label' => 'Creation Date',
                'sortable' => true,
                'type' => 'datetime'
            ), 
            'discoveredDate' => array(
                'initiallyVisible' => true,
                'label' => 'Discovered Date',
                'sortable' => true,
                'type' => 'date'
            ), 
            'nextDueDate' => array(
                'initiallyVisible' => true,
                'label' => 'Next Due Date',
                'sortable' => true,
                'type' => 'datetime'
            ), 
            'closedTs' => array(
                'initiallyVisible' => false,
                'label' => 'Closed Date',
                'sortable' => true,
                'type' => 'datetime'
            ), 
            'legacyFindingKey' => array(
                'initiallyVisible' => false,
                'label' => 'Legacy Finding Key',
                'sortable' => true,
                'type' => 'text'
            ),
            'type' => array(
                'enumValues' => $this->getEnumValues('type'),
                'initiallyVisible' => true,
                'label' => 'Type',
                'sortable' => true,
                'type' => 'enum'
            ),
            'denormalizedStatus' => array(
                'initiallyVisible' => true,
                'label' => 'Status',
                'sortable' => true,
                'type' => 'text'
            ),
            'description' => array(
                'initiallyVisible' => true,
                'label' => 'Description',
                'sortable' => false,
                'type' => 'text'
            ),
            'recommendation' => array(
                'initiallyVisible' => false,
                'label' => 'Recommendation',
                'sortable' => false,
                'type' => 'text'
            ),
            'mitigationStrategy' => array(
                'initiallyVisible' => false,
                'label' => 'Mitigation Strategy',
                'sortable' => false,
                'type' => 'text'
            ),
            'originalEcd' => array(
                'initiallyVisible' => false,
                'label' => 'Original ECD',
                'sortable' => true,
                'type' => 'date'
            ),
            'currentEcd' => array(
                'initiallyVisible' => true,
                'label' => 'Current ECD',
                'sortable' => true,
                'type' => 'date'
            ),
            'threatLevel' => array(
                'enumValues' => $this->getEnumValues('threatLevel'),
                'initiallyVisible' => false,
                'label' => 'Threat Level',
                'sortable' => true,
                'type' => 'enum'
            ),
            'threat' => array(
                'initiallyVisible' => false,
                'label' => 'Threat Description',
                'sortable' => true,
                'type' => 'text'
            ),
            'countermeasuresEffectiveness' => array(
                'enumValues' => $this->getEnumValues('countermeasuresEffectiveness'),
                'initiallyVisible' => false,
                'label' => 'Countermeasures Effectiveness',
                'sortable' => true,
                'type' => 'enum'
            ),
            'countermeasures' => array(
                'initiallyVisible' => false,
                'label' => 'Countermeasures Description',
                'sortable' => true,
                'type' => 'text'
            ),
            'residualRisk' => array(
                'enumValues' => $this->getEnumValues('residualRisk'),
                'initiallyVisible' => true,
                'label' => 'Residual Risk',
                'sortable' => true,
                'type' => 'enum'
            ),
            'securityControl' => array(
                'initiallyVisible' => true,
                'label' => 'Security Control',
                'join' => array(
                    'relation' => 'SecurityControl', 
                    'field' => 'code'
                ),
                'sortable' => true,
                'type' => 'text'
            ),
            'createdByUser' => array(
                'initiallyVisible' => false,
                'label' => 'Created By User',
                'join' => array(
                    'relation' => 'CreatedBy', 
                    'field' => 'username'
                ),
                'sortable' => true,
                'type' => 'text'
            ),
            'lft' => array(
                'hidden' => true,
                'join' => array(
                    'relation' => 'ResponsibleOrganization',
                    'field' => 'lft'
                ),
                'type' => 'integer'
            ),
            'rgt' => array(
                'hidden' => true,
                'join' => array(
                    'relation' => 'ResponsibleOrganization',
                    'field' => 'rgt'
                ),
                'type' => 'integer'
            ),
        );
    }
}
