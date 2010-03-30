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
 * @package Migration
 * @version $Id$
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
        $this->addColumn(
            'user_role', 'userroleid', 'integer', '8', array(
             'notnull' => TRUE,
             'primary' => TRUE,
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
