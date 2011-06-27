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
 * Foreign keys/indexes for user_role_organization and user_Role 
 * 
 * This file contains generated code... skip standards check.
 * @codingStandardsIgnoreFile
 * 
 * @package Migration
 * @copyright (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @author Josh Boyd <joshua.boyd@endeavorsystems.com> 
 * @license http://www.openfisma.org/content/license GPLv3
 */
class Version33 extends Doctrine_Migration_Base
{
    /**
     * Create keys/indexes
     * 
     * The userroleid column was created and populated with a sequence in version 32, and is converted to a primary key
     * in version 33.
     * 
     * @return void
     */
    public function up()
    {
        $this->dropConstraint('user_role', NULL, TRUE);

        $this->changeColumn(
            'user_role', 'userid', '8', 'integer', array(
             'notnull' => TRUE,
             'primary' => FALSE,
             )
        );
        $this->changeColumn(
            'user_role', 'roleid', '8', 'integer', array(
             'notnull' => TRUE,
             'primary' => FALSE,
             )
        );

        $this->createConstraint(
            'user_role', NULL, array(
            'primary' => TRUE,
            'fields' => array('userroleid' => array()),
        )
        );
        $this->changeColumn(
            'user_role', 'userroleid', '8', 'integer', array(
            'autoincrement' => TRUE,
        )
        );

        $this->createForeignKey(
            'user_role_organization', 'user_role_organization_organizationid_organization_id', array(
             'name' => 'user_role_organization_organizationid_organization_id',
             'local' => 'organizationid',
             'foreign' => 'id',
             'foreignTable' => 'organization',
             )
        );
        $this->createForeignKey(
            'user_role_organization', 'user_role_organization_userroleid_user_role_userroleid', array(
             'name' => 'user_role_organization_userroleid_user_role_userroleid',
             'local' => 'userroleid',
             'foreign' => 'userroleid',
             'foreignTable' => 'user_role',
             )
        );

        $this->createForeignKey(
            'user_role', 'user_role_userid_user_id', array(
             'name' => 'user_role_userid_user_id',
             'local' => 'userid',
             'foreign' => 'id',
             'foreignTable' => 'user',
             )
        );

        $this->createForeignKey(
            'user_role', 'user_role_roleid_user_id', array(
             'name' => 'user_role_roleid_user_id',
             'local' => 'roleid',
             'foreign' => 'id',
             'foreignTable' => 'role',
             )
        );
        
        $this->addIndex(
            'user_role', 'userRoleIndexUnique', array(
             'fields' => 
             array(
              0 => 'userid',
              1 => 'roleid',
             ),
             'type' => 'unique',
             )
        );
        $this->addIndex(
            'user_role_organization', 'user_role_organization_organizationid', array(
             'fields' => 
             array(
              0 => 'organizationid',
             ),
             )
        );
        $this->addIndex(
            'user_role_organization', 'user_role_organization_userroleid', array(
             'fields' => 
             array(
              0 => 'userroleid',
             ),
             )
        );
    }

    /**
     * Remove keys/indexes 
     * 
     * @return void
     */
    public function down()
    {
        $this->dropForeignKey('user_role_organization', 'user_role_organization_organizationid_organization_id');
        $this->dropForeignKey('user_role_organization', 'user_role_organization_userroleid_user_role_userroleid');
        $this->removeIndex(
            'user_role', 'userRoleIndexUnique', array(
             'fields' => 
             array(
              0 => 'userid',
              1 => 'roleid',
             ),
             'type' => 'unique',
             )
        );
        $this->removeIndex(
            'user_role_organization', 'user_role_organization_organizationid', array(
             'fields' => 
             array(
              0 => 'organizationid',
             ),
             )
        );
        $this->removeIndex(
            'user_role_organization', 'user_role_organization_userroleid', array(
             'fields' => 
             array(
              0 => 'userroleid',
             ),
             )
        );
    }
}
