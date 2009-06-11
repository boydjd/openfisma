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

Class ListenerFinding extends Doctrine_Record_Listener
{
    /**
     * Set the status to "NEW" when a finding is created 
     * Also save the audit log for the finding creation.
     *
     * @param Doctrine_Event $event
     */
    public function preInsert(Doctrine_Event $event)
    {
        $finding = $event->getInvoker();
        $finding->status = 'NEW';
        $message = 'New Finding Created';

        $auditLog = new AuditLog();
        $auditLog->userId      = $finding->createdByUserId;
        $auditLog->description = $message;
        $auditLog->save();

    }

    /**
     * Change the finding status, currentevaluationid And log the audit process
     * Set the status to "DRAFT" where a finding' type changed at first time
     * Set the current evaluation id when the status is "MSA"
     * Set the current evaluation id when the status is "EA"
     * Save the audit logs
     *
     * @param Doctrine_Event $event
     */
    public function preUpdate(Doctrine_Event $event)
    {
        $finding = $event->getInvoker();
        $modifyValues = $finding->getModified(true);
        if (!empty($modifyValues)) {
            $auditLog = new AuditLog();
            foreach ($modifyValues as $key=>$value) {
                if ('modifyTs' != $key) {
                    $message = 'Update: ' . $key . ' Original: ' . $value . ' NEW: ' . $finding->$key;
                    $auditLog->userId      = $this->createdByUserId;
                    $auditLog->description = $message;
                    $finding->AuditLogs[]  = $auditLog;
                }
            }

            if (array_key_exists('type', $modifyValues) && 'NEW' == $finding->status) {
                $finding->status = 'DRAFT';
            }
        }
    }
}
