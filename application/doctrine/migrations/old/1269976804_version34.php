<?php
/**
 * Copyright (c) 2008 Endeavor Systems, Inc.
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
 * Migration of User organizations 
 * 
 * @package Migration
 * @copyright (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @author Josh Boyd <joshua.boyd@endeavorsystems.com> 
 * @license http://www.openfisma.org/content/license GPLv3
 */
class Version34 extends Doctrine_Migration_Base
{
    /**
     * Move User organizations under the roles assigned to the user
     * 
     * @return void
     */
    public function up()
    {
        $conn = Doctrine_Manager::connection();

        try {
            $conn->beginTransaction();

            $users = Doctrine::getTable('User')->findAll();

            foreach ($users as $user) {
                $roles = $user->UserRole;
                $organizations = $user->Organizations;

                foreach ($roles as $role) {
                    foreach ($organizations as $organization) {
                        $userRoleOrganization = new UserRoleOrganization();
                        $userRoleOrganization->userRoleId = $role->userRoleId;
                        $userRoleOrganization->organizationId = $organization->id;
                        $userRoleOrganization->save();
                    }
                }

                $user->unlink('Organizations');
                $user->save();
            }

            $conn->commit();
        } catch (Doctrine_Exception $e) {
            $conn->rollback();
            throw $e;
        }
    }

    /**
     * Change back to flat style assignment. Not recommended, but it's here if it needs to be done for some reason. 
     * 
     * @access public
     * @return void
     */
    public function down()
    {
        $conn = Doctrine_Manager::connection();

        try {
            $conn->beginTransaction();

            $users = Doctrine::getTable('User')->findAll();

            foreach ($users as $user) {
                $roles = $user->UserRole;

                foreach ($roles as $role) {
                    foreach ($role->Organizations as $organization) {
                        $user->Organizations[] = $organization;
                    }

                    $role->unlink('Organizations');
                    $role->save();
                }

                $user->save();
            }

            $conn->commit();
        } catch (Doctrine_Exception $e) {
            $conn->rollback();
            throw $e;
        }
    }
}
