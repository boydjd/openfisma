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
 * @package   Model
 */

/**
 * An organization represents a grouping of information system resources at various levels.
 * Organizations can be nested inside of each other in order to flexibly model management
 * structures at any federal agency.
 *
 * @package   Model
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/mw/index.php?title=License
 */
class Organization extends BaseOrganization
{
    /**
     * Implements the interface for Zend_Acl_Role_Interface
     */
    public function getRoleId()
    {
        return $this->id;
    }

    /**
     * A mapping from the physical organization types to proper English terms.
     * Notice that for 'system' types, the label is returned from the System class instead.
     */
    private $_orgTypeMap = array(
        'agency' => 'Agency',
        'bureau' => 'Bureau',
        'organization' => 'Organization'
    );

    /**
     * Return the the type of this organization.  Unlike $this->type, this resolves
     * system organizations down to their subtype, such as gss, major or minor
     * 
     * @return string
     */
    public function getType() {
        if ('system' == $this->orgType) {
            return $this->System->type;
        } else {
            return $this->orgType;
        }
    }
    
    /**
     * Return the English version of the orgType field
     * 
     * @return string
     */
    public function getOrgTypeLabel() {
        if ('system' == $this->orgType) {
            return $this->System->getTypeLabel();
        } else {
            return $this->_orgTypeMap[$this->orgType];
        }
    }
    
