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
 * Version121 
 * 
 * @uses Doctrine_Migration_Base
 * @package Migration 
 * @copyright (c) Endeavor Systems, Inc. 2011 {@link http://www.endeavorsystems.com}
 * @author Andrew Reeves <andrew.reeves@endeavorsystems.com>
 * @license http://www.openfisma.org/content/license GPLv3
 */
class Version121 extends Doctrine_Migration_Base
{
    public function up()
    {
        $useriddef = array('local' => 'userid', 'foreign' => 'id', 'foreignTable' => 'poc');
        $this->createForeignKey('comment', 'comment_userid_poc_id', $useriddef);
        $this->createForeignKey('evidence', 'evidence_userid_poc_id', $useriddef);
        $this->createForeignKey(
            'finding',
            'finding_pocid_poc_id',
            array('local' => 'pocid', 'foreign' => 'id', 'foreignTable' => 'poc')
        );
        $this->createForeignKey(
            'finding',
            'finding_createdbyuserid_poc_id',
            array('local' => 'createdbyuserid', 'foreign' => 'id', 'foreignTable' => 'poc')
        );
        $this->createForeignKey('finding_audit_log', 'finding_audit_log_userid_poc_id', $useriddef);
        $this->createForeignKey('finding_comment', 'finding_comment_userid_poc_id', $useriddef);
        $this->createForeignKey('finding_evaluation', 'finding_evaluation_userid_poc_id', $useriddef);
        $this->createForeignKey(
            'incident',
            'incident_reportinguserid_poc_id',
            array('local' => 'reportinguserid', 'foreign' => 'id', 'foreignTable' => 'poc')
        );
        $this->createForeignKey('incident_artifact', 'incident_artifact_userid_poc_id', $useriddef);
        $this->createForeignKey('incident_audit_log', 'incident_audit_log_userid_poc_id', $useriddef);
        $this->createForeignKey('incident_comment', 'incident_comment_userid_poc_id', $useriddef);

        $this->createForeignKey('ir_incident_user', 'ir_incident_user_userid_poc_id', $useriddef);
        $this->createForeignKey('ir_incident_workflow', 'ir_incident_workflow_userid_poc_id', $useriddef);
        $this->createForeignKey('notification', 'notification_userid_poc_id', $useriddef);
        $this->createForeignKey(
            'poc_comment',
            'poc_comment_objectid_poc_id',
            array('local' => 'objectid', 'foreign' => 'id', 'foreignTable' => 'poc')
        );
        $this->createForeignKey('poc_comment', 'poc_comment_userid_poc_id', $useriddef);
        $this->createForeignKey(
            'poc_audit_log',
            'poc_audit_log_objectid_poc_id',
            array('local' => 'objectid', 'foreign' => 'id', 'foreignTable' => 'poc')
        );
        $this->createForeignKey('poc_audit_log', 'poc_audit_log_userid_poc_id', $useriddef);
        $this->createForeignKey('system_document', 'system_document_userid_poc_id', $useriddef);
        $this->createForeignKey('upload', 'upload_userid_poc_id', $useriddef);
        $this->createForeignKey('user_event', 'user_event_userid_poc_id', $useriddef);
        $this->createForeignKey('user_role', 'user_role_userid_poc_id', $useriddef);
        $this->createForeignKey('user_audit_log', 'user_audit_log_userid_poc_id', $useriddef);
        $this->createForeignKey('user_comment', 'user_comment_userid_poc_id', $useriddef);
        $this->createForeignKey(
            'vulnerability',
            'vulnerability_createdbyuserid_poc_id',
            array('local' => 'createdbyuserid', 'foreign' => 'id', 'foreignTable' => 'poc')
        );
        $this->createForeignKey('vulnerability_audit_log', 'vulnerability_audit_log_userid_poc_id', $useriddef);
        $this->createForeignKey('vulnerability_comment', 'vulnerability_comment_userid_poc_id', $useriddef);
        $this->createForeignKey(
            'system',
            'system_aggregatesystemid_system_id',
            array('local' => 'aggregatesystemid', 'foreign' => 'id', 'foreignTable' => 'system')
        );
    }

    public function down()
    {
    }
}
