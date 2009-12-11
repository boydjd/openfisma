<?php
/**
 * Copyright (c) 2008 Endeavor Systems, Inc.
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
 * A business object which represents a plan of action and milestones related
 * to a particular finding.
 *
 * @author     Ryan Yang <ryan@users.sourceforge.net>
 * @copyright  (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Model
 * @version    $Id$
 */
class Finding extends BaseFinding
{
    /**
     * Changes to these fields do not get logged
     * 
     * @var array
     * @todo This will go away when we rewrite the logging as a behavior
     */
    private static $_excludeLogKeys = array(
        'currentEvaluationId',
        'duplicateFindingId',
        'assetId',
        'responsibleOrganizationId',
        'sourceId',
        'securityControlId',
        'createdByUserId',
        'assignedToUserId',
        'currentEvaluationId',
        'uploadId',
        'status',
        'ecdLocked',
        'legacyFindingKey',
        'modifiedTs',
        'closedTs'
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
     * Declares fields stored in related records that should be indexed along with records in this table
     * 
     * @var array
     * @see Asset.php
     * @todo Doctrine 2.0 might provide a nicer approach for this
     */
    public $relationIndex = array(
        'Source' => array('nickname' => array('type' => 'keyword', 'alias' => 'source')),
        'ResponsibleOrganization' => array('nickname' => array('type' => 'unstored', 'alias' => 'system')),
        'Asset' => array('name' => array('type' => 'unstored', 'alias' => 'asset')),
        'SecurityControl' => array('code' => array('type' => 'keyword', 'alias' => 'securitycontrol'))
    );

    /**
     * Threshold of overdue for various status
     * 
     * @var array
     */
    private $_overdue = array('NEW' => 30, 'DRAFT'=>30, 'MSA'=>7, 'EN'=>0, 'EA'=>7);

    /**
     * Override the Doctrine hook to initialize new finding objects
     * 
     * @return void
     */
    public function construct()
    {
        $this->status = 'NEW';
    }
    
    /**
     * Set custom mutators
     * 
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        
        $this->hasMutator('currentEcd', 'setCurrentEcd');
        $this->hasMutator('nextDueDate', 'setNextDueDate');
        $this->hasMutator('originalEcd', 'setOriginalEcd');
        $this->hasMutator('status', 'setStatus');
        $this->hasMutator('type', 'setType');
    }

    /**
     * Returns an ordered list of all possible business statuses
     * 
     * @return array The ordered list of all possible business statuses
     */
    public static function getAllStatuses() 
    {
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
     * Get the detailed status of a Finding
     *
     * @return string The detailed status of current finding
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
     * 
     * @param User $user The specified user to submit the mitigation strategy
     * @return void
     * @throws Fisma_Exception if the mitigation strategy is submitted when the finding is not in NEW or DRAFT status
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
     * 
     * @param User $user The specified user to revise the mitigation strategy
     * @return void
     * @throws Fisma_Exception if the mitigation strategy is revised when the finding is not in EN status
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
     * @param Object $user The specified user to approve the current evaluation
     * @param string $comment The user comment why they accept the current evaluation
     * @return void
     * @throws Fisma_Exception if the findings is approved when the finding is not in MSA or EA status
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
                    $this->status = 'CLOSED';
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
     * @param $user The specified user to deny the current evaluation
     * @param string $comment The deny comment of user
     * @return void
     * @throws Fisma_Exception if the findings is denined 
     * when the finding is not in MSA or EA status or the deny comment missed
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
                $this->status = 'DRAFT';
                $this->CurrentEvaluation = null;
                break;
            case 'EA':
                $this->status = 'EN';
                $this->CurrentEvaluation = null;
                break;
        }
        $this->_updateNextDueDate();
        $this->save();
    }

