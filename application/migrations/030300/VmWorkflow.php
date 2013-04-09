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
class Application_Migration_030300_VmWorkflow extends Fisma_Migration_Abstract
{
    /**
     * Migrate.
     */
    public function migrate()
    {
        $helper = $this->getHelper();

        $this->message('Add new configuration items');
        $helper->addColumn('configuration', 'vm_reopen_destination', 'BIGINT(20) DEFAULT NULL', 'asset_service_tags');

        $this->message('Add HasAttachments behavior to Vulnerability model');
        $helper->createTable(
            'vulnerability_attachment',
            array(
                'uploadid' => "bigint(20) NOT NULL DEFAULT '0' "
                            . "COMMENT 'The uploaded file'",
                'objectid' => "bigint(20) NOT NULL DEFAULT '0' "
                            . "COMMENT 'The parent object to which the attachment belongs'"
            ),
            array('uploadid', 'objectid')
        );
        $helper->addForeignKey('vulnerability_attachment', 'objectid', 'vulnerability', 'id');
        $helper->addIndex('vulnerability_attachment', 'objectid', 'vulnerability_attachment_objectid_vulnerability_id');
        $helper->dropIndexes('vulnerability_attachment', 'objectid_idx');

        // Rename finding_upload => finding_attachment
        $helper->dropForeignKeys('finding_upload', 'finding_upload_objectid_finding_id');
        $helper->dropIndexes('finding_upload', 'finding_upload_objectid_finding_id');
        $helper->exec('ALTER TABLE `finding_upload` RENAME TO `finding_attachment`');
        $helper->addForeignKey('finding_attachment', 'objectid', 'finding', 'id');
        $helper->addIndex('finding_attachment', 'objectid', 'finding_attachment_objectid_finding_id');
        $helper->dropIndexes('finding_attachment', 'objectid_idx');

        // Rename incident_upload => incident_attachment
        $helper->dropForeignKeys('incident_upload', 'incident_upload_objectid_incident_id');
        $helper->dropIndexes('incident_upload', 'incident_upload_objectid_incident_id');
        $helper->exec('ALTER TABLE `incident_upload` RENAME TO `incident_attachment`');
        $helper->addForeignKey('incident_attachment', 'objectid', 'incident', 'id');
        $helper->addIndex('incident_attachment', 'objectid', 'incident_attachment_objectid_incident_id');
        $helper->dropIndexes('incident_attachment', 'objectid_idx');
    }
}
