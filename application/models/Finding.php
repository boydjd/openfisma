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
 * @package    Model
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/mw/index.php?title=License
 */
class Finding extends BaseFinding
{
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
            throw new Fisma_Exception_General("The finding can't be approved");
        }
        $findingEvaluation = new FindingEvaluation();

        if ($this->CurrentEvaluation->approvalGroup == 'evidence') {
            $findingEvaluation->Evidence   = $this->Evidence->getLast();
        }
        $findingEvaluation->Finding    = $this;
        $findingEvaluation->Evaluation = $this->CurrentEvaluation;
        $findingEvaluation->decision   = 'APPROVED';
        $findingEvaluation->User       = $user;
        $findingEvaluation->save();

        switch ($this->status) {
            case 'MSA':
                $mitigationApprovedCount = $this->CurrentEvaluation->getTable()
                                            ->findByDql('approvalGroup = "action"')
                                            ->count();
                $ret = Doctrine_Query::create()->select('e.precedence')
                                               ->from('Evaluation e')
                                               ->where('e.approvalGroup = "action"')
                                               ->orderBy('e.precedence DESC')
                                               ->limit(1)
                                               ->execute();
                $lastPrecedence = $ret[0]->precedence;
                if ($this->CurrentEvaluation->precedence == $lastPrecedence) {
                    $this->status = 'EN';
                }
                break;
            case 'EA':
                $this->CurrentEvaluation = $this->CurrentEvaluation->NextEvaluation;
                if (is_null($this->CurrentEvaluation->id)) {
                    $this->status = 'CLOSED';
                }
                break;
        }
        $this->save();
    }

    /**
     * Deny the current evaluation
     *
     * @param Object $user a specific user
     * @param string $comment deny comment
     */
    public function deny(User $user, $comment)
    {
        if (is_null($this->currentEvaluationId) || !in_array($this->status, array('MSA', 'EA'))) {
            //@todo english
            throw new Fisma_Exception_General("The finding can't be denied");
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
        $findingEvaluation->save();

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
        $this->save();
    }

}
