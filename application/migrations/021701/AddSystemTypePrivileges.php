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
    }

    /**
     * Add privileges
     *
     * @return void
     */
    public function addPrivilege()
    {
        $this->message("Adding privileges for SystemType");
        $query = "INSERT into `privilege` (`resource`, `action`, `description`) VALUES "
                   . "('system_type', 'create', 'Create System Type'),"
                   . "('system_type', 'read', 'Read System Type'),"
                   . "('system_type', 'update', 'Update System Type'),"
                   . "('system_type', 'delete', 'Delete System Type')";
        $this->getHelper()->exec($query);
    }

    /**
     * Assign privilege
     *
     * @return void
     */
    public function assignPrivilege()
    {
        $this->message("Assigning privileges to Admin role");
        $query = "INSERT into `role_privilege` (`roleid`, `privilegeid`) VALUES "
                   . "("
                       . "(SELECT `id` from `role` where `nickname` LIKE 'ADMIN'),"
                       . "(SELECT `id` from `privilege` where `resource` LIKE 'system_type' AND `action` LIKE 'read')"
                   . "), ("
                       . "(SELECT `id` from `role` where `nickname` LIKE 'ADMIN'),"
                       . "(SELECT `id` from `privilege` where `resource` LIKE 'system_type' AND `action` LIKE 'create')"
                   . "), ("
                       . "(SELECT `id` from `role` where `nickname` LIKE 'ADMIN'),"
                       . "(SELECT `id` from `privilege` where `resource` LIKE 'system_type' AND `action` LIKE 'update')"
                   . "), ("
                       . "(SELECT `id` from `role` where `nickname` LIKE 'ADMIN'),"
                       . "(SELECT `id` from `privilege` where `resource` LIKE 'system_type' AND `action` LIKE 'delete')"
                   . ")";
        $this->getHelper()->exec($query);
    }
}