    /**
     * Count the number of findings against this organization (and its children)
     * split into ontime and overdue counts.
     * 
     * Returns an associative array that contains 4 keys:
     * 
     * 'single_ontime' => Count of the number of on-time findings in each status, plus a TOTAL, 
     *                    for this organization only.
     * 
     * 'single_overdue' => Count of the number of overdue findings in each status except CLOSED,
     *                     for this organization only.
     * 
     * 'all_ontime' => Count of the number of on-time findings in each status, plus a TOTAL,
     *                 for this organization and all of its child organizations
     * 
     * 'all_overdue' => Count of the number of overdue findings in each status except CLOSED,
     *                  for this organization and all of its child organizations.
     * 
     * This is a very expensive operation, since it can result in very many DB queries to get all of
     * the data as it walks through the tree. Therefore, these numbers are all cached.
     * 
     * @param string $type The mitigation strategy type to filter for
     * @param int $source The id of the finding source to filter for
     * 
     * @return array 
     */
    public function getSummaryCounts($type = null, $source = null) {
        $cache = Fisma::getCacheInstance('finding_summary');
        $cacheId = $this->getCacheId(array('type' => $type, 'source' => $source));
                     
        if (!($counts = $cache->load($cacheId))) {
            // First get all of the business statuses
            $statusList = Finding::getAllStatuses();

            // Initialize single_ontime and single_overdue counts
            $counts = array();
            $counts['single_ontime'] = array();
            $counts['single_overdue'] = array();
            foreach ($statusList as $status) {
                $counts['single_ontime'][$status] = 0;
                $counts['single_overdue'][$status] = 0;
            }
            $counts['single_ontime']['TOTAL'] = 0;
            unset($counts['single_overdue']['CLOSED']);
        
            // Count the single_ontime and single_overdue
            $now = new Zend_Date();
            $onTimeQuery = Doctrine_Query::create()
                           ->select('COUNT(*) AS count, f.status, e.nickname')
                           ->from('Finding f')
                           ->leftJoin('f.CurrentEvaluation e')
                           ->innerJoin('f.ResponsibleOrganization o')
                           ->where("f.status <> 'PEND'")
                           ->andWhere("f.nextDueDate >= ? OR f.nextDueDate IS NULL", $now->toString('Y-m-d'))
                           ->andWhere('o.id = ?', array($this->id))
                           ->groupBy('f.status, e.nickname')
                           ->setHydrationMode(Doctrine::HYDRATE_SCALAR);

            if (isset($type)) {
                $onTimeQuery->andWhere('f.type = ?', $type);
            }
            if (isset($source)) {
                $onTimeQuery->andWhere('f.sourceId = ?', $source);
            }
            $onTimeFindings = $onTimeQuery->execute();
                    
            foreach ($onTimeFindings as $finding) {
                if ('MSA' == $finding['f_status'] || 'EA' == $finding['f_status']) {
                    $counts['single_ontime'][$finding['e_nickname']] = $finding['f_count'];
                } else {
                    $counts['single_ontime'][$finding['f_status']] = $finding['f_count'];
                }
            }
            
            $now = new Zend_Date();
            $overdueQuery = Doctrine_Query::create()
                            ->select('COUNT(*) AS count, f.status, e.nickname')
                            ->from('Finding f')
                            ->leftJoin('f.CurrentEvaluation e')
                            ->innerJoin('f.ResponsibleOrganization o')
                            ->where("f.status <> 'PEND'")
                            ->andWhere("f.nextDueDate < ?", $now->toString('Y-m-d'))
                            ->andWhere('o.id = ?', array($this->id))
                            ->groupBy('f.status, e.nickname')
                            ->setHydrationMode(Doctrine::HYDRATE_SCALAR);
            if (isset($type)) {
                $overdueQuery->andWhere('f.type = ?', $type);
            }
            if (isset($source)) {
                $overdueQuery->andWhere('f.sourceId = ?', $source);
            }
            $overdueFindings = $overdueQuery->execute();
        
            foreach ($overdueFindings as $finding) {
                if ('MSA' == $finding['f_status'] || 'EA' == $finding['f_status']) {
                    $counts['single_overdue'][$finding['e_nickname']] = $finding['f_count'];
                } else {
                    $counts['single_overdue'][$finding['f_status']] = $finding['f_count'];
                }
            }

            // Recursively get summary counts from each child and add to the running sum
            $counts['all_ontime'] = $counts['single_ontime'];
            $counts['all_overdue'] = $counts['single_overdue'];
            $children = $this->getNode()->getChildren();
            if ($children) {
                $iterator = $children->getNormalIterator();
                foreach ($iterator as $child) {
                    $childCounts = $child->getSummaryCounts($type, $source);
                    unset($childCounts['all_ontime']['TOTAL']);
                    unset($childCounts['all_overdue']['TOTAL']);
                    foreach (array_keys($childCounts['all_ontime']) as $key) {
                        $counts['all_ontime'][$key] += $childCounts['all_ontime'][$key];
                    }
                    if (isset($childCounts['all_overdue'])) {
                        foreach (array_keys($childCounts['all_overdue']) as $key) {
                            $counts['all_overdue'][$key] += $childCounts['all_overdue'][$key];
                        }
                    }
                }
            }

            // Now count up the totals
            $counts['single_ontime']['TOTAL'] = array_sum($counts['single_ontime']);
            $counts['single_ontime']['TOTAL'] += array_sum($counts['single_overdue']);
            $counts['all_ontime']['TOTAL'] = array_sum($counts['all_ontime']);
            $counts['all_ontime']['TOTAL'] += array_sum($counts['all_overdue']);
            
            $cache->save($counts, $cacheId, array($this->getCacheTag()));
        }
        
        return $counts;
    }

    /**
     * Invalidate the summary counts for this organization and all of its parents.
     * 
     * Since the summary counts are cached, other classes need a way of telling the Organization class to 
     * clear the cache and recalculate the summary counts. This method will recursively invalidate the
     * cache all the way up the tree so that parent node caches get recalculated, too.
     */
    public function invalidateCache() 
    {
        $cache = Fisma::getCacheInstance($identify = 'finding_summary');
        
        $cache->clean(Zend_Cache::CLEANING_MODE_MATCHING_TAG,
                      array($this->getCacheTag()));
                      
        $parent = $this->getNode()->getParent();
        if ($parent) {
            $parent->invalidateCache();
        }
    }

    /**
     * Generate a unique cache id based on the query parameters
     * 
     * @param array Query parameters
     * @return string
     */
    public function getCacheId($parameters)
    {
        $cacheId = $this->id;
        
        // Add any query parameters to the cache ID. This prevents us from confusing filtered counts and unfiltered
        // counts.
        foreach ($parameters as $key => $value) {
            if (!empty($value)) {
                // The cache only allows alphanumeric IDs and underscores
                $safeValue = preg_replace('/[^a-zA-Z0-9_]/', '_', $value);
                $cacheId .= "_{$key}_{$safeValue}";
            }
        }
            
        return $cacheId;
    }


