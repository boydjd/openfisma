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
 * System
 * 
 * @author     Ryan Yang <ryan@users.sourceforge.net>
 * @copyright  (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Model
 * @version    $Id$
 */
class System extends BaseSystem implements Fisma_Zend_Acl_OrganizationDependency
{
    /**
     * Confidentiality, Integrity, Availability
     */
    const CIA_HIGH = 'HIGH';
    
    /**
     * Confidentiality, Integrity, Availability
     */
    const CIA_MODERATE = 'MODERATE';
    
    /**
     * Confidentiality, Integrity, Availability
     */
    const CIA_LOW = 'LOW';
    
    /**
     * Only confidentiality can have 'NA'
     */
    const CIA_NA = 'NA';

    /**
     * The number of months for which an Authority To Operate (ATO) is valid and current. 
     * 
     * After this period elapses, the ATO is considered expired.
     */
    const ATO_PERIOD_MONTHS = 36;

    /**
     * The number of months for which a Self-Assessment is valid and current. 
     * 
     * After this period elapses, the Self-Assessment is considered expired.
     */
    const SELF_ASSESSMENT_PERIOD_MONTHS = 12;

    /**
     * The number months for which a contingency plan test is valid and current.
     * 
     * After this period elapses, the Self-Assessment is considered expired.
     */    
    const SELF_CPLAN_PERIOD_MONTHS = 12;

    /**
     * Defines the way counter measure effectiveness and threat level combine to produce the threat likelihood. This
     * array is indexed as: $_threatLikelihoodMatrix[THREAT_LEVEL][COUNTERMEASURE_EFFECTIVENESS] == THREAT_LIKELIHOOD
     * 
     * @var array
     * @see _initThreatLikelihoodMatrix()
     */
    private $_threatLikelihoodMatrix;

    /**
     * A mapping from the physical system types to proper English terms
     * 
     * @var array
     */
    private $_typeMap = array(
        'gss' => 'General Support System',
        'major' => 'Major Application',
        'minor' => 'Minor Application'
    );

    /**
     * Mapping from SDLC Phase physical IDs to English
     *
     * @var array
     */
     private static $_sdlcPhaseMap = array(
                'initiation' => 'Initiation',
                'development' => 'Development/Acquisition',
                'implementation' => 'Implementation/Assessment',
                'operations' => 'Operations/Maintenance',
                'disposal' => 'Disposal'
     );

    /**
     * Doctrine hook which is used to set up mutators
     * 
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        
        $this->hasMutator('availability', 'setAvailability');
        $this->hasMutator('confidentiality', 'setConfidentiality');
        $this->hasMutator('fipsCategory', 'setFipsCategory');
        $this->hasMutator('integrity', 'setIntegrity');
        $this->hasMutator('uniqueProjectId', 'setUniqueProjectId');
    }
    
    /**
     * Return the English version of the orgType field
     * 
     * @return string The English version of the orgType field
     */
    public function getTypeLabel() 
    {
        return $this->_typeMap[$this->type];
    }

    /**
     * Return English name for the SDLC Phase
     *
     * @return string English SDLC Phase label
     */
     public function getSdlcPhaseLabel()
     {
         return self::$_sdlcPhaseMap[$this->sdlcPhase];
     }
    
    /**
     * Get a mapping of SDL phases to their respective English names
     *
     * @return array Mapping from name to English
     */
    public static function getSdlcPhaseMap()
    {
        return self::$_sdlcPhaseMap;
    }

    /**
     * Calculate FIPS-199 Security categorization.
     *
     * This is based on the NIST definition, which is the "high water mark" for the components C, I, and A. If some
     * parts of the CIA are null but at least one part is defined, then the FIPS category will take the high water mark
     * of all the defined parts.
     * 
     * @return string The fips category
     */
    private function _fipsCategory()
    {
        $fipsCategory = null;
        
        if (   $this->confidentiality == self::CIA_HIGH 
            || $this->integrity == self::CIA_HIGH 
            || $this->availability == self::CIA_HIGH) {
            
            $fipsCategory = self::CIA_HIGH;    

        } elseif (   $this->confidentiality == self::CIA_MODERATE 
                  || $this->integrity == self::CIA_MODERATE 
                  || $this->availability == self::CIA_MODERATE) {

            $fipsCategory = self::CIA_MODERATE;

        } elseif (   $this->confidentiality == self::CIA_LOW 
                  || $this->integrity == self::CIA_LOW 
                  || $this->availability == self::CIA_LOW) {

            $fipsCategory = self::CIA_LOW;

        }

        return $fipsCategory;
    }

    /**
     * Calculate min level
     * 
     * @param string $levelA The specified level A
     * @param string $levelB The specified level B
     * @return string The min level of bewteen $levelA and $levelB
     * @see calcSecurityCategory
     */
    public function calcMin($levelA, $levelB)
    {
        $cloumns = $this->getTable()->getEnumValues('availability');
        assert(in_array($levelA, $cloumns));
        assert(in_array($levelB, $cloumns));
        $senseMap = array_flip($cloumns);
        $ret = min($senseMap[$levelA], $senseMap[$levelB]);
        return $cloumns[$ret];
    }
    
    /**
     * Calcuate overall threat level
     * 
     * @param string $threat threat level
     * @param string $countermeasure countermeasure level
     * @return string overall threat
     * @see calcSecurityCategory
     */
    public function calculateThreatLikelihood($threat, $countermeasure)
    {
        // Initialize the threat likelihood matrix if necessary
        if (!$this->_threatLikelihoodMatrix) {
            $this->_initThreatLikelihoodMatrix();
        }
        
        // Map the parameters into the matrix and return the mapped value
        return $this->_threatLikelihoodMatrix[$threat][$countermeasure];
    }
    
    /**
     * Initializes the threat likelihood matrix. This is hardcoded because these values are defined in NIST SP 800-30
     * and are not likely to change very often.
     *
     * @return void
     * @link http://csrc.nist.gov/publications/nistpubs/800-30/sp800-30.pdf
     */
    private function _initThreatLikelihoodMatrix()
    {
        $this->_threatLikelihoodMatrix['HIGH']['LOW']      = 'HIGH';
        $this->_threatLikelihoodMatrix['HIGH']['MODERATE'] = 'MODERATE';
        $this->_threatLikelihoodMatrix['HIGH']['HIGH']     = 'LOW';
        
        $this->_threatLikelihoodMatrix['MODERATE']['LOW']      = 'MODERATE';
        $this->_threatLikelihoodMatrix['MODERATE']['MODERATE'] = 'MODERATE';
        $this->_threatLikelihoodMatrix['MODERATE']['HIGH']     = 'LOW';

        $this->_threatLikelihoodMatrix['LOW']['LOW']      = 'LOW';
        $this->_threatLikelihoodMatrix['LOW']['MODERATE'] = 'LOW';
        $this->_threatLikelihoodMatrix['LOW']['HIGH']     = 'LOW';
    }
    
    /**
     * Return system name with proper formatting
     * 
     * @return string The sysyem name with proper formatting
     */
    public function getName() 
    {
        return $this->Organization->nickname . ' - ' . $this->Organization->name;
    }
    
    /**
     * A post-update hook to send notifications
     * 
     * @param Doctrine_Event $event The triggered doctrine event
     * @return void
     */
    public function postUpdate($event)
    {
        Notification::notify('SYSTEM_UPDATED', $this->Organization, CurrentUser::getInstance());
    }
    
    /**
     * Mutator for availability. Updates the FIPS 199 automatically.
     * 
     * @param string $value The value of availability to set
     * @return void
     */
    public function setAvailability($value)
    {
        $this->_set('availability', $value);
        $this->_set('fipsCategory', $this->_fipsCategory());
    }

    /**
     * Mutator for confidentiality. Updates the FIPS 199 automatically.
     * 
     * @param string $value The value of confidentiality to set
     * @return void
     */
    public function setConfidentiality($value)
    {
        $this->_set('confidentiality', $value);
        $this->_set('fipsCategory', $this->_fipsCategory());
    }
    
    /**
     * FIPS category is not directly settable.
     * 
     * @param string $value The value of FIPS category to set
     * @return void
     * @throws Fisma_Zend_Exception if this mutator is called anytime and anywhere
     */
    public function setFipsCategory($value)
    {
        throw new Fisma_Zend_Exception('Cannot set FIPS Security category directly. It is derived from CIA.');
    }

    /**
     * Mutator for integrity. Updates the FIPS 199 automatically.
     * 
     * @param string $value The value of integrity to set
     * @return void
     */
    public function setIntegrity($value)
    {
        $this->_set('integrity', $value);
        $this->_set('fipsCategory', $this->_fipsCategory());
    }
    
    /**
     * Set the exhibit 53 Unique Project Id (UPI) which has a special format like xxx-xx-xx-xx-xx-xxxx-xx
     * 
     * To help the user out, we reformat the string automatically if required
     * 
     * @param string $value The value of UPI to reformat and set
     * @return void
     */
    public function setUniqueProjectId($value)
    {
        // Remove any existing hyphens and pad out to 17 chars
        $raw = str_pad(str_replace('-', '', $value), 17, '0');
        
        // Now reinsert hypens in the appropriate places
        $upi = substr($raw, 0, 3) . '-'
             . substr($raw, 3, 2) . '-'
             . substr($raw, 5, 2) . '-'
             . substr($raw, 7, 2) . '-'
             . substr($raw, 9, 2) . '-'
             . substr($raw, 11, 4) . '-'
             . substr($raw, 15, 2);
             
        $this->_set('uniqueProjectId', $upi);
    }

    /**
     * Implement the required method for Fisma_Zend_Acl_OrganizationDependency
     * 
     * @return int
     */
    public function getOrganizationDependencyId()
    {
        return $this->Organization->id;
    }

    /**
     * Model-level validation for updates
     *
     * Overridden from Doctrine_Record
     *
     * @return void
     */
    protected function validateOnUpdate() 
    {
        $modified = $this->getModified();
        // sdlcPhase can only be changed to 'disposal' if there are no open findings
        if (isset($modified['sdlcPhase']) && $modified['sdlcPhase'] == 'disposal') {
            $query = $this->getTable()->createQuery()
                     ->from('Finding f')
                     ->leftJoin('f.ResponsibleOrganization ro')
                     ->leftJoin('ro.System s')
                     ->where('f.status != ?', 'CLOSED')
                     ->andWhere('s.id = ?', $this->id);
                 
            if ($query->count() > 0) {
                $this->getErrorStack()->add(
                    'sdlcPhase', 
                    'Systems with open findings cannot be moved into disposal phase.'
                );
            }
        }

        // The following date fields need be validated since Solr update index function does not 
        // accept invalid date format 
        if (isset($modified['securityAuthorizationDt']) 
            && !Zend_Validate::is($modified['securityAuthorizationDt'], 'Date')) {
                 
            $this->getErrorStack()->add(
                'securityAuthorizationDt', 
                'Last Security Authorization Date provided is not a valid date.'
            );
        }

        if (isset($modified['contingencyPlanTestDt']) 
            && !Zend_Validate::is($modified['contingencyPlanTestDt'], 'Date')) {
                 
            $this->getErrorStack()->add(
                'contingencyPlanTestDt', 
                'Last Contingency Plan Test Date provided is not a valid date.'
            );
        }

        if (isset($modified['controlAssessmentDt']) 
            && !Zend_Validate::is($modified['controlAssessmentDt'], 'Date')) {
                 
            $this->getErrorStack()->add(
                'controlAssessmentDt', 
                'Last Self-Assessment Date provided is not a valid date.'
            );
        }

    }
    
    public function preDelete($event)
    {
        if ($this->Organization->Incidents->count() > 0) {
            throw new Fisma_Zend_Exception_User(
                'You cannot delete a organization/system that has Incidents associated with it'
            );
        }
    }
    
}
