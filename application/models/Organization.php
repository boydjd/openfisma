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
 * An organization represents a grouping of information system resources at various levels.
 * Organizations can be nested inside of each other in order to flexibly model management
 * structures at any federal agency.
 *
 * @author     Mark E. Haase <mhaase@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Model
 */
class Organization extends BaseOrganization implements Fisma_Zend_Acl_OrganizationDependency
{
    /**
     * Implements the interface for Zend_Acl_Role_Interface
     * 
     * @return int The role id
     */
    public function getRoleId()
    {
        return $this->id;
    }

    /**
     * A mapping from the physical organization types to proper English terms.
     * Notice that for 'system' types, the label is returned from the System class instead.
     * 
     * @var array
     */
    private $_orgTypeMap = array(
        'agency' => 'Agency',
        'bureau' => 'Bureau',
        'organization' => 'Organization'
    );

    /**
     * Return the type of this organization.  Unlike $this->type, this resolves
     * system organizations down to their subtype, such as gss, major or minor
     * 
     * @return string The type of organization
     */
    public function getType() 
    {
        if ('system' == $this->orgType) {
            return $this->System->type;
        } else {
            return $this->orgType;
        }
    }
    
    /**
     * Return the English version of the orgType field
     * 
     * @return string The English version of the orgType field
     */
    public function getOrgTypeLabel() 
    {
        if ('system' == $this->orgType) {
            return $this->System->getTypeLabel();
        } else {
            return $this->_orgTypeMap[$this->orgType];
        }
    }
    
    /**
     * Return the agency for this organization tree
     *
     * @return Doctrine_Node The agency tree node
     */
    public static function getAgency()
    {
        $agency = Doctrine::getTable('Organization')->getTree()->findRoot();

        return $agency;
    }
    
    /**
     * Return a collection of bureaus for this agency
     *
     * @return Doctrine_Collection The collection of bureau node
     */
    public static function getBureaus()
    {
        $bureaus = Doctrine::getTable('Organization')->findByOrgType('bureau');

        return $bureaus;
    }

