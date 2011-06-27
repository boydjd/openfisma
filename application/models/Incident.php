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
        
        $this->hasMutator('hostIp', 'setHostIp');
        $this->hasMutator('reporterEmail', 'setReporterEmail');
        $this->hasMutator('ReportingUser', 'setReportingUser');
        $this->hasMutator('sourceIp', 'setSourceIp');
    }

    /**
     * Reject this incident
     * 
     * @param string $comment A comment to add with this rejection
     */
    public function reject($comment)
    {
        $conn = Doctrine_Manager::connection();
        $conn->beginTransaction();
        
        if ('new' != $this->status) {
            throw new Fisma_Zend_Exception('Cannot reject an incident unless it is in "new" status');
        }

        // Validate that comment is not empty
        if ('' == trim($comment)) {
            throw new Fisma_Zend_Exception_User('You must provide a comment');
        }

        // Create a workflow step for rejecting then mark it as closed
        $rejectStep = new IrIncidentWorkflow();

        $rejectStep->Incident = $this;
        $rejectStep->name = 'Reject Incident';
        $rejectStep->cardinality = 0;

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
     * Open an incident
     * 
     * This moves the incident from 'new' status to 'open' and also assigns a category and a workflow
     * 
     * @param IrSubCategory $category
     */
    public function open(IrSubCategory $category)
    {
        $conn = Doctrine_Manager::connection();
        $conn->beginTransaction();

        $this->status = 'open';
        $this->Category = $category;
        
        /*
         * Insert an initial workflow step which reflects the opening of the incident
         */
        $openStep = new IrIncidentWorkflow();
        $openStep->Incident = $this;
        $openStep->name = 'Open Incident';
        $openStep->cardinality = 0;
        $openStep->save();
        
        $this->CurrentWorkflowStep = $openStep;
        
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
        
        foreach ($workflowSteps as $step) {
            $iw = new IrIncidentWorkflow();    
           
            $iw->Incident = $this;
            $iw->Role = $step->Role;
            $iw->name = $step->name;
            $iw->description = $step->description;
            $iw->cardinality = $step->cardinality;

            $iw->save();
        }

        $conn->commit();
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
                    . ($completedStep->cardinality + 1)
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
}
