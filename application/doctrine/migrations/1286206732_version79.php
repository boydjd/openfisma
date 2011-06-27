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
 * Insert update legacy finding key and finding source privileges and events
 * 
 * @author     Ben Zheng <benzheng@users.sourceforge.net>
 * @copyright  (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Migration
 */
class Version79 extends Doctrine_Migration_Base
{
    /**
     * Insert privilege and event
     */
    public function up()
    {
        $conn = Doctrine_Manager::connection();

        $conn->beginTransaction();
        
        try {
            $privileges = new Doctrine_Collection('Privilege');

            $updateLegacyFindingkey = new Privilege();
            $updateLegacyFindingkey->resource = 'finding';
            $updateLegacyFindingkey->action = 'update_legacy_finding_key';
            $updateLegacyFindingkey->description = 'Update Legacy Finding Key';
            $privileges[] = $updateLegacyFindingkey;

            $updateFindingSource = new Privilege();
            $updateFindingSource->resource = 'finding';
            $updateFindingSource->action = 'update_finding_source';
            $updateFindingSource->description = 'Update Finding Source';
            $privileges[] = $updateFindingSource;

            $privileges->save();

            // Assign update legacy finding key and source privileges to any role which has the update finding privilege
            $updateFindingRolesQuery = Doctrine_Query::create()
                                       ->from('Role r')
                                       ->innerJoin('r.Privileges p')
                                       ->where('p.resource = ? AND p.action like ?', array('finding', 'update_%'));

            $updateFindingRoles = $updateFindingRolesQuery->execute();
    
            foreach ($updateFindingRoles as $updateFindingRole) {
                $updateFindingRole->link('Privileges', array($updateLegacyFindingkey->id, $updateFindingSource->id));
            }

            $updateFindingRoles->save();
    
            $events = new Doctrine_Collection('Event');

            $findingNotication = Doctrine_Query::create()
                                 ->from('Privilege p')
                                 ->where('p.resource = ? AND p.action = ?', array('notification', 'finding'))
                                 ->fetchOne();

            // Add update legacy finding key and update finding source to event
            $updateLegacyFindingkeyEvent = new Event();
            $updateLegacyFindingkeyEvent->name = 'UPDATE_LEGACY_FINDING_KEY';
            $updateLegacyFindingkeyEvent->description = 'Legacy Finding Key For Finding Updated';
            $updateLegacyFindingkeyEvent->Privilege = $findingNotication;
            $events[] = $updateLegacyFindingkeyEvent;

            $updateFindingSourceEvent = new Event();
            $updateFindingSourceEvent->name = 'UPDATE_FINDING_SOURCE';
            $updateFindingSourceEvent->description = 'Source For Finding Updated';
            $updateFindingSourceEvent->Privilege = $findingNotication;
            $events[] = $updateFindingSourceEvent;
    
            $events->save();

            $conn->commit();
        } catch (Doctrine_Exception $e) {
            $conn->rollback();

            throw $e;
        }
    }

    /**
     * Remove privileges and events
     */
    public function down()
    {
        $conn = Doctrine_Manager::connection();

        $conn->beginTransaction();

        try {
            // Delete privilege
            $privilegeQuery = Doctrine_Query::create()
                              ->from('Privilege')
                              ->where('resource = ?', 'finding')
                              ->andWhereIn('action', array('update_legacy_finding_key', 'update_finding_source'));

            $findingPrivileges = $privilegeQuery->execute();

            // Delete any associations those privileges have to roles
            $deleteRolePrivilegesQuery = Doctrine_Query::create()
                                         ->delete('RolePrivilege')
                                         ->whereIn('privilegeid', $findingPrivileges->getPrimaryKeys());

            $deleteRolePrivilegesQuery->execute();

            // Delete the privileges themselves
            $findingPrivileges->delete();

            // Delete events
            $events = Doctrine_Query::create()
                      ->from('Event')
                      ->whereIn('name', array('UPDATE_LEGACY_FINDING_KEY', 'UPDATE_FINDING_SOURCE'))
                      ->execute();

            // Delete any associations those events have to users
            $deleteUserEventsQuery = Doctrine_Query::create()
                                    ->delete('UserEvent')
                                    ->whereIn('eventid', $events->getPrimaryKeys());

            $deleteUserEventsQuery->execute();

            // Delete any associations those events have to notifications
            $deleteNotificationsQuery = Doctrine_Query::create()
                                       ->delete('Notification')
                                       ->whereIn('eventid', $events->getPrimaryKeys());

            $deleteNotificationsQuery->execute();

            // Delete the events themselves
            $events->delete();

            $conn->commit();
        } catch (Doctrine_Exception $e) {
            $conn->rollback();

            throw $e;
        }
    }
}
