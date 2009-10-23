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
 * @version   $Id:$
 * @package   Model
 */

/**
 * A business object which represents a plan of action and milestones related
 * to a particular finding.
 *
 * @package   Model
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/mw/index.php?title=License
 */
class Finding extends BaseFinding
{
    /**
     * Declares fields stored in related records that should be indexed along with records in this table
     * 
     * @see Asset.php
     * @todo Doctrine 2.0 might provide a nicer approach for this
     * @var array
     */
    public $relationIndex = array(
        'Source' => array('nickname' => array('type' => 'keyword', 'alias' => 'source')),
        'ResponsibleOrganization' => array('nickname' => array('type' => 'unstored', 'alias' => 'system')),
        'Asset' => array('name' => array('type' => 'unstored', 'alias' => 'asset')),
        'SecurityControl' => array('code' => array('type' => 'keyword', 'alias' => 'securitycontrol'))
    );

    /**
     * An array which indicates how many days each status get until it is overdue. 
     * 
     * EN is undefined since EN overdue is based on the ECD date
     * 
     * @var array
     */
    private $_overdue = array(
        'NEW' => 30, 
        'DRAFT' => 30, 
        'MSA' => 7, 
        'EN' => 0, 
        'EA' => 7
    );

    /**
     * These are fields which should not be logged
     * 
     * @todo improve this design. loggable should be a behavior and which fields produce logs should be defined in yaml
     */
    private static $_unLogKeys = array(
        'currentEvaluationId',
        'status',
        'ecdLocked',
        'legacyFindingKey',
        'modifiedTs',
        'closedTs'
    );

    /**
     * Maps fields to their corresponding privileges. This is kind of ugly. A better solution would be to store this
     * information in the model itself, and then include it in a global listener.
     * 
     * @var array
     */
    private static $_requiredPrivileges = array(
        'type' => 'update_type',
        'description' => 'update_description',
        'recommendation' => 'update_recommendation',
        'mitigationStrategy' => 'update_course_of_action',
        'responsibleOrganizationId' => 'update_assignment',
        'securityControl' => 'update_control_assignment',
        'threatLevel' => 'update_threat',
        'threat' => 'update_threat',
        'countermeasures' => 'update_countermeasures',
        'countermeasuresEffectiveness' => 'update_countermeasures',
        'recommendation' => 'update_recommendation',
        'resourcesRequired' => 'update_resources'
    );

    /**
     * Notification type with each keys. The ECD logic is a little more complicated so it is handled separately.
     * Threat & countermeasures are also handled separately.
     * 
     * @var array
     */
    private static $_notificationKeys = array(
        'mitigationStrategy'        => 'UPDATE_COURSE_OF_ACTION',
        'securityControlId'         => 'UPDATE_SECURITY_CONTROL',
        'responsibleOrganizationId' => 'UPDATE_RESPONSIBLE_SYSTEM',
        'countermeasures'           => 'UPDATE_COUNTERMEASURES',
        'threat'                    => 'UPDATE_THREAT',
        'resourcesRequired'         => 'UPDATE_RESOURCES_REQUIRED',
        'description'               => 'UPDATE_DESCRIPTION',
        'recommendation'            => 'UPDATE_RECOMMENDATION',
        'type'                      => 'UPDATE_MITIGATION_TYPE'
    );

    /**
     * Override Doctrine constructor
     */
    public function construct()
    {
        $this->status = 'NEW';
        $this->CreatedBy = User::currentUser();
        $this->_updateNextDueDate();
    }
    
    /**
     * Returns an ordered list of all business possible statuses
     * 
     * @return array
     */
    public static function getAllStatuses() {
        $allStatuses = array('NEW', 'DRAFT');
        
        $mitigationStatuses = Doctrine::getTable('Evaluation')->findByDql('approvalGroup = ?', array('action'));
        foreach ($mitigationStatuses as $status) {
            $allStatuses[] = $status->nickname;
        }
        
        $allStatuses[] = 'EN';

        $evidenceStatus = Doctrine::getTable('Evaluation')->findByDql('approvalGroup = ?', array('evidence'));
        foreach ($evidenceStatus as $status) {
            $allStatuses[] = $status->nickname;
        }
        
        $allStatuses[] = 'CLOSED';

        return $allStatuses;
    }

