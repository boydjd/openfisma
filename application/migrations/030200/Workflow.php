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
class Application_Migration_030200_Workflow extends Fisma_Migration_Abstract
{
    /**
     * Migrate.
     */
    public function migrate()
    {
        $helper = $this->getHelper();

        $this->message('Drop foreign keys');
        $helper->dropForeignKeys('finding', 'finding_currentevaluationid_evaluation_id');
        $helper->dropForeignKeys('evaluation', 'evaluation_nextid_evaluation_id');
        $helper->dropForeignKeys('finding_evaluation', 'finding_evaluation_evaluationid_evaluation_id');

        $this->message('Migrating Workflow, WorkflowStep');
        $this->message('Add tables');
        $helper->createTable(
            'workflow',
            array(
                'id' => 'bigint(20) NOT NULL AUTO_INCREMENT',
                'createdts' => 'datetime NOT NULL',
                'modifiedts' => 'datetime NOT NULL',
                'name' => 'varchar(255)',
                'description' => 'text',
                'isdefault' => 'tinyint(1) NOT NULL DEFAULT 0',
                'module' => "enum('finding','incident','vulnerability') NOT NULL",
                'creatorid' => 'bigint(20)'
            ),
            'id'
        );
        $helper->createTable(
            'workflow_step',
            array(
                'id' => 'bigint(20) NOT NULL AUTO_INCREMENT',
                'createdts' => 'datetime NOT NULL',
                'modifiedts' => 'datetime NOT NULL',
                'cardinality' => 'bigint(20)',
                'name' => 'varchar(255)',
                'label' => 'varchar(255)',
                'description' => 'text',
                'isresolved' => 'tinyint(1) NOT NULL DEFAULT 0',
                'allottedtime' => "enum('unlimited','days','ecd','custom') DEFAULT 'unlimited'",
                'allotteddays' => 'bigint(20)',
                'autotransition' => 'tinyint(1) NOT NULL DEFAULT 0',
                'autotransitiondestination' => 'bigint(20)',
                'attachmenteditable' => 'tinyint(1) NOT NULL DEFAULT 1',
                'prerequisites' => 'text',
                'restrictedfields' => 'text',
                'transitions' => 'text',
                'workflowid' => 'bigint(20)'
            ),
            'id'
        );
        //Add foreign key
        $helper->addForeignKey('workflow', 'creatorid', 'user', 'id');
        $helper->addForeignKey('workflow_step', 'workflowid', 'workflow', 'id');
        $this->message('Create default records');
        $workflows = $this->_getWorkflowArray();
        foreach ($workflows as $workflow) {
            $helper->insert('workflow', $workflow);
        }

        $workflowSteps = $this->_getWorkflowStepArray();
        foreach ($workflowSteps as $workflowStep) {
            $helper->insert('workflow_step', $workflowStep);
        }

        //WorkflowStepUser
        //Create table
        $helper->createTable(
            'workflow_step_user',
            array(
                'userid' => 'bigint(20) NOT NULL DEFAULT 0',
                'stepid' => 'bigint(20) NOT NULL DEFAULT 0'
            ),
            array('userid', 'stepid')
        );
        //Add foreign keys
        $helper->addForeignKey('workflow_step_user', 'stepid', 'workflow_step', 'id');
        $helper->addForeignKey('workflow_step_user', 'userid', 'user', 'id');

        $this->message('Migrating Finding table');
        //Add isResolved, completedSteps, currentStepId
        $helper->addColumn('finding', 'isresolved', 'tinyint(1) NOT NULL DEFAULT 0', 'legacyfindingkey');
        $helper->addColumn('finding', 'completedsteps', 'text', 'isresolved');
        $helper->addColumn('finding', 'currentstepid', 'bigint(20) DEFAULT NULL', 'completedsteps');
        //Remove actualCompletionDate, cvssBaseScore, cvssVector
        $helper->dropColumns('finding', array('actualcompletiondate', 'cvssbasescore', 'cvssvector'));
        //if status = CLOSED set isresolved = 1, check type and send to destination step
        $helper->update(
            'finding',
            array('isresolved' => 1, 'currentstepid' => 14),
            array('status' => 'CLOSED', 'type' => 'CAP')
        );
        $helper->update(
            'finding',
            array('isresolved' => 1, 'currentstepid' => 18),
            array('status' => 'CLOSED', 'type' => 'FP')
        );
        $helper->update(
            'finding',
            array('isresolved' => 1, 'currentstepid' => 16),
            array('status' => 'CLOSED', 'type' => 'AR')
        );
        //save old evaluations => completedsteps
        $evaluations = $helper->query(
            "SELECT * from finding_evaluation fe " .
            "INNER JOIN evaluation e on fe.evaluationid = e.id " .
            "ORDER BY fe.findingid, fe.createdts"
        );
        $findingId = 0;
        $completedSteps = array();
        foreach ($evaluations as $evaluation) {
            if ($findingId > 0 && $evaluation->findingid != $findingId) {
                $helper->exec(
                    "UPDATE finding SET completedsteps = ? WHERE id = ?",
                    array(serialize($completedSteps), $findingId)
                );
                $completedSteps = array();
            }
            $findingId = $evaluation->findingid;
            $completedSteps[] = array(
                'workflow' => array(
                    'name' => 'Legacy',
                    'description' => 'Migrated finding workflow'
                ),
                'step' => array(
                    'name' => $evaluation->name,
                    'label' => $evaluation->nickname,
                    'description' => $evaluation->description
                ),
                'transitionName' => $evaluation->decision,
                'comment' => $evaluation->comment,
                'expirationDate' => '0',
                'userId' => $evaluation->userid,
                'timestamp' => $evaluation->createdts
            );
        }
        if (!empty($completedSteps)) {
            $helper->exec(
                "UPDATE finding SET completedsteps = ? WHERE id = ?",
                array(serialize($completedSteps), $findingId)
            );
            $completedSteps = array();
        }
        //send NEW/DRAFT, MSA, EN, EA to Remediation
        $helper->update(
            'finding',
            array('currentstepid' => 10),
            array('status' => 'NEW')
        );
        $helper->update(
            'finding',
            array('currentstepid' => 10),
            array('status' => 'DRAFT')
        );
        $helper->update(
            'finding',
            array('currentstepid' => 11),
            array('status' => 'MSA')
        );
        $helper->update(
            'finding',
            array('currentstepid' => 12),
            array('status' => 'EN')
        );
        $helper->update(
            'finding',
            array('currentstepid' => 13),
            array('status' => 'EA')
        );
        //send everything without type to Acceptance (this will override some NEW/DRAFT findings above)
        $helper->update(
            'finding',
            array('currentstepid' => 5),
            array('type' => 'NONE')
        );
        //Remove type, status, denormalizedStatus, currentEvaluationId
        $helper->dropColumns('finding', array('type', 'status', 'denormalizedstatus', 'currentevaluationid'));
        //Add foreign key
        $helper->addForeignKey('finding', 'currentstepid', 'workflow_step', 'id');

        $this->message('Remove Evaluation, Comment, FindingEvaluation');
        $helper->dropTable('comment');
        $helper->dropTable('evaluation');
        $helper->dropTable('finding_evaluation');

        $this->message('Migrating Events');
        //Remove all with category evaluation
        //Remove finding MITIGATION_APPROVED, MITIGATION_REJECTED, EVIDENCE_REJECTED
        $helper->exec(
            "DELETE from `event` WHERE " .
                "`category` = 'evaluation' OR " .
                "`name` like 'MITIGATION_%' OR " .
                "`name` like 'EVIDENCE_%'" .
            ";"
        );
        //Add WORKFLOW_COMPLETED
        $newEventId = $helper->insert('event', array(
            'defaultActive' => true,
            'name' => 'WORKFLOW_COMPLETED',
            'description' => 'new items arrive in a workflow step I watch',
            'category' => 'user'
        ));
        $helper->exec(
            "INSERT into `user_event` (`userid`, `eventid`) (" .
                "SELECT id, {$newEventId} from `user` WHERE `deleted_at` IS NULL" .
            ");"
        );

        //Privilege (done in Privilege.php)
        //Add {resource:finding, action:update}, {resource:workflow, action:manage}
        //delete from role_privilege
        //Remove {resource:finding, action:[update_*|upload_evidence|mitigation_*|evidence_*]}
        //Remove {resource:evaluation}

        $this->message('Migrating Vulnerability table');
        //Add isResolved, completedSteps, currentStepId, nextDueDate
        $helper->addColumn('vulnerability', 'isresolved', 'tinyint(1) NOT NULL DEFAULT 0', 'closedts');
        $helper->addColumn('vulnerability', 'completedsteps', 'text', 'pocid');
        $helper->addColumn('vulnerability', 'currentstepid', 'bigint(20) DEFAULT NULL', 'completedsteps');
        $helper->addColumn('vulnerability', 'nextduedate', 'date DEFAULT NULL', 'currentstepid');
        //if status != OPEN set isresolved = 1, check status and send to destination step
        $helper->update(
            'vulnerability',
            array('isresolved' => 1, 'currentstepid' => 2),
            array('status' => 'WONTFIX')
        );
        $helper->update(
            'vulnerability',
            array('isresolved' => 1, 'currentstepid' => 4),
            array('status' => 'FIXED')
        );
        $helper->update(
            'vulnerability',
            array('currentstepid' => 1),
            array('status' => 'OPEN')
        );
        //Remove status
        $helper->dropColumn('vulnerability', 'status');
        //Add foreign key
        $helper->addForeignKey('vulnerability', 'currentstepid', 'workflow_step', 'id');

        $this->message('Updating Configuration');
        //Add workflowTransition backgroundTask enabled 1, number 1, unit day, time 02:00:00
        $bt = $helper->query("SELECT backgroundtasks from configuration");
        $bt = unserialize($bt[0]->backgroundtasks);
        $bt['workflowTransition'] = array(
            'enabled' => '1',
            'number' => '1',
            'unit' => 'day',
            'time' => '02:00:00'
        );

        $helper->update(
            'configuration',
            array('backgroundtasks' => serialize($bt))
        );
    }

    private function _getWorkflowArray()
    {
        $now = $this->getHelper()->now();
        $rootId = $this->getHelper()->query("SELECT id from user where username = 'root'");
        $rootId = $rootId[0]->id;

        $workflow = array();
        include(realpath(dirname(__FILE__) . '/workflow.inc'));
        return $workflow;
    }

    private function _getWorkflowStepArray()
    {
        $now = $this->getHelper()->now();

        $workflowStep = array();
        include(realpath(dirname(__FILE__) . '/workflow_step.inc'));
        return $workflowStep;
    }
}
