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
 * View and edit the finding workflow
 *
 * @author     Duy K. Bui <duy.bui@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2011 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Controller
 */
class Finding_WorkflowController extends Fisma_Zend_Controller_Action_Security
{
    /**
     * Construct the workflow diagram
     *
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
                $type = $chunks[0];
                $id = $chunks[1];
                $attr = $chunks[2];

                if ($id != 'skeleton') {
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
                        $newStep->Event->description = $step['name'];
                        $newStep->Event->Privilege = $notificationPrivilege;

                        $newStep->Privilege = new Privilege();
                        $newStep->Privilege->resource = 'finding';
                        $newStep->Privilege->action = $step['nickname'];
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
                            $finding->setStatus($finding->status);
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
                    $evaluation->nickname = $step['nickname'];
                    $evaluation->description = $step['description'];
                    $evaluation->precedence = $step['precedence'];
                    $evaluation->nextId = $step['nextId'];

                    $evaluation->save();

                    // Update all roles
                    $newRoles = explode('|', $step['roles'], -1);

                    Doctrine_Query::create()
                        ->delete('RolePrivilege r')
                        ->andWhere('r.privilegeId = ?', $evaluation->Privilege->id)
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
                Doctrine_Query::create()
                    ->delete('FindingEvaluation fe')
                    ->whereIn('fe.evaluationId', $removedSteps)
                    ->execute();
                Doctrine_Query::create()
                    ->delete('Evaluation e')
                    ->whereIn('e.id', $removedSteps)
                    ->execute();
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
     * @phpdoc: short description.
     *
     * @return @phpdoc
     */
    public function selectRolesAction()
    {
        $this->_helper->layout()->disableLayout();
        $this->view->roles = Doctrine_Query::create()
            ->from('Role')
            ->execute();
    }

    /**
     * @phpdoc: short description.
     *
     * @return @phpdoc
     */
    public function removeStepAction()
    {
        $this->_helper->layout()->disableLayout();
    }
}
