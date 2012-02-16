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
                $lists[$type][$id][$attr] = $val;
            }
        }

        // @TODO process all ADD's

        // @TODO process all REMOVE's

        // Process all records
        foreach ($lists as $list) {
            $stepIndices = array_keys($list); // Needs to go this way because the indices are strings
            for ($count = 0; $count < count($stepIndices); $count++) {
                $msg .= $stepIndices[$count] . " : ";
                $step = $list[$stepIndices[$count]];
                $msg .= $step['databaseId'] . " : ";

                // @TODO recalculate nextId & precedence
                $step['precedence'] = $count;
                $step['nextId'] = $list[$stepIndices[$count+1]]['databaseId'];
                $msg .= $step['precedence'] . " : ";

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
