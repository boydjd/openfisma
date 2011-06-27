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
 * Adds/removes UserRoleOrganization
 * 
 * This file contains generated code... skip standards check.
 * @codingStandardsIgnoreFile
 * 
 * @package Migration
 * @copyright (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @author Josh Boyd <joshua.boyd@endeavorsystems.com> 
 * @license http://www.openfisma.org/content/license GPLv3
 */
class Version32 extends Doctrine_Migration_Base
{
    /**
     * Add User_Role_Organization and modify User_Role 
     * 
     * @return void
     */
    public function up()
    {
         $this->createTable(
            'user_role_organization', array(
             'userroleid' => 
             array(
              'type' => 'integer',
              'primary' => '1',
              'length' => '8',
             ),
             'organizationid' => 
             array(
              'type' => 'integer',
              'primary' => '1',
              'length' => '8',
             ),
             ), array(
             'primary' => 
             array(
              0 => 'userroleid',
              1 => 'organizationid',
             ),
             )
        );

        $this->addColumn(
            'user_role', 'userroleid', 'integer', '8', array(
             'notnull' => TRUE
             )
        );

    }

    /**
     * Write a new sequence of primary keys into the new primary key column, userroleid.
     * 
     * This cannot be done in up() because the migration queues all of the DMLs and runs them after up() returns,
     * and the following queries cannot be performed until the userroleid column has been added.
     */
    public function postUp()
    {
        $conn = Doctrine_Manager::connection();

        $conn->beginTransaction();
        $userRolesQuery = Doctrine_Query::create()->from('UserRole')->setHydrationMode(Doctrine::HYDRATE_SCALAR);
        $userRoles = $userRolesQuery->execute();

        $i = 1;
        
        /*
         * This is awkward. Because this table has a composite primary key, Doctrine 1.1 cannot hydrate a resultset of
         * objects. So we find all existing user roles and hydrate as scalar, then issue a bunch of queries to add a
         * monotonically increasing sequence of numbers into the new primary key column.
         */
        foreach ($userRoles as $userRole) {
            $update = Doctrine_Query::create()
                      ->update('UserRole u')
                      ->set('u.userRoleId', $i++)
                      ->where('u.userId = ? AND u.roleId = ?', 
                              array($userRole['UserRole_userId'], $userRole['UserRole_roleId']));

            $update->execute();
        }

        $conn->commit();
    }

    /**
     * Reverse the up() function changes 
     * 
     * @return void
     */
    public function down()
    {
        $this->dropTable('user_role_organization');
        $this->removeColumn('user_role', 'userroleid');
        $this->createConstraint(
            'user_role', NULL, array(
            'primary' => true,
            'fields' => array('userid' => array(), 'roleid' => array()),
        )
        );
        $this->changeColumn(
            'user_role', 'userid', '8', 'integer', array(
            'primary' => '1'
        )
        );
        $this->changeColumn(
            'user_role', 'roleid', '8', 'integer', array(
            'primary' => '1'
        )
        );
    }
}
