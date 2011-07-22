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
 * Insert CRUD document type privileges, 'required' column and relative events
 * 
 * @author     Ben Zheng <ben.zheng@reyosoft.com>
 * @copyright  (c) Endeavor Systems, Inc. 2010 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Migration
 */
class Version77 extends Doctrine_Migration_Base
{
    /**
     * Insert privileges, column and events
     */
    public function up()
    {
        // Add unique for name column
        $definition = array(
            'fields' => array(
                'name' => array()
            ),
            'unique' => true
        );

        $this->createConstraint('document_type', 'name', $definition);

        // Add required column for document type
        $options = array(
            'notblank' => true,
            'comment' => 'Indicates whether the document type is required or not',
            'default' => '0',
            'extra' => array(
              'notify' => '1'
            )
        );
        $this->addColumn('document_type', 'required', 'boolean', '25', $options);

        $privileges = new Doctrine_Collection('Privilege');

        $docTypeCreate = new Privilege();
        $docTypeCreate->resource = 'document_type';
        $docTypeCreate->action = 'create';
        $docTypeCreate->description = 'Create Document Type';
        $privileges[] = $docTypeCreate;

        $docTypeRead = new Privilege();
        $docTypeRead->resource = 'document_type';
        $docTypeRead->action = 'read';
        $docTypeRead->description = 'View Document Type';
        $privileges[] = $docTypeRead;

        $docTypeUpdate = new Privilege();
        $docTypeUpdate->resource = 'document_type';
        $docTypeUpdate->action = 'update';
        $docTypeUpdate->description = 'Edit Document Type';
        $privileges[] = $docTypeUpdate;

        $docTypeDelete = new Privilege();
        $docTypeDelete->resource = 'document_type';
        $docTypeDelete->action = 'delete';
        $docTypeDelete->description = 'Delete Document Type';
        $privileges[] = $docTypeDelete;

        $privileges->save();

        // Assign CRUD for document type privileges to admin role
        $adminRole = Doctrine_Query::create()
                     ->from('Role r')
                     ->where('r.nickname = ?', 'ADMIN')
                     ->fetchOne();

        foreach ($privileges as $privilege) {
            $adminRole->Privileges[] = $privilege;
        }

        $adminRole->save();

        // Add event
        $events = new Doctrine_Collection('Event');

        $adminNoticationPrivilege = Doctrine_Query::create()
                                    ->from('Privilege p')
                                    ->where('p.resource = ? AND p.action = ?', array('notification', 'admin'))
                                    ->fetchOne();

        $docTypeCreatedEvent = new Event();
        $docTypeCreatedEvent->name = 'DOCUMENT_TYPE_CREATED';
        $docTypeCreatedEvent->description = 'Document Type Created';
        $docTypeCreatedEvent->Privilege = $adminNoticationPrivilege;
        $events[] = $docTypeCreatedEvent;

        $docTypeUpdatedEvent = new Event();
        $docTypeUpdatedEvent->name = 'DOCUMENT_TYPE_UPDATED';
        $docTypeUpdatedEvent->description = 'Document Type Modified';
        $docTypeUpdatedEvent->Privilege = $adminNoticationPrivilege;
        $events[] = $docTypeUpdatedEvent;

        $docTypeDeletedEvent = new Event();
        $docTypeDeletedEvent->name = 'DOCUMENT_TYPE_DELETED';
        $docTypeDeletedEvent->description = 'Document Type Deleted';
        $docTypeDeletedEvent->Privilege = $adminNoticationPrivilege;
        $events[] = $docTypeDeletedEvent;

        $events->save();
    }

    /**
     * Remove privileges, column and events
     */
    public function down()
    {
        // Remove contraint name
        $this->dropConstraint('document_type', 'name');
        
        // Remove column
        $this->removeColumn('document_type', 'required');

        // Delete privilege
        $privilegeQuery = Doctrine_Query::create()
                          ->from('Privilege')
                          ->where('resource = ?', 'document_type')
                          ->andWhereIn('action', array('create', 'read', 'update', 'delete'));

        $docTypePrivileges = $privilegeQuery->execute();

        // Delete any associations those privileges have to roles
        $deleteRolePrivilegesQuery = Doctrine_Query::create()
                                     ->delete('RolePrivilege')
                                     ->whereIn('privilegeid', $docTypePrivileges->getPrimaryKeys());

        $deleteRolePrivilegesQuery->execute();

        // Delete the privileges themselves
        $docTypePrivileges->delete();

        // Delete events
        $events = Doctrine_Query::create()
                  ->from('Event')
                  ->whereIn(
                      'name',
                      array('DOCUMENT_TYPE_CREATED', 'DOCUMENT_TYPE_UPDATED', 'DOCUMENT_TYPE_DELETED')
                  )
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
    }
}
