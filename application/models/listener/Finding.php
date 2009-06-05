<?php
/**
 * Copyright (c) 2008 Endeavor Systems, Inc.
 *
 * This file is part of OpenFISMA.
 *
 * OpenFISMA is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * OpenFISMA is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with OpenFISMA.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author    Ryan yang <ryan.yang@reyosoft.com>
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/mw/index.php?title=License
 * @version   $Id:$
 * @package   Listener
 */

/**
 * be called by Finding changing
 *
 * @package   Listener
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/mw/index.php?title=License
 */

Class Listener_Finding extends Doctrine_Record_Listener
{
    /**
     * Change the finding status, currentevaluationid And log the audit process
     * Set the status to "NEW" when a finding is created;
     * Set the status to "DRAFT" where a finding' type changed at first time
     * Set the current evaluation id when the status is "MSA"
     * Set the current evaluation id when the status is "EA"
     * Write the audit logs
     *
     * @param Doctrine_Event $event
     * @return void
     */
    public function preSave(Doctrine_Event $event)
    {
        $invoker = $event->getInvoker();
        $modifyValues = $invoker->getModified(true);
        if (!empty($modifyValues)) {
            $auditLog = new AuditLog();
            if (empty($invoker->status)) {
                $invoker->status = 'NEW';
                $description = 'New Finding Created';
            } else {
                foreach ($modifyValues as $key=>$value) {
                    $description = 'Update: '.$key.' Original: '.$value.' NEW: '.$invoker->$key;
                }
            }

            if (array_key_exists('type', $modifyValues) && 'NEW' == $invoker->status) {
                $invoker->status = 'DRAFT';
            }

            if ('MSA' == $invoker->status) {
                $evaluation = Doctrine_Query::create()->from('Evaluation e')
                                                      ->where('e.approvalGroup = "action"')
                                                      ->addWhere('e.precedence = 0')
                                                      ->execute(array(), Doctrine::HYDRATE_ARRAY);
                $invoker->currentEvaluationId = $evaluation[0]['id'];
            }

            if ('EA' == $invoker->status) {
                 $evaluation = Doctrine_Query::create()->from('Evaluation e')
                                                      ->where('e.approvalGroup = "evidence"')
                                                      ->addWhere('e.precedence = 0')
                                                      ->execute(array(), Doctrine::HYDRATE_ARRAY);
                $invoker->currentEvaluationId = $evaluation[0]['id'];
            }

            $auditLog->description = $description;
            $auditLog->findingId   = $invoker->id;
            $auditLog->userId      = $invoker->createdByUserId;
            $auditLog->save();
        }
    }
}
