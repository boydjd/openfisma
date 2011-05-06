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
 * UserRoleTable 
 * 
 * @uses Fisma_Doctrine_Table
 * @package Model 
 * @copyright (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @author Josh Boyd <joshua.boyd@endeavorsystems.com> 
 * @license http://www.openfisma.org/content/license GPLv3
 */
class UserRoleTable extends Fisma_Doctrine_Table
{
    /**
     * getRolesAndUsersByOrganizationIdQuery 
     * 
     * @param mixed $organizationId 
     * @access public
     * @return void
     */
    public function getRolesAndUsersByOrganizationIdQuery($organizationId)
    {
        return Doctrine_Query::create()
              ->from('Role r')
              ->innerJoin('r.UserRole ur')
              ->innerJoin('ur.Organizations o WITH o.id = ?', $organizationId)
              ->innerJoin('ur.User u');
    }

    /**
     * getByUserIdAndRoleIdQuery 
     * 
     * @param mixed $userId 
     * @param mixed $roleId 
     * @access public
     * @return void
     */
    public function getByUserIdAndRoleIdQuery($userId, $roleId)
    {
        return Doctrine_Query::create()
               ->from('UserRole ur')
               ->where('ur.userId = ?', $userId)
               ->andWhere('ur.roleId = ?', $roleId);
    }
}
