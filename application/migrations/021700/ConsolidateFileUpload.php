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
 * Migration for new file upload logic.
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
     * Main migration function called by migration script.
     *
     * @return void
     */
    public function migrate()
    {
        $this->message("This migration may take some time on installations with large numbers of uploaded documents.");

        $this->createNewStuff();
        $this->migrateIncidents();
        $this->migrateSystemDocuments();
        $this->migrateFindingEvidence();
        $this->dropOldStuff();
    }

    /**
     * Migrate System Documents
     *
     * @return void
     */
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
            $uid = $this->_insertUpload(
                $document->createdts,
                $document->filename,
                $hash,
                $document->userid,
                $document->description
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
            $uid = $this->_insertUpload(
                $document->createdts,
                $document->filename,
                $hash,
                $document->userid,
                $document->description
            );
            $this->getHelper()->update(
                'system_document_version',
                array('uploadid' => $uid),
                array('id' => $document->id, 'version' => $document->version)
            );
        }

    }

    /**
     * Migrate Incidents
     *
     * @return void
     */
    public function migrateIncidents()
    {
        $incidentsPath = Fisma::getPath('uploads') . '/incident_artifact/%s/';
        $fm = $this->getFileManager();
        $artifacts = $this->getHelper()->execute('SELECT * FROM incident_artifact');
        foreach ($artifacts as $artifact) {
            $filepath = sprintf($incidentsPath, $artifact->objectid) . $artifact->filename;
            $hash = $fm->store($filepath);
            $uid = $this->_insertUpload(
                $artifact->createdts,
                $artifact->filename,
                $hash,
                $artifact->userid,
                $artifact->comment
            );
            $this->getHelper()->insert(
                'incident_upload',
                array('uploadid' => $uid, 'objectid' => $artifact->id)
            );
        }
    }

    /**
     * Migrate Finding Evidence
     * * @return void
     */
    public function migrateFindingEvidence()
    {
        $evidencePath = Fisma::getPath('uploads') . '/evidence/%s/';
        $fm = $this->getFileManager();

        // If evidence is ever DENIED, it should not show up after migration, but we still need to save it
        $query = "SELECT e.*, SUM(IF(fe.decision = 'DENIED', 1, 0)) AS denied "
                . 'FROM evidence e LEFT JOIN finding_evaluation fe ON fe.evidenceid = e.id '
                . 'GROUP BY e.id';
        $results = $this->getHelper()->execute($query);
        $deletedUploads = array();
        foreach ($results as $evidence) {
            $filepath = sprintf($evidencePath, $evidence->findingid) . $evidence->filename;
            $hash = file_exists($filepath) ? $fm->store($filepath) : null;
            $uid = $this->_insertUpload($evidence->createdts, $evidence->filename, $hash, $evidence->userid, "");
            if ($evidence->denied == 0) {
                // link the finding to the upload
                $this->getHelper()->insert(
                    'finding_upload',
                    array('uploadid' => $uid, 'objectid' => $evidence->findingid)
                );
            } else {
                $deletedUploads[$evidence->findingid][] = $uid;
            }
        }

        // output audit log messages for delted uploads
        foreach ($deletedUploads as $findingId => $uploadIds) {
            $msg = 'Rejected files have been automatically hidden during the upgrade to release 2.17. '
                   . '(' . implode(', ', $uploadIds) . ')';
            $this->getHelper()->insert(
                'finding_audit_log',
                array('createdts' => Fisma::now(), 'message' => $msg, 'objectid' => $findingId)
            );
        }

    }

    /**
     * Create new tables and columns.
     *
     * @return void
     */
    public function createNewStuff()
    {
        $columns = array(
            'uploadid' => 'bigint(20) NOT NULL DEFAULT 0',
            'objectid' => 'bigint(20) NOT NULL DEFAULT 0'
        );

        $this->getHelper()->createTable('finding_upload', $columns, array('uploadid', 'objectid'));
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
            'ALTER TABLE `finding_upload` '
            . 'ADD INDEX `finding_upload_objectid_finding_id` (`objectid`) USING BTREE, '
            . 'ADD CONSTRAINT `finding_upload_objectid_finding_id` '
            . '    FOREIGN KEY `finding_upload_objectid_finding_id` (`objectid`) REFERENCES `finding` (`id`) '
            . '    ON DELETE RESTRICT ON UPDATE RESTRICT;'
        );

        $this->getHelper()->exec(
            'ALTER TABLE `incident_upload` '
            . 'ADD INDEX `incident_upload_objectid_incident_id` (`objectid`) USING BTREE, '
            . 'ADD CONSTRAINT `incident_upload_objectid_incident_id` '
            . '    FOREIGN KEY `incident_upload_objectid_incident_id` (`objectid`) REFERENCES `incident` (`id`) '
            . '    ON DELETE RESTRICT ON UPDATE RESTRICT;'
        );
    }

    /**
     * Drop old tables and columns.
     *
     * @return void
     */
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

        $this->getHelper()->exec(
            'ALTER TABLE `finding_evaluation` '
            . 'DROP COLUMN `evidenceid`, '
            . 'DROP FOREIGN KEY `finding_evaluation_evidenceid_evidence_id`, '
            . 'DROP INDEX `evidenceid_idx`;'
        );
        $this->getHelper()->dropTable('evidence');
    }

    /**
     * Insert an Upload record.
     *
     * @param string $created
     * @param string $filename
     * @param string $hash
     * @param int $userId
     * @param string $description
     * @return int ID of the inserted record.
     */
    protected function _insertUpload($created, $filename, $hash, $userId, $description)
    {
        return $this->getHelper()->insert(
            'upload',
            array(
                'createdts' => $created,
                'filename' => $filename,
                'filehash' => $hash,
                'userid' => $userId,
                'description' => $description,
                'updated_at' => Fisma::now()
            )
        );
    }
}
