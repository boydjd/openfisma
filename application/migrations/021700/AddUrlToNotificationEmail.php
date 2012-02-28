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
 * This migration adds the migration table.
 *
 * @author     Mark Ma <mark.ma@reyosoft.com>
 * @copyright  (c) Endeavor Systems, Inc. 2012 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Migration
 */
class Application_Migration_021700_AddUrlToNotificationEmail extends Fisma_Migration_Abstract
{
    /**
     * Main migration function called by migration script
     *
     * @return void
     */
    public function migrate()
    {
        $this->addUrlPathColumnToEvent();
        $this->addUrlPathData();
        $this->addUrlColumnToNotification(); 
    }

    /**
     * Add urlPath column to Event table
     *
     * @return void
     */
    public function addUrlPathColumnToEvent()
    {
        $this->getHelper()->exec(
            'ALTER TABLE `event` '
            . 'ADD COLUMN `urlpath` varchar(255) NULL AFTER `privilegeid`'
        );
    }

    /**
     * Add data of urlPath 
     *
     * @return void
     */
    public function addUrlPathData()
    {
        $updates = array(
            'FINDING_CREATED'               => '/finding/remediation/view/id/',
            'FINDING_INJECTED'              => '/finding/remediation/view/id/',
            'VULNERABILITY_CREATED'         => '/vm/vulnerability/view/id/',
            'VULNERABILITY_INJECTED'        => '/vm/vulnerability/view/id/',
            'ASSET_CREATED'                 => '/asset/view/id/',
            'ASSET_UPDATED'                 => '/asset/view/id/',
            'UPDATE_MITIGATION_TYPE'        => '/finding/remediation/view/id/',
            'UPDATE_COURSE_OF_ACTION'       => '/finding/remediation/view/id/',
            'UPDATE_RESPONSIBLE_SYSTEM'     => '/finding/remediation/view/id/',
            'UPDATE_DESCRIPTION'            => '/finding/remediation/view/id/',
            'UPDATE_SECURITY_CONTROL'       => '/finding/remediation/view/id/',
            'UPDATE_COUNTERMEASURES'        => '/finding/remediation/view/id/',
            'UPDATE_THREAT'                 => '/finding/remediation/view/id/',
            'UPDATE_RECOMMENDATION'         => '/finding/remediation/view/id/',
            'UPDATE_RESOURCES_REQUIRED'     => '/finding/remediation/view/id/',
            'UPDATE_ECD'                    => '/finding/remediation/view/id/',
            'UPDATE_LOCKED_ECD'             => '/finding/remediation/view/id/',
            'UPDATE_LEGACY_FINDING_KEY'     => '/finding/remediation/view/id/',
            'UPDATE_FINDING_SOURCE'         => '/finding/remediation/view/id/',
            'FINDING_CLOSED'                => '/finding/remediation/view/id/',
            'EVIDENCE_UPLOADED'             => '/finding/remediation/view/id/',
            'MITIGATION_ISSO'               => '/finding/remediation/view/id/',
            'MITIGATION_IVV'                => '/finding/remediation/view/id/',
            'EVIDENCE_ISSO'                 => '/finding/remediation/view/id/',
            'EVIDENCE_IVV'                  => '/finding/remediation/view/id/',
            'USER_CREATED'                  => '/user/profile',
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
            'ECD_EXPIRES_TODAY'             => '/finding/remediation/view/id/',
            'ECD_EXPIRES_7_DAYS'            => '/finding/remediation/view/id/',
            'ECD_EXPIRES_14_DAYS'           => '/finding/remediation/view/id/',
            'ECD_EXPIRES_21_DAYS'           => '/finding/remediation/view/id/',
            'APPROVAL_DENIED'               => '/finding/remediation/view/id/',
            'USER_LOCKED'                   => '/user/view/id/',
            'MITIGATION_APPROVED'           => '/finding/remediation/view/id/',
            'MITIGATION_REVISE'             => '/finding/remediation/view/id/',
            'SYSTEM_DOCUMENT_UPDATED'       => '/system/view/id/',
            'SYSTEM_DOCUMENT_CREATED'       => '/system/view/id/',
            'DOCUMENT_TYPE_CREATED'         => '/document-type/view/id/',
            'DOCUMENT_TYPE_UPDATED'         => '/document-type/view/id/',
            'ORGANIZATION_TYPE_CREATED'     => '/organization-type/view/id/',
            'ORGANIZATION_TYPE_UPDATED'     => '/organization-type/view/id/'
        );

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
            . 'ADD COLUMN `url` varchar(255) NULL AFTER `userid`'
        );
    }
}
