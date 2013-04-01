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
 * IncidentTable
 *
 * @uses Fisma_Doctrine_Table
 * @package Models
 * @copyright (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @author Josh Boyd <joshua.boyd@endeavorsystems.com>
 * @license http://www.openfisma.org/content/license GPLv3
 */
class IncidentTable extends Fisma_Doctrine_Table implements Fisma_Search_Searchable
{
    /**
     * Returns a query which matches all of the current user's viewable incidents
     *
     * @param User $user
     * @param Fisma_Zend_Acl $acl   Optional, defaults to $user->acl()
     * @return Doctrine_Query
     */
    public function getUserIncidentQuery(User $user, Fisma_Zend_Acl $acl = null)
    {
        $incidentQuery = Doctrine_Query::create()
                         ->from('Incident i');

        /*
         * A user can read *all* incidents if he has the "incident read" privilege. Otherwise, he is only allowed to
         * view those incidents for which he is an actor or an observer.
         */
        $acl = (isset($acl)) ? $acl : $user->acl();
        if (!$acl->hasPrivilegeForClass('read', 'Incident')) {
            $incidentQuery->leftJoin('i.Users u')
                          ->where('u.id = ?', $user->id);
        }

        return $incidentQuery;
    }

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
                    'prefix' => '/incident/view/id/'
                )
            ),
            'incidentDate' => array(
                'initiallyVisible' => true,
                'label' => 'Occured',
                'sortable' => true,
                'type' => 'date',
                'formatter' => 'date',
                'timezoneAbbrField' => 'incidentTimezone'
            ),
            'reportTs' => array(
                'initiallyVisible' => false,
                'label' => 'Reported',
                'sortable' => true,
                'type' => 'datetime',
                'formatter' => 'datetime',
                'timezoneAbbrField' => 'reportTz'
            ),
            'reporter' => array(
                'initiallyVisible' => false,
                'label' => 'Reporter',
                'join' => array(
                    'model' => 'User',
                    'relation' => 'ReportingUser',
                    'field' => 'displayName'
                ),
                'sortable' => true,
                'type' => 'text'
            ),
            'status' => array(
                'enumValues' => $this->getEnumValues('status'),
                'initiallyVisible' => true,
                'label' => 'Status',
                'sortable' => true,
                'type' => 'enum'
            ),
            'pocUser' => array(
                'initiallyVisible' => false,
                'label' => 'Incident_Point_of_Contact',
                'join' => array(
                    'model' => 'User',
                    'relation' => 'PointOfContact',
                    'field' => 'displayName'
                ),
                'sortable' => true,
                'type' => 'text'
            ),
            'organization' => array(
                'initiallyVisible' => true,
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
                'initiallyVisible' => true,
                'label' => 'Parent Organization/System',
                'join' => array(
                    'model' => 'Organization',
                    'relation' => 'ParentOrganization',
                    'field' => 'nickname'
                ),
                'sortable' => true,
                'type' => 'text'
            ),
            'additionalInfo' => array(
                'initiallyVisible' => true,
                'label' => 'Description',
                'sortable' => true,
                'type' => 'text'
            ),
            'locationBuilding' => array(
                'initiallyVisible' => false,
                'label' => 'Building',
                'sortable' => true,
                'type' => 'text'
            ),
            'locationRoom' => array(
                'initiallyVisible' => false,
                'label' => 'Room',
                'sortable' => true,
                'type' => 'text'
            ),
            'source' => array(
                'initiallyVisible' => false,
                'label' => 'Source',
                'type' => 'text',
                'sortable' => true
            ),
            'severityLevel' => array(
                'initiallyVisible' => false,
                'label' => 'Severity',
                'type' => 'text',
                'sortable' => true
            ),
            'impact' => array(
                'initiallyVisible' => true,
                'label' => 'Impact',
                'sortable' => true,
                'type' => 'text'
            ),
            'category' => array(
                'initiallyVisible' => false,
                'label' => 'Category',
                'join' => array(
                    'model' => 'IrCategory',
                    'relation' => 'Category.Category',
                    'field' => 'category'
                ),
                'sortable' => true,
                'type' => 'text'
            ),
            'categoryName' => array(
                'initiallyVisible' => false,
                'label' => 'Category Name',
                'join' => array(
                    'model' => 'IrCategory',
                    'relation' => 'Category.Category',
                    'field' => 'name'
                ),
                'sortable' => true,
                'type' => 'text'
            ),
            'subcategory' => array(
                'initiallyVisible' => false,
                'label' => 'Subcategory',
                'join' => array(
                    'model' => 'IrSubCategory',
                    'relation' => 'Category',
                    'field' => 'name'
                ),
                'sortable' => true,
                'type' => 'text'
            ),
            'currentWorkflowName' => array(
                'initiallyVisible' => false,
                'label' => 'Workflow',
                'sortable' => true,
                'type' => 'text'
            ),
            'modifiedTs' => array(
                'initiallyVisible' => false,
                'label' => 'Updated',
                'sortable' => true,
                'type' => 'datetime',
                'formatter' => 'datetime'
            ),
            'piiInvolved' => array(
                'enumValues' => $this->getEnumValues('piiInvolved'),
                'initiallyVisible' => true,
                'label' => 'PII Involved?',
                'sortable' => true,
                'type' => 'enum'
            ),
            'piiAdditional' => array(
                'initiallyVisible' => false,
                'label' => 'PII Additional Information',
                'sortable' => false,
                'type' => 'text'
            ),
            'piiMobileMedia' => array(
                'initiallyVisible' => false,
                'label' => 'PII Mobile Media Involed?',
                'sortable' => true,
                'enumValues' => $this->getEnumValues('piiMobileMedia'),
                'type' => 'enum'
            ),
            'piiMobileMediaType' => array(
                'initiallyVisible' => false,
                'label' => 'PII Mobile Media Type',
                'sortable' => true,
                'type' => 'text'
            ),
            'piiEncrypted' => array(
                'initiallyVisible' => false,
                'label' => 'PII Encrypted?',
                'sortable' => true,
                'enumValues' => $this->getEnumValues('piiEncrypted'),
                'type' => 'enum'
            ),
            'piiAuthoritiesContacted' => array(
                'initiallyVisible' => false,
                'label' => 'PII Authorities Contacted?',
                'sortable' => true,
                'enumValues' => $this->getEnumValues('piiAuthoritiesContacted'),
                'type' => 'enum'
            ),
            'piiPoliceReport' => array(
                'initiallyVisible' => false,
                'label' => 'PII Police Report?',
                'sortable' => true,
                'enumValues' => $this->getEnumValues('piiPoliceReport'),
                'type' => 'enum'
            ),
            'piiIndividualsCount' => array(
                'initiallyVisible' => false,
                'label' => 'PII Individuals Count',
                'type' => 'integer'
            ),
            'piiIndividualsNotified' => array(
                'initiallyVisible' => false,
                'label' => 'PII Individuals Notified?',
                'sortable' => true,
                'enumValues' => $this->getEnumValues('piiIndividualsNotified'),
                'type' => 'enum'
            ),
            'piiShipment' => array(
                'initiallyVisible' => false,
                'label' => 'PII Shipment Involved?',
                'sortable' => true,
                'enumValues' => $this->getEnumValues('piiShipment'),
                'type' => 'enum'
            ),
            'piiShipmentSenderContacted' => array(
                'initiallyVisible' => false,
                'label' => 'PII Shipment Sender Contacted?',
                'sortable' => true,
                'enumValues' => $this->getEnumValues('piiShipmentSenderContacted'),
                'type' => 'enum'
            ),
            'piiShipmentSenderCompany' => array(
                'initiallyVisible' => false,
                'label' => 'PII Shipment Sender Company',
                'sortable' => true,
                'type' => 'text'
            ),
            'piiShipmentTimeline' => array(
                'initiallyVisible' => false,
                'label' => 'PII Shipment Timeline',
                'sortable' => false,
                'type' => 'text'
            ),
            'piiShipmentTrackingNumbers' => array(
                'initiallyVisible' => false,
                'label' => 'PII Shipment Tracking Numbers',
                'sortable' => true,
                'type' => 'text'
            ),
            'hostIp' => array(
                'initiallyVisible' => false,
                'label' => 'Host IP',
                'sortable' => true,
                'type' => 'text'
            ),
            'hostName' => array(
                'initiallyVisible' => false,
                'label' => 'Host Name',
                'sortable' => true,
                'type' => 'text'
            ),
            'hostOs' => array(
                'enumValues' => $this->getEnumValues('hostOs'),
                'initiallyVisible' => false,
                'label' => 'Host OS',
                'sortable' => true,
                'type' => 'enum'
            ),
            'hostAdditional' => array(
                'initiallyVisible' => false,
                'label' => 'Host Additional Information',
                'sortable' => false,
                'type' => 'text'
            ),
            'sourceIp' => array(
                'initiallyVisible' => false,
                'label' => 'Attacker IP',
                'sortable' => true,
                'type' => 'text'
            ),
            'sourceAdditional' => array(
                'initiallyVisible' => false,
                'label' => 'Attacker Additional Information',
                'sortable' => false,
                'type' => 'text'
            ),
            'actionsTaken' => array(
                'initiallyVisible' => false,
                'label' => 'Actions Taken',
                'sortable' => false,
                'type' => 'text'
            ),
            'jsonComments' => array(
                'initiallyVisible' => true,
                'label' => 'Comments',
                'sortable' => false,
                'type' => 'text',
                'formatter' => 'Fisma.TableFormat.formatComments'
            ),
            'closedTs' => array(
                'initiallyVisible' => false,
                'label' => 'Resolved',
                'sortable' => true,
                'type' => 'datetime',
                'formatter' => 'datetime'
            ),
            'reportingUserId' => array(
                'initiallyVisible' => false,
                'type' => 'integer',
                'hidden' => true
            ),
            'reportTz' => array(
                'initiallyVisible' => false,
                'type' => 'text',
                'hidden' => true,
                'sortable' => false
            ),
            'incidentTimezone' => array(
                'initiallyVisible' => false,
                'type' => 'text',
                'hidden' => true,
                'sortable' => false
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
        if (CurrentUser::getInstance()->acl()->hasPrivilegeForClass('read', 'Incident')) {
            // If the user has the privilege to view all incidents, then no ACL is required.
            return array();
        } else {
            // Otherwise use the IrIncidentUser join table to determine access rights
            return array('id' => 'IncidentTable::getIncidentIds');
        }
    }

    /**
     * Provide ID list for ACL filter
     *
     * @return array
     * @deprecated pending on the removal of executions from model classes
     */
    static function getIncidentIds($incidentAccessQuery = null)
    {
        $incidentAccessQuery = (isset($incidentAccessQuery)) ? $incidentAccessQuery : self::getIncidentIdsQuery();
        $results = $incidentAccessQuery->execute();
        $incidentIds = array_keys($results);
        return $incidentIds;
    }

    /**
     * Build the query for getIncidentIds
     *
     * @return Doctrine_Query
     */
    static function getIncidentIdsQuery()
    {
        $currentUser = CurrentUser::getInstance();

        $incidentAccessQuery = Doctrine_Query::create()
                               ->select('incidentId')
                               ->from('IrIncidentUser INDEXBY incidentId')
                               ->where('userId = ?', $currentUser->id)
                               ->setHydrationMode(Doctrine::HYDRATE_ARRAY);
        return $incidentAccessQuery;
    }

    /**
     * Return the query to fetch one attachment (if any) from a finding
     *
     * @param int $incidentId THe id of the Finding to get
     * @param int $attachmentId The id of the Attachment to get
     *
     * @return Doctrine_Query
     */
    public static function getAttachmentQuery($incidentId, $attachmentId)
    {
        return Doctrine_Query::create()
               ->from('Incident i')
               ->leftJoin('i.Attachments a')
               ->where('i.id = ?', $incidentId)
               ->andWhere('a.id = ?', $attachmentId);
    }
}
