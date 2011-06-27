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
 * Add account log read privilege to ADMIN
 *
 * @author     Josh Boyd <joshua.boyd@endeavorsystems.com
 * @copyright  (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Migration
 */
class Version12 extends Doctrine_Migration_Base
{
    /**
     * Add privileges
     * 
     * @return void
     */
    public function up()
    {
        $privilege = new Privilege();
        $privilege->resource = 'account_log';
        $privilege->action = 'read';
        $privilege->description = 'Account Audit Logs';
        $privilege->save();

        $role      = $this->_getRole();

        foreach ($role as $r) {
            $this->_deleteRolePrivilege($r['id'], $privilege->id);

            $rolePrivilege = new RolePrivilege();
            $rolePrivilege->roleId = $r['id'];
            $rolePrivilege->privilegeId = $privilege->id;
            $rolePrivilege->save();
        }
    }
    
    /**
     * Remove privileges
     * 
     * @return void
     */
    public function down()
    {
        $privilege = $this->_getPrivilege();
        $role      = $this->_getRole();

        foreach ($role as $r) {
            $this->_deleteRolePrivilege($r['id'], $privilege[0]['id']);
        }

        $privilege = Doctrine::getTable('Privilege')->find($privilege[0]['id']);
        $privilege->delete();
    }

    /**
     * Retrieve privileges with the specified resource and action condition
     * 
     * @return array The array of the condition matched privileges
     */
    private function _getPrivilege() 
    {
        $privilege = Doctrine_Query::create()
                     ->from('Privilege')
                     ->where('resource = ?', 'account_log')
                     ->andWhere('action = ?', 'read')
                     ->fetchArray();

        return $privilege;
    }

    /**
     * Retrieve roles with the specified nickname condition
     * 
     * @return array The array of the condition matched roles
     */
    private function _getRole() 
    {
        $role = Doctrine_Query::create()
                ->from('Role')
                ->whereIn('nickname', array('ADMIN'))
                ->fetchArray();

        return $role;
    }

    /**
     * Delete the relation of between the specified role and privilege
     * 
     * @param int $role The specified role id
     * @param int $privilege The specified privilege id
     * @return void
     */
    private function _deleteRolePrivilege($role, $privilege) 
    {
        $q = Doctrine_Query::create()
             ->delete('RolePrivilege')
             ->where('roleId = ?', $role)
             ->andWhere('privilegeId = ?', $privilege)
             ->execute();
    }
}
