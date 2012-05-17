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
 * This migration adds the privileges for Evaluation model and assign these privileges to the admin role
 *
 * @author     Duy K. Bui <duy.bui@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2012 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Migration
 * @tickets    OFJ-1696 (fixes FindingWorkflowAndNotificationEmail in 2.17.0)
 */
class Application_Migration_021702_AddEvaluationPrivileges extends Fisma_Migration_Abstract
{
    /**
     * Add privileges for Evaluation and assign to ADMIN
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
        $this->message("Adding privileges for Evaluation");

        $insertStatement = "INSERT into `privilege` (`resource`, `action`, `description`) VALUE ('evaluation', ";
        $queryStatement = "SELECT `id` from `privilege` WHERE `resource` LIKE 'evaluation' AND `action` LIKE ";

        if (!$this->getHelper()->query($queryStatement . "'create'")) {
            $this->getHelper()->exec($insertStatement . "'create', 'Create Evaluation'" . ")");
        }
        if (!$this->getHelper()->query($queryStatement."'update'")) {
            $this->getHelper()->exec($insertStatement . "'update', 'Update Evaluation'" . ")");
        }
        if (!$this->getHelper()->query($queryStatement . "'delete'")) {
            $this->getHelper()->exec($insertStatement . "'delete', 'Delete Evaluation'" . ")");
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

        $privilegeQueryStatement = "SELECT `id` from privilege WHERE `resource` LIKE 'evaluation' AND `action` LIKE ";
        $createPrivilege = $this->getHelper()->query($privilegeQueryStatement . "'create'");
        $updatePrivilege = $this->getHelper()->query($privilegeQueryStatement . "'update'");
        $deletePrivilege = $this->getHelper()->query($privilegeQueryStatement . "'delete'");

        $adminRole = $this->getHelper()->query("SELECT `id` from `role` WHERE `nickname` LIKE 'ADMIN'");

        $queryStatement = "SELECT * from `role_privilege` WHERE `roleid` = {$adminRole[0]->id} AND `privilegeid` = ";
        $insertStatement = "INSERT into `role_privilege` VALUE ({$adminRole[0]->id}, ";
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
}
