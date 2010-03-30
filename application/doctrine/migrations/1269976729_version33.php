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
 * @package Migration
 * @version $Id$
 * @copyright (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @author Josh Boyd <joshua.boyd@endeavorsystems.com> 
 * @license http://www.openfisma.org/content/license GPLv3
 */
class Version33 extends Doctrine_Migration_Base
{
    /**
     * Create keys/indexes
     * 
     * @return void
     */
    public function up()
    {
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
