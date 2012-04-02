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
 * This migration:
 *  - adds urlPath column and data to event table and url column to notification table; and
 *  - adds Finding workflow administration related changes.
 * These two are done together due to interdependencies, namely on the event table.
 *
 * @author     Mark Ma <mark.ma@reyosoft.com>
 * @copyright  (c) Endeavor Systems, Inc. 2012 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Migration
 */
class Application_Migration_021700_FindingWorkflowAndNotificationEmail extends Fisma_Migration_Abstract
{
    /**
     * Call three functions to add urlPath column and data to event table and url column to notification table.
     *
     * @return void
     */
    public function migrate()
    {
        $this->addUrlPathColumnToEvent();
        $this->addUrlPathData();
        $this->addUrlColumnToNotification();
        $this->turnOnSoftDelete();
    }

    /**
     * Add urlPath column to Event table.
     *
     * @return void
     */
    public function addUrlPathColumnToEvent()
    {
        $this->getHelper()->exec(
            'ALTER TABLE `event` '
            . 'ADD COLUMN `urlpath` varchar(255) NULL '
            . "COMMENT 'The url path used for constructing url in the notification email.' AFTER `privilegeid`"
        );
    }

    /**
     * Add data of urlPath to Event table.
     *
     * @return void
     */
    public function addUrlPathData()
    {
        $updates = array(
            'VULNERABILITY_CREATED'         => '/vm/vulnerability/view/id/',
            'VULNERABILITY_INJECTED'        => '/vm/vulnerability/view/id/',
            'ASSET_CREATED'                 => '/asset/view/id/',
            'ASSET_UPDATED'                 => '/asset/view/id/',
            'USER_CREATED'                  => '/user/view/id/',
            'USER_UPDATED'                  => '/user/profile',
            'POC_CREATED'                   => '/poc/view/id/',
            'POC_UPDATED'                   => '/poc/view/id/',
            'ORGANIZATION_CREATED'          => '/organization/view/id/',
            'ORGANIZATION_UPDATED'          => '/organization/view/id/',
            'SYSTEM_UPDATED'                => '/system/view/id/',
            'SYSTEM_CREATED'                => '/system/view/id/',
            'PRODUCT_CREATED'               => '/vm/product/view/id/',
            'PRODUCT_UPDATED'               => '/vm/product/view/id/',
            'ROLE_CREATED'                  => '/role/view/id/',
            'ROLE_UPDATED'                  => '/role/view/id/',
            'SOURCE_CREATED'                => '/finding/source/view/id/',
            'SOURCE_UPDATED'                => '/finding/source/view/id/',
            'NETWORK_CREATED'               => '/network/view/id/',
            'NETWORK_UPDATED'               => '/network/view/id/',
            'CONFIGURATION_UPDATED'         => '/config/general',
            'USER_UPDATED'                  => '/user/profile',
            'USER_LOCKED'                   => '/user/view/id/',
            'SYSTEM_DOCUMENT_UPDATED'       => '/system/view/id/',
            'SYSTEM_DOCUMENT_CREATED'       => '/system/view/id/',
            'DOCUMENT_TYPE_CREATED'         => '/document-type/view/id/',
            'DOCUMENT_TYPE_UPDATED'         => '/document-type/view/id/',
            'ORGANIZATION_TYPE_CREATED'     => '/organization-type/view/id/',
            'ORGANIZATION_TYPE_UPDATED'     => '/organization-type/view/id/'
        );

        // Since some event's names associated with finding action might be different in the user's  database,
        // it's better update urlpath with privilegeid of finding action instead of event name.
        $privileges = $this->getHelper()->query(
            'SELECT p.id FROM privilege p WHERE p.resource = "notification" AND p.action = "finding"');

        foreach ($privileges as $privilege) {
            $this->getHelper()->exec(
                "UPDATE `event` SET `urlpath` = '/finding/remediation/view/id/' WHERE `privilegeid` ="
                . $privilege->id
                . " AND `name` NOT LIKE '%_deleted'"
                );
        }

        foreach ($updates as $where => $to) {
            $this->getHelper()->update('event', array('urlpath' => $to), array('name' => $where));
        }
    }

    /**
     * Add url column to Notification table
     *
     * @return void
     */
    public function addUrlColumnToNotification()
    {
        $this->getHelper()->exec(
            'ALTER TABLE `notification` '
            . "ADD COLUMN `url` varchar(255) NULL COMMENT 'the url which is sent to the user' AFTER `userid`"
        );
    }

    /**
     * Turn on "SoftDelete" behavior and add a "description" column to Evaluation model
     *
     * @return void
     */
    public function turnOnSoftDelete()
    {
        $this->getHelper()->exec(
            'ALTER TABLE `evaluation` '
            . 'ADD COLUMN `description` text NULL AFTER `nickname`, '
            . 'ADD COLUMN `deleted_at` datetime NULL AFTER `daysuntildue`;'
        );
        $this->getHelper()->exec(
            'ALTER TABLE `event` '
            . 'ADD COLUMN `deleted_at` datetime NULL AFTER `urlpath`;'
        );
        $this->getHelper()->exec(
            'ALTER TABLE `privilege` '
            . 'ADD COLUMN `deleted_at` datetime NULL AFTER `description`;'
        );
    }
}