    /**
     * Upload Evidence
     * Set the status as 'EA' and the currentEvaluationId as the first Evidence Evaluation id
     *
     * @param string $fileName The uploaded evidence file name
     * @param User $user The specified user to upload the evidence
     * @return void
     * @throws Fisma_Exception if the evidence is updated when the finding is not in EN status
     */
    public function uploadEvidence($fileName, User $user)
    {
        if ('EN' != $this->status) {
            throw new Fisma_Exception("Evidence can only be updated when the finding is in EN status");
        }
        $this->status = 'EA';
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
     * Set the nextduedate when the status has changed except 'CLOSED'
     * 
     * @return void
     * @throws Fisma_Exception if cannot update the next due dates since of the an invalid finding status
     * @todo why the 'Y-m-d' is a wrong date
     */
    private function _updateNextDueDate()
    {
        if (in_array($this->status, array('PEND', 'CLOSED'))) {
            $this->_set('nextDueDate', null);
            return;
        }
        
        switch ($this->status) {
            case 'NEW':
            case 'DRAFT':
            case 'MSA':
            case 'EA':
                $startDate = Fisma::now();
                break;
            case 'EN':
                $startDate = $this->currentEcd;
                break;
            default:
                throw new Fisma_Exception('Cannot update the next due date because the finding has an'
                                        . " invalid status: '$this->status'");
        }

        $nextDueDate = new Zend_Date($startDate, 'Y-m-d');
        $nextDueDate->add($this->_overdue[$this->status], Zend_Date::DAY);
        $this->_set('nextDueDate', $nextDueDate->toString('Y-m-d'));
    }

    /**
     * Get the finding evaluations by approval group
     *
     * @param string $approvalGroup The specified evaluation approval group to search
     * @return array The matched finding evaluations in array
     * @throws Fisma_Exception if the specified approval group for evaluations is neither 'action' nor 'evidence'
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
     * Write the audit log
     * 
     * @param string $description The specified audit log message to write
     * @return void
     */
    public function log($description)
    {
        $auditLog = new AuditLog();
        $auditLog->User        = User::currentUser();
        $auditLog->description = html_entity_decode($description);
        $this->AuditLogs[]     = $auditLog;
    }

    /**
     * Update logs after object insert
     * 
     * @param Doctrine_Event $event The listened doctrine event to be processed
     * @return void
     */
    public function postInsert($event)
    {
        $finding = $event->getInvoker();

        $finding->log('New Finding Created');
    }

    /**
     * Invalidate the caches that contain this finding. 
     * 
     * This will ensure that users always see accurate summary counts on the finding summary screen.
     * 
     * @param Doctrine_Event $event The listened doctrine event to be processed
     * @return void
     */
    public function postSave($event)
    {
        // Invalidate the caches that contain this finding. This will ensure that users always see
        // accurate summary counts on the finding summary screen.
        $this->ResponsibleOrganization->invalidateCache();
    }

    /**
     * Doctrine hook
     * 
     * @param Doctrine_Event $event The listened doctrine event to be processed
     * @return void
     * @todo this is copied from the former FindingListener and will be removed when the logging & notifications
     * are refactored
     */
    public function preSave($event)
    {
        $modifyValues = $this->getModified(true);

        if (!empty($modifyValues)) {
            foreach ($modifyValues as $key => $value) {
                $newValue = $this->$key;

                switch ($key) {
                    case 'status':
                        if ('MSA' == $value && 'EN' == $newValue) {
                            Notification::notify(
                                'MITIGATION_APPROVED', 
                                $this, 
                                User::currentUser(), 
                                $this->responsibleOrganizationId
                            );
                        } elseif ('EN' == $value && 'DRAFT' == $newValue) {
                            Notification::notify(
                                'MITIGATION_REVISE', 
                                $this, 
                                User::currentUser(), 
                                $this->responsibleOrganizationId
                            );
                        } elseif ('EA' == $newValue) {
                            Notification::notify(
                                'EVIDENCE_UPLOADED', 
                                $this, 
                                User::currentUser(), 
                                $this->responsibleOrganizationId
                            );
                        } elseif ( ('EA' == $value && 'EN' == $newValue)
                             || ('MSA' == $value && 'DRAFT' == $newValue) ) {
                            Notification::notify(
                                'APPROVAL_DENIED', 
                                $this, 
                                User::currentUser(), 
                                $this->responsibleOrganizationId
                            );
                        } elseif ('EA' == $value && 'CLOSED' == $newValue) {
                            Notification::notify(
                                'FINDING_CLOSED', 
                                $this, 
                                User::currentUser(), 
                                $this->responsibleOrganizationId
                            );
                            $this->log('Finding closed');
                        }
                        break;
                    case 'currentEvaluationId':
                        $event = isset($this->CurrentEvaluation->Event->name) 
                               ? $this->CurrentEvaluation->Event->name 
                               : null;
                        // If the event is null, then that indicates this was the last evaluation within its approval
                        // process. That condition is handled above.
                        if (isset($event)) {
                            Notification::notify(
                                $event, 
                                $this, 
                                User::currentUser(), 
                                $this->responsibleOrganizationId
                            );
                        }
                        break;
                    case 'currentEcd':
                        if ($this->ecdLocked && empty($this->ecdChangeDescription)) {
                            $error = 'When the ECD is locked, the user must provide a change description'
                                   . ' in order to modify the ECD.';
                            throw new Fisma_Exception($error);
                        }
                        if (!$this->ecdLocked) {
                            Fisma_Acl::requirePrivilege(
                                'finding', 
                                'update_ecd', 
                                $this->ResponsibleOrganization->nickname
                            );
                            Notification::notify(
                                'UPDATE_ECD', 
                                $this, 
                                User::currentUser(), 
                                $this->responsibleOrganizationId
                            );
                        } else {
                            Fisma_Acl::requirePrivilege(
                                'finding', 
                                'update_locked_ecd', 
                                $this->ResponsibleOrganization->nickname
                            );
                            Notification::notify(
                                'UPDATE_LOCKED_ECD', 
                                $this, 
                                User::currentUser(), 
                                $this->responsibleOrganizationId
                            );
                        }
                        break;
                    default:
                        break;
                }
                
                if (in_array($key, self::$_excludeLogKeys)) {
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
                    // See if you can look up a logical name for this column in the schema definition. 
                    // If its not defined, then use the physical name instead
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
     * Check ACL before updating a record. See if any notifications need to be sent.
     * 
     * @param Doctrine_Event $event The listened doctrine event to be processed
     * @return void
     */
    public function preUpdate($event) 
    {
        $modified = $this->getModified(true);

        if (!empty($modified)) {
            foreach ($modified as $key => $value) {
                // Check whether the user has the privilege to update this column
                if (isset(self::$_requiredPrivileges[$key])) {
                    Fisma_Acl::requirePrivilege(
                        'finding', 
                        self::$_requiredPrivileges[$key], 
                        $this->ResponsibleOrganization->nickname
                    );
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
     * Logic for updating the current expected completion date
     * 
     * @param string $value The specified value of current ECD to set
     * @return void
     */
    public function setCurrentEcd($value)
    {
        $this->_set('currentEcd', $value);
        
        // If the original ECD is not locked, then keep it synchronized with this ECD
        if (!$this->ecdLocked) {
            $this->_set('originalEcd', $value);
        }
    }

    /**
     * Throws an exception if you try to set next due date directly
     * 
     * @param string $value The specified valud of next due date to set
     * @throws Fisma_Exception if the method is called
     */
    public function setNextDueDate($value)
    {
        throw new Fisma_Exception('Next due date cannot be set directly');
    }

    /**
     * Original ECD cannot be set directly
     * 
     * @param string $value The specofoed value of original ECD to set
     * @throws Fisma_Exception if the method is called
     */
    public function setOriginalEcd($value)
    {
        throw new Fisma_Exception('Original ECD cannot be set directly');
    }

    /**
     * Mutator for status
     * 
     * @param string $value The value of status to set
     * @return void
     */
    public function setStatus($value)
    {
        // Business rules for status changes
        switch ($value) {
            case 'EN':
                if (!$this->ecdLocked) {
                    $this->ecdLocked = true;
                }
                break;
            case 'EA':
                $this->actualCompletionDate = Fisma::now();
                break;
            case 'CLOSED':
                $this->closedTs = Fisma::now();
                break;
        }

        // Update the value
        $this->_set('status', $value);
        
        // Next due date is always affected by status changes
        $this->_updateNextDueDate();
    }
    
    /**
     * Mutator for type
     * 
     * @param string $value The specified value of type to set
     * @return void
     */
    public function setType($value)
    {
        // If this is a NEW finding and the type is being set, then move it to DRAFT status
        if ('NEW' == $this->status) {
            $this->status = 'DRAFT';
        }
        
        $this->_set('type', $value);
    }
}
