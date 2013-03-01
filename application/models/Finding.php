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
     * Override the Doctrine hook to initialize new finding objects
     *
     * @return void
     */
    public function construct()
    {
        // Set default status for new objects (i.e. objects with transient state)
        $state = $this->state();
        if ($state == Doctrine_Record::STATE_TCLEAN || $state == Doctrine_Record::STATE_TDIRTY) {
            $this->countermeasuresEffectiveness = 'LOW';
            $this->countermeasures = 'N/A';
            try {
                $workflow = Doctrine::getTable('Workflow')->findDefaultByModule('finding');
                $this->CurrentStep = $workflow->getFirstStep();
                switch ($this->CurrentStep->allottedTime) {
                    case 'days':
                        $this->nextDueDate = Zend_Date::now()
                            ->addDay($this->CurrentStep->allottedDays)
                            ->toString(Fisma_Date::FORMAT_DATE);
                        break;
                    case 'custom':
                    case 'unlimited':
                    case 'ecd':
                    default:
                        $this->nextDueDate = null;
                }
            } catch (Exception $e) {
            }
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
        $this->hasMutator('discoveredDate', 'setDiscoveredDate');
        $this->hasMutator('originalEcd', 'setOriginalEcd');
        $this->hasMutator('pocId', 'setPocId');
        $this->hasMutator('threatLevel', 'setThreatLevel');
        $this->hasMutator('responsibleOrganizationId', 'setResponsibleOrganizationId');
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
                if (!$this->canEdit($key)) {
                    throw new Fisma_Zend_Exception_User($key . ' cannot be editted in the current workflow step.');
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
        if (!$this->isDeleted()) {

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

    /**
     * setResponsibleOrganizationId
     *
     * @param mixed $value
     * @param mixed $load
     * @return void
     */
    public function setResponsibleOrganizationId($value, $load = true)
    {
        $this->_set('responsibleOrganizationId', $value);

        // if $load is false, early out to avoid creating worthless objects
        if (!$load) {
            return;
        }

        // now deal with the parent organization
        $parentOrganizationId = null;
        if (!empty($value)) {
            $this->refreshRelated('Organization');
            $parent = $this->Organization->getNode()->getParent();
            while (!empty($parent) && !empty($parent->systemId)) {
                $parent = $parent->getNode()->getParent();
            }
            if (!empty($parent)) {
                $parentOrganizationId = $parent->id;
            }
        }
        $this->_set('denormalizedParentOrganizationId', $parentOrganizationId);
    }

    public function canEdit($field)
    {
        if ($this->CurrentStep && !empty($this->CurrentStep->restrictedFields)) {
            if (in_array($field, $this->CurrentStep->restrictedFields)) {
                return false;
            }
        }
        return true;
    }
}