    /**
     * get the detailed status of a Finding
     *
     * @return string
     */
    public function getStatus()
    {
        if (!in_array($this->status, array('MSA', 'EA'))) {
            return $this->status;
        } else {
            return $this->CurrentEvaluation->nickname;
        }
    }

    /**
     * Submit Mitigation Strategy
     * Set the status as "MSA" and the currentEvaluationId as the first mitigation evaluation id
     */
    public function submitMitigation(User $user)
    {
        if (!('NEW' == $this->status || 'DRAFT' == $this->status)) {
            throw new Fisma_Exception("Mitigation strategy can only be submitted in NEW or DRAFT status");
        }
        $this->status = 'MSA';
        $this->_updateNextDueDate();
        $evaluation = Doctrine::getTable('Evaluation')
                      ->findByDql('approvalGroup = "action" AND precedence = 0');
        $this->CurrentEvaluation = $evaluation[0];
        $this->log('Submitted mitigation strategy');

        $this->save();
    }

    /**
     * Revise the Mitigation Strategy
     * Set the status as "DRAFT" and the currentEvaluationId as null
     */
    public function reviseMitigation(User $user)
    {
        if ('EN' != $this->status) {
            throw new Fisma_Exception("The mitigation strategy can only be revised in EN status");
        }
        $this->status = 'DRAFT';
        $this->_updateNextDueDate();
        $this->CurrentEvaluation = null;
        $this->log('Revise mitigation strategy');

        $this->save();
    }

    /**
     * Approve the current evaluation,
     * then update the status to either point to
     * a new Evaluation or else to change the status to DRAFT, EN,
     * or CLOSED as appropriate
     * 
     * @param Object $user a specific user object
     * @param string $comment The user can comment on why they are approving it
     */
    public function approve(User $user, $comment)
    {
        if (is_null($this->currentEvaluationId) || !in_array($this->status, array('MSA', 'EA'))) {
            throw new Fisma_Exception("Findings can only be approved when in MSA or EA status");
        }
        
        $findingEvaluation = new FindingEvaluation();
        if ($this->CurrentEvaluation->approvalGroup == 'evidence') {
            $findingEvaluation->Evidence   = $this->Evidence->getLast();
        }
        $findingEvaluation->Finding    = $this;
        $findingEvaluation->Evaluation = $this->CurrentEvaluation;
        $findingEvaluation->decision   = 'APPROVED';
        $findingEvaluation->User       = $user;
        $findingEvaluation->comment      = $comment;
        $this->FindingEvaluations[]    = $findingEvaluation;

        $this->log('Approved: ' . $this->getStatus());

        switch ($this->status) {
            case 'MSA':
                if ($this->CurrentEvaluation->nextId == null) {
                    $this->status = 'EN';
                }
                break;
            case 'EA':
                if ($this->CurrentEvaluation->nextId == null) {
                    $this->status   = 'CLOSED';
                    $this->closedTs = date('Y-m-d');
                }
                break;
        }
        
        if ($this->CurrentEvaluation->nextId != null) {
            $this->CurrentEvaluation = $this->CurrentEvaluation->NextEvaluation;
        } else {
            $this->CurrentEvaluation = null;
        }
        $this->_updateNextDueDate();
        $this->save();
    }

