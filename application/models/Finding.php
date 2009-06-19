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
        if ('DRAFT' != $this->status) {
            //@todo english
            throw new Fisma_Exception("The finding can't be submited mitigation strategy");
        }
        $this->status = 'MSA';
        $this->_updateNextDueDate();
        $evaluation = Doctrine::getTable('Evaluation')
                                        ->findByDql('approvalGroup = "action" AND precedence = 0');
        $this->CurrentEvaluation = $evaluation[0];
        $this->save();
    }

    /**
     * Revise the Mitigation Strategy
     * Set the status as "DRAFT" and the currentEvaluationId as null
     */
    public function reviseMitigation(User $user)
    {
        if ('EN' != $this->status) {
            //@todo english
            throw new Fisma_Exception("The finding can't be revised mitigation strategy");
        }
        $this->status = 'DRAFT';
        $this->_updateNextDueDate();
        $this->CurrentEvaluation = null;
        $this->save();
    }

    /**
     * Approve the current evaluation,
     * then update the status to either point to
     * a new Evaluation or else to change the status to DRAFT, EN,
     * or CLOSED as appropriate
     * 
     * @param Object $user a specific user object
     */
    public function approve(User $user)
    {
        if (is_null($this->currentEvaluationId) || !in_array($this->status, array('MSA', 'EA'))) {
            //@todo english
            throw new Fisma_Exception("The finding can't be approved");
        }
        
        //Start Doctrine Transaction
        $conn = Doctrine_Manager::connection();
        $conn->beginTransaction();

        $findingEvaluation = new FindingEvaluation();
        if ($this->CurrentEvaluation->approvalGroup == 'evidence') {
            $findingEvaluation->Evidence   = $this->Evidence->getLast();
        }
        $findingEvaluation->Finding    = $this;
        $findingEvaluation->Evaluation = $this->CurrentEvaluation;
        $findingEvaluation->decision   = 'APPROVED';
        $findingEvaluation->User       = $user;
        $this->FindingEvaluations[]    = $findingEvaluation;

        $auditLog = new AuditLog();
        $auditLog->User      = $this->CreatedBy;
        $auditLog->description = 'Update: ' . $this->status . ' Original: "NONE" New: "APPROVED"';
        $this->AuditLogs[] = $auditLog;

        $nextEvaluation = $this->CurrentEvaluation->NextEvaluation->toArray();
        switch ($this->status) {
            case 'MSA':
                //@todo is there any way to judge the NextEvaluation is empty unless use toArray()
                if (empty($nextEvaluation['id'])) {
                    $this->status = 'EN';
                }
                break;
            case 'EA':
                if (empty($nextEvaluation['id'])) {
                    $this->status   = 'CLOSED';
                    $this->closedTs = date('Y-m-d');
                }
                break;
        }
        $this->CurrentEvaluation = $this->CurrentEvaluation->NextEvaluation;
        $this->_updateNextDueDate();
        $this->save();
        $conn->commit();
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
            //@todo english
            throw new Fisma_Exception("The finding can't be denied");
        }

        //Start Doctrine Transaction
        $conn = Doctrine_Manager::connection();
        $conn->beginTransaction();

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

        $auditLog = new AuditLog();
        $auditLog->User      = $this->CreatedBy;
        $auditLog->description = 'Update: ' . $this->status . ' Original: "NONE" New: "DENIED"';
        $this->AuditLogs[] = $auditLog;

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
        $conn->commit();
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
            //@todo english
            throw new Fisma_Exception("The finding can't be uploaded evidence");
        }
        $this->status = 'EA';
        $this->_updateNextDueDate();
        $evaluation = Doctrine::getTable('Evaluation')
                                        ->findByDql('approvalGroup = "evidence" AND precedence = 0 ');
        $this->CurrentEvaluation = $evaluation[0];
        $evidence = new Evidence();
        $evidence->filename = $fileName;
        $evidence->Finding  = $this;
        $evidence->User     = $user;
        $this->Evidence[]  = $evidence;
        $this->save();
    }

    /**
     * Set the nextduedate when the status has changed except 'CLOSED'
     * @todo why the 'Y-m-d' is a wrong date
     */
    private function _updateNextDueDate()
    {
        if (in_array($this->status, array('PEND', 'CLOSED'))) {
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
                $startDate = date('Y-M-d');
            case 'EN':
                $startDate = $this->expectedCompletionDate;
                break;
            case 'EA':
                $startDate = date('Y-M-d');
                break;
        }
        $nextDueDate = new Zend_Date($startDate, 'Y-M-d');
        $nextDueDate->add($this->_overdue[$this->status], Zend_Date::DAY);
        $this->nextDueDate = $nextDueDate->toString('Y-M-d');
    }
}
