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
 * Represents the report of an information security incident
 *
 * @author     Mark E. Haase <mhaase@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Model
 */
class Incident extends BaseIncident
{
    /**
     * Override constructor to set initial values
     */
    public function construct()
    {
        // Only operate on new objects (i.e. transient), not persistent objects which are being rehydrated
        $state = $this->state();
        if ($state == Doctrine_Record::STATE_TCLEAN || $state == Doctrine_Record::STATE_TDIRTY) {

            // REMOTE_ADDR may not be set (e.g. command line mode)
            if (isset($_SERVER['REMOTE_ADDR'])) {
                $this->reporterIp = $_SERVER['REMOTE_ADDR'];
            }

            $this->status = 'new';

            $this->reportTs = Fisma::now();
            $this->reportTz = Zend_Date::now()->toString(Zend_Date::TIMEZONE);
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

        $this->hasMutator('categoryId', 'setCategoryId');
        $this->hasMutator('hostIp', 'setHostIp');
        $this->hasMutator('organizationId', 'setOrganizationId');
        $this->hasMutator('pocId', 'setPocId');
        $this->hasMutator('reporterEmail', 'setReporterEmail');
        $this->hasMutator('ReportingUser', 'setReportingUser');
        $this->hasMutator('sourceIp', 'setSourceIp');
    }

    /**
     * Reject this incident
     *
     * @param string $comment A comment to add with this rejection
     */
    public function reject($comment = null)
    {
        $conn = Doctrine_Manager::connection();
        $conn->beginTransaction();

        if ('new' != $this->status) {
            throw new Fisma_Zend_Exception('Cannot reject an incident unless it is in "new" status');
        }

        // Create a workflow step for rejecting then mark it as closed
        $rejectStep = new IrIncidentWorkflow();

        $rejectStep->Incident = $this;
        $rejectStep->name = 'Reject Incident';
        $rejectStep->cardinality = 1;

        $rejectStep->completeStep($comment);
        $rejectStep->save();

        $this->status = 'closed';
        $this->closedTs = Zend_Date::now()->get(Zend_Date::ISO_8601);
        $this->resolution = 'rejected';

        // Update incident log
        $this->getAuditLog()->write('Rejected Incident Report');

        $conn->commit();
    }

    /**
     * Sets the category (and corresponding workflow) for this incident.
     *
     * If it doesn't already have a workflow, then the workflow steps are copied from the workflow definition into this
     * incident's workflow.
     *
     * If a workflow *does* exist, then all completed steps are kept as-is, but the remaining
     * steps are removed. Then a new step is inserted showing the change in workflow (and marked as completed by
     * the current user) then the steps for the new workflow are appended to the end of the list.
     *
     * @param int $categoryId An IrSubCategory primary key
     */
    public function setCategoryId($categoryId)
    {
        if ($categoryId === '0') {
            // This is the "I don't know" category in the report wizard
            return;
        }

        $this->_set('categoryId', $categoryId);
        $category = Doctrine::getTable('IrSubCategory')->find($categoryId);

        // Setting a category makes the incident 'open'
        if ('new' == $this->status) {
            $this->status = 'open';
        }

        // Handle any pre-existing workflows (e.g. when changing from one category to another)
        $baseCardinality = 0;

        if ($this->CurrentWorkflowStep) {
            if ($this->CurrentWorkflowStep->cardinality > 1) {
                // The current workflow is already in progress so change the step to show that the workflow has changed
                $changedWorkflowMessage = "<p>The category has been changed to \"{$category->name}\" and the workflow"
                                        . " has been modified accordingly.</p>";

                $this->CurrentWorkflowStep->name = "Change Workflows";
                $this->CurrentWorkflowStep->description = $changedWorkflowMessage;
                $this->CurrentWorkflowStep->completeStep();
                $this->CurrentWorkflowStep->save();

                $baseCardinality = $this->CurrentWorkflowStep->cardinality;
            }

            // Delete all remaining workflow items
            $this->CurrentWorkflowStep = null;
            $this->save();

            Doctrine_Query::create()->delete()
                                    ->from('IrIncidentWorkflow w')
                                    ->where('w.incidentId = ?', $this->id)
                                    ->andWhere('w.cardinality > ?', $baseCardinality)
                                    ->execute();
        }

        /*
         * Create a copy of the workflow and assign it to this incident. This is like a SQL
         * 'INSERT INTO <table> SELECT...' statement, except Doctrine doesn't support that kind of query.
         */
        $workflowQuery = Doctrine_Query::create()
                         ->select('s.id, s.roleId, s.cardinality, s.name, s.description')
                         ->from('IrStep s')
                         ->where('s.workflowid = ?', $category->workflowId)
                         ->orderby('s.cardinality');

        $workflowSteps = $workflowQuery->execute();

        $firstLoop = true;

        foreach ($workflowSteps as $step) {
            $iw = new IrIncidentWorkflow();

            $iw->Incident = $this;
            $iw->Role = $step->Role;
            $iw->name = $step->name;
            $iw->description = $step->description;
            $iw->cardinality = $step->cardinality + $baseCardinality;

            if ($firstLoop) {
                $firstLoop = false;

                $iw->status = 'current';
                $this->CurrentWorkflowStep = $iw;
            }

            $iw->save();
        }

        $this->getAuditLog()->write('Changed Category: ' .  $category->name);
    }

    /**
     * Set the organization ID.
     *
     * @param int $organizationId
     */
    public function setOrganizationId($organizationId)
    {
        if ($organizationId === '0') {
            // This is the "I don't know" category in the report wizard
            $this->_set('organizationId', null);
        } else {
            $this->_set('organizationId', $organizationId);
        }
    }

    /**
     * Complete the current workflow step for this incident and advance to the next step
     *
     * @param string $comment The user's comment associated with completing this workflow step
     */
    public function completeStep($comment)
    {
        // Validate that comment is not empty
        if ('' == trim($comment)) {
            throw new Fisma_Zend_Exception_User('You must provide a comment');
        }

        // Update the completed step first
        $completedStep = $this->CurrentWorkflowStep;
        $completedStep->completeStep($comment);
        $this->save();

        // Log the completed step
        $logMessage = 'Completed workflow step #'
                    . $completedStep->cardinality
                    . ': '
                    . $completedStep->name;
        $this->getAuditLog()->write($logMessage);

        /*
         * The next step can be identified by its cardinality, which is always one more than the cardinality of the
         * current step. If no such step exists, then the current step is the last step.
         */
        $nextStepQuery = Doctrine_Query::create()
                         ->from('IrIncidentWorkflow iw')
                         ->where('iw.incidentId = ?', $this->id)
                         ->andWhere('iw.cardinality = ?', $completedStep->cardinality + 1);

        $nextStepResult = $nextStepQuery->execute();

        if (0 == count($nextStepResult)) {
            // There is no next step, so close this incident
            $this->CurrentWorkflowStep = null;
            $this->status = 'closed';
            $this->closedTs = Zend_Date::now()->get(Zend_Date::ISO_8601);
            $this->resolution = 'resolved';
            $this->save();

            // Log the closure of the incident
            $this->getAuditLog()->write('Incident Resolved and Closed');
        } elseif (1 == count($nextStepResult)) {
            // The next step will change status to 'current'
            $nextStep = $nextStepResult[0];
            $nextStep->status = 'current';
            $nextStep->save();

            // Update this record's workflow pointer
            $this->CurrentWorkflowStep = $nextStep;
            $this->save();
        } else {
            $message = "The workflow for this incident ($this->id) appears to be corrupted. There are two steps"
                     . " with the same id.";
            throw new Fisma_Zend_Exception($message);
        }
    }

    /**
     * Mutator for hostIp to convert blank values to null for validation purposes
     *
     * @param string $value
     */
    public function setHostIp($value)
    {
        if (empty($value)) {
            $this->_set('hostIp', null);
        } else {
            $this->_set('hostIp', $value);
        }
    }

    /**
     * Mutator for sourceIp to convert blank values to null for validation purposes
     *
     * @param string $value
     */
    public function setSourceIp($value)
    {
        if (empty($value)) {
            $this->_set('sourceIp', null);
        } else {
            $this->_set('sourceIp', $value);
        }
    }

    /**
     * Mutator for reporterEmail to convert blank values to null for validation purposes
     *
     * @param string $value
     */
    public function setReporterEmail($value='')
    {
        if (empty($value)) {
            $this->_set('reporterEmail', null);
        } else {
            $this->_set('reporterEmail', $value);
        }
    }

    /**
     * When setting a user as the incident reporter, then unset all of the reporter fields
     *
     * @param User $user
     */
    public function setReportingUser($user)
    {
        // Since we're overridding the setter, we have to manipulate the ids directly
        $this->reportingUserId = $user->id;

        $this->reporterTitle = null;
        $this->reporterFirstName = null;
        $this->reporterLastName = null;
        $this->reporterOrganization = null;
        $this->reporterAddress1 = null;
        $this->reporterAddress2 = null;
        $this->reporterCity = null;
        $this->reporterState = null;
        $this->reporterZip = null;
        $this->reporterPhone = null;
        $this->reporterFax = null;
        $this->reporterEmail = null;
    }

    /**
     * Make sure POCs are added as actors on the incident.
     *
     * @param string $value
     */
    public function setPocId($value)
    {
        // Clear out null values
        $sanitized = (int)$value;

        if (empty($sanitized)) {
            $this->_set('pocId', null);
        } else {
            $this->_set('pocId', $sanitized);

            // Make sure the POC is an actor
            $poc = Doctrine::getTable('Poc')->find($value);

            if ($poc instanceof User) {
                $pocIsActorQuery = Doctrine_Query::create()->from('IrIncidentUser iu')
                                                           ->leftJoin('iu.Incident i')
                                                           ->leftJoin('iu.User u')
                                                           ->where('i.id = ?', $this->id)
                                                           ->andWhere('iu.accessType = ?', 'ACTOR')
                                                           ->andWhere('u.id = ?', $poc->id);
                if ($pocIsActorQuery->count() === 0) {
                    $actor = new IrIncidentUser;

                    $actor->accessType = 'ACTOR';
                    $actor->userId = $poc->id;
                    $actor->incidentId = $this->id;

                    $actor->save();
                }
            }
        }
    }
}
