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
 * Insert update legacy finding key privilege
 * 
 * @author     Ben Zheng <benzheng@users.sourceforge.net>
 * @copyright  (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Migration
 * @version    $Id$
 */
class Version75 extends Doctrine_Migration_Base
{
    /**
     * Insert privilege
     */
    public function up()
    {
        $updateLegacyFindingkey = new Privilege();
        $updateLegacyFindingkey->resource = 'finding';
        $updateLegacyFindingkey->action = 'update_legacy_finding_key';
        $updateLegacyFindingkey->description = 'Update Legacy Finding Key';
        $updateLegacyFindingkey->save();
        
        // Assign update legacy finding key privileges to any role which has the update finding privilege
        $updateFindingRolesQuery = Doctrine_Query::create()
                                      ->from('Role r')
                                      ->innerJoin('r.Privileges p')
                                      ->where('p.resource = ? AND p.action like ?', array('finding', 'update_%'));

        $updateFindingRoles = $updateFindingRolesQuery->execute();
        
        foreach ($updateFindingRoles as $updateFindingRole) {
            $updateFindingRole->link('Privileges', array( $updateLegacyFindingkey->id));
        }
        
        $updateFindingRoles->save();
    }

    /**
     * Remove privileges 
     */
    public function down()
    {
        // Delete privilege
        $privilegeQuery = Doctrine_Query::create()
                          ->from('Privilege')
                          ->where('resource = ? AND action = ?', array('finding', 'update_legacy_finding_key'));
        
        $findingPrivileges = $privilegeQuery->execute();
        
        // Delete any associations those privileges have to roles
        $deleteRolePrivilegesQuery = Doctrine_Query::create()
                                     ->delete('RolePrivilege')
                                     ->whereIn('privilegeid', $findingPrivileges->getPrimaryKeys());

        $deleteRolePrivilegesQuery->execute();
        
        // Delete the privileges themselves
        $findingPrivileges->delete();
    }
}
