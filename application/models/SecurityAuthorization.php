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
}