    /**
     * Return a matrix of statistics that corresponds to the FISMA report
     *
     * Only applies to bureaus. Other organization types cannot use this method since there is no business logic to
     * follow.
     *
     * This calculates data for both the quarterly and annual reports. I think this is a simpler design than having
     * separate methods for each report since most of the data is the same; but it is less efficient because some data
     * will be generated and thrown away.
     * 
     * @return array The matrix of statistics which corresponds to the FISMA report in array
     * @throws Fisma_Zend_Exception if this method is called when the type of organziation is not bureau
     * @todo refactor... this turned into a huge method really quickly, but no time to fix it now
     */
    public function getFismaStatistics()
    {
        // Reject any organization which is not a bureau
        if ('bureau' != $this->orgType) {
            throw new Fisma_Zend_Exception('getFismaStatistics() is only valid for Bureaus, but was called on a '
                                    . "'$this->orgType' instead.");
        }
        
        // Setup structure of the returned array
        $securityCategories = array('AGENCY' => 0, 
                                    'CONTRACTOR' => 0, 
                                    'TOTAL_CERTIFIED' => 0, 
                                    'TOTAL_SELF_ASSESSMENT' => 0, 
                                    'TOTAL_CONTINGENCY_PLAN_TESTED' => 0,
                                    'CERTIFIED_THIS_QUARTER' => 0,
                                    'POAM_90_TO_120' => 0,
                                    'POAM_120_PLUS' => 0);
        $securityStats = array('HIGH' => $securityCategories, 
                               'MODERATE' => $securityCategories, 
                               'LOW' => $securityCategories, 
                               'NC' => $securityCategories);
                               
        $privacyCategories = array('AGENCY' => 0, 
                                   'CONTRACTOR' => 0);
        $privacyStats = array('FEDERAL_INFORMATION' => $privacyCategories,
                              'PIA_REQUIRED' => $privacyCategories,
                              'PIA_COVERED' => $privacyCategories,
                              'PIA_URL' => array(),
                              'SORN_REQUIRED' => $privacyCategories,
                              'SORN_PUBLISHED' => $privacyCategories,
                              'SORN_URL' => array());
                              
        $systems = array();
                              
        $today = new Zend_Date();
                       
        // Calculate the inventory statistics, such as agency/contractor, C&A, etc.
        if ($children = $this->getNode()->getDescendants()) {
            $children->loadRelated();
            foreach ($children as $child) {
                if ('system' != $child->orgType) {
                    continue;
                }
            
                $system = $child->System;
                $fipsCategory = empty($system->fipsCategory) ? 'NC' : $system->fipsCategory;
            
                // Controlled by the agency or a contractor?
                if (!empty($system->controlledBy)) {
                    $securityStats[$fipsCategory][$system->controlledBy]++;
                
                    // Has federal information in identifiable form?
                    if ('YES' == $system->hasFiif) {
                        $privacyStats['FEDERAL_INFORMATION'][$system->controlledBy]++;
                    }

                    // Requires a PIA?
                    if ('YES' == $system->piaRequired) {
                        $privacyStats['PIA_REQUIRED'][$system->controlledBy]++;

                        // Has a PIA?
                        if ('YES' == $system->piaRequired) {
                            $privacyStats['PIA_COVERED'][$system->controlledBy]++;
                            $privacyStats['PIA_URL'][] = $system->piaUrl;
                        }
                    }

                    // Requires a SORN?
                    if ('YES' == $system->piaRequired) {
                        $privacyStats['SORN_REQUIRED'][$system->controlledBy]++;
                 
                        // Is the SORN published?
                        if ('YES' == $system->piaRequired) {
                            $privacyStats['SORN_PUBLISHED'][$system->controlledBy]++;
                            $privacyStats['SORN_URL'][] = $system->sornUrl;
                        }
                    }
                }
            
                if (!empty($system->securityAuthorizationDt)) {
                    // Was the system C&A'ed in the last 3 years? 
                    $currentCaDate = new Zend_Date($system->securityAuthorizationDt, Fisma_Date::FORMAT_DATE);
                    $nextCaDate = $currentCaDate->addYear(3);
                    
                    /** 
                     * @todo should have used isEarlier and isLater() instead of compare()
                     * compare is not very readable 
                     */
                    if (1 == $nextCaDate->compare($today)) {
                        $securityStats[$fipsCategory]['TOTAL_CERTIFIED']++;
                    } else {
                        // System has an expired C&A. Add to section 2.e. of the annual report.
                        $systems[] = array(
                            'name' => $child->name,
                            'fipsCategory' => $fipsCategory,
                            'uniqueProjectId' => $system->uniqueProjectId
                        );
                    }
                
                    // Was the system C&A'ed in the last quarter?
                    $lastQuarter = $today->subMonth(3);
                    if (1 == $currentCaDate->compare($lastQuarter)) {
                        $securityStats[$fipsCategory]['CERTIFIED_THIS_QUARTER']++;
                    }
                } else {
                    // System does not have any C&A at all. Add to section 2.e. of the annual report.
                    if (empty($system->securityAuthorizationDt)) {
                        $systems[] = array(
                            'name' => $child->name,
                            'fipsCategory' => $fipsCategory,
                            'uniqueProjectId' => $system->uniqueProjectId
                        );
                    }
                }

                // Controls self-assessed in the last year?
                if (!empty($system->controlAssessmentDt)) {
                    $currentSelfAssessmentDate = new Zend_Date($system->securityAuthorizationDt,
                        Fisma_Date::FORMAT_DATE);
                    $nextSelfAssessmentDate = $currentSelfAssessmentDate->addYear(1);
                    if (1 == $nextSelfAssessmentDate->compare($today)) {
                        $securityStats[$fipsCategory]['TOTAL_SELF_ASSESSMENT']++;
                    }
                }
            
                // Contingency plan has been tested in the last year?
                if (!empty($system->contingencyPlanTestDt)) {
                    $currentContingencyPlanTestDate = new Zend_Date($system->contingencyPlanTestDt,
                        Fisma_Date::FORMAT_DATE);
                    $nextContingencyPlanTestDate = $currentContingencyPlanTestDate->addYear(1);
                    if (1 == $nextContingencyPlanTestDate->compare($today)) {
                        $securityStats[$fipsCategory]['TOTAL_CONTINGENCY_PLAN_TESTED']++;
                    }
                }
            }
        }

        // Get the number of HIGH, MODERATE, and NC systems which have overdue POAM items between 90 and 120 days, or 
        // greater than 120 days
        $poamQuery = Doctrine_Query::create()
                     ->select('s.fipsCategory')
                     ->from('System s INDEXBY s.fipsCategory')
                     ->innerJoin('s.Organization o')
                     ->innerJoin('o.Findings f')
                     ->where('f.currentEcd <= ?')
                     ->andWhere('f.status <> ?', 'CLOSED')
                     ->andWhere('o.lft > ?', $this->lft)
                     ->andWhere('o.rgt < ?', $this->rgt)
                     ->andWhere('s.fipsCategory <> ?', 'LOW')
                     ->groupBy('s.fipsCategory')
                     ->setHydrationMode(Doctrine::HYDRATE_ARRAY);

        // Count the 120+ first.
        $today = new Zend_Date();
        $overdueDate121 = $today->subDay(121)->toString(Fisma_Date::FORMAT_DATE);
        $result121 = $poamQuery->execute(array($overdueDate121));
        foreach ($result121 as $level => $system) {
            if (empty($level)) {
                $level = 'NC';
            }
            $securityStats[$level]['POAM_120_PLUS']++;
        }
        
        // Now count the 90+
        $today = new Zend_Date();        
        $overdueDate90 = $today->subDay(90)->toString(Fisma_Date::FORMAT_DATE);
        $result90 = $poamQuery->execute(array($overdueDate90));
        foreach ($result90 as $level => $system) {
            if (empty($level)) {
                $level = 'NC';
            }
            $securityStats[$level]['POAM_90_TO_120']++;
        }
        
        // Now subtract the 120+ from the 90+ to get only the 90-120 day range
        foreach (array('HIGH', 'MODERATE', 'NC') as $level) {
            $securityStats[$level]['POAM_90_TO_120'] -= $securityStats[$level]['POAM_120_PLUS'];
        }
        
        // Now assemble all statistics
        $stats = array();
        $stats['name'] = $this->name;
        $stats['systems'] = $systems;
        $stats['security'] = $securityStats;
        $stats['privacy'] = $privacyStats;

        return $stats;
    }
    