    /**
     * Returns a cache tag that is unique to this organization
     * 
     * @return string
     */
    public function getCacheTag()
    {
        return "organization_$this->id";
    }
    
    /**
     * Return the agency for this organization tree
     *
     * @return Doctrine_Node
     */
    public static function getAgency()
    {
        $agency = Doctrine::getTable('Organization')->getTree()->findRoot();

        return $agency;
    }
    
    /**
     * Return a collection of bureaus for this agency
     *
     * @return Doctrine_Collection
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
     * @todo refactor... this turned into a huge method really quickly, but no time to fix it now
     * 
     * @return array
     */
    public function getFismaStatistics()
    {
        // Reject any organization which is not a bureau
        if ('bureau' != $this->orgType) {
            throw new Fisma_Exception('getFismaStatistics() is only valid for Bureaus, but was called on a '
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
        $children = $this->getNode()->getDescendants();
        $children->loadRelated();
        foreach ($children as $child) {
            if ('system' != $child->orgType) {
                continue;
            }
            
            $system = $child->System;
            $fipsCategory = empty($system->fipsCategory) ? 'NC' : $system->fipsCategory;
            
            // Create the systems matrix for section 2.e. of the annual report. This only includes systems that have not
            // been C&A'ed.
            if (empty($system->securityAuthorizationDt)) {
                $systems[] = array(
                    'name' => $child->name,
                    'fipsCategory' => $fipsCategory,
                    'uniqueProjectId' => $system->uniqueProjectId
                );
            }
            
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
                $currentCaDate = new Zend_Date($system->securityAuthorizationDt, 'Y-m-d');
                $nextCaDate = $currentCaDate->addYear(3);
                /** @todo should have used isEarlier and isLater() instead of compare() -- compare is not very readable */
                if (1 == $nextCaDate->compare($today)) {
                    $securityStats[$fipsCategory]['TOTAL_CERTIFIED']++;
                }
                
                // Was the system C&A'ed in the last quarter?
                $lastQuarter = $today->subMonth(3);
                if (1 == $currentCaDate->compare($lastQuarter)) {
                    $securityStats[$fipsCategory]['CERTIFIED_THIS_QUARTER']++;
                }
            }

            // Controls self-assessed in the last year?
            if (!empty($system->controlAssessmentDt)) {
                $currentSelfAssessmentDate = new Zend_Date($system->securityAuthorizationDt, 'Y-m-d');
                $nextSelfAssessmentDate = $currentSelfAssessmentDate->addYear(1);
                if (1 == $nextSelfAssessmentDate->compare($today)) {
                    $securityStats[$fipsCategory]['TOTAL_SELF_ASSESSMENT']++;
                }
            }
            
            // Contingency plan has been tested in the last year?
            if (!empty($system->contingencyPlanTestDt)) {
                $currentContingencyPlanTestDate = new Zend_Date($system->contingencyPlanTestDt, 'Y-m-d');
                $nextContingencyPlanTestDate = $currentContingencyPlanTestDate->addYear(1);
                if (1 == $nextContingencyPlanTestDate->compare($today)) {
                    $securityStats[$fipsCategory]['TOTAL_CONTINGENCY_PLAN_TESTED']++;
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
                     ->groupBy('s.id')
                     ->setHydrationMode(Doctrine::HYDRATE_ARRAY);

        // Count the 120+ first.
        $today = new Zend_Date();
        $overdueDate121 = $today->subDay(121)->toString('Y-m-d');
        $result121 = $poamQuery->execute(array($overdueDate121));
        foreach ($result121 as $level => $system) {
            if (empty($level)) {
                $level = 'NC';
            }
            $securityStats[$level]['POAM_120_PLUS']++;
        }
        
        // Now count the 90+
        $today = new Zend_Date();        
        $overdueDate90 = $today->subDay(90)->toString('Y-m-d');
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
}
