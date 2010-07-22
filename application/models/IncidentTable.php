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
 * @uses Doctrine_Table
 * @package Models 
 * @copyright (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @author Josh Boyd <joshua.boyd@endeavorsystems.com> 
 * @license http://www.openfisma.org/content/license GPLv3
 */
class IncidentTable extends Doctrine_Table
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
}
