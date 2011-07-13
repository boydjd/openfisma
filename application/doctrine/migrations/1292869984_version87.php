<?php
// @codingStandardsIgnoreFile
/**
 * Copyright (c) 2010 Endeavor Systems, Inc.
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
 * Setup Foreign keys and Indexes on the new vulnerability tables
 *
 * @package Migration
 * @copyright (c) Endeavor Systems, Inc. 2010 {@link http://www.endeavorsystems.com}
 * @author Christian Smith <christian.smith@endeavorsystems.com>
 * @license http://www.openfisma.org/content/license GPLv3
 */
class Version87 extends Doctrine_Migration_Base
{
    public function up()
    {
        $this->createForeignKey('vulnerability', 'vulnerability_assetid_asset_id', array(
            'name' => 'vulnerability_assetid_asset_id',
            'local' => 'assetid',
            'foreign' => 'id',
            'foreignTable' => 'asset',
        ));
        $this->createForeignKey('vulnerability', 'vulnerability_createdbyuserid_user_id', array(
            'name' => 'vulnerability_createdbyuserid_user_id',
            'local' => 'createdbyuserid',
            'foreign' => 'id',
            'foreignTable' => 'user',
        ));
        $this->createForeignKey('vulnerability', 'vulnerability_resolutionid_vulnerability_resolution_id', array(
            'name' => 'vulnerability_resolutionid_vulnerability_resolution_id',
            'local' => 'resolutionid',
            'foreign' => 'id',
            'foreignTable' => 'vulnerability_resolution',
        ));
        $this->createForeignKey('vulnerability_bugtraq', 'vulnerability_bugtraq_bugtraq_id_bugtraq_id', array(
            'name' => 'vulnerability_bugtraq_bugtraq_id_bugtraq_id',
            'local' => 'bugtraq_id',
            'foreign' => 'id',
            'foreignTable' => 'bugtraq',
        ));
        $this->createForeignKey('vulnerability_bugtraq', 'vulnerability_bugtraq_vulnerability_id_vulnerability_id', array(
            'name' => 'vulnerability_bugtraq_vulnerability_id_vulnerability_id',
            'local' => 'vulnerability_id',
            'foreign' => 'id',
            'foreignTable' => 'vulnerability',
        ));
        $this->createForeignKey('vulnerability_cve', 'vulnerability_cve_cve_id_cve_id', array(
            'name' => 'vulnerability_cve_cve_id_cve_id',
            'local' => 'cve_id',
            'foreign' => 'id',
            'foreignTable' => 'cve',
        ));
        $this->createForeignKey('vulnerability_cve', 'vulnerability_cve_vulnerability_id_vulnerability_id', array(
            'name' => 'vulnerability_cve_vulnerability_id_vulnerability_id',
            'local' => 'vulnerability_id',
            'foreign' => 'id',
            'foreignTable' => 'vulnerability',
        ));
        $this->createForeignKey('vulnerability_upload', 'vulnerability_upload_vulnerabilityid_vulnerability_id', array(
            'name' => 'vulnerability_upload_vulnerabilityid_vulnerability_id',
            'local' => 'vulnerabilityid',
            'foreign' => 'id',
            'foreignTable' => 'vulnerability',
        ));
        $this->createForeignKey('vulnerability_upload', 'vulnerability_upload_uploadid_upload_id', array(
            'name' => 'vulnerability_upload_uploadid_upload_id',
            'local' => 'uploadid',
            'foreign' => 'id',
            'foreignTable' => 'upload',
        ));
        $this->createForeignKey('vulnerability_xref', 'vulnerability_xref_vulnerability_id_vulnerability_id', array(
            'name' => 'vulnerability_xref_vulnerability_id_vulnerability_id',
            'local' => 'vulnerability_id',
            'foreign' => 'id',
            'foreignTable' => 'vulnerability',
        ));
        $this->createForeignKey('user_audit_log', 'user_audit_log_objectid_user_id', array(
            'name' => 'user_audit_log_objectid_user_id',
            'local' => 'objectid',
            'foreign' => 'id',
            'foreignTable' => 'user',
        ));
        $this->createForeignKey('vulnerability_audit_log', 'vulnerability_audit_log_objectid_vulnerability_id', array(
            'name' => 'vulnerability_audit_log_objectid_vulnerability_id',
            'local' => 'objectid',
            'foreign' => 'id',
            'foreignTable' => 'vulnerability',
        ));
        $this->createForeignKey('vulnerability_audit_log', 'vulnerability_audit_log_userid_user_id', array(
            'name' => 'vulnerability_audit_log_userid_user_id',
            'local' => 'userid',
            'foreign' => 'id',
            'foreignTable' => 'user',
        ));
        $this->createForeignKey('vulnerability_comment', 'vulnerability_comment_objectid_vulnerability_id', array(
            'name' => 'vulnerability_comment_objectid_vulnerability_id',
            'local' => 'objectid',
            'foreign' => 'id',
            'foreignTable' => 'vulnerability',
        ));
        $this->createForeignKey('vulnerability_comment', 'vulnerability_comment_userid_user_id', array(
            'name' => 'vulnerability_comment_userid_user_id',
            'local' => 'userid',
            'foreign' => 'id',
            'foreignTable' => 'user',
        ));
        $this->addIndex('vulnerability', 'vulnerability_assetid', array(
            'fields' => 
            array(
                0 => 'assetid',
            ),
        ));
        $this->addIndex('vulnerability', 'vulnerability_createdbyuserid', array(
            'fields' => 
            array(
                0 => 'createdbyuserid',
            ),
        ));
        $this->addIndex('vulnerability', 'vulnerability_resolutionid', array(
            'fields' => 
            array(
                0 => 'resolutionid',
            ),
        ));
        $this->addIndex('vulnerability_bugtraq', 'vulnerability_bugtraq_bugtraq_id', array(
            'fields' => 
            array(
                0 => 'bugtraq_id',
            ),
        ));
        $this->addIndex('vulnerability_bugtraq', 'vulnerability_bugtraq_vulnerability_id', array(
            'fields' => 
            array(
                0 => 'vulnerability_id',
            ),
        ));
        $this->addIndex('vulnerability_cve', 'vulnerability_cve_cve_id', array(
            'fields' => 
            array(
                0 => 'cve_id',
            ),
        ));
        $this->addIndex('vulnerability_cve', 'vulnerability_cve_vulnerability_id', array(
            'fields' => 
            array(
                0 => 'vulnerability_id',
            ),
        ));
        $this->addIndex('vulnerability_upload', 'vulnerability_upload_vulnerabilityid', array(
            'fields' => 
            array(
                0 => 'vulnerabilityid',
            ),
        ));
        $this->addIndex('vulnerability_upload', 'vulnerability_upload_uploadid', array(
            'fields' => 
            array(
                0 => 'uploadid',
            ),
        ));
        $this->addIndex('vulnerability_xref', 'vulnerability_xref_vulnerability_id', array(
            'fields' => 
            array(
                0 => 'vulnerability_id',
            ),
        ));
        $this->addIndex('user_audit_log', 'user_audit_log_objectid', array(
            'fields' => 
            array(
                0 => 'objectid',
            ),
        ));
        $this->addIndex('vulnerability_audit_log', 'vulnerability_audit_log_objectid', array(
            'fields' => 
            array(
                0 => 'objectid',
            ),
        ));
        $this->addIndex('vulnerability_audit_log', 'vulnerability_audit_log_userid', array(
            'fields' => 
            array(
                0 => 'userid',
            ),
        ));
        $this->addIndex('vulnerability_comment', 'vulnerability_comment_objectid', array(
            'fields' => 
            array(
                0 => 'objectid',
            ),
        ));
        $this->addIndex('vulnerability_comment', 'vulnerability_comment_userid', array(
            'fields' => 
            array(
                0 => 'userid',
            ),
        ));
    }

