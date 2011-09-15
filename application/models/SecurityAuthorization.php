<?php
/**
 * Copyright (c) 2010 Endeavor Systems, Inc.
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
 * SecurityAuthorization
 * 
 * @author     Andrew Reeves <andrew.reeves@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2010 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Model
 */
class SecurityAuthorization extends BaseSecurityAuthorization
{
    /**
     * Update the SA to the next step
     *
     * @param string $step Step to complete.  Must be the models current status.
     * @return boolean
     */
    public function completeStep($step)
    {
        if ($this->status != $step) {
            return false;
        }

        switch ($this->status) {
            case 'Select':
                $this->initImplementation();
                $this->status = 'Implement';
                $this->implementStartTs = Zend_Date::now()->toString(Fisma_Date::FORMAT_DATETIME);
                break;
            case 'Implement':
                $this->initAssessmentPlan();
                $this->status = 'Assessment Plan';
                break;
            case 'Assessment Plan':
                $this->status = 'Assessment';
                break;
            case 'Assessment':
                $this->status = 'Authorization';
                break;
            case 'Authorization':
                $this->status = 'Active';
                break;
            case 'Active':
                $this->status = 'Retired';
                break;
            default:
                throw new Exception('Unable to complete step: ' . $this->status);
        }

        return true;
    }

    /**
     * @return void
     */
    public function initAssessmentPlan()
    {
        // populate assessment plan entries for security controls
        $apeColl = new Doctrine_Collection('AssessmentPlanEntry');

        foreach ($this->SaSecurityControls as $sasc) {
            $cProcedures = Doctrine_Query::create()
                ->from('AssessmentProcedure ap')
                ->where('ap.controlCode = ?', $sasc->SecurityControl->code)
                ->andWhere('ap.enhancement IS NULL')
                ->execute();
            foreach ($cProcedures as $ap) {
                $apeColl->add($this->_createAssessmentPlanEntry($ap, $sasc));
            }
            foreach ($sasc->SaSecurityControlEnhancements as $sasce) {
                $eProcedures = Doctrine_Query::create()
                    ->from('AssessmentProcedure ap')
                    ->where('ap.controlCode = ?', $sasc->SecurityControl->code)
                    ->andWhere('ap.enhancement = ?', $sasce->SecurityControlEnhancement->number)
                    ->execute();
                foreach ($eProcedures as $ap) {
                    $apeColl->add($this->_createAssessmentPlanEntry($ap, $sasce));
                }
            }
        }

        $apeColl->save();
    }

    protected function _createAssessmentPlanEntry(AssessmentProcedure $ap, SaSecurityControlAggregate $sasca)
    {
        $ape = new AssessmentPlanEntry();
        $ape->SaSecurityControlAggregate = $sasca;
        $ape->number = $ap->number;
        $ape->objective = $ap->objective;
        $ape->examine = $ap->examine;
        $ape->interview = $ap->interview;
        $ape->test = $ap->test;
        return $ape;
    }

    protected function initImplementation()
    {
        // populate implementations for security controls
        $coll = new Doctrine_Collection('SaSecurityControlAggregate');

        foreach ($this->SaSecurityControls as $sasc) {
            $sasc->Implementation = new SaImplementation();
            $coll->add($sasc);
            foreach ($sasc->SaSecurityControlEnhancements as $sasce) {
                $sasce->Implementation = new SaImplementation();
                $coll->add($sasce);
            }
        }

        $coll->save();
    }

    /**
     * Return -1 if $status is before this SA's status, return 1 if $status is after this SA's status, and return
     * 0 if $status is this SA's status.
     * 
     * @param string $status
     * @return int
     */
    public function compareStatus($status)
    {
        $statuses = $this->getTable()->getEnumValues('status');
        
        $compareIndex = array_search($status, $statuses);
        $thisIndex = array_search($this->status, $statuses);

        if ($compareIndex === FALSE) {
            throw new Fisma_Zend_Exception("Invalid status used for comparison: " . $status);
        }

        if ($compareIndex < $thisIndex) {
            return -1;
        } elseif ($compareIndex > $thisIndex) {
            return 1;
        } else {
            return 0;
        }
    }
    
    /**
     * Return the number of controls attached to this SA.
     * 
     * @return int
     */
    public function getControlsCount()
    {
        $count = Doctrine_Query::create()
                 ->select('COUNT(*)')
                 ->from('SecurityAuthorization sa')
                 ->innerJoin('sa.SecurityControls sc')
                 ->where('sa.id = ?', $this->id)
                 ->setHydrationMode(Doctrine::HYDRATE_SINGLE_SCALAR)
                 ->execute();

        return (int)$count;
    }
    
    /**
     * Return the number of controls attached to this SA that have an implementation statement.
     */
    public function getImplementedControlsCount()
    {
        $count = Doctrine_Query::create()
                 ->select('COUNT(*)')
                 ->from('SecurityAuthorization sa')
                 ->innerJoin('sa.SaSecurityControls sc')
                 ->innerJoin('sc.Implementation i')
                 ->where('sa.id = ?', $this->id)
                 ->andWhere('i.status = ?', 'Complete')
                 ->setHydrationMode(Doctrine::HYDRATE_SINGLE_SCALAR)
                 ->execute();

        return (int)$count;
    }
    
    /**
     * Return the number of controls with completed assessments
     */
    public function getAssessedControlsCount()
    {
        $count = Doctrine_Query::create()
                 ->select('COUNT(*)')
                 ->from('SecurityAuthorization sa')
                 ->innerJoin('sa.SaSecurityControls sc')
                 ->innerJoin('sc.AssessmentProcedures ap')
                 ->where('sa.id = ?', $this->id)
                 ->andWhere('ap.status = ?', 'Complete')
                 ->groupBy('sc.id')
                 ->setHydrationMode(Doctrine::HYDRATE_SINGLE_SCALAR)
                 ->execute();

        return (int)$count;
    }
}
