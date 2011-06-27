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
 * Add a privilege to control the role definition, then assign this privilege to ADMIN.
 *
 * @author     Ryan yang <ryanyang@users.sourceforge.net>
 * @copyright  (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Migration
 */
class Version1 extends Doctrine_Migration_Base
{
    /**
     * Implement the doing changes, create the privilege "assignPrivileges" and assign ti to ADMIN
     * 
     * @return void
     */
    public function up()
    {
        $query = Doctrine_Query::create()
                    ->from('Privilege p')
                    ->where('resource = ? AND action = ?', array('role', 'assignPrivileges')); 
        $privilege = $query->fetchOne();

        if (empty($privilege)) {
            $privilege = new Privilege();
            $privilege->resource    = 'role';
            $privilege->action      = 'assignPrivileges';
            $privilege->description = 'Assign Privileges';
            $privilege->save();
        }

        $adminRole     = Doctrine::getTable('Role')->findOneByNickname('ADMIN');
        if (empty($adminRole)) {
            /** @todo english */
            print ("Role 'ADMIN' is not exist. Migration can't be executed until the 'ADMIN' is back."
                   ." Please revert the nickname of 'administrator' to 'ADMIN' \n");
            return;
        }

        $query = Doctrine_Query::create()
                    ->from('RolePrivilege rp')
                    ->where('roleId = ? AND privilegeId = ?', array($adminRole->id, $privilege->id));
        $assignRolePrivilege = $query->fetchOne();
        if (empty($rolePrivilege)) {
            $adminRole->Privileges[]   = $privilege;
            $adminRole->save();
        }
    }
    
    /**
     * Implement the undoing changes, Remove the privilege "assignPrivileges" from ADMIN
     * and delete it from privileges table
     * 
     * @return void
     */
    public function down()
    {
        $query = Doctrine_Query::create()
                    ->from('Privilege p')
                    ->where('resource = ? AND action = ?', array('role', 'assignPrivileges')); 
        $privilege = $query->fetchOne();
        if ($privilege) {
            $deleteRolePrivilege = Doctrine_Query::create()
                                    ->delete('RolePrivilege')
                                    ->where('privilegeId = ?', $privilege->id); 
            $deleteRolePrivilege->execute(); 
            $privilege->delete();
        }
    }
}

