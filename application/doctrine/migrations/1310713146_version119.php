<?php
// @codingStandardsIgnoreFile
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
 * Insert CRUD organization type privileges and relative events.
 * Add orgtypeid column and foreign key to organization table
 * 
 * @author     Ben Zheng <ben.zheng@reyosoft.com>
 * @copyright  (c) Endeavor Systems, Inc. 2011 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Migration
 */
class Version119 extends Doctrine_Migration_Base
{
    /**
     * Add organization_type table, organization orgtypeid column and foreign key
     * 
     * @return void
     */
    public function up()
    {
        $this->createTable('organization_type', array(
            'id' => 
            array(
                'type' => 'integer',
                'length' => '8',
                'autoincrement' => '1',
                'primary' => '1',
            ),
            'name' => 
            array(
                'type' => 'string',
                'length' => '255',
                'notblank' => true,
                'notnull' => true,
                'extra' => 
                array(
                    'notify' => true
                ),
            ),
            'nickname' => 
            array(
                'type' => 'string',
                'length' => '255',
                'notblank' => true,
                'notnull' => true,
                'extra' => 
                array(
                    'notify' => true
                ),
            ),
            'icon' => 
            array(
                'type' => 'string',
                'length' => '255',
                'extra' => 
                array(
                    'purify' => 'html',
                    'notify' => true
                ),
                'comment' => 'Icon of organization types (agency, bureau, organization)'
            ),
            'description' => 
            array(
                'type' => 'string',
                'extra' => 
                array(
                    'purify' => 'html',
                    'notify' => true
                ),
                'length' => '',
            ),
            'createdts' => 
            array(
                'notnull' => '1',
                'type' => 'timestamp',
                'length' => '25',
            ),
            'modifiedts' => 
            array(
                'notnull' => '1',
                'type' => 'timestamp',
                'length' => '25',
            ),
            ), array(
             'indexes' =>
             array(
                 'nicknameIndex' => array('fields' => array(0 => 'nickname'), 'type' => 'unique')
             ),
             'primary' => 
             array(
                  0 => 'id',
             ),
        ));

        // Add orgtypeid column to organization table
        $this->addColumn('organization', 'orgtypeid', 'integer', '8', array(
            'comment' => 'Foreign key to organization type table',
        ));

        // Add foreign key for orgtypeid column in organization table
        $this->createForeignKey('organization', 'organization_orgtypeid_organization_type_id', array(
              'name' => 'organization_orgtypeid_organization_type_id',
              'local' => 'orgtypeid',
              'foreign' => 'id',
              'foreignTable' => 'organization_type',
              ));
    }

    /*
     * Use postUp to migrate data from old column to new column.
     * Add privileges and event for organization type
     */
    public function postUp()
    {
        $conn = Doctrine_Manager::connection();
        $conn->beginTransaction();
        try {
            $this->_addOrganizationTypes();

            $this->_addPrivileges();

            $this->_addEvents();

            $conn->commit();
        } catch (Doctrine_Exception $e) {
            $conn->rollback();

            throw $e;
        }
    }

    /**
     * Irreversible migration 
     * 
     * @return void
     */
    public function down()
    {
        throw new Doctrine_Migration_IrreversibleMigrationException();
    }

