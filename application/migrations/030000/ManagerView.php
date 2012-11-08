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
 * Add Privilege for the Manager View & View As features as defined in OFJ-1952
 *
 * @author     Duy K. Bui <duy.bui@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2012 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Migration
 */
class Application_Migration_030000_ManagerView extends Fisma_Migration_Abstract
{
    /**
     * Migrate.
     */
    public function migrate()
    {
        $this->message("Adding Manager role and privilege...");

        // Insert new role
        $roleId = $this->getHelper()->insert(
            'role',
            array(
                'name' => 'Manager',
                'nickname' => 'MANAGER',
                'createdts' => date('Ymd'),
                'modifiedts' => date('Ymd'),
                'description' => "<p><strong>[OpenFISMA Definition]</strong></p><p>The Manager Group gives users the" .
                " same privileges a Reviewer would have for all items assigned to the systems and/or people directly " .
                "under the managed organizations.</p>"
            )
        );

        // Insert new privilege
        $this->getHelper()->insert(
            'privilege',
            array(
                'resource' => 'organzation',
                'action' => 'oversee',
                'description' => 'Oversee Organizations/Systems'
            )
        );

        // Fetch all privileges for the new role
        $privileges = array(
            array('area', 'dashboard'),
            array('area', 'finding'),
            array('area', 'finding_report'),
            array('area', 'system_inventory'),
            array('area', 'vulnerability'),
            array('area', 'vulnerability_report'),
            array('asset', 'read'),
            array('finding', 'read'),
            array('notification', 'asset'),
            array('notification', 'finding'),
            array('notification', 'vulnerability'),
            array('organization', 'read'),
            array('organization', 'oversee'),
            array('vulnerability', 'read')
        );
        foreach ($privileges as $index => $privilege) {
            $privileges[$index] = "(`resource` LIKE '{$privilege[0]}' AND `action` LIKE '{$privilege[1]}')";
        }
        $privilegeCollection = $this->getHelper()->query(
            "SELECT `id` from `privilege` WHERE " . implode(' OR ', $privileges) . ";"
        );

        foreach ($privilegeCollection as $privilege) {
            $this->getHelper()->exec(
                "INSERT into `role_privilege` " .
                "(`roleid`, `privilegeid`) " .
                "VALUE ($roleId, {$privilege->id});"
            );
        }
    }
}

