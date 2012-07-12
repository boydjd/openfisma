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
 * Migrate User Notifications (OFJ-1678)
 *
 * @author     Duy K. Bui <duy.bui@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2012 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Migration
 */
class Application_Migration_021800_MigrateEvents extends Fisma_Migration_Abstract
{
    public function migrate()
    {
        $this->message("Migrating User Notifications...");

        $this->_deleteOldEvents();
        $this->_updateDescriptions();
        $this->_renameEvents();
        $this->_addEvents();
        $this->_addColumns();
        $this->_updateCategories();
        $this->_updateDefaultActive();

        $this->message("... done.");
    }

    protected function _deleteOldEvents()
    {
        $events = array(
            'FINDING_INJECTED',
            'VULNERABILITY_INJECTED',
            'ASSET_DELETED',
            'UPDATE_MITIGATION_TYPE',
            'UPDATE_COURSE_OF_ACTION',
            'UPDATE_RESPONSIBLE_SYSTEM',
            'UPDATE_DESCRIPTION',
            'UPDATE_SECURITY_CONTROL',
            'UPDATE_COUNTERMEASURES',
            'UPDATE_THREAT',
            'UPDATE_RECOMMENDATION',
            'UPDATE_RESOURCES_REQUIRED',
            'UPDATE_ECD',
            'UPDATE_LOCKED_ECD',
            'UPDATE_LEGACY_FINDING_KEY',
            'UPDATE_FINDING_SOURCE',
            'EVIDENCE_UPLOADED',
            'POC_CREATED',
            'POC_UPDATED',
            'POC_DELETED',
            'ORGANIZATION_DELETED',
            'SYSTEM_DELETED',
            'SYSTEM_UPDATED',
            'SYSTEM_CREATED',
            'PRODUCT_CREATED',
            'PRODUCT_UPDATED',
            'PRODUCT_DELETED',
            'ROLE_CREATED',
            'ROLE_DELETED',
            'ROLE_UPDATED',
            'SOURCE_CREATED',
            'SOURCE_UPDATED',
            'SOURCE_DELETED',
            'NETWORK_UPDATED',
            'NETWORK_CREATED',
            'NETWORK_DELETED',
            'LOGOUT',
            'ECD_EXPIRES_TODAY',
            'ECD_EXPIRES_7_DAYS',
            'ECD_EXPIRES_14_DAYS',
            'ECD_EXPIRES_21_DAYS',
            'APPROVAL_DENIED',
            'MITIGATION_REVISE',
            'SYSTEM_DOCUMENT_CREATED',
            'SYSTEM_DOCUMENT_UPDATED',
            'DOCUMENT_TYPE_UPDATED',
            'DOCUMENT_TYPE_CREATED',
            'DOCUMENT_TYPE_DELETED',
            'ORGANIZATION_TYPE_UPDATED',
            'ORGANIZATION_TYPE_CREATED',
            'ORGANIZATION_TYPE_DELETED',
            'SYSTEM_TYPE_UPDATED',
            'SYSTEM_TYPE_CREATED',
            'SYSTEM_TYPE_DELETED'
        );
        $this->message("> Deleting " . count($events) . " old events.");
        $in = '(' . implode(',', array_fill(0, count($events), '?')) . ')';
        $this->getHelper()->exec(
            "DELETE FROM user_event WHERE eventid IN (SELECT id FROM event WHERE name IN $in)", $events
        );
        $this->getHelper()->exec("DELETE from `event` WHERE `name` in $in", $events);
    }

    protected function _updateDescriptions()
    {
        $this->message("> Updating descriptions.");
        $this->getHelper()->exec(
            "UPDATE `event` e SET e.`description` = CONCAT('a finding is ', e.`description`) " .
            "WHERE e.`id` in (SELECT DISTINCT `eventid` from `evaluation`);"
        );
        $events = array(
            'FINDING_CREATED' => 'a finding is created',
            'FINDING_DELETED' => 'a finding is deleted',
            'FINDING_CLOSED' => 'a finding is resolved',
            'VULNERABILITY_CREATED' => 'a vulnerability is created',
            'ASSET_CREATED' => 'an asset is created',
            'ASSET_UPDATED' => 'an asset is modified',
            'USER_CREATED' => 'a user account is created',
            'USER_UPDATED' => 'a user account is modified',
            'USER_DELETED' => 'a user account is deleted',
            'ORGANIZATION_CREATED' => 'an organization or system is created',
            'ORGANIZATION_UPDATED' => 'an organization or system is modified',
            'CONFIGURATION_UPDATED' => 'system configuration is modified',
            'LOGIN_SUCCESS' => 'a user account logins successfully',
            'LOGIN_FAILURE' => 'a user account fails to login',
            'USER_LOCKED' => 'a user account is locked',
            'MITIGATION_APPROVED' => 'a finding is awaiting Evidence Package submission'
        );
        foreach ($events as $key => $value) {
            $this->getHelper()->update('event', array('description' => $value), array('name' => $key));
        }
    }

