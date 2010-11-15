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
class SystemDocumentTable extends Fisma_Doctrine_Table
{
    /**
     * Get an array of system documents including system id, name and percentage
     * 
     * @return array
     */
    public function getSystemDocuments()
    {
        $organizationIds = CurrentUser::getInstance()
                           ->getOrganizationsByPrivilege('organization', 'read')
                           ->toKeyValueArray('id', 'id');

        $docTypeRequiredCount = Doctrine::getTable('DocumentType')->getRequiredDocTypeCount();

        // Get data for the report
        $systemDocumentQuery = Doctrine_Query::create()
                               ->select('s.id As id')
                               ->addSelect('o.name AS name')
                               ->addSelect(
                                   "CONCAT(ROUND(SUM(IF(dt.required = true, 1, 0)) / "
                                   . "($docTypeRequiredCount)*100, 1), '%') AS percentage"
                               )
                               ->from('SystemDocument sd')
                               ->innerJoin('sd.DocumentType dt')
                               ->innerJoin('sd.System s')
                               ->innerJoin('s.Organization o')
                               ->whereIn('o.id', $organizationIds)
                               ->andWhere('o.orgType = ?', array('system'))
                               ->andWhere('s.sdlcPhase <> ?', 'disposal')
                               ->andWhere('dt.required = ?', true)
                               ->groupBy('o.name')
                               ->setHydrationMode(Doctrine::HYDRATE_SCALAR);

        return $systemDocumentQuery->execute();
    }
}
