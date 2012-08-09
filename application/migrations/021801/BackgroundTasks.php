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
 * Add backgroundtasks field to configuration table.
 *
 * @author     Andrew Reeves <andrew.reeves@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2012 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Migration
 */
class Application_Migration_021801_BackgroundTasks extends Fisma_Migration_Abstract
{
    /**
     * Migrate.
     */
    public function migrate()
    {
        $this->getHelper()->addColumn(
            'configuration',
            'backgroundtasks',
            'text',
            'unlock_duration'
        );

        $this->getHelper()->modifyColumn(
            'notification',
            'eventtext',
            'text',
            'createdts'
        );

        $this->getHelper()->modifyColumn(
            'event',
            'category',
            "enum('admin','user','finding','vulnerability','inventory','incident','evaluation','script') " .
            "NOT NULL DEFAULT 'user'",
            'urlpath'
        );

        $privileges = $this->getHelper()->query(
            "SELECT `id` from `privilege` WHERE `resource` = 'notification' AND `action` = 'admin';"
        );
        $this->getHelper()->insert(
            'event',
            array(
                'name' => 'LDAP_SYNC',
                'description' => 'system finishes refreshing user information from LDAP',
                'privilegeid' => $privileges[0]->id,
                'urlpath' => '/config/list-ldap',
                'category' => 'script'
            )
        );
    }
}

