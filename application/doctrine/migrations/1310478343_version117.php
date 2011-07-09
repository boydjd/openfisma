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
 * Version117 
 * 
 * @uses Doctrine_Migration_Base
 * @package Migration 
 * @copyright (c) Endeavor Systems, Inc. 2011 {@link http://www.endeavorsystems.com}
 * @author Andrew Reeves <andrew.reeves@endeavorsystems.com>
 * @license http://www.openfisma.org/content/license GPLv3
 */
class Version117 extends Doctrine_Migration_Base
{
    public function up()
    {
        $this->dropForeignKey('comment', 'comment_userid_user_id');
        $this->dropForeignKey('evidence', 'evidence_userid_user_id');
        $this->dropForeignKey('finding', 'finding_assignedtouserid_user_id');
        $this->dropForeignKey('finding', 'finding_createdbyuserid_user_id');
        $this->dropForeignKey('finding_audit_log', 'finding_audit_log_userid_user_id');
        $this->dropForeignKey('finding_comment', 'finding_comment_userid_user_id');
        $this->dropForeignKey('finding_evaluation', 'finding_evaluation_userid_user_id');
        $this->dropForeignKey('incident', 'incident_reportinguserid_user_id');
        $this->dropForeignKey('incident_artifact', 'incident_artifact_userid_user_id');
        $this->dropForeignKey('incident_audit_log', 'incident_audit_log_userid_user_id');
        $this->dropForeignKey('incident_comment', 'incident_comment_userid_user_id');
        $this->dropForeignKey('ir_incident_user', 'ir_incident_user_userid_user_id');
        $this->dropForeignKey('ir_incident_workflow', 'ir_incident_workflow_userid_user_id');
        $this->dropForeignKey('notification', 'notification_userid_user_id');
        $this->dropForeignKey('user_audit_log', 'user_audit_log_userid_user_id');
        $this->dropForeignKey('user_comment', 'user_comment_userid_user_id');
        $this->dropForeignKey('system_document', 'system_document_userid_user_id');
        $this->dropForeignKey('upload', 'upload_userid_user_id');
        $this->dropForeignKey('user_event', 'user_event_userid_user_id');
        $this->dropForeignKey('user_role', 'user_role_userid_user_id');
        $this->dropForeignKey('vulnerability', 'vulnerability_createdbyuserid_user_id');
        $this->dropForeignKey('vulnerability_audit_log', 'vulnerability_audit_log_userid_user_id');
        $this->dropForeignKey('vulnerability_comment', 'vulnerability_comment_userid_user_id');
    }

    public function down()
    {
    }
}
