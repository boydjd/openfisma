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
 */
class Finding extends BaseFinding implements Fisma_Zend_Acl_OrganizationDependency
{
    /**
     * Threshold of overdue for various status
     *
     * @var array
     */
    private $_overdue = array('NEW' => 30, 'DRAFT'=>30, 'EN'=>0);

    /**
     * Override the Doctrine hook to initialize new finding objects
     *
     * @return void
     */
    public function construct()
    {
        // Set default status for new objects (i.e. objects with transient state)
        $state = $this->state();
        if ($state == Doctrine_Record::STATE_TCLEAN || $state == Doctrine_Record::STATE_TDIRTY) {
            $this->status = 'NEW';
            $this->countermeasuresEffectiveness = 'LOW';
            $this->countermeasures = 'N/A';
        }
    }

    /**
     * Set custom mutators
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();

        $this->hasMutator('countermeasuresEffectiveness', 'setCountermeasuresEffectiveness');
        $this->hasMutator('currentEcd', 'setCurrentEcd');
        $this->hasMutator('ecdChangeDescription', 'setEcdChangeDescription');
        $this->hasMutator('nextDueDate', 'setNextDueDate');
        $this->hasMutator('discoveredDate', 'setDiscoveredDate');
        $this->hasMutator('originalEcd', 'setOriginalEcd');
        $this->hasMutator('pocId', 'setPocId');
        $this->hasMutator('status', 'setStatus');
        $this->hasMutator('threatLevel', 'setThreatLevel');
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
     * Check if the Mitigation Strategy can be submitted
     *
     * @return array
     */
    public function getMissingMSFields()
    {
        $array = array(
            'Action Type' => array('value' => $this->type, 'tab' => 'Mitigation Strategy'),
            'Action Plan' => array('value' => $this->mitigationStrategy, 'tab' => 'Mitigation Strategy'),
            'Resources Required' => array('value' => $this->resourcesRequired, 'tab' => 'Mitigation Strategy'),
            'Expected Completion Date' => array('value' => $this->currentEcd, 'tab' => 'Mitigation Strategy'),
            'Security Control' => array('value' => $this->securityControlId, 'tab' => 'Security Control'),
            'Threat Level' => array('value' => $this->threatLevel, 'tab' => 'Risk Analysis'),
            'Threat Description' => array('value' => $this->threat, 'tab' => 'Risk Analysis'),
            'Countermeasures Effectiveness'
                => array('value' => $this->countermeasuresEffectiveness, 'tab' => 'Risk Analysis'),
            'Description of Countermeasures' => array('value' => $this->countermeasures, 'tab' => 'Risk Analysis')
        );
        $results = array();
        foreach ($array as $name => $row) {
            $value = strip_tags($row['value']);
            if ($value == '' || $value == 'NONE' || $value == '0000-00-00') {
                $results[$name] = $row['tab'];
            }
        }
        return $results;
    }

    /**
     * Submit Mitigation Strategy
     * Set the status as "MSA" and the currentEvaluationId as the first mitigation evaluation id
     *
     * @param User $user The specified user to submit the mitigation strategy
     * @return void
     * @throws Fisma_Zend_Exception if the mitigation strategy is submitted when the finding is not in NEW or DRAFT
     * status
     */
    public function submitMitigation(User $user)
    {
        if (!('NEW' == $this->status || 'DRAFT' == $this->status)) {
            throw new Fisma_Zend_Exception("Mitigation strategy can only be submitted in NEW or DRAFT status");
        }
        $this->status = 'MSA';
        $evaluation = Doctrine::getTable('Evaluation')
                      ->findByDql('approvalGroup = "action" AND precedence = 0');
        $this->CurrentEvaluation = $evaluation[0];
        $this->_updateNextDueDate();

        $this->updateDenormalizedStatus();

        $this->save();

        $this->getAuditLog()->write('Submitted Mitigation Strategy');
    }

