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
 * A common ancestor for all incident controllers which provides some shared logic
 * 
 * @author     Mark E. Haase
 * @copyright  (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Controllers
 * @version    $Id$
 */
class IncidentBaseController extends SecurityController
{
    /**
     * Returns a query which matches all of the current user's viewable incidents
     * 
     * @return Doctrine_Query
     */
    protected function _getUserIncidentQuery() 
    {
        
        /*
         * A user can read *all* incidents if he has the "incident read" privilege. Otherwise, he is only allowed to 
         * view those incidents for which he is an actor or an observer.
         */
        $incidentQuery = Doctrine_Query::create()
                         ->from('Incident i');
        
        if (!$this->_acl->hasPrivilegeForClass('read', 'Incident')) {
            $incidentQuery->leftJoin('i.Users u')
                          ->where('u.id = ?', CurrentUser::getInstance()->id);
        }

        return $incidentQuery;
    }
}