    /**
     * A post-insert hook to send notifications
     * 
     * @param Doctrine_Event $event The triggered doctrine event
     * @return void
     */
    public function postInsert($event)
    {    
        // This model can generate events for organization objects AND system objects
        if ('organization' == $this->orgType) {
            $eventName = 'ORGANIZATION_CREATED';
        } else {
            $eventName = 'SYSTEM_CREATED';
        }

        Notification::notify($eventName, $this, CurrentUser::getInstance());
    }
    
    /**
     * A post-update hook to send notifications
     * 
     * @param Doctrine_Event $event The triggered doctrine event
     * @return void
     */
    public function postUpdate($event)
    {        
        // The system model will handle update events on its own, but we need to filter them out here
        // in case the system model somehow triggers a save() on its related organization object
        if ('organization' == $this->orgType) {
            $eventName = 'ORGANIZATION_UPDATED';
            Notification::notify($eventName, $this, CurrentUser::getInstance());
        }
    }

    /**
     * A post-delete hook to send notifications
     * 
     * @param Doctrine_Event $event The triggered doctrine event
     * @return void
     */
    public function postDelete($event)
    {        
        // This model can generate events for organization objects AND system objects
        if ('organization' == $this->orgType) {
            $eventName = 'ORGANIZATION_DELETED';
        } else {
            $eventName = 'SYSTEM_DELETED';
        }
        
        Notification::notify($eventName, $this, CurrentUser::getInstance());
    }

    /**
     * Implement the required method for Fisma_Zend_Acl_OrganizationDependency
     * 
     * @return int
     */
    public function getOrganizationDependencyId()
    {
        return $this->id;
    }
}