    protected function _renameEvents()
    {
        $events = array(
            'LOGIN_SUCCESS' => 'ACCOUNT_LOGIN_SUCCESS',
            'LOGIN_FAILURE' => 'ACCOUNT_LOGIN_FAILURE',
            'USER_LOCKED' => 'ACCOUNT_LOCKED'
        );
        $this->message("> Renaming " . count($events) . "events.");
        foreach ($events as $key => $value) {
            $this->getHelper()->update('event', array('name' => $value), array('name' => $key));
        }
    }

    protected function _addEvents()
    {
        $privileges = $this->getHelper()->query(
            "SELECT `id` from `privilege` WHERE `resource` = 'notification' ORDER BY `action`"
        );
        // order: 0 => 'admin', 1 => 'asset', 2 => 'finding', 3 => 'vulnerability'

        $events = array(
            array(
                'name' => 'ACCOUNT_DISABLED',
                'description' => 'a user account is disabled',
                'privilegeid' => $privileges[0]->id,
                'urlpath' => '/user/view/id'
            ),
            array(
                'name' => 'VULNERABILITY_CLOSED',
                'description' => 'a vulnerability is resolved',
                'privilegeid' => $privileges[3]->id,
                'urlpath' => '/vm/vulnerability/view/id'
            ),
            array(
                'name' => 'VULNERABILITY_DELETED',
                'description' => 'a vulnerability is deleted',
                'privilegeid' => $privileges[3]->id
            ),
            array(
                'name' => 'USER_LOCKED',
                'description' => 'my user account is locked'
            ),
            array(
                'name' => 'USER_DISABLED',
                'description' => 'my user account is disabled'
            ),
            array(
                'name' => 'USER_LOGIN_SUCCESS',
                'description' => 'there is a successful login'
            ),
            array(
                'name' => 'USER_LOGIN_FAILURE',
                'description' => 'there is a failed login attempt'
            ),
            array(
                'name' => 'USER_POC',
                'description' => 'I\'m assigned as a Point of Contact'
            )
        );
        $this->message("> Adding " . count($events) . "events.");
        foreach ($events as $event) {
            $this->getHelper()->insert('event', $event);
        }
    }

    protected function _addColumns()
    {
        $this->message('> Adding category and defaultActive columns to events.');
        $this->getHelper()->addColumns(
            'event',
            array(
                'category' => "enum('admin', 'user', 'finding', 'vulnerability',  'inventory','incident', 'evaluation')"
                            . " NOT NULL default 'user'",
                'defaultactive' => 'tinyint(1) default 0'
            ),
            'urlPath'
        );
    }

    protected function _updateCategories()
    {
        $events = array(
            'MITIGATION_IVV' => 'evaluation',
            'MITIGATION_ISSO' => 'evaluation',
            'EVIDENCE_IVV' => 'evaluation',
            'EVIDENCE_ISSO' => 'evaluation',
            'CONFIGURATION_UPDATED' => 'admin',
            'USER_CREATED' => 'admin',
            'USER_UPDATED' => 'admin',
            'ACCOUNT_LOCKED' => 'admin',
            'ACCOUNT_DISABLED' => 'admin',
            'USER_DELETED' => 'admin',
            'ACCOUNT_LOGIN_SUCCESS' => 'admin',
            'ACCOUNT_LOGIN_FAILURE' => 'admin',
            'ORGANIZATION_CREATED' => 'inventory',
            'ORGANIZATION_UPDATED' => 'inventory',
            'ASSET_CREATED' => 'inventory',
            'ASSET_UPDATED' => 'inventory',
            'USER_LOCKED' => 'user',
            'USER_DISABLED' => 'user',
            'USER_LOGIN_SUCCESS' => 'user',
            'USER_LOGIN_FAILURE' => 'user',
            'USER_POC' => 'user',
            'VULNERABILITY_CREATED' => 'vulnerability',
            'VULNERABILITY_CLOSED' => 'vulnerability',
            'VULNERABILITY_DELETED' => 'vulnerability',
            'FINDING_CREATED' => 'finding',
            'FINDING_CLOSED' => 'finding',
            'FINDING_DELETED' => 'finding',
            'MITIGATION_APPROVED' => 'finding'
        );
        $this->message("> Updating categories for " . count($events) . "events.");
        foreach ($events as $key => $value) {
            $this->getHelper()->update('event', array('category' => $value), array('name' => $key));
        }
    }

    protected function _updateDefaultActive()
    {
        $events = array(
            'CONFIGURATION_UPDATED',
            'USER_CREATED',
            'USER_UPDATED',
            'ACCOUNT_LOCKED',
            'ACCOUNT_DISABLED',
            'USER_DELETED',
            'ORGANIZATION_CREATED',
            'ORGANIZATION_UPDATED',
            'ASSET_CREATED',
            'ASSET_UPDATED',
            'USER_LOCKED',
            'USER_DISABLED',
            'USER_LOGIN_FAILURE',
            'USER_POC',
            'VULNERABILITY_CREATED',
            'VULNERABILITY_DELETED',
            'FINDING_CREATED',
            'FINDING_DELETED'
        );
        $this->message("> Set " . count($events) . "events as default.");
        $in = '(' . implode(',', array_fill(0, count($events), '?')) . ')';
        $this->getHelper()->exec("UPDATE `event` SET `defaultactive` = 1 WHERE `name` in $in", $events);
    }
}
