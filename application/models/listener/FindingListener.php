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
 * @version   $Id$
 * @package   Listener
 */

/**
 * be called by Finding changing
 *
 * @package   Listener
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/mw/index.php?title=License
 */
class FindingListener extends Doctrine_Record_Listener
{
    /**
     * Set the status as "NEW"  for a new finding created or as "PEND" when duplicated
     * write the audit log
     * 
     * @param Doctrine_Event $event
     */
    public function preInsert(Doctrine_Event $event)
    {
        $finding = $event->getInvoker();
        /** @doctrine i found a sql injection */
        $duplicateFinding  = $finding->getTable()
                                     ->findByDql("description LIKE '%$user->description%'");
        if (!empty($duplicateFinding[0])) {
            $finding->DuplicateFinding = $duplicateFinding[0];
            $finding->status           = 'PEND';
        } else {
            $finding->status           = 'NEW';
        }
        $finding->CreatedBy       = User::currentUser();
        $finding->_updateNextDueDate();

        $auditLog              = new AuditLog();
        $auditLog->User        = User::currentUser();
        $auditLog->description = 'New Finding Created';
        $finding->AuditLogs[]  = $auditLog;
    }

    /**
     * Write the audit logs
     * @todo the log need to get the user who did it
     * @param Doctrine_Event $event
     */
    public function preUpdate(Doctrine_Event $event)
    {
        $finding = $event->getInvoker();
        $modifyValues = $finding->getModified(true);
        if (!empty($modifyValues)) {
            foreach ($modifyValues as $key=>$value) {
                //We don't want to log these keys
                if (!array_key_exists($key, array('currentEvaluationId', 'nextDueDate', 'legacyFindingKey'))) {
                    $auditLog = new AuditLog();
                    $message = 'Update: ' . $key . ' Original: ' . $value . ' NEW: ' . $finding->$key;
                    $auditLog->User        = User::currentUser();
                    $auditLog->description = $message;
                    $finding->AuditLogs[]     = $auditLog;
                }
            }

            if (array_key_exists('type', $modifyValues)) {
                if ('NEW' == $finding->status) {
                    $finding->status = 'DRAFT';
                } else {
                    //@todo english
                    throw new Fisma_Exception("The finding's type can't be changed at the current status");
                }
            }
        }
    }
}