    /**
     * Deny the current evaluation
     *
     * @param $user a specific user
     * @param string $comment deny comment
     */
    public function deny(User $user, $comment)
    {
        if (is_null($this->currentEvaluationId) || !in_array($this->status, array('MSA', 'EA'))) {
            throw new Fisma_Exception("Findings can only be denined when in MSA or EA status");
        }

        if (is_null($comment) || empty($comment)) {
            throw new Fisma_Exception("Comments are required when denying an evaluation");
        }

        $findingEvaluation = new FindingEvaluation();
        if ($this->CurrentEvaluation->approvalGroup == 'evidence') {
            $findingEvaluation->Evidence   = $this->Evidence->getLast();
        }
        $findingEvaluation->Finding      = $this;
        $findingEvaluation->Evaluation   = $this->CurrentEvaluation;
        $findingEvaluation->decision     = 'DENIED';
        $findingEvaluation->User         = $user;
        $findingEvaluation->comment      = $comment;
        $this->FindingEvaluations[]      = $findingEvaluation;

        $this->log('Denied: ' . $this->getStatus() . ' &emdash; ' . $comment);

        switch ($this->status) {
            case 'MSA':
                $this->status              = 'DRAFT';
                $this->CurrentEvaluation   = null;
                break;
            case 'EA':
                $this->status              = 'EN';
                $this->CurrentEvaluation   = null;
                break;
        }
        $this->_updateNextDueDate();
        $this->save();
    }

    /**
     * Upload Evidence
     * Set the status as 'EA' and the currentEvaluationId as the first Evidence Evaluation id
     *
     * @param string $fileName evidence file name
     * @param $user
     */
    public function uploadEvidence($fileName, User $user)
    {
        if ('EN' != $this->status) {
            throw new Fisma_Exception("Evidence can only be updated when the finding is in EN status");
        }
        $this->status    = 'EA';
        $this->ecdLocked = true;
        $this->_updateNextDueDate();
        $evaluation = Doctrine::getTable('Evaluation')
                                        ->findByDql('approvalGroup = "evidence" AND precedence = 0 ');
        $this->CurrentEvaluation = $evaluation[0];
        $evidence = new Evidence();
        $evidence->filename = $fileName;
        $evidence->Finding  = $this;
        $evidence->User     = $user;
        $this->Evidence[]   = $evidence;

        $this->log('Upload evidence: ' . $fileName);
        $this->save();
    }

    /**
     * Due dates vary depending on the status of the finding
     */
    private function _updateNextDueDate()
    {
        if (in_array($this->status, array('PEND', 'CLOSED'))) {
            $this->nextDueDate = null;
            return;
        }
        
        switch ($this->status) {
            case 'NEW':
            case 'DRAFT':
            case 'MSA':
            case 'EA':
                $startDate = Zend_Date::now();
                break;
            case 'EN':
                $startDate = new Zend_Date($this->currentEcd, 'Y-m-d');
                break;
            default:
                throw new Fisma_Exception('Cannot update the next due date because the finding has an'
                                        . " invalid status: '$this->status'");
        }
        
        $nextDueDate = $startDate;
        $nextDueDate->addDay($this->_overdue[$this->status]);
        $this->nextDueDate = $nextDueDate->toString('Y-m-d');
    }

