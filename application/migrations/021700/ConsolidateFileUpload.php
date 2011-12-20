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

/**
 * Application_Migration_021700_ConsolidateFileUpload 
 * 
 * @uses Fisma_Migration_Abstract
 * @package Migration
 * @copyright (c) Endeavor Systems, Inc. 2011 {@link http://www.endeavorsystems.com}
 * @author Andrew Reeves <andrew.reeves@endeavorsystems.com> 
 * @license http://www.openfisma.org/content/license GPLv3
 */
class Application_Migration_021700_ConsolidateFileUpload extends Fisma_Migration_Abstract
{
    /**
     * migrate 
     * 
     * @return void
     */
    public function migrate()
    {
        $this->createNewStuff();
        $this->migrateIncidents();
        $this->migrateSystemDocuments();
        $this->dropOldStuff();
    }

    public function migrateSystemDocuments()
    {
        $path = Fisma::getPath('uploads') . '/system-document/%s/';

        $fm = $this->getFileManager();
        $documents = $this->getHelper()->execute(
            'SELECT d.*, o.id oid FROM system_document d LEFT JOIN organization o ON d.systemid = o.systemid'
        );
        foreach ($documents as $document) {
            $filepath = sprintf($path, $document->oid) . $document->filename;
            $hash = $fm->store($filepath);
            $uid = $this->getHelper()->insert(
                'upload',
                array(
                    'createdts' => $document->createdts,
                    'filename' => $document->filename,
                    'filehash' => $hash,
                    'userid' => $document->userid,
                    'description' => $document->description,
                    'updated_at' => Fisma::now()
                )
            );
            $this->getHelper()->update(
                'system_document',
                array('uploadid' => $uid),
                array('id' => $document->id)
            );
        }
        // again, but for document versions
        $documents = $this->getHelper()->execute(
            'SELECT d.*, o.id oid FROM system_document_version d LEFT JOIN organization o ON d.systemid = o.systemid'
        );
        foreach ($documents as $document) {
            $filepath = sprintf($path, $document->oid) . $document->filename;
            $hash = $fm->store($filepath);
            $uid = $this->getHelper()->insert(
                'upload',
                array(
                    'createdts' => $document->createdts,
                    'filename' => $document->filename,
                    'filehash' => $hash,
                    'userid' => $document->userid,
                    'description' => $document->description,
                    'updated_at' => Fisma::now()
                )
            );
            $this->getHelper()->update(
                'system_document_version',
                array('uploadid' => $uid),
                array('id' => $document->id, 'version' => $document->version)
            );
        }

    }

    public function migrateIncidents()
    {
        $incidentsPath = Fisma::getPath('uploads') . '/incident_artifact/%s/';
        $fm = $this->getFileManager();
        $artifacts = $this->getHelper()->execute('SELECT * FROM incident_artifact');
        foreach ($artifacts as $artifact) {
            $filepath = sprintf($incidentsPath, $artifact->objectid) . $artifact->filename;
            $hash = $fm->store($filepath);
            $uid = $this->getHelper()->insert(
                'upload',
                array(
                    'createdts' => $artifact->createdts,
                    'filename' => $artifact->filename,
                    'filehash' => $hash,
                    'userid' => $artifact->userid,
                    'description' => $artifact->comment,
                    'updated_at' => Fisma::now()
                )
            );
            $this->getHelper()->insert(
                'incident_upload',
                array('uploadid' => $uid, 'objectid' => $artifact->id)
            );
        }
    }

    public function createNewStuff()
    {
        $columns = array(
            'uploadid' => 'bigint(20) NOT NULL DEFAULT 0',
            'objectid' => 'bigint(20) NOT NULL DEFAULT 0'
        );

        $this->getHelper()->createTable('evidence_upload', $columns, array('uploadid', 'objectid'));
        $this->getHelper()->createTable('incident_upload', $columns, array('uploadid', 'objectid'));

        $this->getHelper()->exec(
            'ALTER TABLE `system_document` '
            . 'ADD COLUMN `uploadid` bigint(20) NULL AFTER `documenttypeid`, '
            . 'ADD INDEX `uploadid_idx` (`uploadid`) USING BTREE, '
            . 'ADD CONSTRAINT `system_document_uploadid_upload_id` '
            . '    FOREIGN KEY `system_document_uploadid_upload_id` (`uploadid`) REFERENCES `upload` (`id`) '
            . '    ON DELETE RESTRICT ON UPDATE RESTRICT'
        );

        $this->getHelper()->exec(
            'ALTER TABLE `system_document_version` '
            . 'ADD COLUMN `uploadid` bigint(20) NULL AFTER `documenttypeid`'
        );

        $this->getHelper()->exec(
            'ALTER TABLE `upload` '
            . 'ADD COLUMN `filehash` char(40) NULL AFTER `filename`, '
            . 'ADD COLUMN `uploadip` varchar(39) NULL AFTER `filehash`, '
            . 'ADD COLUMN `description` text NULL AFTER `userid`;'
        );

        $this->getHelper()->exec(
            'ALTER TABLE `evidence_upload` '
            . 'ADD INDEX `evidence_upload_objectid_evidence_id` (`objectid`) USING BTREE, '
            . 'ADD CONSTRAINT `evidence_upload_objectid_evidence_id` '
            . '    FOREIGN KEY `evidence_upload_objectid_evidence_id` (`objectid`) REFERENCES `evidence` (`id`) '
            . '    ON DELETE RESTRICT ON UPDATE RESTRICT;'
        );

        $this->getHelper()->exec(
            'ALTER TABLE `incident_upload` '
            . 'ADD INDEX `incident_upload_objectid_incident_id` (`objectid`) USING BTREE, '
            . 'A""DD CONSTRAINT `incident_upload_objectid_incident_id` '
            . '    FOREIGN KEY `incident_upload_objectid_incident_id` (`objectid`) REFERENCES `incident` (`id`) '
            . '    ON DELETE RESTRICT ON UPDATE RESTRICT;'
        );
    }

    public function dropOldStuff()
    {
        $this->getHelper()->dropTable('incident_artifact');

        $this->getHelper()->exec(
            'ALTER TABLE `system_document` '
            . 'DROP COLUMN `createdts`, '
            . 'DROP COLUMN `mimetype`, '
            . 'DROP COLUMN `filename`, '
            . 'DROP COLUMN `size`, '
            . 'DROP COLUMN `userid`, '
            . 'DROP COLUMN `updated_at`, '
            . 'DROP FOREIGN KEY `system_document_userid_poc_id`, '
            . 'DROP INDEX `userid_idx`'
        );

        $this->getHelper()->exec(
            'ALTER TABLE `system_document_version` '
            . 'DROP COLUMN `createdts`, '
            . 'DROP COLUMN `mimetype`, '
            . 'DROP COLUMN `filename`, '
            . 'DROP COLUMN `size`, '
            . 'DROP COLUMN `userid`'
        );
    }
}