    /**
     * Revise the Mitigation Strategy
     * Set the status as "DRAFT" and the currentEvaluationId as null
     *
     * @param User $user The specified user to revise the mitigation strategy
     * @return void
     * @throws Fisma_Zend_Exception if the mitigation strategy is revised when the finding is not in EN status
     */
    public function reviseMitigation(User $user)
    {
        if ('EN' != $this->status) {
            throw new Fisma_Zend_Exception("The mitigation strategy can only be revised in EN status");
        }
        $this->status = 'DRAFT';
        $this->_updateNextDueDate();
        $this->CurrentEvaluation = null;
        $this->getAuditLog()->write('Revise mitigation strategy');

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
     * @throws Fisma_Zend_Exception if the findings is approved when the finding is not in MSA or EA status
     */
    public function approve(User $user, $comment)
    {
        if (is_null($this->currentEvaluationId) || !in_array($this->status, array('MSA', 'EA'))) {
            throw new Fisma_Zend_Exception("Findings can only be approved when in MSA or EA status");
        }

        $findingEvaluation = new FindingEvaluation();
        $findingEvaluation->Finding    = $this;
        $findingEvaluation->Evaluation = $this->CurrentEvaluation;
        $findingEvaluation->decision   = 'APPROVED';
        $findingEvaluation->User       = $user;
        $findingEvaluation->comment      = $comment;
        $this->FindingEvaluations[]    = $findingEvaluation;

        $logMessage = 'Evidence package approved: '
                    . $this->getStatus()
                    . (preg_match('/^\s*$/', $comment) ? '' : "\n\nComment:\n" . $comment);

        $this->getAuditLog()->write($logMessage);

        switch ($this->status) {
            case 'MSA':
                if ($this->CurrentEvaluation->nextId == null) {
                    $this->status = 'EN';
                }
                break;
            case 'EA':
                if ($this->CurrentEvaluation->nextId == null) {
                    $this->status = 'CLOSED';
                    $this->closedTs = Fisma::now();
                }
                break;
        }

        if ($this->CurrentEvaluation->nextId != null) {
            $this->CurrentEvaluation = $this->CurrentEvaluation->NextEvaluation;
        } else {
            $this->CurrentEvaluation = null;
        }
        $this->_updateNextDueDate();

        $this->updateDenormalizedStatus();

        $this->save();
    }

    /**
     * Calculate the residual risk based on threat level and taking into account the countermeasures' effectiveness
     *
     * Based on NIST 800-30 risk analysis method.
     *
     * @param string $threatLevel HIGH, MODERATE, or LOW
     * @param string $countermeasuresEffectiveness HIGH, MODERATE, LOW, or null
     * @return string HIGH, MODERATE, LOW, or null
     */
    public function calculateResidualRisk($threatLevel, $countermeasuresEffectiveness)
    {
        if (empty($threatLevel)) {
            return null;
        }

        if (empty($countermeasuresEffectiveness)) {
            return $threatLevel;
        }

        /*
         * This 2d array has threat level as the outer key and cmeasures as the inner key. The value is
         * the residual risk.
         */
        $riskMatrix = array(
            'HIGH' => array(
                'LOW' => 'HIGH',
                'MODERATE' => 'MODERATE',
                'HIGH' => 'LOW'
            ),
            'MODERATE' => array(
                'LOW' => 'MODERATE',
                'MODERATE' => 'MODERATE',
                'HIGH' => 'LOW'
            ),
            'LOW' => array(
                'LOW' => 'LOW',
                'MODERATE' => 'LOW',
                'HIGH' => 'LOW'
            ),
        );

        if (isset($riskMatrix[$threatLevel][$countermeasuresEffectiveness])) {
            return $riskMatrix[$threatLevel][$countermeasuresEffectiveness];
        } else {
            $message = "Invalid threat level ($threatLevel) or countermeasures effectiveness "
                     . " ($countermeasuresEffectiveness).";

            throw new Fisma_Zend_Exception($message);
        }
    }

    /**
     * Deny the current evaluation
     *
     * @param $user The specified user to deny the current evaluation
     * @param string $comment The deny comment of user
     * @return void
     * @throws Fisma_Zend_Exception if the findings is denined
     * when the finding is not in MSA or EA status or the deny comment missed
     */
    public function deny(User $user, $comment)
    {
        if (is_null($this->currentEvaluationId) || !in_array($this->status, array('MSA', 'EA'))) {
            throw new Fisma_Zend_Exception_User("Findings can only be denined when in MSA or EA status");
        }

        if (is_null($comment) || preg_match('/^\s*$/', $comment)) {
            throw new Fisma_Zend_Exception_User("Comments are required when denying an evaluation");
        }

        $findingEvaluation = new FindingEvaluation();
        $findingEvaluation->Finding      = $this;
        $findingEvaluation->Evaluation   = $this->CurrentEvaluation;
        $findingEvaluation->decision     = 'DENIED';
        $findingEvaluation->User         = $user;
        $findingEvaluation->comment      = $comment;
        $this->FindingEvaluations[]      = $findingEvaluation;

        $this->getAuditLog()->write('Evidence package denied: ' . $this->getStatus() . "\n\nComment:\n" . $comment);

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

        $this->updateDenormalizedStatus();

        $this->save();
    }

    /**
     * Reject the evaluation back to a specific stage
     *
     * @param User  $user         The user who commit the decision
     * @param mixed $comment      The comment to put in
     * @param mixed $targetStatus The id of the target Evaluation stage, 0 means no EN
     *
     * @return void
     */
    public function rejectTo(User $user, $comment, $targetStatus)
    {
        $currentStatus = $this->status;
        $this->deny($user, $comment);
        if ($targetStatus > 0) {
            $this->CurrentEvaluation = Doctrine::getTable('Evaluation')->find($targetStatus);
            $this->status = $currentStatus;
            $this->getAuditLog()->write('Evidence package sent to ' . $this->CurrentEvaluation->nickname);
        }
        $this->_updateNextDueDate();
        $this->updateDenormalizedStatus();

        $this->save();
    }

    /**
     * Set the status as 'EA' and the currentEvaluationId as the first Evidence Evaluation id
     *
     * @return void
     */
    public function submitEvidence()
    {
        if ('EN' != $this->status) {
            throw new Fisma_Zend_Exception("Evidence can only be updated when the finding is in EN status");
        }
        $this->status = 'EA';
        $this->ecdLocked = true;
        $evaluation = Doctrine::getTable('Evaluation')
                                        ->findByDql('approvalGroup = "evidence" AND precedence = 0 ');
        $this->CurrentEvaluation = $evaluation[0];
        $this->_updateNextDueDate();

        $this->updateDenormalizedStatus();

        $this->getAuditLog()->write('Evidence package submitted.');
        $this->save();
    }
    /**
     * Set the nextduedate when the status has changed except 'CLOSED'
     *
     * @return void
     * @throws Fisma_Zend_Exception if cannot update the next due dates since of the an invalid finding status
     * @todo why the 'Y-m-d' is a wrong date
     */
    private function _updateNextDueDate()
    {
        if (Fisma::RUN_MODE_COMMAND_LINE != Fisma::mode() && Fisma::RUN_MODE_TEST != Fisma::mode()) {
            $config = Fisma::configuration();
            $this->_overdue['NEW'] = $config->getConfig('finding_new_due');
            $this->_overdue['DRAFT'] = $config->getConfig('finding_draft_due');
        }

        if (in_array($this->status, array('PEND', 'CLOSED'))) {
            $this->_set('nextDueDate', null);
            return;
        }

        switch ($this->status) {
            case 'NEW':
            case 'DRAFT':
                // If this is an unpersisted object, then it won't have a createdTs yet
                if (isset($this->createdTs)) {
                    $createdDt = new Zend_Date($this->createdTs, Zend_Date::ISO_8601);
                    $startDate = $createdDt->toString(Fisma_Date::FORMAT_DATE);
                } else {
                    $startDate = Zend_Date::now()->toString(Fisma_Date::FORMAT_DATE);
                }
                break;
            case 'MSA':
            case 'EA':
                $startDate = Fisma::now();
                break;
            case 'EN':
                $startDate = $this->currentEcd;
                break;
            default:
                throw new Fisma_Zend_Exception('Cannot update the next due date because the finding has an'
                                        . " invalid status: '$this->status'");
        }

        $daysuntildue = $this->getDaysUntilDue();
        $nextDueDate = new Zend_Date($startDate, Fisma_Date::FORMAT_DATE);
        $nextDueDate->add($daysuntildue, Zend_Date::DAY);
        $this->_set('nextDueDate', $nextDueDate->toString(Fisma_Date::FORMAT_DATE));
    }

    /**
     * Get the number of allocated days to complete the current workflow step
     *
     * @return integer
     */
    public function getDaysUntilDue()
    {
        if (array_key_exists($this->status, $this->_overdue)) {
            // This is a New, Draft, or EN status
            if (Fisma::RUN_MODE_COMMAND_LINE != Fisma::mode() && Fisma::RUN_MODE_TEST != Fisma::mode()) {
                $config = Fisma::configuration();
                $this->_overdue['NEW'] = $config->getConfig('finding_new_due');
                $this->_overdue['DRAFT'] = $config->getConfig('finding_draft_due');
            }

            $daysuntildue = $this->_overdue[$this->status];
        } else {
            // Get the daysUntilDue value for this workflow status on the Evaluation table
            $daysuntildue = $this->CurrentEvaluation->daysUntilDue;
        }
        return $daysuntildue;
    }

    /**
     * Get the finding evaluations by approval group
     *
     * @param string $approvalGroup The specified evaluation approval group to search
     * @return array The matched finding evaluations in array
     * @throws Fisma_Zend_Exception if the specified approval group for evaluations is neither 'action' nor 'evidence'
     */
    public function getFindingEvaluations($approvalGroup)
    {
        if (!in_array($approvalGroup, array('action', 'evidence'))) {
            throw new Fisma_Zend_Exception("The approval group for evaluations must either be 'action' or 'evidence',
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
                                CurrentUser::getInstance()
                            );
                        } elseif ('MSA' == $value && 'DRAFT' == $newValue) {
                            Notification::notify(
                                'MITIGATION_REJECTED',
                                $this,
                                CurrentUser::getInstance()
                            );
                        } elseif ('EV' == $value && 'EN' == $newValue) {
                            Notification::notify(
                                'EVIDENCE_REJECTED',
                                $this,
                                CurrentUser::getInstance()
                            );
                        } elseif ('CLOSED' == $newValue) {
                            Notification::notify(
                                'FINDING_CLOSED',
                                $this,
                                CurrentUser::getInstance()
                            );
                            $this->getAuditLog()->write('Finding closed');
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
                                CurrentUser::getInstance()
                            );
                        }
                        break;
                    case 'pocId':
                        if ($this->id) {
                            Notification::notify(
                                'USER_POC',
                                $this,
                                CurrentUser::getInstance(),
                                array('userId' => $newValue, 'url' => '/finding/remediation/view/id/')
                            );
                        }
                        break;
                    default:
                        break;
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
            if ($this->isDeleted()) {
                throw new Fisma_Zend_Exception_User('The finding cannot be modified since it has been deleted.');
            }

            $table = Doctrine::getTable('Finding');
            foreach ($modified as $key => $value) {
                // Check whether the user has the privilege to update this column
                if (Fisma::mode() != Fisma::RUN_MODE_COMMAND_LINE) {
                    $fieldDefinition = $table->getDefinitionOf($key);

                    if (isset($fieldDefinition['extra'])) {
                        $requiredPrivilege = isset($fieldDefinition['extra']['requiredPrivilege']) ?
                                            $fieldDefinition['extra']['requiredPrivilege'] : null;

                        if (!is_null($requiredPrivilege)) {
                            CurrentUser::getInstance()->acl()->requirePrivilegeForObject(
                                $requiredPrivilege, $this
                            );
                        }

                        $updateStatus = isset($fieldDefinition['extra']['requiredUpdateStatus']) ?
                                        $fieldDefinition['extra']['requiredUpdateStatus'] : null;

                        if (!is_null($updateStatus) && !in_array($this->status, $updateStatus)) {
                            throw new Fisma_Zend_Exception_User(
                                'The finding cannot be modified because its status is not in '
                                . implode(", ", $updateStatus));
                        }
                    }
                }
            }
        }
    }

    /**
     * Update the residual risk when countermeasures effectiveness changes
     *
     * @param string $value
     */
    public function setCountermeasuresEffectiveness($value)
    {
        if (empty($value)) {
            throw new Fisma_Zend_Exception('Countermeasures cannot be null or blank.');
        }

        $this->_set('countermeasuresEffectiveness', $value);

        $this->residualRisk = $this->calculateResidualRisk($this->threatLevel, $this->countermeasuresEffectiveness);
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
     * Set ECD change description to null if it is set to a blank value
     *
     * @param string $value
     * @return void
     */
    public function setEcdChangeDescription($value)
    {
        if ('' == trim($value)) {
            $this->_set('ecdChangeDescription', null);
        } else {
            $this->_set('ecdChangeDescription', $value);
        }
    }

    /**
     * Throws an exception if you try to set next due date directly
     *
     * @param string $value The specified valud of next due date to set
     * @throws Fisma_Zend_Exception if the method is called
     */
    public function setNextDueDate($value)
    {
        throw new Fisma_Zend_Exception('Next due date cannot be set directly');
    }

    /**
     * Original ECD cannot be set directly
     *
     * @param string $value The specified value of original ECD to set
     * @throws Fisma_Zend_Exception if the method is called
     */
    public function setOriginalEcd($value)
    {
        throw new Fisma_Zend_Exception('Original ECD cannot be set directly');
    }

    /**
     * If the POC ID is blank or null, then unset it
     *
     * I added this because I was getting validation errors about POC ID having the wrong type (string) when I didn't
     * fill out the POC field on the form. Because AutoComplete is an unusual form element, I don't know how to add
     * a filter to mask out null/blank values.
     *
     * @param string $value The specified value of original ECD to set
     * @throws Fisma_Zend_Exception if the method is called
     */
    public function setPocId($value)
    {
        $sanitized = (int)$value;

        if (empty($sanitized)) {
            $this->_set('pocId', null);
        } else {
            $this->_set('pocId', $sanitized);
        }
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

                if (!is_null($this->actualCompletionDate)) {
                    $this->actualCompletionDate = null;
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

        $this->updateDenormalizedStatus();

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
        if ('NEW' == $this->status && in_array($value, array('CAP', 'AR', 'FP'))) {
            $this->status = 'DRAFT';
        }

        $this->_set('type', $value);
    }

    /**
     * Update the residual risk when threat level changes
     *
     * @param string $value
     */
    public function setThreatLevel($value)
    {
        $this->_set('threatLevel', $value);

        $this->residualRisk = $this->calculateResidualRisk($this->threatLevel, $this->countermeasuresEffectiveness);
    }

    /**
     * Check if current user can edit the ECD of this finding
     *
     * @return boolean True if the ECD is editable, false otherwise
     */
    public function isEcdEditable()
    {
        // The ECD is only editable in NEW or DRAFT state
        if (!$this->isDeleted() && in_array($this->status, array('NEW', 'DRAFT'))) {

            // If the ECD is unlocked, then you need the update_ecd privilege
            if (!$this->ecdLocked && CurrentUser::getInstance()->acl()->hasPrivilegeForObject('update_ecd', $this)) {

                return true;
            }

            // If the ECD is locked, then you need the update_locked_ecd privilege
            if (
                $this->ecdLocked &&
                CurrentUser::getInstance()->acl()->hasPrivilegeForObject('update_locked_ecd', $this)
            ) {

                return true;
            }
        }

        // If none of the above conditions match, then the default is false
        return false;
    }

    /**
     * Implement the required method for Fisma_Zend_Acl_OrganizationDependency
     *
     * @return int
     */
    public function getOrganizationDependencyId()
    {
        return $this->responsibleOrganizationId;
    }

    /**
     * Validation before insert.
     *
     * Overrides method from Doctrine_Record
     */
    protected function validateOnInsert()
    {
        $org = $this->Organization;

        if ($org->OrganizationType->nickname == 'system' && $org->System->sdlcPhase == 'disposal') {
            $message = 'Cannot create a finding for a System in the Disposal phase.';

            $this->getErrorStack()->add('Organization', $message);
        }
    }

    /**
     * Update the denormalized status field, which is a string field that contains the logical value for the status
     * as derived from the actual status field and the currentEvaluationId
     */
    public function updateDenormalizedStatus()
    {
        if ($this->status == 'MSA' || $this->status == 'EA') {
            $this->denormalizedStatus = $this->CurrentEvaluation->nickname;
        } else {
            $this->denormalizedStatus = $this->status;
        }
    }

    /**
     * Return a user-friendly status
     *
     * @param String $status The acronym status, if called by static
     * @return String
     */
    public function getLongStatus($status = null)
    {
        $activeEvaluation = (empty($status)) ? $this->CurrentEvaluation
                                             : Doctrine::getTable('Evaluation')->findOneByNickname($status);
        $status = (empty($status)) ? $this->denormalizedStatus : $status;

        switch ($status) {
            case 'NEW':
                return "{$status}: Awaiting Mitigation Strategy";
            case 'DRAFT':
                return "{$status}: Awaiting Mitigation Strategy Submission";
            case 'EN':
                return "{$status}: Awaiting Evidence Package Submission";
            case 'CLOSED':
                return "{$status}: Finding Officially Closed";
            default:
                return "{$status}: Awaiting {$activeEvaluation->name}";
        }
    }

    /**
     * Doctrine hook
     *
     * @param Doctrine_Event $event The listened doctrine event to be processed
     * @return void
     */
    public function postInsert($event)
    {
        if ($newValue = $this->pocId) {
            Notification::notify(
                'USER_POC',
                $this,
                CurrentUser::getInstance(),
                array('userId' => $newValue, 'url' => '/finding/remediation/view/id/')
            );
        }
    }

    /**
     * Mutator for discoveredDate to automatically set default Audit Year
     *
     * @param Date $value
     * @return void
     */
    public function setDiscoveredDate($value)
    {
        $this->_set('discoveredDate', $value);
        if (!$this->auditYear) {
            $discoveredDate = new Zend_Date($this->discoveredDate, Fisma_Date::FORMAT_DATE);
            $this->_set('auditYear', $discoveredDate->toString(Zend_Date::YEAR));
        }
    }
}
