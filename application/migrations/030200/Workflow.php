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

        //Drop foreign keys
        $helper->dropForeignKeys('finding', 'finding_currentevaluationid_evaluation_id');
        $helper->dropForeignKeys('evaluation', 'evaluation_nextid_evaluation_id');
        $helper->dropForeignKeys('finding_evaluation', 'finding_evaluation_evaluationid_evaluation_id');

        //Workflow, WorkflowStep
        //Add tables
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
        //Create default records
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

        //Finding table
        //Add isResolved, completedSteps, currentStepId
        $helper->addColumn('finding', 'isresolved', 'tinyint(1) NOT NULL DEFAULT 0', 'legacyfindingkey');
        $helper->addColumn('finding', 'completedsteps', 'text', 'isresolved');
        $helper->addColumn('finding', 'currentstepid', 'bigint(20) DEFAULT NULL', 'completedsteps');
        //Remove actualCompletionDate, cvssBaseScore, cvssVector
        $helper->dropColumns('finding', array('actualcompletiondate', 'cvssbasescore', 'cvssvector'));
        //if status = CLOSED set isresolved = 1, check type and send to destination step
        $helper->update(
            'finding',
            array('isresolved' => 1, 'currentstepid' => 16),
            array('status' => 'CLOSED', 'type' => 'CAP')
        );
        $helper->update(
            'finding',
            array('isresolved' => 1, 'currentstepid' => 9),
            array('status' => 'CLOSED', 'type' => 'FP')
        );
        $helper->update(
            'finding',
            array('isresolved' => 1, 'currentstepid' => 8),
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

        //Remove Evaluation, Comment, FindingEvaluation
        $helper->dropTable('comment');
        $helper->dropTable('evaluation');
        $helper->dropTable('finding_evaluation');

        //Event
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

        //Vulnerability
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

        //Configuration
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
        $now = Zend_Date::now()->toString('yyyy-MM-dd HH:mm:ss');
        $rootId = $this->getHelper()->query("SELECT id from user where username = 'root'");
        $rootId = $rootId[0]->id;

        return array(
            array(
                'id' => '1',
                'createdts' => $now,
                'modifiedts' => $now,
                'name' => 'Acceptance',
                'description' => 'Decide whether to fix a vulnerability',
                'isdefault' => '1',
                'module' => 'vulnerability',
                'creatorid' => $rootId
            ),
            array(
                'id' => '2',
                'createdts' => $now,
                'modifiedts' => $now,
                'name' => 'Won\'t Fix',
                'description' => 'Decide not to fix the vulnerability',
                'isdefault' => '0',
                'module' => 'vulnerability',
                'creatorid' => $rootId
            ),
            array(
                'id' => '3',
                'createdts' => $now,
                'modifiedts' => $now,
                'name' => 'Remediation',
                'description' => 'Remediate a vulnerability',
                'isdefault' => '0',
                'module' => 'vulnerability',
                'creatorid' => $rootId
            ),
            array(
                'id' => '4',
                'createdts' => $now,
                'modifiedts' => $now,
                'name' => 'Acceptance',
                'description' => 'Default bucket for new findings',
                'isdefault' => '1',
                'module' => 'finding',
                'creatorid' => $rootId
            ),
            array(
                'id' => '5',
                'createdts' => $now,
                'modifiedts' => $now,
                'name' => 'Risk Acceptance',
                'description' => 'Workflow to accept the risks',
                'isdefault' => '0',
                'module' => 'finding',
                'creatorid' => $rootId
            ),
            array(
                'id' => '6',
                'createdts' => $now,
                'modifiedts' => $now,
                'name' => 'False Positive',
                'description' => 'Discard findings as False Positives',
                'isdefault' => '0',
                'module' => 'finding',
                'creatorid' => $rootId
            ),
            array(
                'id' => '7',
                'createdts' => $now,
                'modifiedts' => $now,
                'name' => 'Remediation',
                'description' => 'Workflow to remediate the findings',
                'isdefault' => '0',
                'module' => 'finding',
                'creatorid' => $rootId
            )
        );
    }

    private function _getWorkflowStepArray()
    {
        $now = Zend_Date::now()->toString('yyyy-MM-dd HH:mm:ss');

        return array(
            array(
                'id' => '1',
                'createdts' => $now,
                'modifiedts' => $now,
                'cardinality' => '1',
                'name' => 'Action Plan',
                'label' => 'AP',
                'description' =>
                    'Decide whether to fix the vulnerability and if not, why (\'Breaks system\', \'Cost prohibitive\'' .
                    ', \'Technically infeasible\'...).',
                'isresolved' => '0',
                'allottedtime' => 'days',
                'allotteddays' => '30',
                'autotransition' => '0',
                'autotransitiondestination' => NULL,
                'attachmenteditable' => '0',
                'prerequisites' => NULL,
                'restrictedfields' => NULL,
                'transitions' =>
                    'a:2:{i:0;a:4:{s:4:"name";s:3:"Fix";s:11:"destination";s:6:"custom";s:5:"roles";s:2:"[]";s:17:"cu' .
                    'stomDestination";s:1:"3";}i:1;a:4:{s:4:"name";s:9:"Won\'t Fix";s:11:"destination";s:6:"custom";s' .
                    ':5:"roles";s:2:"[]";s:17:"customDestination";s:1:"2";}}',
                'workflowid' => '1'
            ),
            array(
                'id' => '2',
                'createdts' => $now,
                'modifiedts' => $now,
                'cardinality' => '1',
                'name' => 'Won\'t Fix',
                'label' => 'WONTFIX',
                'description' => 'Please provide a reason if this vulnerability needs re-evaluation',
                'isresolved' => '1',
                'allottedtime' => 'unlimited',
                'allotteddays' => NULL,
                'autotransition' => '0',
                'autotransitiondestination' => NULL,
                'attachmenteditable' => '0',
                'prerequisites' => NULL,
                'restrictedfields' => NULL,
                'transitions' =>
                    'a:1:{i:0;a:4:{s:4:"name";s:11:"Re-evaluate";s:11:"destination";s:6:"custom";s:5:"roles";s:2:"[]"' .
                    ';s:17:"customDestination";s:1:"1";}}',
                'workflowid' => '2'
            ),
            array(
                'id' => '3',
                'createdts' => $now,
                'modifiedts' => $now,
                'cardinality' => '1',
                'name' => NULL,
                'label' => 'AR',
                'description' => NULL,
                'isresolved' => '0',
                'allottedtime' => 'unlimited',
                'allotteddays' => NULL,
                'autotransition' => '0',
                'autotransitiondestination' => NULL,
                'attachmenteditable' => '1',
                'prerequisites' => NULL,
                'restrictedfields' => NULL,
                'transitions' =>
                    'a:1:{i:0;a:4:{s:4:"name";s:8:"Complete";s:11:"destination";s:4:"next";s:5:"roles";s:2:"[]";s:17:' .
                    '"customDestination";s:9:"undefined";}}',
                'workflowid' => '3'
            ),
            array(
                'id' => '4',
                'createdts' => $now,
                'modifiedts' => $now,
                'cardinality' => '2',
                'name' => 'Fixed',
                'label' => 'FIXED',
                'description' => 'Please provide a reason if this vulnerability needs to be re-opened',
                'isresolved' => '1',
                'allottedtime' => 'unlimited',
                'allotteddays' => NULL,
                'autotransition' => '0',
                'autotransitiondestination' => NULL,
                'attachmenteditable' => '0',
                'prerequisites' => NULL,
                'restrictedfields' => NULL,
                'transitions' =>
                    'a:1:{i:0;a:4:{s:4:"name";s:7:"Re-open";s:11:"destination";s:6:"custom";s:5:"roles";s:2:"[]";s:17' .
                    ':"customDestination";s:1:"1";}}',
                'workflowid' => '3'
            ),
            array(
                'id' => '5',
                'createdts' => $now,
                'modifiedts' => $now,
                'cardinality' => '1',
                'name' => 'Mitigation Strategy',
                'label' => 'MS',
                'description' =>
                    'Decide whether to accept the risks, to start remediation, or to discard the finding as False Pos' .
                    'itive',
                'isresolved' => '0',
                'allottedtime' => 'days',
                'allotteddays' => '30',
                'autotransition' => '0',
                'autotransitiondestination' => NULL,
                'attachmenteditable' => '0',
                'prerequisites' => NULL,
                'restrictedfields' => NULL,
                'transitions' =>
                    'a:3:{i:0;a:4:{s:4:"name";s:11:"Accept Risk";s:11:"destination";s:6:"custom";s:5:"roles";s:2:"[]"' .
                    ';s:17:"customDestination";s:1:"6";}i:1;a:4:{s:4:"name";s:14:"False Positive";s:11:"destination";' .
                    's:6:"custom";s:5:"roles";s:2:"[]";s:17:"customDestination";s:1:"9";}i:2;a:4:{s:4:"name";s:9:"Rem' .
                    'ediate";s:11:"destination";s:6:"custom";s:5:"roles";s:2:"[]";s:17:"customDestination";s:2:"10";}}',
                'workflowid' => '4'
            ),
            array(
                'id' => '6',
                'createdts' => $now,
                'modifiedts' => $now,
                'cardinality' => '1',
                'name' => 'Accept Risk Justification',
                'label' => 'ARJ',
                'description' =>
                    'Please provide the following details: Categorize (input below), Business Case (Mitigation Strate' .
                    'gy tab - Action Plan field), Residual Risk (Risk Analysis tab), Evidence (Attachments tab), Esti' .
            'mated Completion Date (Mitigation Strategy tab), and Countermeasures (Risk Analysis tab)',
                'isresolved' => '0',
                'allottedtime' => 'days',
                'allotteddays' => '30',
                'autotransition' => '0',
                'autotransitiondestination' => NULL,
                'attachmenteditable' => '1',
                'prerequisites' => NULL,
                'restrictedfields' => NULL,
                'transitions' =>
                    'a:2:{i:0;a:4:{s:4:"name";s:20:"Submit Justification";s:11:"destination";s:4:"next";s:5:"roles";s' .
                    ':2:"[]";s:17:"customDestination";s:9:"undefined";}i:1;a:4:{s:4:"name";s:26:"Change Mitigation St' .
                    'rategy";s:11:"destination";s:6:"custom";s:5:"roles";s:2:"[]";s:17:"customDestination";s:1:"5";}}',
                'workflowid' => '5'
            ),
            array(
                'id' => '7',
                'createdts' => $now,
                'modifiedts' => $now,
                'cardinality' => '2',
                'name' => 'Accept Risk Approval',
                'label' => 'ARA',
                'description' => 'Please approve or deny the Accept-Risk justification and provide explanation.',
                'isresolved' => '0',
                'allottedtime' => 'days',
                'allotteddays' => '7',
                'autotransition' => '0',
                'autotransitiondestination' => NULL,
                'attachmenteditable' => '1',
                'prerequisites' =>
                    'a:7:{i:0;s:18:"mitigationStrategy";i:1;s:10:"currentEcd";i:2;s:11:"threatLevel";i:3;s:6:"threat"' .
                    ';i:4;s:28:"countermeasuresEffectiveness";i:5;s:15:"countermeasures";i:6;s:17:"securityControlId"' .
                    ';}',
                'restrictedfields' =>
                    'a:11:{i:0;s:11:"description";i:1;s:14:"recommendation";i:2;s:8:"sourceId";i:3;s:10:"currentEcd";' .
                    'i:4;s:18:"mitigationStrategy";i:5;s:17:"resourcesRequired";i:6;s:11:"threatLevel";i:7;s:6:"threa' .
                    't";i:8;s:28:"countermeasuresEffectiveness";i:9;s:15:"countermeasures";i:10;s:17:"securityControl' .
                    'Id";}',
                'transitions' =>
                    'a:2:{i:0;a:4:{s:4:"name";s:7:"Approve";s:11:"destination";s:4:"next";s:5:"roles";s:2:"[]";s:17:"' .
                    'customDestination";s:9:"undefined";}i:1;a:4:{s:4:"name";s:4:"Deny";s:11:"destination";s:4:"back"' .
                    ';s:5:"roles";s:2:"[]";s:17:"customDestination";s:9:"undefined";}}',
                'workflowid' => '5'
            ),
            array(
                'id' => '8',
                'createdts' => $now,
                'modifiedts' => $now,
                'cardinality' => '3',
                'name' => 'Accepted Risk',
                'label' => 'AR',
                'description' => 'Please provide a reason (if required) to re-evaluate the finding.',
                'isresolved' => '1',
                'allottedtime' => 'custom',
                'allotteddays' => NULL,
                'autotransition' => '1',
                'autotransitiondestination' => '5',
                'attachmenteditable' => '0',
                'prerequisites' => NULL,
                'restrictedfields' =>
                    'a:11:{i:0;s:11:"description";i:1;s:14:"recommendation";i:2;s:8:"sourceId";i:3;s:10:"currentEcd";' .
                    'i:4;s:18:"mitigationStrategy";i:5;s:17:"resourcesRequired";i:6;s:11:"threatLevel";i:7;s:6:"threa' .
                    't";i:8;s:28:"countermeasuresEffectiveness";i:9;s:15:"countermeasures";i:10;s:17:"securityControl' .
                    'Id";}',
                'transitions' =>
                    'a:1:{i:0;a:4:{s:4:"name";s:11:"Review Risk";s:11:"destination";s:6:"custom";s:5:"roles";s:2:"[]"' .
                    ';s:17:"customDestination";s:1:"6";}}',
                'workflowid' => '5'
            ),
            array(
                'id' => '9',
                'createdts' => $now,
                'modifiedts' => $now,
                'cardinality' => '1',
                'name' => 'Closed',
                'label' => 'CLOSED',
                'description' => 'Please provide a reason (if required) to re-evaluate the finding.',
                'isresolved' => '1',
                'allottedtime' => 'unlimited',
                'allotteddays' => NULL,
                'autotransition' => '0',
                'autotransitiondestination' => NULL,
                'attachmenteditable' => '0',
                'prerequisites' => NULL,
                'restrictedfields' =>
                    'a:11:{i:0;s:11:"description";i:1;s:14:"recommendation";i:2;s:8:"sourceId";i:3;s:10:"currentEcd";' .
                    'i:4;s:18:"mitigationStrategy";i:5;s:17:"resourcesRequired";i:6;s:11:"threatLevel";i:7;s:6:"threa' .
                    't";i:8;s:28:"countermeasuresEffectiveness";i:9;s:15:"countermeasures";i:10;s:17:"securityControl' .
                    'Id";}',
                'transitions' =>
                    'a:1:{i:0;a:4:{s:4:"name";s:15:"Re-Open Finding";s:11:"destination";s:6:"custom";s:5:"roles";s:2:' .
                    '"[]";s:17:"customDestination";s:1:"5";}}',
                'workflowid' => '6'
            ),
            array(
                'id' => '10',
                'createdts' => $now,
                'modifiedts' => $now,
                'cardinality' => '1',
                'name' => 'Remediation Plan',
                'label' => 'RP',
                'description' => 'Please complete the Mitigation Strategy, Risk Analysis, and Security Control tabs',
                'isresolved' => '0',
                'allottedtime' => 'days',
                'allotteddays' => '30',
                'autotransition' => '0',
                'autotransitiondestination' => NULL,
                'attachmenteditable' => '1',
                'prerequisites' => NULL,
                'restrictedfields' => NULL,
                'transitions' =>
                    'a:2:{i:0;a:4:{s:4:"name";s:6:"Submit";s:11:"destination";s:4:"next";s:5:"roles";s:2:"[]";s:17:"c' .
                    'ustomDestination";s:9:"undefined";}i:1;a:4:{s:4:"name";s:26:"Change Mitigation Strategy";s:11:"d' .
                    'estination";s:6:"custom";s:5:"roles";s:2:"[]";s:17:"customDestination";s:1:"5";}}',
                'workflowid' => '7'
            ),
            array(
                'id' => '11',
                'createdts' => $now,
                'modifiedts' => $now,
                'cardinality' => '2',
                'name' => 'Remediation Plan Approval',
                'label' => 'RPA',
                'description' => 'Please approve or deny the Mitigation Strategy and provide explanation.',
                'isresolved' => '0',
                'allottedtime' => 'days',
                'allotteddays' => '7',
                'autotransition' => '0',
                'autotransitiondestination' => NULL,
                'attachmenteditable' => '1',
                'prerequisites' =>
                    'a:11:{i:0;s:11:"description";i:1;s:14:"recommendation";i:2;s:8:"sourceId";i:3;s:10:"currentEcd";' .
                    'i:4;s:18:"mitigationStrategy";i:5;s:17:"resourcesRequired";i:6;s:11:"threatLevel";i:7;s:6:"threa' .
                    't";i:8;s:28:"countermeasuresEffectiveness";i:9;s:15:"countermeasures";i:10;s:17:"securityControl' .
                    'Id";}',
                'restrictedfields' =>
                    'a:11:{i:0;s:11:"description";i:1;s:14:"recommendation";i:2;s:8:"sourceId";i:3;s:10:"currentEcd";' .
                    'i:4;s:18:"mitigationStrategy";i:5;s:17:"resourcesRequired";i:6;s:11:"threatLevel";i:7;s:6:"threa' .
                    't";i:8;s:28:"countermeasuresEffectiveness";i:9;s:15:"countermeasures";i:10;s:17:"securityControl' .
                    'Id";}',
                'transitions' =>
                    'a:2:{i:0;a:4:{s:4:"name";s:7:"Approve";s:11:"destination";s:4:"next";s:5:"roles";s:2:"[]";s:17:"' .
                    'customDestination";s:9:"undefined";}i:1;a:4:{s:4:"name";s:4:"Deny";s:11:"destination";s:4:"back"' .
                    ';s:5:"roles";s:2:"[]";s:17:"customDestination";s:9:"undefined";}}',
                'workflowid' => '7'
            ),
            array(
                'id' => '12',
                'createdts' => $now,
                'modifiedts' => $now,
                'cardinality' => '3',
                'name' => 'Implementation',
                'label' => 'RI',
                'description' => 'Please remediate the finding and submit evidence.',
                'isresolved' => '0',
                'allottedtime' => 'ecd',
                'allotteddays' => NULL,
                'autotransition' => '0',
                'autotransitiondestination' => NULL,
                'attachmenteditable' => '1',
                'prerequisites' => NULL,
                'restrictedfields' =>
                    'a:11:{i:0;s:11:"description";i:1;s:14:"recommendation";i:2;s:8:"sourceId";i:3;s:10:"currentEcd";' .
                    'i:4;s:18:"mitigationStrategy";i:5;s:17:"resourcesRequired";i:6;s:11:"threatLevel";i:7;s:6:"threa' .
                    't";i:8;s:28:"countermeasuresEffectiveness";i:9;s:15:"countermeasures";i:10;s:17:"securityControl' .
                    'Id";}',
                'transitions' =>
                    'a:2:{i:0;a:4:{s:4:"name";s:6:"Submit";s:11:"destination";s:4:"next";s:5:"roles";s:2:"[]";s:17:"c' .
                    'ustomDestination";s:9:"undefined";}i:1;a:4:{s:4:"name";s:17:"Request Extension";s:11:"destinatio' .
                    'n";s:6:"custom";s:5:"roles";s:2:"[]";s:17:"customDestination";s:2:"10";}}',
                'workflowid' => '7'
            ),
            array(
                'id' => '13',
                'createdts' => $now,
                'modifiedts' => $now,
                'cardinality' => '4',
                'name' => 'Implementation Validation',
                'label' => 'RIV',
                'description' => 'Please approve or deny the Evidence Package and provide explanation.',
                'isresolved' => '0',
                'allottedtime' => 'days',
                'allotteddays' => '7',
                'autotransition' => '0',
                'autotransitiondestination' => NULL,
                'attachmenteditable' => '1',
                'prerequisites' => NULL,
                'restrictedfields' =>
                    'a:11:{i:0;s:11:"description";i:1;s:14:"recommendation";i:2;s:8:"sourceId";i:3;s:10:"currentEcd";' .
                    'i:4;s:18:"mitigationStrategy";i:5;s:17:"resourcesRequired";i:6;s:11:"threatLevel";i:7;s:6:"threa' .
                    't";i:8;s:28:"countermeasuresEffectiveness";i:9;s:15:"countermeasures";i:10;s:17:"securityControl' .
                    'Id";}',
                'transitions' =>
                    'a:2:{i:0;a:4:{s:4:"name";s:15:"Approve & Close";s:11:"destination";s:4:"next";s:5:"roles";s:2:"[' .
                    ']";s:17:"customDestination";s:9:"undefined";}i:1;a:4:{s:4:"name";s:4:"Deny";s:11:"destination";s' .
                    ':4:"back";s:5:"roles";s:2:"[]";s:17:"customDestination";s:9:"undefined";}}',
                'workflowid' => '7'
            ),
            array(
                'id' => '14',
                'createdts' => $now,
                'modifiedts' => $now,
                'cardinality' => '5',
                'name' => 'Closed',
                'label' => 'FIXED',
                'description' => 'Please provide a reason (if required) to re-evaluate the finding.',
                'isresolved' => '1',
                'allottedtime' => 'unlimited',
                'allotteddays' => NULL,
                'autotransition' => '0',
                'autotransitiondestination' => '5',
                'attachmenteditable' => '0',
                'prerequisites' => NULL,
                'restrictedfields' =>
                    'a:11:{i:0;s:11:"description";i:1;s:14:"recommendation";i:2;s:8:"sourceId";i:3;s:10:"currentEcd";' .
                    'i:4;s:18:"mitigationStrategy";i:5;s:17:"resourcesRequired";i:6;s:11:"threatLevel";i:7;s:6:"threa' .
                    't";i:8;s:28:"countermeasuresEffectiveness";i:9;s:15:"countermeasures";i:10;s:17:"securityControl' .
                    'Id";}',
                'transitions' =>
                    'a:1:{i:0;a:4:{s:4:"name";s:15:"Re-open Finding";s:11:"destination";s:6:"custom";s:5:"roles";s:2:' .
                    '"[]";s:17:"customDestination";s:1:"5";}}',
                'workflowid' => '7'
            )
        );
    }
}
