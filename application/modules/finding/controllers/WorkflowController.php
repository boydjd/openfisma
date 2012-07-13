<?php
/**
 * Copyright (c) 2012 Endeavor Systems, Inc.
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
 * View and edit the finding workflow
 *
 * @author     Duy K. Bui <duy.bui@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2012 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Controller
 */
class Finding_WorkflowController extends Fisma_Zend_Controller_Action_Security
{
    /**
     * Return HTML code for a span with the tooltip
     *
     * @param String $type Either workflowTitle, chartLabel, statusMessage, or onTime
     * @param String $id   The prefix id to identify the unique element carrying the tooltip
     *
     * @return Fisma_Yui_Tooltip
     */
    public static function getWorkflowTooltip($type, $id)
    {
        $workflowTitle = 'The workflow title is used to distinguish the workflow step and to auto-create the status me'
                       . 'ssage. The status message adds the word awaiting to the beginning of the workflow title. For'
                       . ' example, if you create a workflow step called \\"CISO Approval of Evidence Package\\", the '
                       . 'status message becomes \\"Awaiting CISO Approval of Evidence Package\\". Please note that ch'
                       . 'art labels cannot be longer than 255 characters including whitespace.';
        $chartLabel = 'The chart label is used to designate the workflow step in short notation. The chart label is us'
                    . 'ed for graphs, reports, and searching. For example, if the chart label for workflow step ISSO A'
                    . 'pproval of Mitigation Strategy is set to \\"MS ISSO\\" then the graph will identify the workflo'
                    . 'w step as \\"MS ISSO\\" and the user will be able to search for items currently in the \\"MS IS'
                    . 'SO\\" status. Please note that chart labels cannot be longer than 255 characters including whit'
                    . 'espace.';
        $statusMessage = 'The status message is a brief synopsis of the current status used to give additional context'
                       . ' to the user rather than showing them an acronym. For example, the status message \\"Awaitin'
                       . 'g ISSO Approval of Mitigation Strategy\\" informs the user what has to happen before the sta'
                       . 'tus is changed.';
        $onTime = 'The on time period sets the number of calendar days that the workflow step is considered on time. O'
                . 'nce the period has lapsed, the workflow step becomes overdue. For example, if the period is set to '
                . '7, then the action will remain on-time for 7 calendar days.';

        switch ($type) {
        case 'workflowTitle':
            return new Fisma_Yui_Tooltip(
                "{$id}_workflowtitle",
                'Workflow Title',
                $workflowTitle
            );
        case 'chartLabel':
            return new Fisma_Yui_Tooltip(
                "{$id}_chartlabel",
                'Chart Label',
                $chartLabel
            );
        case 'statusMessage':
            return new Fisma_Yui_Tooltip(
                "{$id}_statusmessage",
                'Status Message',
                $statusMessage
            );
        case 'onTime':
            return new Fisma_Yui_Tooltip(
                "{$id}_ontime",
                'On Time',
                $onTime
            );
        default:
            return "";
        }
    }
    /**
     * Construct the workflow diagram
     *
     * @GETAllowed
     * @return void
     */
    public function viewAction()
    {
        $this->view->msList = Doctrine_Query::create()
                                  ->from('Evaluation e')
                                  ->leftJoin('e.Privilege p')
                                  ->leftJoin('p.Roles')
                                  ->where('e.approvalGroup = ?', 'action')
                                  ->orderBy('e.precedence')
                                  ->execute();
        $this->view->evList = Doctrine_Query::create()
                                  ->from('Evaluation e')
                                  ->leftJoin('e.Privilege p')
                                  ->leftJoin('p.Roles')
                                  ->where('e.approvalGroup = ?', 'evidence')
                                  ->orderBy('e.precedence')
                                  ->execute();

        $this->view->editMode = (
            $this->_acl->hasPrivilegeForClass('create', 'Evaluation') ||
            $this->_acl->hasPrivilegeForClass('update', 'Evaluation') ||
            $this->_acl->hasPrivilegeForClass('delete', 'Evaluation')
        );

        if ($this->view->editMode) {
            $this->view->saveButton = new Fisma_Yui_Form_Button(
                'saveButton',
                array(
                    'label' => 'Save',
                    'imageSrc' => '/images/ok.png',
                    'onClickFunction' => 'Fisma.FindingWorkflow.forceSubmit'
                )
            );
            $this->view->cancelButton = new Fisma_Yui_Form_Button_Link(
                'cancelButton',
                array(
                    'value' => 'Discard',
                    'imageSrc' => '/images/no_entry.png',
                    'href' => '/finding/workflow/view'
                )
            );
        }

        $config = Fisma::configuration();
        $this->view->findingNewDue = $config->getConfig('finding_new_due');
        $this->view->findingDraftDue = $config->getConfig('finding_draft_due');
    }