    /**
     * Get the finding evaluations by approval group
     *
     * @param string $approvalGroup evaluation approval group
     * @return array
     */
    public function getFindingEvaluations($approvalGroup)
    {
        if (!in_array($approvalGroup, array('action', 'evidence'))) {
            throw new Fisma_Exception("The approval group for evaluations must either be 'action' or 'evidence', 
                but was actually '$approvalGroup'");
        }
        $findingEvaluations = array();
        foreach ($this->FindingEvaluations as $findingEvaluation) {
            if ($approvalGroup == $findingEvaluation->Evaluation->approvalGroup) {
                $findingEvaluations[] = $findingEvaluation;
            }
        }
        return $findingEvaluations;
    }

    /**
     * write the audit log
     * 
     * @param string $description log message
     * @return this
     */
    public function log($description)
    {
        $auditLog = new AuditLog();
        $auditLog->User        = User::currentUser();
        $auditLog->description = html_entity_decode($description);
        $this->AuditLogs[]     = $auditLog;
    }

    /**
     * Setting the current ECD can, in some cases, also update the original ECD
     * 
     * @param string $newEcd
     */
    public function setCurrentEcd($newEcd)
    {
        if (!$this->ecdLocked) {
            // Have to use private mutator to avoid invoking the public mutator (which would result in an exception)
            $this->_set('originalEcd', $newEcd);
        }

        $this->currentEcd = $newEcd;
    }
    /**
     * The original ECD can't be set directly, so this mutator throws an exception if somebody tries to set it
     * 
     * This is necessary because Doctrine will provide the mutator automatically. So we have to override the parent 
     * method in order to enforce our policy.
     * 
     * @param string $unused This parameter is ignored
     */
    public function setOriginalEcd($unused)
    {
        throw new Fisma_Exception('The original ECD cannot be set directly.');
    }
    
    /**
     * Business rules for status
     * 
     * @param string $newStatus
     */
    public function setStatus($newStatus)
    {
        // Once the mitigation strategy is approved, the original ECD becomes locked.
        if ('EN' == $newStatus) {
            $this->ecdLocked = true;
        }
        
        $this->status = $newStatus;
    }
    
    /**
     * Busines rules associated for the mitigation type
     * 
     * @param string $newType
     */
    public function setType($newType)
    {
        // If the finding is in NEW status, changing the type puts it into DRAFT
        if ('NEW' == $this->status) {
            $this->status = 'DRAFT';
        }
        
        // Once set, the type cannot be unset
        if (!empty($this->status) && empty($newType)) {
            throw new Fisma_Exception("Mitigation type cannot be unset after it has been set previously");
        }
        
        $this->type = $newType;
        var_dump($this->toArray());die;
        
    }

    /**
     * Override Doctrine hook
     * 
     * @param Doctrine_Event $event
     */
    public function preInsert(Doctrine_Event $event)
    {
        // Duplicate findings have special logic... see requirements
        $duplicateFinding  = $this->getTable()->findByDql('description LIKE ?', $finding->description);
        if (!empty($duplicateFinding[0]) && $duplicateFinding[0]->status != 'CLOSED') {
            $finding->DuplicateFinding = $duplicateFinding[0];
            $finding->status           = 'PEND';
        } elseif (in_array($finding->type, array('CAP', 'AR', 'FP'))) {
            $finding->status           = 'DRAFT';
        } else {
            $finding->status           = 'NEW';
        }

        $finding->log('New Finding Created');
    }

    /**
     * Perform ACL checks and send notifications
     * 
     * @param Doctrine_Event $event
     */
    public function preUpdate(Doctrine_Event $event) 
    {
        $modified = $this->getModified(true);

        if (!empty($modified)) {
            foreach ($modified as $key => $value) {
                // Check whether the user has the privilege to update this column
                if (isset(self::$_requiredPrivileges[$key])) {
                    Fisma_Acl::requirePrivilege('finding', 
                                                self::$_requiredPrivileges[$key], 
                                                $this->ResponsibleOrganization->nickname);
                }
            
                // Check whether this field generates any notification events
                if (array_key_exists($key, self::$_notificationKeys)) {
                    $type = self::$_notificationKeys[$key];
                }

                if (isset($type)) {
                    Notification::notify($type, $this, User::currentUser(), $this->responsibleOrganizationId);
                }
            }
        }       
    }

    /**
     * Write the audit logs and send notifications
     * 
     * @todo Notifications should be in post-save... or better yet a separate behavior
     * 
     * @param Doctrine_Event $event
     */
    public function preSave(Doctrine_Event $event)
    {
        $modifyValues = $this->getModified(true);

        if (!empty($modifyValues)) {
            foreach ($modifyValues as $key => $value) {
                $newValue = $this->$key;

                // Some related fields need to be fetched to log the change values
                switch ($key) {
                    case 'securityControlId':
                        $key      = 'Security Control';
                        $value    = Doctrine::getTable('SecurityControl')->find($value)->code;
                        $newValue = $this->SecurityControl->code;
                        break;
                    case 'responsibleOrganizationId':
                        $key      = 'Responsible Organization';
                        $value    = Doctrine::getTable('Organization')->find($value)->name;
                        $newValue = $this->ResponsibleOrganization->name;
                        break;
                    case 'status':
                        if ('MSA' == $value && 'EN' == $newValue) {
                            Notification::notify('MITIGATION_APPROVED', 
                                                 $this, 
                                                 User::currentUser(), 
                                                 $this->responsibleOrganizationId);
                        } elseif ('EN' == $value && 'DRAFT' == $newValue) {
                            Notification::notify('MITIGATION_REVISE', 
                                                 $this, 
                                                 User::currentUser(), 
                                                 $this->responsibleOrganizationId);
                        } elseif ('EA' == $newValue) {
                            Notification::notify('EVIDENCE_UPLOADED', 
                                                 $this, 
                                                 User::currentUser(), 
                                                 $this->responsibleOrganizationId);
                            $this->actualCompletionDate = Fisma::now();
                        } elseif ( ('EA' == $value && 'EN' == $newValue)
                             || ('MSA' == $value && 'DRAFT' == $newValue) ) {
                            Notification::notify('APPROVAL_DENIED', 
                                                 $this, 
                                                 User::currentUser(), 
                                                 $this->responsibleOrganizationId);
                        } elseif ('EA' == $value && 'CLOSED' == $newValue) {
                            Notification::notify('FINDING_CLOSED', 
                                                 $this, 
                                                 User::currentUser(), 
                                                 $this->responsibleOrganizationId);
                            $this->closedTs = Fisma::now();
                            $this->log('Finding closed');
                        }
                        
                        break;
                    case 'currentEvaluationId':
                        $event = $this->CurrentEvaluation->Event->name;
                        // If the event is null, then that indicates this was the last evaluation within its approval
                        // process. That condition is handled above.
                        if (isset($event)) {
                            Notification::notify($event, 
                                                 $this, 
                                                 User::currentUser(), 
                                                 $this->responsibleOrganizationId);
                        }
                        break;
                    case 'currentEcd':
                        if ($this->ecdLocked && empty($this->ecdChangeDescription)) {
                            throw new Fisma_Exception('When the ECD is locked, the user must provide a change description
                                                       in order to modify the ECD.');
                        }
                        if (!$this->ecdLocked) {
                            Fisma_Acl::requirePrivilege('finding', 
                                                        'update_ecd', 
                                                        $this->ResponsibleOrganization->nickname);
                            $this->originalEcd = $this->currentEcd;
                            Notification::notify('UPDATE_ECD', 
                                                 $this, 
                                                 User::currentUser(), 
                                                 $this->responsibleOrganizationId);
                        } else {
                            Fisma_Acl::requirePrivilege('finding', 
                                                        'update_locked_ecd', 
                                                        $this->ResponsibleOrganization->nickname);
                            Notification::notify('UPDATE_LOCKED_ECD', 
                                                 $this, 
                                                 User::currentUser(), 
                                                 $this->responsibleOrganizationId);
                        }
                        break;
                    default:
                        break;
                }
                if (in_array($key, self::$_unLogKeys)) {
                    continue;
                }

                $value    = $value ? html_entity_decode(strip_tags($value)) : 'NULL';
                $newValue = html_entity_decode(strip_tags($newValue));

                // Only log if $newValue is actually different from $value. 
                // Ignore changes to NULL/""/NONE if one of these was present 
                // in the original $value.
                if ( (!(is_null($value) || empty($value) || $value == 'NONE') && 
                      !(is_null($newValue) || empty($newValue) || $newValue == 'NONE'))
                      || ($value != $newValue)) {
                    // See if you can look up a logical name for this column in the schema definition. If its not defined,
                    // then use the physical name instead
                    $column = $this->getTable()->getColumnDefinition(strtolower($key));
                    $logicalName = (isset($column['extra']) && isset($column['extra']['logicalName']))
                                 ? $column['extra']['logicalName']
                                 : $key;
 
                    $message = "UPDATE: $logicalName\n ORIGINAL: $value\nNEW: $newValue";
                    $this->log($message);
                }
            }
        }
    }

    /**
     * Invalidate finding caches after record is saved
     * 
     * @param Doctrine_Event $event
     */
    public function postSave(Doctrine_Event $event)
    {
        $this->ResponsibleOrganization->invalidateCache();
    }
}