    public function down()
    {
        $this->createForeignKey('finding', 'finding_assetid_asset_id', array(
            'name' => 'finding_assetid_asset_id',
            'local' => 'assetid',
            'foreign' => 'id',
            'foreignTable' => 'asset',
        ));
        $this->createForeignKey('finding_bugtraq', 'finding_bugtraq_bugtraq_id_bugtraq_id', array(
            'name' => 'finding_bugtraq_bugtraq_id_bugtraq_id',
            'local' => 'bugtraq_id',
            'foreign' => 'id',
            'foreignTable' => 'bugtraq',
        ));
        $this->createForeignKey('finding_bugtraq', 'finding_bugtraq_finding_id_finding_id', array(
            'name' => 'finding_bugtraq_finding_id_finding_id',
            'local' => 'finding_id',
            'foreign' => 'id',
            'foreignTable' => 'finding',
        ));
        $this->createForeignKey('finding_cve', 'finding_cve_cve_id_cve_id', array(
            'name' => 'finding_cve_cve_id_cve_id',
            'local' => 'cve_id',
            'foreign' => 'id',
            'foreignTable' => 'cve',
        ));
        $this->createForeignKey('finding_cve', 'finding_cve_finding_id_finding_id', array(
            'name' => 'finding_cve_finding_id_finding_id',
            'local' => 'finding_id',
            'foreign' => 'id',
            'foreignTable' => 'finding',
        ));
        $this->createForeignKey('finding_xref', 'finding_xref_finding_id_finding_id', array(
            'name' => 'finding_xref_finding_id_finding_id',
            'local' => 'finding_id',
            'foreign' => 'id',
            'foreignTable' => 'finding',
        ));
        $this->dropForeignKey('vulnerability', 'vulnerability_assetid_asset_id');
        $this->dropForeignKey('vulnerability', 'vulnerability_createdbyuserid_user_id');
        $this->dropForeignKey('vulnerability', 'vulnerability_resolutionid_vulnerability_resolution_id');
        $this->dropForeignKey('vulnerability_bugtraq', 'vulnerability_bugtraq_bugtraq_id_bugtraq_id');
        $this->dropForeignKey('vulnerability_bugtraq', 'vulnerability_bugtraq_vulnerability_id_vulnerability_id');
        $this->dropForeignKey('vulnerability_cve', 'vulnerability_cve_cve_id_cve_id');
        $this->dropForeignKey('vulnerability_cve', 'vulnerability_cve_vulnerability_id_vulnerability_id');
        $this->dropForeignKey('vulnerability_upload', 'vulnerability_upload_vulnerabilityid_vulnerability_id');
        $this->dropForeignKey('vulnerability_upload', 'vulnerability_upload_uploadid_upload_id');
        $this->dropForeignKey('vulnerability_xref', 'vulnerability_xref_vulnerability_id_vulnerability_id');
        $this->dropForeignKey('user_audit_log', 'user_audit_log_objectid_user_id');
        $this->dropForeignKey('vulnerability_audit_log', 'vulnerability_audit_log_objectid_vulnerability_id');
        $this->dropForeignKey('vulnerability_audit_log', 'vulnerability_audit_log_userid_user_id');
        $this->dropForeignKey('vulnerability_comment', 'vulnerability_comment_objectid_vulnerability_id');
        $this->dropForeignKey('vulnerability_comment', 'vulnerability_comment_userid_user_id');
        $this->removeIndex('vulnerability', 'vulnerability_assetid', array(
            'fields' => 
            array(
                0 => 'assetid',
            ),
        ));
        $this->removeIndex('vulnerability', 'vulnerability_createdbyuserid', array(
            'fields' => 
            array(
                0 => 'createdbyuserid',
            ),
        ));
        $this->removeIndex('vulnerability', 'vulnerability_resolutionid', array(
            'fields' => 
            array(
                0 => 'resolutionid',
            ),
        ));
        $this->removeIndex('vulnerability_bugtraq', 'vulnerability_bugtraq_bugtraq_id', array(
            'fields' => 
            array(
                0 => 'bugtraq_id',
            ),
        ));
        $this->removeIndex('vulnerability_bugtraq', 'vulnerability_bugtraq_vulnerability_id', array(
            'fields' => 
            array(
                0 => 'vulnerability_id',
            ),
        ));
        $this->removeIndex('vulnerability_cve', 'vulnerability_cve_cve_id', array(
            'fields' => 
            array(
                0 => 'cve_id',
            ),
        ));
        $this->removeIndex('vulnerability_cve', 'vulnerability_cve_vulnerability_id', array(
            'fields' => 
            array(
                0 => 'vulnerability_id',
            ),
        ));
        $this->removeIndex('vulnerability_upload', 'vulnerability_upload_vulnerabilityid', array(
            'fields' => 
            array(
                0 => 'vulnerabilityid',
            ),
        ));
        $this->removeIndex('vulnerability_upload', 'vulnerability_upload_uploadid', array(
            'fields' => 
            array(
                0 => 'uploadid',
            ),
        ));
        $this->removeIndex('vulnerability_xref', 'vulnerability_xref_vulnerability_id', array(
            'fields' => 
            array(
                0 => 'vulnerability_id',
            ),
        ));
        $this->removeIndex('user_audit_log', 'user_audit_log_objectid', array(
            'fields' => 
            array(
                0 => 'objectid',
            ),
        ));
        $this->removeIndex('vulnerability_audit_log', 'vulnerability_audit_log_objectid', array(
            'fields' => 
            array(
                0 => 'objectid',
            ),
        ));
        $this->removeIndex('vulnerability_audit_log', 'vulnerability_audit_log_userid', array(
            'fields' => 
            array(
                0 => 'userid',
            ),
        ));
        $this->removeIndex('vulnerability_comment', 'vulnerability_comment_objectid', array(
            'fields' => 
            array(
                0 => 'objectid',
            ),
        ));
        $this->removeIndex('vulnerability_comment', 'vulnerability_comment_userid', array(
            'fields' => 
            array(
                0 => 'userid',
            ),
        ));
    }
}
