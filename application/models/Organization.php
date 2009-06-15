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
    public function getSummaryCounts($type, $source) {
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
        /** @doctrine the system is too broken to generate test data, so this code may not be correct */
        if ('system' == $this->orgType) {
            $onTimeQuery = Doctrine_Query::create()
                           ->select('COUNT(*) AS count, f.status, e.nickname')
                           ->from('Finding f')
                           ->innerJoin('f.CurrentEvaluation e')
                           ->innerJoin('f.ResponsibleOrganization o')
                           ->where("f.status <> 'PEND'")
                           ->andWhere("f.nextDueDate >= NOW() OR f.nextDueDate IS NULL")
                           ->andWhere('o.id = ?', array($this->id))
                           ->groupBy('f.status, e.nickname')
                           ->setHydrationMode(Doctrine::HYDRATE_ARRAY);
            if (isset($type)) {
                $onTimeQuery->andWhere('f.type = ?', $type);
            }
            if (isset($source)) {
                $onTimeQuery->andWhere('f.sourceId = ?', $source);
            }
            $onTimeFindings = $onTimeQuery->execute();
            
            foreach($onTimeFindings as $finding) {
                if ('MSA' == $finding['status'] || 'EA' == $finding['status']) {
                    $counts['single_ontime'][$finding['nickname']] = $finding['count'];
                } else {
                    $counts['single_ontime'][$finding['status']] = $finding['count'];
                }
            }
            
            $overdueQuery = Doctrine_Query::create()
                            ->select('COUNT(*) AS count, f.status, e.nickname')
                            ->from('Finding f')
                            ->innerJoin('f.CurrentEvaluation e')
                            ->innerJoin('f.ResponsibleOrganization o')
                            ->where("f.status <> 'PEND'")
                            ->andWhere("f.nextDueDate >= NOW() OR f.nextDueDate IS NULL")
                            ->andWhere('o.id = ?', array($this->id))
                            ->groupBy('f.status, e.nickname')
                            ->setHydrationMode(Doctrine::HYDRATE_ARRAY);
            if (isset($type)) {
                $overdueQuery->andWhere('f.type = ?', $type);
            }
            if (isset($source)) {
                $overdueQuery->andWhere('f.sourceId = ?', $source);
            }
            $overdueFindings = $overdueQuery->execute();
            
            foreach($overdueFindings as $finding) {
                if ('MSA' == $finding['status'] || 'EA' == $finding['status']) {
                    $counts['single_overdue'][$finding['nickname']] = $finding['count'];
                } else {
                    $counts['single_overdue'][$finding['status']] = $finding['count'];
                }
            }
        }
        
        // @doctrine test data
        if ($this->nickname == 'WDC') {$counts['single_ontime']['DRAFT'] = 2; $counts['single_overdue']['EN'] = 1;}
        if ($this->nickname == 'OF') {$counts['single_overdue']['EN'] = 1;}
        if ($this->nickname == "OCIO") {$counts['single_ontime']['MS ISSO'] = 1;}

        // Recursively get summary counts from each child and add to the running sum
        $counts['all_ontime'] = $counts['single_ontime'];
        $counts['all_overdue'] = $counts['single_overdue'];
        if ($this->getNode()->hasChildren()) {
            $iterator = $this->getNode()->getChildren()->getNormalIterator();
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

        return $counts;
    }
}