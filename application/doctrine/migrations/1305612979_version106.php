<?php
/**
 * Copyright (c) 2011 Endeavor Systems, Inc.
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
 * Remove approve finding and reject incident privilege
 *
 * @package   Migration
 * @copyright (c) Endeavor Systems, Inc. 2011 {@link http://www.endeavorsystems.com}
 * @author    Ben Zheng <ben.zheng@reyosoft.com>
 * @license   http://www.openfisma.org/content/license GPLv3
 */
class Version106 extends Doctrine_Migration_Base
{

    /**
     * Remove unused privileges
     */
    public function up()
    {
        // Remove finding approve privilege
        $this->_removePrivilege('finding', 'approve');

        // Remove incident reject privilege
        $this->_removePrivilege('incident', 'reject');
    }

    /**
     * Add privilege
     */
    public function down()
    {
        // Add approve finding privilege and ssign it to admin, iv&v and saiso role
        $approveFindingPrivilege = array('finding', 'approve', 'Approve Pending Findings');
        $approveFindingRoles = array('ADMIN', 'IV&V', 'SAISO');
        $this->_addPrivilege($approveFindingPrivilege, $approveFindingRoles);

        // Add reject incident privilege and assign it to admin and iso role
        $rejectIncidentPrivilege = array('incident', 'reject', 'Reject Incident');
        $rejectIncidentRoles = array('ADMIN', 'IRC');
        $this->_addPrivilege($rejectIncidentPrivilege, $rejectIncidentRoles);
    }

    /**
     * Remove privilege and role privilege
     * 
     * @param string $resource
     * @param string $action
     * @return void
     */
    private function _removePrivilege($resource, $action)
    {
        $privilege = Doctrine::getTable('Privilege')->getResourceActionQuery($resource, $action)->fetchOne();

        $privilege->unlink('Roles');
        $privilege->save();
        $privilege->delete();
    }

    /**
     * Add privilege and assign it to roles
     * 
     * @param array $privilegeArray
     * @param array $roles
     * @return void
     */
    private function _addPrivilege($privilegeArray, $roles)
    {
        // Add a privilege
        $privilege = new Privilege();
        $privilege->resource = $privilegeArray[0];
        $privilege->action = $privilegeArray[1];
        $privilege->description = $privilegeArray[2];
        $privilege->save();

        // Assign this privilege to roles
        $roles = Doctrine_Query::create()
                 ->from('Role r')
                 ->whereIn('r.nickname', $roles)
                 ->execute();

        $privilege->Roles->merge($roles);
        $privilege->save();
    }
}
