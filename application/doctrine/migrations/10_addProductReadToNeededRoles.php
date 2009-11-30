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
 * Add product read privilege to IV&V and SAISO 
 *
 * @package    Migration
 * @copyright  (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/mw/index.php?title=License
 */
class AddProductReadToNeededRoles extends Doctrine_Migration_Base
{
    /**
     * Add privileges 
     * 
     * @param $direction either 'up' or 'down'
     */
    public function up()
    {
        $privilege = $this->_getPrivilege();
        $role      = $this->_getRole();

        foreach ($role as $r) {
            $this->_deleteRolePrivilege($r['id'], $privilege[0]['id']);

            $rolePrivilege = new RolePrivilege();
            $rolePrivilege->roleId = $r['id'];
            $rolePrivilege->privilegeId = $privilege[0]['id'];
            $rolePrivilege->save();
        }
    }
    
    /**
     * Remove privileges 
     * 
     * @param $direction either 'up' or 'down'
     */
    public function down()
    {
        $privilege = $this->_getPrivilege();
        $role      = $this->_getRole();

        foreach ($role as $r) {
            $this->_deleteRolePrivilege($r['id'], $privilege[0]['id']);
        }
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
                     ->where('resource = ?', 'product')
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
                ->whereIn('nickname', array('SAISO', 'IV&amp;V'))
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
