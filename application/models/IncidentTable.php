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
     * @param Fisma_Zend_Acl $acl
     * @return Doctrine_Query
     */
    public function getUserIncidentQuery(User $user, Fisma_Zend_Acl $acl)
    {
        /*
         * A user can read *all* incidents if he has the "incident read" privilege. Otherwise, he is only allowed to 
         * view those incidents for which he is an actor or an observer.
         */
        $incidentQuery = Doctrine_Query::create()
                         ->from('Incident i');
        
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
                'type' => 'integer'
            ), 
            'reportTs' => array(
                'initiallyVisible' => true,
                'label' => 'Report Date',
                'sortable' => true,
                'type' => 'datetime'
            ), 
            'modifiedTs' => array(
                'initiallyVisible' => false,
                'label' => 'Modification Date',
                'sortable' => true,
                'type' => 'datetime'
            ), 
            'closedTs' => array(
                'initiallyVisible' => false,
                'label' => 'Closed Date',
                'sortable' => true,
                'type' => 'datetime'
            ), 
            'status' => array(
                'enumValues' => $this->getEnumValues('status'),
                'initiallyVisible' => true,
                'label' => 'Status',
                'sortable' => true,
                'type' => 'enum'
            ),
            'resolution' => array(
                'enumValues' => $this->getEnumValues('resolution'),
                'initiallyVisible' => true,
                'label' => 'Resolution',
                'sortable' => true,
                'type' => 'enum'
            ),
            'incidentDate' => array(
                'initiallyVisible' => false,
                'label' => 'Incident Date',
                'sortable' => true,
                'type' => 'date'
            ),
            'additionalInfo' => array(
                'initiallyVisible' => true,
                'label' => 'Description',
                'sortable' => true,
                'type' => 'text'
            ),
            'piiInvolved' => array(
                'enumValues' => $this->getEnumValues('piiInvolved'),
                'initiallyVisible' => true,
                'label' => 'PII Involved',
                'sortable' => true,
                'type' => 'enum'
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
            'sourceIp' => array(
                'initiallyVisible' => false,
                'label' => 'Source IP',
                'sortable' => true,
                'type' => 'text'
            )
        );
    }
}
