<?php
/**
 * Copyright (c) 2011 Endeavor Systems, Inc.
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

require_once(realpath(dirname(__FILE__) . '/../../Case/Unit.php'));

/**
 * Test_Application_Models_UserRoleTable
 * 
 * @uses Test_Case_Unit
 * @package Test 
 * @copyright (c) Endeavor Systems, Inc. 2011 {@link http://www.endeavorsystems.com}
 * @author Josh Boyd <joshua.boyd@endeavorsystems.com> 
 * @license http://www.openfisma.org/content/license GPLv3
 */
class Test_Application_Models_UserRoleTable extends Test_Case_Unit
{
    /**
     * @todo: short description.
     * 
     * @return @todo
     */
    public function testGetRolesAndUsersByOrganizationIdQuery()
    {
        $query = UserRoleTable::getRolesAndUsersByOrganizationIdQuery(0)->getSql();
        $expectedQuery = 'FROM role r '
                        .'INNER JOIN user_role u ON r.id = u.roleid '
                        .'INNER JOIN user_role_organization u2 ON (u.userroleid = u2.userroleid) '
                        .'INNER JOIN organization o ON o.id = u2.organizationid AND (o.id = ?) '
                        .'INNER JOIN user u3 ON u.userid = u3.id';
        $this->assertContains($expectedQuery, $query);
        $this->assertContains('locktype is null', $query);
    }

    /**
     * @todo: short description.
     * 
     * @return @todo
     */
    public function testGetByUserIdAndRoleIdQuery()
    {
        $query = UserRoleTable::getByUserIdAndRoleIdQuery(0, 0)->getSql();
        $expectedQuery = 'FROM user_role u WHERE u.userid = ? AND u.roleid = ?';
        $this->assertContains($expectedQuery, $query);
    }
}