    /*
     * Add organization type privileges
     */
    private function _addPrivileges()
    {
        $privileges = new Doctrine_Collection('Privilege');

        $orgTypeCreate = new Privilege();
        $orgTypeCreate->resource = 'organization_type';
        $orgTypeCreate->action = 'create';
        $orgTypeCreate->description = 'Create Organization Type';
        $privileges[] = $orgTypeCreate;

        $orgTypeRead = new Privilege();
        $orgTypeRead->resource = 'organization_type';
        $orgTypeRead->action = 'read';
        $orgTypeRead->description = 'View Organization Type';
        $privileges[] = $orgTypeRead;

        $orgTypeUpdate = new Privilege();
        $orgTypeUpdate->resource = 'organization_type';
        $orgTypeUpdate->action = 'update';
        $orgTypeUpdate->description = 'Edit Organization Type';
        $privileges[] = $orgTypeUpdate;

        $orgTypeDelete = new Privilege();
        $orgTypeDelete->resource = 'organization_type';
        $orgTypeDelete->action = 'delete';
        $orgTypeDelete->description = 'Delete Organization Type';
        $privileges[] = $orgTypeDelete;

        $privileges->save();

        // Assign CRUD for organization type privileges to admin role
        $adminRole = Doctrine_Query::create()
                     ->from('Role r')
                     ->where('r.nickname = ?', 'ADMIN')
                     ->fetchOne();

        foreach ($privileges as $privilege) {
            $adminRole->Privileges[] = $privilege;
        }

        $adminRole->save();
    }

    /*
     * Add organization type events
     */
    private function _addEvents()
    {
        $events = new Doctrine_Collection('Event');

        $adminNoticationPrivilege = Doctrine_Query::create()
                                    ->from('Privilege p')
                                    ->where('p.resource = ? AND p.action = ?', array('notification', 'admin'))
                                    ->fetchOne();

        $orgTypeCreatedEvent = new Event();
        $orgTypeCreatedEvent->name = 'ORGANIZATION_TYPE_CREATED';
        $orgTypeCreatedEvent->description = 'Organization Type Created';
        $orgTypeCreatedEvent->Privilege = $adminNoticationPrivilege;
        $events[] = $orgTypeCreatedEvent;

        $orgTypeUpdatedEvent = new Event();
        $orgTypeUpdatedEvent->name = 'ORGANIZATION_TYPE_UPDATED';
        $orgTypeUpdatedEvent->description = 'Organization Type Modified';
        $orgTypeUpdatedEvent->Privilege = $adminNoticationPrivilege;
        $events[] = $orgTypeUpdatedEvent;

        $orgTypeDeletedEvent = new Event();
        $orgTypeDeletedEvent->name = 'ORGANIZATION_TYPE_DELETED';
        $orgTypeDeletedEvent->description = 'Organization Type Deleted';
        $orgTypeDeletedEvent->Privilege = $adminNoticationPrivilege;
        $events[] = $orgTypeDeletedEvent;

        $events->save();
    }

    /*
     * Insert agency, bureau, organization, system to organization type table.
     * Update corresponding orgtypeid in organization table
     */
    private function _addOrganizationTypes()
    {
        // Insert organization type
        $organizationTypes = new Doctrine_Collection('OrganizationType');

        $agency = new OrganizationType();
        $agency->name = 'Agency';
        $agency->nickname = 'agency';
        $agency->icon = 'agency';
        $organizationTypes[] = $agency;

        $bureau = new OrganizationType();
        $bureau->name = 'Bureau';
        $bureau->nickname = 'bureau';
        $bureau->icon = 'bureau';
        $organizationTypes[] = $bureau;

        $organization = new OrganizationType();
        $organization->name = 'Organization';
        $organization->nickname = 'organization';
        $organization->icon = 'organization';
        $organizationTypes[] = $organization;

        $system = new OrganizationType();
        $system->name = 'System';
        $system->nickname = 'system';
        $system->icon = 'system';
        $organizationTypes[] = $system;

        $organizationTypes->save();

        foreach ($organizationTypes as $organizationType) {
            $organizationIds = $this->_getOrganizationIdByType($organizationType->nickname);
            $organizationType->link('Organization', $organizationIds);
            $organizationType->save();
        }
    }

    /*
     * Get ids from organization by nickname of organization type
     * 
     * @type string the nickname of organization type
     * @return array
     */
    private function _getOrganizationIdByType($type)
    {
        $query = Doctrine_Query::create()
                          ->select('id')
                          ->from('Organization')
                          ->where('orgtype = ?', $type)
                          ->execute();

        $ids = $query->toKeyValueArray('id', 'id');

        return $ids;
    }
}
