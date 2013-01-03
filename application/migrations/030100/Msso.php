<?php
/**
 * Copyright (c) 2012 Endeavor Systems, Inc.
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
 * Add MSSO changes as defined in OFJ-1991, OFJ-1992, OFJ-1994, OFJ-1995, OFJ-1996
 *
 * @author     Duy K. Bui <duy.bui@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2012 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Migration
 */
class Application_Migration_030100_Msso extends Fisma_Migration_Abstract
{
    /**
     * Migrate.
     */
    public function migrate()
    {
        $this->message("Adding Optional fields...");
        $this->getHelper()->addColumn(
            'configuration',
            'optionalfields',
            'text',
            'backgroundtasks'
        );
        $this->getHelper()->dropColumn(
            'configuration',
            'use_legacy_finding_key'
        );

        $this->message("Adding Organization POC List...");
        $this->getHelper()->addColumn(
            'configuration',
            'organization_poc_list',
            'text',
            'system_name'
        );
        $this->getHelper()->exec(
            "UPDATE configuration SET `organization_poc_list` = 'Business Owner,Information Security Officer,Authoriz" .
            "ing Official';"
        );
        $this->getHelper()->createTable(
            'organization_poc',
            array(
                'id' => 'bigint(20) not null auto_increment',
                'pocid' => 'bigint(20)',
                'type' => 'varchar(255)',
                'objectid' => 'bigint(20)'
            ),
            'id'
        );
        $this->getHelper()->addIndex('organization_poc', 'objectid');
        $this->getHelper()->addForeignKey('organization_poc', 'objectid', 'organization', 'id');
        $this->getHelper()->addIndex('organization_poc', 'pocid');
        $this->getHelper()->addForeignKey('organization_poc', 'pocid', 'user', 'id');

        $this->message("Adding Finding Link Types...");
        $this->getHelper()->addColumn(
            'configuration',
            'finding_link_types',
            'text',
            'finding_draft_due'
        );
        $this->getHelper()->exec(
            "UPDATE configuration SET `finding_link_types` = 'is a repeat of,duplicates/is duplicated by,fixes/is fix" .
            "ed by';"
        );
        $this->getHelper()->createTable(
            'finding_relationship',
            array(
                'id' => 'bigint(20) not null auto_increment',
                'createdts' => 'datetime not null',
                'modifiedts' => 'datetime not null',
                'startfindingid' => 'bigint(20)',
                'endfindingid' => 'bigint(20)',
                'relationship' => 'varchar(255)',
                'createdbyuserid' => 'bigint(20)'
            ),
            'id'
        );
        $this->getHelper()->addIndex('finding_relationship', 'startfindingid');
        $this->getHelper()->addForeignKey('finding_relationship', 'startfindingid', 'finding', 'id');
        $this->getHelper()->addIndex('finding_relationship', 'endfindingid');
        $this->getHelper()->addForeignKey('finding_relationship', 'endfindingid', 'finding', 'id');
        $this->getHelper()->addIndex('finding_relationship', 'createdbyuserid');
        $this->getHelper()->addForeignKey('finding_relationship', 'createdbyuserid', 'user', 'id');

        $this->message("Adding Finding Audit Year...");
        $this->getHelper()->addColumn(
            'finding',
            'audityear',
            'varchar(4)',
            'discovereddate'
        );

        $this->message("Adding System FISMA Reportable...");
        $this->getHelper()->addColumn(
            'system',
            'fismareportable',
            'tinyint(1) NULL DEFAULT 1',
            'fipscategory'
        );

        $this->message("Adding System Next SA Date...");
        $this->getHelper()->addColumn(
            'system',
            'nextsecurityauthorizationdt',
            'date',
            'fismareportable'
        );

        $this->message("Adding Privileges...");
        $orgPocPrivilege = $this->getHelper()->insert(
            'privilege',
            array(
                'resource' => 'organization',
                'action' => 'manage_poc_list',
                'description' => 'Manage POC Positions'
            )
        );
        $assetSrvPrivilege = $this->getHelper()->insert(
            'privilege',
            array(
                'resource' => 'asset',
                'action' => 'manage_service_tags',
                'description' => 'Manage Asset Environments'
            )
        );
        $findingAdyPrivilege = $this->getHelper()->insert(
            'privilege',
            array(
                'resource' => 'finding',
                'action' => 'update_audit_year',
                'description' => 'Update Finding Audit Year'
            )
        );
        $findingLinkPrivilege = $this->getHelper()->insert(
            'privilege',
            array(
                'resource' => 'finding',
                'action' => 'update_relationship',
                'description' => 'Update Finding Links'
            )
        );
        $findingLinkTypePrivilege = $this->getHelper()->insert(
            'privilege',
            array(
                'resource' => 'finding',
                'action' => 'manage_relationships',
                'description' => 'Manage Finding Link Types'
            )
        );

        $this->message("Assigning Privileges...");
        $roleQuery = "SELECT rp.roleid as rid from role_privilege rp INNER JOIN privilege p on rp.privilegeid = p.id "
                   . "WHERE p.action = ? and p.resource = ?";
        $orgPocRoles = $this->getHelper()->query($roleQuery, array('update', 'organization'));
        foreach ($orgPocRoles as $role) {
            $this->getHelper()->insert(
                'role_privilege',
                array(
                    'roleid' => $role->rid,
                    'privilegeid' => $orgPocPrivilege
                )
            );
        }
        $assetSrvRoles = $this->getHelper()->query($roleQuery, array('unaffiliated', 'asset'));
        foreach ($assetSrvRoles as $role) {
            $this->getHelper()->insert(
                'role_privilege',
                array(
                    'roleid' => $role->rid,
                    'privilegeid' => $assetSrvPrivilege
                )
            );
        }
        $findingUserRoles = $this->getHelper()->query($roleQuery, array('update_legacy_finding_key', 'finding'));
        foreach ($findingUserRoles as $role) {
            $this->getHelper()->insert(
                'role_privilege',
                array(
                    'roleid' => $role->rid,
                    'privilegeid' => $findingAdyPrivilege
                )
            );
            $this->getHelper()->insert(
                'role_privilege',
                array(
                    'roleid' => $role->rid,
                    'privilegeid' => $findingLinkPrivilege
                )
            );
        }
        $findingAdminRoles = $this->getHelper()->query($roleQuery, array('create', 'source'));
        foreach ($findingAdminRoles as $role) {
            $this->getHelper()->insert(
                'role_privilege',
                array(
                    'roleid' => $role->rid,
                    'privilegeid' => $findingLinkTypePrivilege
                )
            );
        }
    }
}

