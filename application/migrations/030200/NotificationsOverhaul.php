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
class Application_Migration_030200_NotificationsOverhaul extends Fisma_Migration_Abstract
{
    /**
     * Migrate.
     */
    public function migrate()
    {
        $this->message("Updating USER_DELETED urlpath");
        $this->getHelper()->update(
            'event',
            array('urlpath' => '/user/view/id'),
            array('name' => 'USER_DELETED')
        );

        $this->message("Adding new privilege");
        $privilegeId = $this->getHelper()->insert('privilege', array(
            'resource' => 'notification',
            'action' => 'incident',
            'description' => 'Incident Notifications'
        ));
        $findingPrivilegeId = $this->getHelper()->query(
            "SELECT `id` from `privilege` WHERE `resource` = 'notification' AND `action` = 'finding'"
        );
        $vulnerabilityPrivilegeId = $this->getHelper()->query(
            "SELECT `id` from `privilege` WHERE `resource` = 'notification' AND `action` = 'vulnerability'"
        );

        $eventsToAdd = array(
            array(
                'defaultactive' => 'true',
                'name' => 'INCIDENT_CREATED',
                'description' => 'an incident is reported',
                'privilegeid' => $privilegeId,
                'category' => 'incident',
                'urlpath' => '/incident/view/id/'
            ),
            array(
                'defaultactive' => 'true',
                'name' => 'INCIDENT_UPDATED',
                'description' => 'an incident is modified',
                'privilegeid' => $privilegeId,
                'category' => 'incident',
                'urlpath' => '/incident/view/id/'
            ),
            array(
                'defaultactive' => 'true',
                'name' => 'INCIDENT_LOCKED',
                'description' => 'an incident is locked',
                'privilegeid' => $privilegeId,
                'category' => 'incident',
                'urlpath' => '/incident/view/id/'
            ),
            array(
                'defaultactive' => 'true',
                'name' => 'INCIDENT_UNLOCKED',
                'description' => 'an incident is unlocked',
                'privilegeid' => $privilegeId,
                'category' => 'incident',
                'urlpath' => '/incident/view/id/'
            ),
            array(
                'defaultactive' => 'true',
                'name' => 'INCIDENT_STEP',
                'description' => 'a workflow step is completed for my assigned incidents',
                'privilegeid' => $privilegeId,
                'category' => 'incident',
                'urlpath' => '/incident/view/id/'
            ),
            array(
                'defaultactive' => 'true',
                'name' => 'INCIDENT_DELETED',
                'description' => 'an incident is deleted',
                'privilegeid' => $privilegeId,
                'category' => 'incident'
            ),
            array(
                'defaultactive' => 'true',
                'name' => 'FINDING_UPDATED',
                'description' => 'a finding is modified',
                'privilegeid' => $findingPrivilegeId[0]->id,
                'category' => 'finding',
                'urlpath' => '/finding/remediation/view/id/'
            ),
            array(
                'defaultactive' => 'true',
                'name' => 'VULNERABILITY_UPDATED',
                'description' => 'a vulnerability is modified',
                'privilegeid' => $vulnerabilityPrivilegeId[0]->id,
                'category' => 'vulnerability',
                'urlpath' => '/vm/vulnerability/view/id'
            )
        );
        $this->message("Adding new events");
        foreach ($eventsToAdd as $event) {
            $this->getHelper()->insert('event', $event);
        }

        $roles = $this->getHelper()->query(
            "SELECT `id` from `role` WHERE `name` IN ('Administrator', 'Power User', 'Limited User', 'Viewer')"
        );
        foreach ($roles as $role) {
            $this->getHelper()->insert(
                'role_privilege',
                array(
                    'roleid' => $role->id,
                    'privilegeid' => $privilegeId
                )
            );
        }

        $this->message("Adding columns to configuration");
        $this->getHelper()->addColumn(
            'configuration',
            'email_detail',
            'tinyint(1) default 1',
            'smtp_tls'
        );

        $this->message("Adding columns to mail");
        $this->getHelper()->addColumn(
            'mail',
            'format',
            "enum('text','html') default 'text'",
            'body'
        );

        $this->message("Adding columns to notification");
        $this->getHelper()->addColumn(
            'notification',
            'eventtitle',
            'text',
            'createdts'
        );
        $this->getHelper()->addColumns(
            'notification',
            array(
                'denormalizedemail' => 'varchar(255) not null',
                'denormalizedrecipient' => 'text'
            ),
            'userid'
        );
    }
}
