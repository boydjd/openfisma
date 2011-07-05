<?php
// @codingStandardsIgnoreFile
/**
 * Copyright (c) 2008 Endeavor Systems, Inc.
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
 * Add foreign keys for incident tables (Versions 39-49)
 * 
 * This file contains generated code... skip standards check.
 * 
 * @package Migration
 * @copyright (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @author Mark E. Haase <mhaase@endeavorsystems.com> 
 * @license http://www.openfisma.org/content/license GPLv3
 */
class Version50 extends Doctrine_Migration_Base
{
    /**
     * Create foreign keys for all tables in the incident module
     */
    public function up()
    {
		$this->createForeignKey('incident', 'incident_currentworkflowstepid_ir_incident_workflow_id', array(
             'name' => 'incident_currentworkflowstepid_ir_incident_workflow_id',
             'local' => 'currentworkflowstepid',
             'foreign' => 'id',
             'foreignTable' => 'ir_incident_workflow',
             ));
		$this->createForeignKey('incident', 'incident_reportinguserid_user_id', array(
             'name' => 'incident_reportinguserid_user_id',
             'local' => 'reportinguserid',
             'foreign' => 'id',
             'foreignTable' => 'user',
             ));
		$this->createForeignKey('incident', 'incident_categoryid_ir_sub_category_id', array(
             'name' => 'incident_categoryid_ir_sub_category_id',
             'local' => 'categoryid',
             'foreign' => 'id',
             'foreignTable' => 'ir_sub_category',
             ));
		$this->createForeignKey('ir_incident_actor', 'ir_incident_actor_incidentid_incident_id', array(
             'name' => 'ir_incident_actor_incidentid_incident_id',
             'local' => 'incidentid',
             'foreign' => 'id',
             'foreignTable' => 'incident',
             ));
		$this->createForeignKey('ir_incident_observer', 'ir_incident_observer_incidentid_incident_id', array(
             'name' => 'ir_incident_observer_incidentid_incident_id',
             'local' => 'incidentid',
             'foreign' => 'id',
             'foreignTable' => 'incident',
             ));
		$this->createForeignKey('ir_incident_workflow', 'ir_incident_workflow_incidentid_incident_id', array(
             'name' => 'ir_incident_workflow_incidentid_incident_id',
             'local' => 'incidentid',
             'foreign' => 'id',
             'foreignTable' => 'incident',
             ));
		$this->createForeignKey('ir_incident_workflow', 'ir_incident_workflow_roleid_role_id', array(
             'name' => 'ir_incident_workflow_roleid_role_id',
             'local' => 'roleid',
             'foreign' => 'id',
             'foreignTable' => 'role',
             ));
		$this->createForeignKey('ir_incident_workflow', 'ir_incident_workflow_userid_user_id', array(
             'name' => 'ir_incident_workflow_userid_user_id',
             'local' => 'userid',
             'foreign' => 'id',
             'foreignTable' => 'user',
             ));
		$this->createForeignKey('ir_step', 'ir_step_workflowid_ir_workflow_def_id', array(
             'name' => 'ir_step_workflowid_ir_workflow_def_id',
             'local' => 'workflowid',
             'foreign' => 'id',
             'foreignTable' => 'ir_workflow_def',
             ));
		$this->createForeignKey('ir_step', 'ir_step_roleid_role_id', array(
             'name' => 'ir_step_roleid_role_id',
             'local' => 'roleid',
             'foreign' => 'id',
             'foreignTable' => 'role',
             ));
		$this->createForeignKey('ir_sub_category', 'ir_sub_category_categoryid_ir_category_id', array(
             'name' => 'ir_sub_category_categoryid_ir_category_id',
             'local' => 'categoryid',
             'foreign' => 'id',
             'foreignTable' => 'ir_category',
             ));
		$this->createForeignKey('ir_sub_category', 'ir_sub_category_workflowid_ir_workflow_def_id', array(
             'name' => 'ir_sub_category_workflowid_ir_workflow_def_id',
             'local' => 'workflowid',
             'foreign' => 'id',
             'foreignTable' => 'ir_workflow_def',
             ));
		$this->createForeignKey('incident_comment', 'incident_comment_objectid_incident_id', array(
             'name' => 'incident_comment_objectid_incident_id',
             'local' => 'objectid',
             'foreign' => 'id',
             'foreignTable' => 'incident',
             ));
		$this->createForeignKey('incident_comment', 'incident_comment_userid_user_id', array(
             'name' => 'incident_comment_userid_user_id',
             'local' => 'userid',
             'foreign' => 'id',
             'foreignTable' => 'user',
             ));
		$this->createForeignKey('incident_artifact', 'incident_artifact_objectid_incident_id', array(
             'name' => 'incident_artifact_objectid_incident_id',
             'local' => 'objectid',
             'foreign' => 'id',
             'foreignTable' => 'incident',
             ));
		$this->createForeignKey('incident_artifact', 'incident_artifact_userid_user_id', array(
             'name' => 'incident_artifact_userid_user_id',
             'local' => 'userid',
             'foreign' => 'id',
             'foreignTable' => 'user',
             ));
		$this->createForeignKey('incident_audit_log', 'incident_audit_log_objectid_incident_id', array(
             'name' => 'incident_audit_log_objectid_incident_id',
             'local' => 'objectid',
             'foreign' => 'id',
             'foreignTable' => 'incident',
             ));
		$this->createForeignKey('incident_audit_log', 'incident_audit_log_userid_user_id', array(
             'name' => 'incident_audit_log_userid_user_id',
             'local' => 'userid',
             'foreign' => 'id',
             'foreignTable' => 'user',
             ));
    }

