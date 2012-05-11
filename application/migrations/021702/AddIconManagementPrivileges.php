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
 * This migration adds the privilege for IconManagement page and assign the privilege to the admin role
 *
 * @author     Duy K. Bui <duy.bui@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2012 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Migration
 */
class Application_Migration_021702_AddIconManagementPrivileges extends Fisma_Migration_Abstract
{
    /**
     * Add privileges for IconManagement and assign to ADMIN
     *
     * @return void
     */
    public function migrate()
    {
        $this->addPrivilege();
        $this->assignPrivilege();
    }

    /**
     * Add privileges
     *
     * @return void
     */
    public function addPrivilege()
    {
        $this->message("Adding privileges for IconManagement");

        $insertStatement = "INSERT into `privilege` (`resource`, `action`, `description`) "
                         . "VALUE ('icon', 'manage', 'Manage Icons');";
        $this->getHelper()->exec($insertStatement);
    }

    /**
     * Assign privilege
     *
     * @return void
     */
    public function assignPrivilege()
    {
        $this->message("Assigning privileges to Admin role");

        $privilegeQueryStatement = "SELECT `id` from privilege WHERE `resource` LIKE 'icon' AND `action` LIKE 'manage'";
        $privilege = $this->getHelper()->query($privilegeQueryStatement);

        $adminRole = $this->getHelper()->query("SELECT `id` from `role` WHERE `nickname` LIKE 'ADMIN'");

        $insertStatement = "INSERT into `role_privilege` VALUE ({$adminRole[0]->id}, {$privilege[0]->id});";
        $this->getHelper()->exec($insertStatement);
    }
}
