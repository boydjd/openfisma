<?php
/**
 * Copyright (c) 2008 Endeavor Systems, Inc.
 *
 * This file is part of OpenFISMA.
 *
 * OpenFISMA is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * OpenFISMA is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with OpenFISMA.  If not, see {@link http://www.gnu.org/licenses/}.
 *
 * @author    Josh Boyd <joshua.boyd@endeavorsystems.com 
 * @copyright (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @license   http://openfisma.org/content/license
 * @version   $Id$
 * @package   Migration
 */

/**
 * Add account log read privilege to ADMIN
 *
 * @package    Migration
 * @copyright  (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/mw/index.php?title=License
 */
class AddAccountLogReadToAdmin extends Doctrine_Migration_Base
{
    /**
     * Add privileges 
     */
    public function up()
    {
        $privilege = new Privilege();
        $privilege->resource = 'account_log';
        $privilege->action = 'read';
        $privilege->description = 'Account Audit Logs';
        $privilege->orgSpecific = 0;
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
     * _getPrivilege 
     * 
     * @access private
     * @return array
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
     * _getRole 
     * 
     * @access private
     * @return array 
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
     * _deleteRolePrivilege 
     * 
     * @param mixed $role 
     * @param mixed $privilege 
     * @access private
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
