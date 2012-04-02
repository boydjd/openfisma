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
 * THIS ALSO CONTAINS THE MIGRATION FORMERLY KNOWN AS AddIcons. That migration had a dependency on this migration,
 * so it has been merged into this same class.
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
     * These are all of the icons included in the fixtures.
     *
     * The SHA1s refer to the files in 32x32px and 16x16px.
     *
     * @var array
     */
    private $_iconFiles = array(
        'globe' => array(
            '32' => 'ecc8af06815fd9f9b0737a1f9b1f82284d770c31',
            '16' => '97e0068d60d356b4cf371f2898774c58d866a80f'
        ),
        'two_terminals' => array(
            '32' => 'ce08f01dd9282dd9c7590f9f6b6deb73f1d58a39',
            '16' => 'b92cf47688db5f132305aa6d1f042efbc1ebde4e'
        ),
        'drawers' => array(
            '32' => '0904bf27da39893b6b7b8f9f17310a5d2c40e2ad',
            '16' => '07f4f747beef93c004aef2504f12b8950768112d'
        ),
        'folder' => array(
            '32' => '86c037a3ae2590dab79d695e23116239eb7b9e00',
            '16' => '6bd43c1d413e4e89a240d904ae5475010913e107'
        ),
        'server' => array(
            '32' => '805a464e7726ee4c854b5a45a847de7cb36e6872',
            '16' => '48e69f83b0fcd87edaa0afb791b2c58eb6d8dcbd'
        ),
        'terminal' => array(
            '32' => '3b54acad5b1607bab2033f7c54ce948d77f08af0',
            '16' => 'a87edd236ff6263c147cc6a0dc378f2064f88b76'
        ),
        'address_book' => array(
            '32' => '02fc2a59f3332891a42b2b070da4c0090fefe246',
            '16' => '96e5e5a098c81b47ba6fec75b9cb72a617375ca9'
        ),
        'speech_bubbles' => array(
            '32' => 'ae7fafe1f0db1d85ad4a841b53260f5f2b8aae63',
            '16' => '2f059e21ea5b5eff244b789004cd80d1072bcfe6'
        ),
        'bookmark' => array(
            '32' => 'da85536a3868d3a9f012f92b47f548e5149dfaa3',
            '16' => '34c855d7f593c7fd30547a5fec980d96898fcb6b'
        ),
        'clouds' => array(
            '32' => '5719e4da497598308d901292c554f203edd06436',
            '16' => '0edbfbdeaeb892240ae4e8ab8d9d4a1f3ac8fea6'
        ),
        'monitor' => array(
            '32' => '0a1bc7467ade61a75b4abc2af66bc703742e8af1',
            '16' => 'b4a32d50357cff8a055b5434ea2ee97a9f9fc44d'
        ),
        'mouse' => array(
            '32' => 'a5c72909beea9c4f927238a5fbc049675eb2de8c',
            '16' => '267c218368f3f235ea14e749b791c8f125a82577'
        ),
    );

    /**
     * Stores the id's associated with each icon type.
     *
     * This is filled in later as the icon rows are inserted.
     */
    private $_iconIds = array();

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
        $this->migrateOrphans(Fisma::getPath('uploads') . '/scanreports/');
        $this->migrateOrphans(Fisma::getPath('uploads') . '/spreadsheets/');
        $this->dropOldStuff();

        $this->message("Adding icons");
        $this->_addIcons();

        $this->message("Adding system types");
        $this->_addSystemTypes();

        $this->message("Adding icons to organization types");
        $this->_addIconToOrganizationType();
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
        $documents = $this->getHelper()->query(
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
        $documents = $this->getHelper()->query(
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
        Fisma_FileSystem::recursiveDelete(Fisma::getPath('uploads') . '/system-document');

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
        $artifacts = $this->getHelper()->query('SELECT * FROM incident_artifact');
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
        Fisma_FileSystem::recursiveDelete(Fisma::getPath('uploads') . '/incident_artifact');
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
        $results = $this->getHelper()->query($query);
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
        Fisma_FileSystem::recursiveDelete(Fisma::getPath('uploads') . '/evidence');
    }

    /**
     * Migrate orphaned files from the given path
     * @param string $path Path in which to search for orphans
     * @return void
     */
    public function migrateOrphans($path)
    {
        $fm = $this->getFileManager();

        // return if directory doesn't exist
        if (!is_dir($path)) {
            return;
        }
        // get a list of files from the directory
        $files = scandir($path);
        // remove "dot" files
        foreach ($files as $key => $value) {
            if ($value{0} == '.') {
                unset($files[$key]);
            }
        }
        $files = array_values($files);

        // if no files, nothing to do
        if (count($files) === 0) {
            return;
        }

        $query = "SELECT * FROM upload "
                 . "WHERE filehash IS NULL "
                 . "AND filename IN (" . implode(',', array_fill(0, count($files), '?')) . ")";
        $results = $this->getHelper()->query($query, $files);
        foreach ($results as $record) {
            $hash = $fm->store($path . $record->filename);
            $this->getHelper()->update(
                'upload',
                array('filehash' => $hash),
                array('id' => $record->id)
            );
            unlink($path . $record->filename);
        }
        Fisma_FileSystem::recursiveDelete($path);
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

    /**
     * Add the icon table and fixtures.
     */
    private function _addIcons()
    {
        // Create icon table
        $this->getHelper()->createTable(
            'icon',
            array(
                'id' => 'bigint(20) NOT NULL AUTO_INCREMENT',
                'largeiconid' => 'bigint(20) DEFAULT NULL',
                'smalliconid' => 'bigint(20) DEFAULT NULL'
            ),
            'id'
        );

        // Add icon fixtures
        foreach ($this->_iconFiles as $iconType => $iconFile) {
            $largeIconId = $this->getHelper()->insert(
                'upload',
                array('fileName' => "{$iconType}_32.png", 'fileHash' => $iconFile['32'], 'userId' => 1)
            );

            $smallIconId = $this->getHelper()->insert(
                'upload',
                array('fileName' => "{$iconType}_16.png", 'fileHash' => $iconFile['16'], 'userId' => 1)
            );

            $this->_iconIds[$iconType] = $this->getHelper()->insert(
                'icon',
                array("largeiconid" => $largeIconId, "smalliconid" => $smallIconId)
            );
        }

        $this->getHelper()->addForeignKey('icon', 'largeiconid', 'upload', 'id');
        $this->getHelper()->addForeignKey('icon', 'smalliconid', 'upload', 'id');
    }

    /**
     * Add system types and fixtures.
     */
    private function _addSystemTypes()
    {
        // Add new system type table
        $this->getHelper()->createTable(
            'system_type',
            array(
                'id' => 'bigint(20) NOT NULL AUTO_INCREMENT',
                'createdts' => 'datetime NOT NULL',
                'modifiedts' => 'datetime NOT NULL',
                'name' => 'varchar(255) COLLATE utf8_unicode_ci NOT NULL',
                'nickname' => 'varchar(255) COLLATE utf8_unicode_ci NOT NULL',
                'iconid' => 'bigint(20) DEFAULT NULL',
                'description' => 'text COLLATE utf8_unicode_ci'
            ),
            'id'
        );

        $this->getHelper()->addUniqueKey('system_type', 'nickname');
        $this->getHelper()->addForeignKey('system_type', 'iconid', 'icon', 'id');

        // Add system type fixtures
        $gssId = $this->getHelper()->insert(
            'system_type',
            array(
                'name' => 'General Support System',
                'nickname' => 'GSS',
                'iconid' => $this->_iconIds['server'],
                'description' => '<p>A general support system is a set of interconnected information resources'
                               . ' under the same'
                               . ' direct management control that shares common functionality.  A general support'
                               . ' system normally includes hardware, software, information, data, applications,'
                               . ' communications, facilities, and people and provides support for a variety of'
                               . ' users and/or applications. A general support system, for example, can be a:</p>'
                               . ' <ul>'
                               . ' <li>LAN including smart terminals that supports a branch office</li>'
                               . ' <li>Backbone (e.g., agency-wide)</li>'
                               . ' <li>Communications network</li>'
                               . ' <li>Departmental data processing center including '
                               . ' its operating system and utilities</li>'
                               . ' <li>Tactical radio network</li>'
                               . ' <li>Shared information processing service organization</li>'
                               . '</ul>'
            )
        );

        $majorId = $this->getHelper()->insert(
            'system_type',
            array(
                'name' => 'Major Application',
                'nickname' => 'Major',
                'iconid' => $this->_iconIds['terminal'],
                'description' => '<p>Major applications are systems that perform clearly defined functions for which'
                               . ' there are readily identifiable security considerations and needs (e.g., an'
                               . ' electronic funds transfer system).  A major application might comprise many'
                               . ' individual programs and hardware, software, and telecommunications components.'
                               . ' These components can be a single software application or a combination of'
                               . ' hardware/software focused on supporting a specific mission-related function.  A'
                               . ' major application may also consist of multiple individual applications if all'
                               . ' are related to a single mission function (e.g., payroll or personnel).</p>'
            )
        );

        $minorId = $this->getHelper()->insert(
            'system_type',
            array(
                'name' => 'Minor Application',
                'nickname' => 'Minor',
                'iconid' => $this->_iconIds['two_terminals']
            )
        );

        // Modify system table to accept a system type
        $this->getHelper()->addColumn('system', 'systemtypeid', 'bigint(20) NULL', 'aggregatesystemid');
        $this->getHelper()->addForeignKey('system', 'systemtypeid', 'system_type', 'id');

        $this->getHelper()->update('system', array('systemtypeid' => $gssId), array('type' => 'gss'));
        $this->getHelper()->update('system', array('systemtypeid' => $majorId), array('type' => 'major'));
        $this->getHelper()->update('system', array('systemtypeid' => $minorId), array('type' => 'minor'));

        $this->getHelper()->dropColumn('system', 'type');
    }

    /**
     * Modify the organization type table to include an icon.
     */
    private function _addIconToOrganizationType()
    {
        $this->getHelper()->addColumn('organization_type', 'iconid', 'bigint(20) NULL', 'nickname');
        $this->getHelper()->addForeignKey('organization_type', 'iconid', 'icon', 'id');

        $this->getHelper()->update(
            'organization_type',
            array('iconId' => $this->_iconIds['globe']),
            array('icon' => 'agency')
        );
        $this->getHelper()->update(
            'organization_type',
            array('iconId' => $this->_iconIds['drawers']),
            array('icon' => 'bureau')
        );
        $this->getHelper()->update(
            'organization_type',
            array('iconId' => $this->_iconIds['folder']),
            array('icon' => 'organization')
        );

        $this->getHelper()->dropColumn('organization_type', 'icon');
    }
}
