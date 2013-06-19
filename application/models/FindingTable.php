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
 * FindingTable
 *
 * @uses Fisma_Doctrine_Table
 * @package Model
 * @copyright (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @author Josh Boyd <joshua.boyd@endeavorsystems.com>
 * @license http://www.openfisma.org/content/license GPLv3
 */
class FindingTable extends Fisma_Doctrine_Table implements Fisma_Search_Searchable,
                                                           Fisma_Search_CustomChunkSize_Interface
{
    protected $_customLogicalNames = array(
        'createdTs' => 'Created',
        'modifiedTs' => 'Updated'
    );
    protected $_viewUrl = '/finding/remediation/view/id/';

    /**
     * Because the finding model is quite complex, it has a smaller-than-normal index chunk size which
     * uses less memory and should provide a more responsive UI.
     *
     * @var int
     */
    const INDEX_CHUNK_SIZE = 20;

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
                'type' => 'integer',
                'formatter' => 'Fisma.TableFormat.recordLink',
                'formatterParameters' => array(
                    'prefix' => '/finding/remediation/view/id/'
                )
            ),
            'legacyFindingKey' => array(
                'initiallyVisible' => false,
                'sortable' => true,
                'type' => 'text'
            ),
            'discoveredDate' => array(
                'initiallyVisible' => false,
                'sortable' => true,
                'type' => 'date',
                'formatter' => 'date'
            ),
            'auditYear' => array(
                'initiallyVisible' => false,
                'sortable' => true,
                'type' => 'integer'
            ),
            'createdTs' => array(
                'initiallyVisible' => true,
                'sortable' => true,
                'type' => 'datetime',
                'formatter' => 'date'
            ),
            'createdByUser' => array(
                'initiallyVisible' => false,
                'label' => 'Creator',
                'join' => array(
                    'model' => 'User',
                    'relation' => 'CreatedBy',
                    'field' => 'displayName'
                ),
                'sortable' => true,
                'type' => 'text'
            ),
            'residualRisk' => array(
                'enumValues' => $this->getEnumValues('residualRisk'),
                'initiallyVisible' =>
                    Fisma::configuration()->getConfig('threat_type') == 'residual_risk' ? true : false,
                'sortable' => true,
                'type' => 'enum',
                'hidden' => Fisma::configuration()->getConfig('threat_type') == 'residual_risk' ? false : true
            ),
            'threatLevel' => array(
                'enumValues' => $this->getEnumValues('threatLevel'),
                'initiallyVisible' =>
                    Fisma::configuration()->getConfig('threat_type') == 'threat_level' ? true : false,
                'sortable' => true,
                'type' => 'enum'
            ),
            'threat' => array(
                'initiallyVisible' => false,
                'sortable' => true,
                'type' => 'text'
            ),
            'pocUser' => array(
                'initiallyVisible' => true,
                'label' => 'Finding_Point_of_Contact',
                'join' => array(
                    'model' => 'User',
                    'relation' => 'PointOfContact',
                    'field' => 'displayName'
                ),
                'sortable' => true,
                'type' => 'text'
            ),
            'pocOrg' => array(
                'initiallyVisible' => false,
                'extraCriteria' => array(
                    'organizationSubtree' => array(
                        'idField' => 'pocOrgId',
                        'idProvider' => 'OrganizationTable::getOrganizationSubtreeIds',
                        'label' => 'Organizational Unit',
                        'renderer' => 'text',
                        'query' => 'oneInput',
                    ),
                    'organizationChildren' => array(
                        'idField' => 'responsibleOrganizationId',
                        'idProvider' => 'OrganizationTable::getOrganizationChildrenIds',
                        'label' => 'Managed Under',
                        'renderer' => 'text',
                        'query' => 'oneInput',
                    )
                ),
                'label' => 'Finding_Point_of_Contact_Organization',
                'join' => array(
                    'model' => 'Organization',
                    'relation' => 'PointOfContact.ReportingOrganization',
                    'field' => 'nickname'
                ),
                'sortable' => true,
                'type' => 'text'
            ),
            'organization' => array(
                'initiallyVisible' => true,
                'extraCriteria' => array(
                    // This criterion requires the responsibleOrganizationId field to be indexed on finding (see below)
                    'organizationSubtree' => array(
                        'idField' => 'responsibleOrganizationId',
                        'idProvider' => 'OrganizationTable::getOrganizationSubtreeIds',
                        'label' => 'Organizational Unit',
                        'renderer' => 'text',
                        'query' => 'oneInput',
                    ),
                    'organizationChildren' => array(
                        'idField' => 'responsibleOrganizationId',
                        'idProvider' => 'OrganizationTable::getOrganizationChildrenIds',
                        'label' => 'Managed Under',
                        'renderer' => 'text',
                        'query' => 'oneInput',
                    )
                ),
                'label' => 'Organization/System',
                'join' => array(
                    'model' => 'Organization',
                    'relation' => 'Organization',
                    'field' => 'nickname'
                ),
                'sortable' => true,
                'type' => 'text'
            ),
            'parentOrganization' => array(
                'initiallyVisible' => false,
                'label' => 'Parent Organization',
                'join' => array(
                    'model' => 'Organization',
                    'relation' => 'ParentOrganization',
                    'field' => 'nickname'
                ),
                'sortable' => true,
                'type' => 'text'
            ),
            'source' => array(
                'initiallyVisible' => false,
                'label' => 'Source',
                'join' => array(
                    'model' => 'Source',
                    'relation' => 'Source',
                    'field' => 'nickname'
                ),
                'sortable' => true,
                'type' => 'text'
            ),
            'securityControl' => array(
                'initiallyVisible' => false,
                'label' => 'Security Control',
                'join' => array(
                    'model' => 'SecurityControl',
                    'relation' => 'SecurityControl',
                    'field' => 'code'
                ),
                'sortable' => true,
                'type' => 'text'
            ),
            'description' => array(
                'initiallyVisible' => false,
                'sortable' => false,
                'type' => 'text'
            ),
            'recommendation' => array(
                'initiallyVisible' => false,
                'sortable' => false,
                'type' => 'text'
            ),
            'jsonComments' => array(
                'initiallyVisible' => false,
                'sortable' => false,
                'type' => 'text',
                'label' => 'Comments',
                'formatter' => 'Fisma.TableFormat.formatComments'
            ),
            'mitigationStrategy' => array(
                'initiallyVisible' => false,
                'sortable' => false,
                'type' => 'text'
            ),
            'originalEcd' => array(
                'initiallyVisible' => false,
                'sortable' => true,
                'type' => 'date',
                'formatter' => 'date'
            ),
            'currentEcd' => array(
                'initiallyVisible' => true,
                'sortable' => true,
                'type' => 'date',
                'formatter' => 'Fisma.TableFormat.formatDuedate'
            ),
            'nextDueDate' => array(
                'initiallyVisible' => true,
                'sortable' => true,
                'type' => 'date',
                'formatter' => 'Fisma.TableFormat.formatDuedate'
            ),
            'countermeasuresEffectiveness' => array(
                'enumValues' => $this->getEnumValues('countermeasuresEffectiveness'),
                'initiallyVisible' => false,
                'sortable' => true,
                'type' => 'enum',
                'hidden' => Fisma::configuration()->getConfig('threat_type') == 'residual_risk' ? false : true
            ),
            'countermeasures' => array(
                'initiallyVisible' => false,
                'sortable' => true,
                'type' => 'text',
                'hidden' => Fisma::configuration()->getConfig('threat_type') == 'residual_risk' ? false : true
            ),

            'workflow' => array(
                'initiallyVisible' => false,
                'label' => 'Workflow',
                'sortable' => true,
                'type' => 'text',
                'join' => array(
                    'model' => 'Workflow',
                    'relation' => 'CurrentStep.Workflow',
                    'field' => 'name'
                )
            ),
            'workflowStep' => array(
                'initiallyVisible' => true,
                'label' => 'Workflow Step',
                'sortable' => true,
                'type' => 'text',
                'join' => array(
                    'model' => 'WorkflowStep',
                    'relation' => 'CurrentStep',
                    'field' => 'name'
                )
            ),
            'isResolved' => array(
                'initiallyVisible' => false,
                'label' => 'Finding_Status',
                'sortable' => true,
                'type' => 'boolean'
            ),
            'closedTs' => array(
                'initiallyVisible' => false,
                'sortable' => true,
                'type' => 'datetime',
                'formatter' => 'date'
            ),
            'deleted_at' => array(
                'hidden' => true,
                'type' => 'datetime'
            ),
            'responsibleOrganizationId' => array(
                'hidden' => true,
                'type' => 'integer'
            ),
            'pocOrgId' => array(
                'hidden' => true,
                'join' => array(
                    'model' => 'Organization',
                    'relation' => 'PointOfContact.ReportingOrganization',
                    'field' => 'id'
                ),
                'type' => 'integer'
            ),
            'uploadid' => array(
                'initiallyVisible' => false,
                'sortable' => true,
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
        return array(
            'pocUser' => 'CurrentUser::getAclDisplayName',
            'responsibleOrganizationId' => 'FindingTable::getOrganizationIds'
        );
    }

    /**
     * Provide ID list for ACL filter
     *
     * @return array
     */
    public static function getOrganizationIds()
    {
        $currentUser = CurrentUser::getInstance();

        $organizationIds = $currentUser->getOrganizationsByPrivilege('finding', 'read')->toKeyValueArray('id', 'id');

        return $organizationIds;
    }

    /**
     * Implement required interface for custom chunk size.
     *
     * @return int
     */
    public function getIndexChunkSize()
    {
        return self::INDEX_CHUNK_SIZE;
    }

    /**
     * Return the query to fetch one attachment (if any) from a finding
     *
     * @param int $findingId THe id of the Finding to get
     * @param int $attachmentId The id of the Attachment to get
     *
     * @return Doctrine_Query
     */
    public static function getAttachmentQuery($findingId, $attachmentId)
    {
        return Doctrine_Query::create()
               ->from('Finding f')
               ->leftJoin('f.Attachments a')
               ->where('f.id = ?', $findingId)
               ->andWhere('a.id = ?', $attachmentId);
    }

    protected $_editableFields = array(
        'pocId',
        'auditYear',
        'description',
        'recommendation',
        'mitigationStrategy',
        'resourcesRequired',
        'currentEcd',
        'threatLevel',
        'threat',
        'countermeasuresEffectiveness',
        'countermeasures',
        'securityControlId',
        'sourceId'
    );
}
