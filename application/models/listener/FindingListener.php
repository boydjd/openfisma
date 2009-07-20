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
 * @author    Ryan Yang <ryan@users.sourceforge.net>
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
     * these keys don't catch logs
     */
    private static $unLogKeys = array(
                            'currentEvaluationId',
                            'status',
                            'ecdLocked',
                            'nextDueDate',
                            'legacyFindingKey',
                            'modifiedTs',
                            'closedTs'
                         );

    /**
     * Notification type with each keys
     */
    private static $notificationKeys = array(
                               'mitigationStrategy'        => Notification::UPDATE_COURSE_OF_ACTION,
                               'securityControlId'         => Notification::UPDATE_CONTROL_ASSIGNMENT,
                               'responsibleOrganizationId' => Notification::UPDATE_FINDING_ASSIGNMENT,
                               'countermeasures'           => Notification::UPDATE_COUNTERMEASURES,
                               'threat'                    => Notification::UPDATE_THREAT,
                               'resourcesRequired'         => Notification::UPDATE_FINDING_RESOURCES,
                               'expectedCompletionDate'    => Notification::UPDATE_EST_COMPLETION_DATE,
                                            );
    /**
     * Set the status as "NEW"  for a new finding created or as "PEND" when duplicated
     * write the audit log
     * 
     * @param Doctrine_Event $event
     */
    public function preInsert(Doctrine_Event $event)
    {
        $finding = $event->getInvoker();
        $duplicateFinding  = $finding->getTable()
                                     ->findByDql('description LIKE ?', "%{$finding->description}%");
        if (!empty($duplicateFinding[0])) {
            $finding->DuplicateFinding = $duplicateFinding[0];
            $finding->status           = 'PEND';
        } elseif (in_array($finding->type, array('CAP', 'AR', 'FP'))) {
            $finding->status           = 'DRAFT';
        } else {
            $finding->status           = 'NEW';
        }
        $finding->CreatedBy       = User::currentUser();
        $finding->updateNextDueDate();
        $finding->log('New Finding Created');
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
            foreach ($modifyValues as $key => $value) {
                $newValue = $finding->$key;
                $type     = null;
                switch ($key) {
                    case 'type':
                        $finding->status = 'DRAFT';
                        $finding->updateNextDueDate();
                        break;
                    case 'securityControlId':
                        $key      = 'Security Control';
                        $value    = Doctrine::getTable('SecurityControl')->find($value)->code;
                        $newValue = $finding->SecurityControl->code;
                        break;
                    case 'responsibleOrganizationId':
                        $key      = 'Responsible Organization';
                        $value    = Doctrine::getTable('Organization')->find($value)->name;
                        $newValue = $finding->ResponsibleOrganization->name;
                        break;
                    case 'status':
                        if ('DRAFT' == $value) {
                            $type = Notification::MITIGATION_STRATEGY_SUBMIT;
                        }
                        if ('EN' == $value && 'DRAFT' == $newValue) {
                            $type = Notification::MITIGATION_STRATEGY_REVISE;
                        }
                        if ('EA' == $newValue) {
                            $type = Notification::EVIDENCE_UPLOAD;
                            $finding->actualCompletionDate = date('Y-m-d');
                        }
                        if ('EA' == $value && 'EN' == $newValue) {
                            $type = Notification::EVIDENCE_DENIED;
                        }
                        if ('EA' == $value && 'CLOSED' == $newValue) {
                            $type = Notification::FINDING_CLOSED;
                        }
                        if ('EN' == $newValue) {
                            $finding->expectedCompletionDate = $finding->currentEcd;
                        }
                        break;
                    case 'currentEvaluationId':
                        $evaluation = Doctrine::getTable('Evaluation')->find($value);
                        if ('action' == $evaluation->approvalGroup && 'DRAFT' != $finding->status) {
                            if ('0' == $evaluation->precedence) {
                                $type = Notification::MITIGATION_APPROVED_SSO;
                            }
                            if ('1' == $evaluation->precedence) {
                                $type = Notification::MITIGATION_APPROVED_IVV;
                            }
                        }
                        if ('evidence' == $evaluation->approvalGroup && 'EN' != $finding->status) {
                            if ('0' == $evaluation->precedence) {
                                $type = Notification::EVIDENCE_APPROVED_1ST;
                            }
                            if ('1' == $evaluation->precedence) {
                                $type = Notification::EVIDENCE_APPROVED_2ND;
                            }
                        }
                        break;
                    case 'expectedCompletionDate':
                        if ('DRAFT' == $finding->status) {
                            $finding->currentEcd = $finding->expectedCompletionDate;
                            $finding->expectedCompletionDate = null;
                        }
                        break;
                    default:
                        break;
                }
                if (array_key_exists($key, self::$notificationKeys)) {
                    $type = self::$notificationKeys[$key];
                }
                if (!empty($type)) {
                    Notification::notify($type, $finding, User::currentUser(), $finding->responsibleOrganizationId);
                }
                if (in_array($key, self::$unLogKeys)) {
                    continue;
                }

                // See if you can look up a logical name for this column in the schema definition. If its not defined,
                // then use the physical name instead
                $column = $finding->getTable()->getColumnDefinition(strtolower($key));
                $logicalName = (isset($column['extra']) && isset($column['extra']['logicalName']))
                             ? $column['extra']['logicalName']
                             : $key;
                
                $value    = $value ? html_entity_decode(strip_tags($value)) : 'NULL';
                $newValue = html_entity_decode(strip_tags($newValue));
                $message = "UPDATE: $logicalName\n ORIGINAL: $value\nNEW: $newValue";
                $finding->log($message);
            }
        }
    }

    /**
     * Notify the finding creation, the finding id exists now.
     */
    public function postInsert(Doctrine_Event $event)
    {
        $finding = $event->getInvoker();
        if ('scan' == $finding->Asset->source) {
            $notifyType = Notification::FINDING_INJECT;
        } else {
            if ($finding->uploadId) {
                $notifyType = Notification::FINDING_IMPORT;
            } else {
                $notifyType = Notification::FINDING_CREATED;
            }
        }
        Notification::notify($notifyType, $finding, User::currentUser(), $finding->responsibleOrganizationId);
    }

    /**
     * Insert or Update finding lucene index
     */
    public function postSave(Doctrine_Event $event)
    {
        $finding  = $event->getInvoker();
        $modified = $finding->getModified($old=false, $last=true);
        Fisma_Lucene::updateIndex('finding', $finding->id, $modified);
        
        // Invalidate the caches that contain this finding. This will ensure that user's always see
        // accurate summary counts on the finding summary screen.
        $finding->ResponsibleOrganization->invalidateCache();
    }

    /**
     * Delete a finding lucene index
     */
    public function postDelete(Doctrine_Event $event)
    {
        $finding  = $event->getInvoker();
        Fisma_Lucene::deleteIndex('finding', $finding->id);
    }
}
