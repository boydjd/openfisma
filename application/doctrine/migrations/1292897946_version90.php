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
 * Assign incident_lock privilege to administrator role
 * 
 * @package Migration
 * @copyright (c) Endeavor Systems, Inc. 2010 {@link http://www.endeavorsystems.com}
 * @author Mark Ma <mark.ma@reyosoft.com>
 * @license http://www.openfisma.org/content/license GPLv3
 */
class Version90 extends Doctrine_Migration_Base
{
    /**
     * Insert admin role id and privilege id to role_privilege table
     */
    public function up()
    {
        $conn = Doctrine_Manager::connection();
        
        $conn->beginTransaction();
        
        try {
            $adminRole = Doctrine_Query::create()
                         ->from('Role r')
                         ->where('nickname = ?', 'ADMIN')
                         ->fetchOne();
            
            $incidentlock = Doctrine_Query::create()
                            ->from('Privilege p')
                            ->where('resource = ? and action = ?', array('incident', 'lock'))
                            ->fetchOne();

            $adminRole->Privileges[] = $incidentlock;
            $adminRole->save();
            $conn->commit();

        } catch (Doctrine_Exception $e){ 
            $conn->rollback();
            
            throw $e;
        }
    }

    /**
     * Remove admin role id and privilege id from role_privilege table 
     */
    public function down()
    {
        $conn = Doctrine_Manager::connection();
        
        $conn->beginTransaction();
        
        try {
            $admin = Doctrine_Query::create()
                     ->from('Role r')
                     ->where('nickname = ? and name = ?', array('admin','Application Administrator'))
                     ->fetchOne();
            
            $incidentlock = Doctrine_Query::create()
                            ->from('Privilege p')
                            ->where('resource = ? and action = ?', array('incident', 'lock'))
                            ->fetchOne();
            
            $removeincidentlockQuery = Doctrine_Query::create()
                                       ->delete('RolePrivilege')
                                       ->where('privilegeId = ? and roleId = ?', array($incidentlock->id, $admin->id));

            $removeincidentlockQuery->execute(); 
            $conn->commit();
        } catch (Doctrine_Exception $e) {
            $conn->rollback();
            
            throw $e;
        }
    }
}