    /**
     * Drop foreign keys for all tables in the incident module
     */
    public function down()
    {
		$this->dropForeignKey('incident', 'incident_currentworkflowstepid_ir_incident_workflow_id');
		$this->dropForeignKey('incident', 'incident_reportinguserid_user_id');
		$this->dropForeignKey('incident', 'incident_categoryid_ir_sub_category_id');
		$this->dropForeignKey('ir_audit_log', 'ir_audit_log_incidentid_incident_id');
		$this->dropForeignKey('ir_audit_log', 'ir_audit_log_userid_user_id');
		$this->dropForeignKey('ir_history', 'ir_history_userid_user_id');
		$this->dropForeignKey('ir_history', 'ir_history_incidentid_incident_id');
		$this->dropForeignKey('ir_incident_actor', 'ir_incident_actor_incidentid_incident_id');
		$this->dropForeignKey('ir_incident_observer', 'ir_incident_observer_incidentid_incident_id');
		$this->dropForeignKey('ir_incident_workflow', 'ir_incident_workflow_incidentid_incident_id');
		$this->dropForeignKey('ir_incident_workflow', 'ir_incident_workflow_roleid_role_id');
		$this->dropForeignKey('ir_incident_workflow', 'ir_incident_workflow_userid_user_id');
		$this->dropForeignKey('ir_step', 'ir_step_workflowid_ir_workflow_def_id');
		$this->dropForeignKey('ir_step', 'ir_step_roleid_role_id');
		$this->dropForeignKey('ir_sub_category', 'ir_sub_category_categoryid_ir_category_id');
		$this->dropForeignKey('ir_sub_category', 'ir_sub_category_workflowid_ir_workflow_def_id');
		$this->dropForeignKey('incident_comment', 'incident_comment_objectid_incident_id');
		$this->dropForeignKey('incident_comment', 'incident_comment_userid_user_id');
		$this->dropForeignKey('incident_artifact', 'incident_artifact_objectid_incident_id');
		$this->dropForeignKey('incident_artifact', 'incident_artifact_userid_user_id');
		$this->dropForeignKey('incident_audit_log', 'incident_audit_log_objectid_incident_id');
		$this->dropForeignKey('incident_audit_log', 'incident_audit_log_userid_user_id');
    }
}