    /**
     * Modify the workflow
     *
     * @return void
     */
    public function modifyAction()
    {
        $lists = array();

        foreach ($_POST as $arg => $val) {
            $chunks = explode("_", $arg);
            if (count($chunks) >= 3) {
                $type = array_shift($chunks);
                $id = array_shift($chunks);
                $attr = implode("_", $chunks);

                if ($id != 'skeleton') { //skeleton set is the blank set of inputs used to clone other sets dynamically
                    $lists[$type][$id][$attr] = $val;
                }
            }
        }

        $notificationPrivilege = Doctrine_Query::create()
                                     ->from('Privilege p')
                                     ->where('p.resource = ?', 'notification')
                                     ->andWhere('p.action = ?', 'finding')
                                     ->execute()
                                     ->getFirst();
        try {
            Doctrine_Manager::connection()->beginTransaction();

            // Processing general finding configuration
            $config = Fisma::configuration();

            if ($lists['finding']['new']['due'] != $config->getConfig('finding_new_due')) {
                $config->setConfig('finding_new_due', $lists['finding']['new']['due']);
                $findings = Doctrine_Query::create()
                ->from('Finding f')
                ->where('f.status = ?', 'NEW')
                ->execute();
                foreach ($findings as $finding) {
                    $finding->setStatus($finding->status); // to trigger the private _updateNextDueDate()
                    $finding->save();
                }
            }

            if ($lists['finding']['draft']['due'] != $config->getConfig('finding_draft_due')) {
                $config->setConfig('finding_draft_due', $lists['finding']['draft']['due']);
                $findings = Doctrine_Query::create()
                ->from('Finding f')
                ->where('f.status = ?', 'DRAFT')
                ->execute();
                foreach ($findings as $finding) {
                    $finding->setStatus($finding->status); // to trigger the private _updateNextDueDate()
                    $finding->save();
                }
            }

            unset($lists['finding']); // stop processing 'finding' list automatically

            // Process all ADD's
            foreach ($lists as $listName => &$list) {
                foreach ($list as &$step) {
                    if (empty($step['databaseId'])) {
                        $newStep = new Evaluation();

                        $newStep->name = $step['name'];
                        $newStep->nickname = $step['nickname'];
                        $newStep->precedence = 0; // temporary value
                        $newStep->description = $step['description'];
                        $newStep->approvalGroup = $listName;

                        $newStep->Event = new Event();
                        $newStep->Event->name = $step['nickname'];
                        $newStep->Event->description = "a finding is awaiting " . $step['name'];
                        $newStep->Event->Privilege = $notificationPrivilege;
                        $newStep->Event->category = 'evaluation';
                        $newStep->Event->urlPath = '/finding/remediation/view/id/';

                        $privilege = Doctrine_Query::create()
                            ->from('Privilege p')
                            ->where('p.resource = ?', 'finding')
                            ->andWhere('p.action = ?', $step['nickname'])
                            ->andWhere('p.deleted_at is not ?', null)
                            ->execute()
                            ->getFirst();
                        if (empty($privilege)) {
                            $newStep->Privilege = new Privilege();
                            $newStep->Privilege->resource = 'finding';
                            $newStep->Privilege->action = $step['nickname'];
                        } else {
                            $newStep->Privilege = $privilege;
                            $newStep->Privilege->deleted_at = null;
                        }
                        $newStep->Privilege->description = $step['nickname'] . " Approval";

                        $newStep->save(); // precedence & nextId must be updated after save() ...
                        $step['databaseId'] = $newStep->id; // ... in order to fetch all databaseId's
                    }
                }
            }

            // Process all REMOVE's
            $removedSteps = array();
            foreach ($lists as $listName => &$list) {
                $stepIndices = array_keys($list); // Needs to go this way because the indices are strings
                for ($count = 0; $count < count($stepIndices); $count++) {
                    $step = &$list[$stepIndices[$count]];
                    if (!empty($step['destinationId'])) {
                        $findings = Doctrine_Query::create()
                            ->from('Finding f')
                            ->where('f.currentEvaluationId = ?', $step['databaseId'])
                            ->execute();
                        foreach ($findings as &$finding) {
                            $finding->currentEvaluationId = $list[$step['destinationId']]['databaseId'];
                            $finding->setStatus($finding->status); // updating denormalizedStatus & NextDueDate
                        }
                        $findings->save();

                        $removedSteps[] = $step['databaseId'];
                        unset($list[$stepIndices[$count]]);
                    }
                }
            }

            // Update all records
            foreach ($lists as $listName => &$list) {
                $stepIndices = array_keys($list); // Needs to go this way because the indices are uniqid()'s
                for ($count = 0; $count < count($stepIndices); $count++) {
                    $step = &$list[$stepIndices[$count]];

                    // recalculate nextId & precedence
                    $step['precedence'] = $count;
                    $step['nextId'] = ($count<count($stepIndices)-1) // if not last
                                    ? $list[$stepIndices[$count+1]]['databaseId'] // this->nextId = next->databaseId
                                    : null; // else this->nextId = null

                    // Update Evaluation metadata
                    $evaluation = Doctrine::getTable('Evaluation')->find($step['databaseId']);

                    $evaluation->name = $step['name'];
                    $evaluation->setNickname($step['nickname']);
                    $evaluation->description = $step['description'];
                    $evaluation->precedence = $step['precedence'];
                    $evaluation->nextId = $step['nextId'];
                    $evaluation->setDaysUntilDue($step['due']);

                    $evaluation->Event->name = $step['nickname'];
                    $evaluation->Event->description = "a finding is awaiting " . $step['name'];

                    $evaluation->Privilege->description = $step['nickname'] . " Approval";

                    $evaluation->save();

                    // Update all roles
                    $newRoles = explode('|', $step['roles'], -1);

                    Doctrine_Query::create()
                        ->delete('RolePrivilege r')
                        ->where('r.privilegeId = ?', $evaluation->Privilege->id)
                        ->execute();

                    $evaluation->Privilege->refresh();

                    foreach ($newRoles as $roleId) {
                        $evaluation->Privilege->Roles[] = Doctrine::getTable('Role')->find($roleId);
                    }
                    $evaluation->Privilege->save();
                }
            }

            // Remove orphan records (if any)
            if (count($removedSteps) > 0) {
                $evaluations = Doctrine_Query::create()
                    ->from('Evaluation e')
                    ->whereIn('e.id', $removedSteps)
                    ->execute();
                foreach ($evaluations as $evaluation) {
                    $privilege = $evaluation->Privilege;
                    $event = $evaluation->Event;
                    $evaluation->delete();
                    $event->delete();
                    $privilege->delete();
                }
            }

            // Commit
            Doctrine_Manager::connection()->commit();
        } catch (Doctrine_Exception $e) {
            // We cannot access the view script from here (for priority messenger), so rethrow after roll-back
            Doctrine_Manager::connection()->rollback();
            throw $e;
        }
        $this->_redirect('/finding/workflow/view');
    }

    /**
     * Renders the list of roles to choose in a panel
     *
     * @GETAllowed
     * @return void
     */
    public function selectRolesAction()
    {
        $this->_helper->layout()->disableLayout();
        $this->view->roles = Doctrine::getTable('Role')->getAllRolesQuery()->execute();
    }

    /**
     * Renders the remove-step panel
     *
     * @GETAllowed
     * @return void
     */
    public function removeStepAction()
    {
        $this->_helper->layout()->disableLayout();
    }
}
