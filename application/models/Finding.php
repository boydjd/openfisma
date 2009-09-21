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
     */
    public $relationIndex = array(
        'Source' => array('nickname' => array('type' => 'keyword', 'alias' => 'source')),
        'ResponsibleOrganization' => array('nickname' => array('type' => 'unstored', 'alias' => 'system')),
        'Asset' => array('name' => array('type' => 'unstored', 'alias' => 'asset')),
        'SecurityControl' => array('code' => array('type' => 'keyword', 'alias' => 'securitycontrol'))
    );

    //Threshold of overdue for various status
    private $_overdue = array('NEW' => 30, 'DRAFT'=>30, 'MSA'=>7, 'EN'=>0, 'EA'=>7);

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
        $this->updateNextDueDate();
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
        $this->updateNextDueDate();
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
        $this->updateNextDueDate();
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
        $this->updateNextDueDate();
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
        $this->updateNextDueDate();
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
     * @todo why the 'Y-m-d' is a wrong date
     */
    public function updateNextDueDate()
    {
        if (in_array($this->status, array('PEND', 'CLOSED'))) {
            $this->nextDueDate = null;
            return;
        }
        switch ($this->status) {
            case 'NEW':
                $startDate = $this->createdTs;
                break;
            case 'DRAFT':
                $startDate = $this->createdTs;
                break;
            case 'MSA':
                $startDate = Fisma::now();
                break;
            case 'EN':
                $startDate = $this->currentEcd;
                break;
            case 'EA':
                $startDate = Fisma::now();
                break;
        }
        $nextDueDate = new Zend_Date($startDate, 'Y-m-d');
        $nextDueDate->add($this->_overdue[$this->status], Zend_Date::DAY);
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

}
