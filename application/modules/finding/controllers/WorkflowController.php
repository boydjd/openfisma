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
        $msg = '';
        $debug = true;
        $debug = false;

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

        // @TODO process all ADD's
        foreach ($lists as $listName => &$list) {
            foreach ($list as &$step) {
                if (empty($step['databaseId'])) {
                    $newStep = new Evaluation();

                    $newStep->name = $step['name'];
                    $newStep->nickname = $step['nickname'];
                    $newStep->precedence = 0;//$step['precedence'];
                    $newStep->description = $step['description'];
                    $newStep->approvalGroup = $listName;

                    $newStep->Event = new Event();
                    $newStep->Event->name = $step['nickname'];
                    $newStep->Event->description = $step['name'];
                    $newStep->Event->privilegeId = 2; // @TODO fetch the privilegeId from database

                    $newStep->Privilege = new Privilege();
                    $newStep->Privilege->resource = 'finding';
                    $newStep->Privilege->action = $step['nickname'];
                    $newStep->Privilege->description = $step['nickname'] . " Approval";

                    $newStep->save(); // precedence & nextId are temporary and must be updated later
                    $step['databaseId'] = $newStep->id; // this is why nextId's must be calculated after all insertions
                }
            }
        }

        /* @TODO process all REMOVE's
        foreach ($lists as $listName => $list) {
            $stepIndices = array_keys($list); // Needs to go this way because the indices are strings
            for ($count = 0; $count < count($stepIndices); $count++) {
                $step = $list[$stepIndices[$count]];
            }
        }*/

        // Process all records
        foreach ($lists as $listName => &$list) {
            $stepIndices = array_keys($list); // Needs to go this way because the indices are strings
            for ($count = 0; $count < count($stepIndices); $count++) {
                $step = &$list[$stepIndices[$count]];

                // recalculate nextId & precedence
                $step['precedence'] = $count;
                $step['nextId'] = $list[$stepIndices[$count+1]]['databaseId'];

                // Update all records
                $updateQuery = Doctrine_Query::create()
                    ->update('Evaluation e')
                    ->set('e.name', '?', $step['name'])
                    ->set('e.nickname', '?', $step['nickname'])
                    ->set('e.description', '?', $step['description'])
                    ->set('e.precedence', '?', $step['precedence'])
                    ->set('e.nextId', ($step['nextId']) ? '?' : 'null', $step['nextId'])
                    ->where('e.id = ?', $step['databaseId']);
                $msg .= $updateQuery->execute();
                $msg .= '<br/>';
            }
        }

        if ($debug) {
            throw new Exception("<br/>$msg<br/>");
        } else {
            $this->_redirect('/finding/workflow/view');
        }
    }
}
