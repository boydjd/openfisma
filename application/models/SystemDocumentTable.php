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
 * SystemDocumentTable 
 * 
 * @uses Fisma_Doctrine_Table
 * @package Model 
 * @copyright (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @author Josh Boyd <joshua.boyd@endeavorsystems.com> 
 * @license http://www.openfisma.org/content/license GPLv3
 */
class SystemDocumentTable extends Fisma_Doctrine_Table implements Fisma_Search_Searchable
{
    /**
     * Implement the interface for Searchable
     */
    public function getSearchableFields()
    {
        return array (
            'organization' => array(
                'initiallyVisible' => true,
                'label' => 'Organization',
                'join' => array(
                    'model' => 'Organization',
                    'relation' => 'System.Organization',
                    'field' => 'nickname'
                ),
                'sortable' => true,
                'type' => 'text'
            ),
            'documentType' => array(
                'initiallyVisible' => true,
                'label' => 'Document Type',
                'join' => array(
                    'model' => 'DocumentType',
                    'relation' => 'DocumentType',
                    'field' => 'name'
                ),
                'sortable' => true,
                'type' => 'text'
            ),
            'version' => array(
                'initiallyVisible' => true,
                'label' => 'Current Version',
                'sortable' => true,
                'type' => 'integer'
            ),
            'description' => array(
                'initiallyVisible' => true,
                'label' => 'Version Notes',
                'sortable' => false,
                'type' => 'text'
            ),
            'size' => array(
                'initiallyVisible' => true,
                'label' => 'Size (bytes)',
                'sortable' => true,
                'type' => 'integer'
            ),
            'createdTs' => array(
                'initiallyVisible' => true,
                'label' => 'Last Modification Date',
                'sortable' => true,
                'type' => 'date'
            ),
            'lastModifiedUser' => array(
                'initiallyVisible' => true,
                'label' => 'Last Modified By User',
                'join' => array(
                    'model' => 'User',
                    'relation' => 'User',
                    'field' => 'username'
                ),
                'sortable' => true,
                'type' => 'text'
            ),
            'systemId' => array(
                'hidden' => true,
                'join' => array(
                    'model' => 'System',
                    'relation' => 'System',
                    'field' => 'id'
                ),
                'type' => 'integer'
            )
        );
    }

    /**
     * Implement required interface, but there is no field-level ACL in this model
     *
     * @return array
     */
    public function getAclFields()
    {
        return array('systemId' => 'SystemTable::getSystemIds');
    }

    /*
     * Get an array of system documents including system id, nickname - name, uploaded required document
     * and percentage
     * 
     * @return array
     */
    public function getSystemDocumentReportDataQuery()
    {
        $baseQuery = CurrentUser::getInstance()->getOrganizationsByPrivilegeQuery('organization', 'read');

        $docTypeRequiredCount = Doctrine::getTable('DocumentType')->getAllRequiredDocumentTypeQuery()->count();

        // Get data for the report
        $systemDocumentQuery = $baseQuery
                               ->select('s.id As id')
                               ->addSelect("CONCAT(o.nickname, ' - ', o.name) AS name")
                               ->addSelect(
                                   "ROUND(SUM(IF(dt.required = true, 1, 0)) / "
                                   . "($docTypeRequiredCount)*100, 1) AS percentage"
                               )
                               ->addSelect("IFNULL(GROUP_CONCAT(dt.name), 'N/A') AS uploadedRequiredDocument")
                               ->leftJoin('o.System s')
                               ->leftJoin('s.Documents sd')
                               ->leftJoin('sd.DocumentType dt')
                               ->andWhere('o.orgType = ?', array('system'))
                               ->andWhere('s.sdlcPhase <> ?', 'disposal')
                               ->groupBy('o.nickname')
                               ->orderBy('o.nickname')
                               ->setHydrationMode(Doctrine::HYDRATE_SCALAR);

        return $systemDocumentQuery;
    }
}
