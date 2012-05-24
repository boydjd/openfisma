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
 * This migration adds the privileges for SystemType model and assign these privileges to the admin role
 *
 * @author     Duy K. Bui <duy.bui@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2012 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Migration
 * @tickets    OFJ-1622 (fixes IncidentModelCleanup in 2.17.0)
 */
class Application_Migration_021701_AddSystemTypePrivileges extends Fisma_Migration_Abstract
{
    /**
     * Add privileges for SystemType and assign to ADMIN
     *
     * @return void
     */
    public function migrate()
    {
        $this->addPrivilege();
        $this->assignPrivilege();
        $this->addEvents();
    }

    /**
     * Add privileges
     *
     * @return void
     */
    public function addPrivilege()
    {
        $this->message("Adding privileges for SystemType");

        $insertStatement = "INSERT into `privilege` (`resource`, `action`, `description`) VALUE ('system_type', ";
        $queryStatement = "SELECT `id` from `privilege` WHERE `resource` LIKE 'system_type' AND `action` LIKE ";

        if (!$this->getHelper()->query($queryStatement . "'read'")) {
            $this->getHelper()->exec($insertStatement . "'read', 'Read System Type'" . ")");
        }
        if (!$this->getHelper()->query($queryStatement . "'create'")) {
            $this->getHelper()->exec($insertStatement . "'create', 'Create System Type'" . ")");
        }
        if (!$this->getHelper()->query($queryStatement."'update'")) {
            $this->getHelper()->exec($insertStatement . "'update', 'Update System Type'" . ")");
        }
        if (!$this->getHelper()->query($queryStatement . "'delete'")) {
            $this->getHelper()->exec($insertStatement . "'delete', 'Delete System Type'" . ")");
        }
    }

    /**
     * Assign privilege
     *
     * @return void
     */
    public function assignPrivilege()
    {
        $this->message("Assigning privileges to Admin role");

        $privilegeQueryStatement = "SELECT `id` from privilege WHERE `resource` LIKE 'system_type' AND `action` LIKE ";
        $readPrivilege = $this->getHelper()->query($privilegeQueryStatement . "'read'");
        $createPrivilege = $this->getHelper()->query($privilegeQueryStatement . "'create'");
        $updatePrivilege = $this->getHelper()->query($privilegeQueryStatement . "'update'");
        $deletePrivilege = $this->getHelper()->query($privilegeQueryStatement . "'delete'");

        $adminRole = $this->getHelper()->query("SELECT `id` from `role` WHERE `nickname` LIKE 'ADMIN'");

        $queryStatement = "SELECT * from `role_privilege` WHERE `roleid` = {$adminRole[0]->id} AND `privilegeid` = ";
        $insertStatement = "INSERT into `role_privilege` VALUE ({$adminRole[0]->id}, ";
        if (!$this->getHelper()->query($queryStatement . $readPrivilege[0]->id)) {
            $this->getHelper()->exec($insertStatement . $readPrivilege[0]->id . ")");
        }
        if (!$this->getHelper()->query($queryStatement . $createPrivilege[0]->id)) {
            $this->getHelper()->exec($insertStatement . $createPrivilege[0]->id . ")");
        }
        if (!$this->getHelper()->query($queryStatement . $updatePrivilege[0]->id)) {
            $this->getHelper()->exec($insertStatement . $updatePrivilege[0]->id . ")");
        }
        if (!$this->getHelper()->query($queryStatement . $deletePrivilege[0]->id)) {
            $this->getHelper()->exec($insertStatement . $deletePrivilege[0]->id . ")");
        }
    }

    /**
     * Add events
     *
     * @return void
     */
    public function addEvents()
    {
        $this->message("Adding events for SystemType");

        $insertStatement = "INSERT into `event` (`privilegeid`, `name`, `description`, `urlpath`) VALUE ("
            . "(SELECT `id` from `privilege` where `resource` LIKE 'notification' AND `action` LIKE 'admin'), ";
        $queryStatement = "SELECT `id` from `event` WHERE `name` LIKE ";

        if (!$this->getHelper()->query($queryStatement . "'SYSTEM_TYPE_CREATED'")) {
            $this->getHelper()->exec(
                $insertStatement
                    . "'SYSTEM_TYPE_CREATED', 'System Type Created', '/system-type/view/id/'"
                . ")"
            );
        }
        if (!$this->getHelper()->query($queryStatement . "'SYSTEM_TYPE_UPDATED'")) {
            $this->getHelper()->exec(
                $insertStatement
                    . "'SYSTEM_TYPE_UPDATED', 'System Type Modified', '/system-type/view/id/'"
                . ")"
            );
        }
        if (!$this->getHelper()->query($queryStatement . "'SYSTEM_TYPE_DELETED'")) {
            $this->getHelper()->exec(
                $insertStatement
                    . "'SYSTEM_TYPE_DELETED', 'System Type Deleted', NULL"
                . ")"
            );
        }
    }
}
