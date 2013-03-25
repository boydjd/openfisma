<?php
/**
 * Copyright (c) 2013 Endeavor Systems, Inc.
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
 * @author     Duy K. Bui <duy.bui@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2013 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Migration
 */
class Application_Migration_030200_OrganizationRole extends Fisma_Migration_Abstract
{
    /**
     * Migrate.
     */
    public function migrate()
    {
        $helper = $this->getHelper();
        $now = Zend_Date::now()->toString('yyyy-MM-dd HH:mm:ss');

        $helper->addColumn('role', 'type', "enum('ACCOUNT_TYPE','USER_GROUP')", 'description');
        $this->message('Converting current roles into Account Types');
        $helper->update('role', array('type' => 'ACCOUNT_TYPE'));

        $this->message('Converting current people types into User Groups');
        $groups = $helper->query('SELECT `organization_poc_list` FROM `configuration` LIMIT 0, 1');
        $groups = explode(',', $groups[0]->organization_poc_list);

        foreach ($groups as $group) {
            $this->message('Migrating ' . $group);

            $roleId = $helper->insert('role', array(
                'type' => 'USER_GROUP',
                'name' => $group,
                'nickname' => $group,
                'createdts' => $now,
                'modifiedts' => $now
            ));

            $assignments = $helper->query('SELECT * FROM `organization_poc` WHERE `type` = ?', array($group));
            $this->message(count($assignments) . ' assignment(s).');

            if (count($assignments > 0)) {
                foreach ($assignments as $assignment) {
                    $userRoleId = $helper->query(
                        'SELECT `userroleid` FROM `user_role` WHERE `roleid` = ? AND `userid` = ?',
                        array($roleId, $assignment->pocid)
                    );
                    if (count($userRoleId) > 0) {
                        $userRoleId = $userRoleId[0]->userroleid;
                    } else {
                        $userRoleId = $helper->insert('user_role', array(
                            'roleid' => $roleId,
                            'userid' => $assignment->pocid
                        ));
                    }
                    $helper->insert('user_role_organization', array(
                        'userroleid' => $userRoleId,
                        'organizationid' => $assignment->objectid
                    ));
                }
            }
        }

        $this->message('Dropping People Types');
        $helper->dropTable('organization_poc');
        $helper->dropColumn('configuration', 'organization_poc_list');
    }
}
