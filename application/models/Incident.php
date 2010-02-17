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
 * @author    Mark E. Haase <mhaase@endeavorsystems.com>
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/mw/index.php?title=License
 * @version   $Id$
 */

/**
 * Represents the report of an information security incident
 * 
 * @package Model
 */
class Incident extends BaseIncident
{
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
    }

    /**
     * Reject this incident
     * 
     * @param string $comment A comment to add with this rejection
     */
    public function reject($comment)
    {
        if ('new' != $this->status) {
            throw new Fisma_Exception('Cannot reject an incident unless it is in "new" status');
        }
        
        $this->status = 'rejected';
    
        /* Add rejected step to workflow table*/
        $iw = new IrIncidentWorkflow();    
        $iw->Incident    = $this; 
        $iw->name        = 'Incident Rejected';
        $iw->comments    = $comment;
        $iw->cardinality = 0;
        $iw->User        = User::currentUser();
        $iw->completeTs  = date('Y-m-d H:i:s');
        $iw->status      = 'completed';
        $iw->save();

        /* Add final close step to incident workflow table*/
        $iw = new IrIncidentWorkflow();    
        $iw->Incident     = $this; 
        $iw->name         = 'Close Incident';
        $iw->cardinality  = 1;
        $iw->status      = 'queued';
        $iw->save();
    }
    
    /**
     * Close an incident
     * 
     * @todo redesign the incident model so that stepId doesn't need to be passed as a parameter
     * 
     * @param string $comment
     * @param int $stepId The closure workflow step (this is a bad design)
     */
    public function close($comment, $stepId)
    {
        if ( !('resolved' == $this->status || 'rejected' == $this->status) ) {
            throw new Fisma_Exception('Cannot reject an incident unless it is in "new" status');
        }
        $this->status = 'closed';

        $step = Doctrine::getTable('IrIncidentWorkflow')->find($stepId);
        $step->status     = 'completed';
        $step->comments   = $comment;
        $step->userId     = Zend_Auth::getInstance()->getIdentity()->id;
        $step->completeTs = date('Y-m-d H:i:s');
        $step->save();
    }

    /**
     * Pre-insert hook
     * 
     * @param Doctrine_Event $event
     */
    public function preInsert($event)
    {
        $this->sourceIp = $_SERVER['REMOTE_ADDR'];

        $this->reportTs = date('Y-m-d H:i:s');
        $this->reportTz = date('T');
        
        $this->status = 'new';
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