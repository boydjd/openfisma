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
 * Add metadata for points of contact feature
 *
 * @uses Doctrine_Migration_Base
 * @package Migration
 * @copyright (c) Endeavor Systems, Inc. 2011 {@link http://www.endeavorsystems.com}
 * @author Andrew Reeves <andrew.reeves@endeavorsystems.com>
 * @license http://www.openfisma.org/content/license GPLv3
 */
class Version120 extends Doctrine_Migration_Base
{
    /**
     * Add POC metadata
     */
    public function up()
    {
        $conn = Doctrine_Manager::connection()->beginTransaction();

        // Get a reference to the admin notification privilege (for creating the notification events below)
        $adminNotificationParams = array('notification', 'admin');
        $adminNotificationPrivileges = Doctrine::getTable('Privilege')
                                       ->findByDql('WHERE resource = ? AND action = ?', $adminNotificationParams);

        if (count($adminNotificationPrivileges) != 1) {
            throw new Fisma_Exception("Not able to find the admin notification privilege.");
        }

        $adminNotificationPrivilege = $adminNotificationPrivileges[0];

        // Now create notification events
        $pocCreateEvent = new Event();
        $pocCreateEvent->merge(array(
            'name' => 'POC_CREATED',
            'description' => 'POC Created',
        ));
        $pocCreateEvent->Privilege = $adminNotificationPrivilege;
        $pocCreateEvent->save();

        $pocUpdateEvent = new Event();
        $pocUpdateEvent->merge(array(
            'name' => 'POC_UPDATED',
            'description' => 'POC Modified',
        ));
        $pocUpdateEvent->Privilege = $adminNotificationPrivilege;
        $pocUpdateEvent->save();

        $pocDeleteEvent = new Event();
        $pocDeleteEvent->merge(array(
            'name' => 'POC_DELETED',
            'description' => 'POC Deleted',
        ));
        $pocDeleteEvent->Privilege = $adminNotificationPrivilege;
        $pocDeleteEvent->save();

        // Create new privileges
        $findingUpdatePocPrivilege = new Privilege();
        $findingUpdatePocPrivilege->merge(array(
            'resource' => 'finding',
            'action' => 'update_poc',
            'description' => 'Update Finding Point Of Contact'
        ));
        $findingUpdatePocPrivilege->save();

        $pocCreatePrivilege = new Privilege();
        $pocCreatePrivilege->merge(array(
            'resource' => 'poc',
            'action' => 'create',
            'description' => 'Create Points of Contact'
        ));
        $pocCreatePrivilege->save();

        $pocReadPrivilege = new Privilege();
        $pocReadPrivilege->merge(array(
            'resource' => 'poc',
            'action' => 'read',
            'pocReadPrivilege' => 'View Points of Contact'
        ));
        $pocReadPrivilege->save();

        $pocUpdatePrivilege = new Privilege();
        $pocUpdatePrivilege->merge(array(
            'resource' => 'poc',
            'action' => 'update',
            'description' => 'Edit Points of Contact'
        ));
        $pocUpdatePrivilege->save();

        $pocDeletePrivilege = new Privilege();
        $pocDeletePrivilege->merge(array(
            'resource' => 'poc',
            'action' => 'delete',
            'description' => 'Delete Points of Contact'
        ));
        $pocDeletePrivilege->save();

        $conn = Doctrine_Manager::connection()->commit();
    }

    /**
     * Remove POC metadata
     */
    public function down()
    {
        $conn = Doctrine_Manager::connection()->beginTransaction();

        // Remove events (they all start with POC_)
        Doctrine_Query::create()->delete('Event e')->where('e.name like ?', array('POC_%'))->execute();

        // Get IDs of privileges we want to drop
        $privilegeIds = Doctrine_Query::create()
                                      ->select('p.id')
                                      ->from('Privilege p')
                                      ->where('p.resource like ? AND p.action like ?', array('finding', 'update_poc'))
                                      ->orWhere('p.resource like ?', 'poc')
                                      ->execute()
                                      ->toKeyValueArray('id', 'id');

        // Remove privileges (and any connected records, too)
        Doctrine_Query::create()->delete('RolePrivilege rp')->whereIn('rp.privilegeId', $privilegeIds)->execute();
        Doctrine_Query::create()->delete('Privilege p')->whereIn('p.id', $privilegeIds)->execute();

        $conn = Doctrine_Manager::connection()->commit();
    }
}
