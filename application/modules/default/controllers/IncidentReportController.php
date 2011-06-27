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
 * A controller for the incident reporting
 * 
 * @author     Mark E. Haase
 * @copyright  (c) Endeavor Systems, Inc. 2010 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Controller
 */
class IncidentReportController extends Fisma_Zend_Controller_Action_Security
{
    /**
     * Initialize the controller
     */
    public function init()
    {
        $this->_helper->reportContextSwitch()
                      ->addActionContext('category', array('html', 'pdf', 'xls'))
                      ->addActionContext('history', array('html', 'pdf', 'xls'))
                      ->addActionContext('bureau', array('html', 'pdf', 'xls'))
                      ->initContext();
        
        parent::init();        
    }
    
    /**
     * Check that the user has the privilege to run reports
     */
    public function preDispatch()
    {
        parent::preDispatch();

        $this->_acl->requireArea('incident_report');

        $module = Doctrine::getTable('Module')->findOneByName('Incident Reporting');

        if (!$module->enabled) {
            throw new Fisma_Zend_Exception('This module is not enabled.');
        }
    }

    /**
     * The rate of incident reports
     */
    public function historyAction()
    {
        /*
         * This data is gotten in 2 separate queries and then glued together in PHP. These are base queries which are 
         * extended below. First query gets the stats for reported incidents, second query gets stats for rejected and
         * resolved incidents. This can't be done in one query because one query operates on reportTs and the other 
         * operates on closedTs.
         */
        $reportedIncidentsQuery = Doctrine_Query::create()
                                  ->from('Incident i')
                                  ->having('COUNT(*) > 0')
                                  ->setHydrationMode(Doctrine::HYDRATE_SCALAR);
        
        $closedIncidentsQuery = Doctrine_Query::create()
                                ->from('Incident i')
                                ->select('i.resolution')
                                ->whereIn('i.resolution', array('resolved', 'rejected'))
                                ->groupBy('i.resolution')
                                ->orderBy('i.resolution')
                                ->setHydrationMode(Doctrine::HYDRATE_SCALAR);

        // Create base report object -- additional columns are added in the loop below
        $report = new Fisma_Report();

        $report->setTitle('Incident Creation, Resolution, and Rejection (Previous 12 Months)')
               ->addColumn(new Fisma_Report_Column('Action', true));

        // Now add one column for each of last 12 months (including current month)
        $startDate = Zend_Date::now()->setDay(1)->subMonth(12);
        $months = array();

        for ($monthOffset = 0; $monthOffset < 12; $monthOffset++) {
            $startDate->addMonth(1);
            
            $report->addColumn(
                new Fisma_Report_Column(
                    $startDate->get(Zend_Date::MONTH_NAME_SHORT),
                    true,
                    null,
                    null,
                    false,
                    'number'
                )
            );

            // Get month number without leading zero
            $month = $startDate->get(Zend_Date::MONTH_SHORT);
            
            // Get year number
            $year = $startDate->get(Zend_Date::YEAR);
            
            // Column name must be unique for each month
            $columnName = "month_{$month}_year_{$year}";

            $months['i_' . $columnName] = $startDate->get(Zend_Date::MONTH_NAME_SHORT);
            
            /*
             * Notice interpolated parameters in the addSelect()... There is no way to bind parameters in a select() or 
             * addSelect() so they must be interpolated. This is safe, however, because the interpolated parameters 
             * are generated based off of internal dates and then filtered through Zend_Date.
             */
            $reportedSelect = "SUM(IF(MONTH(i.reportTs) = $month AND YEAR(i.reportTs) = $year, 1, 0)) AS $columnName";
            $reportedIncidentsQuery->addSelect($reportedSelect);

            $closedSelect = "SUM(IF(MONTH(i.closedTs) = $month AND YEAR(i.closedTs) = $year, 1, 0)) AS $columnName";
            $closedIncidentsQuery->addSelect($closedSelect);
        }

        $reportedIncidents = $reportedIncidentsQuery->execute();
        $closedIncidents = $closedIncidentsQuery->execute();

        /*
         * Consolidate query results into a single 2d array, filling in blank entries in case either query above
         * doesn't have any hits.
         */
        $history = array();

        if (count($reportedIncidents) == 0) {
            // Create 12 blank months
            $history['Reported'] = array_combine(array_keys($months), array_fill(0, 12, 0));
        } else {
            $history['Reported'] = $reportedIncidents[0];
        }

        if (count($closedIncidents) == 0) {
            // This means no closed incidents were found. Create blank arrays for rejected and resolved.
            $history['Rejected'] = array_combine(array_keys($months), array_fill(0, 12, 0));
            $history['Resolved'] = array_combine(array_keys($months), array_fill(0, 12, 0));
        } else {
            if (count($closedIncidents) == 2) {
                // Rejected and resolved incidents found. Rejected will always be in index 0 due to sort in query.
                $history['Rejected'] = $closedIncidents[0];
                unset($history['Rejected']['i_resolution']);
                
                $history['Resolved'] = $closedIncidents[1];
                unset($history['Resolved']['i_resolution']);                
            } elseif ('resolved' == $closedIncidents[0]['i_resolution']) {
                // No rejected incidents were found but resolved incidents were found
                $history['Rejected'] = array_combine(array_keys($months), array_fill(0, 12, 0));

                $history['Resolved'] = $closedIncidents[0];
                unset($history['Resolved']['i_resolution']);
            } else {
                // No resolved incidents were found but rejected incidents were found
                $history['Rejected'] = $closedIncidents[0];
                unset($history['Rejected']['i_resolution']);

                $history['Resolved'] = array_combine(array_keys($months), array_fill(0, 12, 0));
            }
        }
        
        // Each array is missing its first column, the "Action", so add it now
        array_unshift($history['Reported'], 'Reported');
        array_unshift($history['Rejected'], 'Rejected');
        array_unshift($history['Resolved'], 'Resolved');

        $report->setData($history);

        $this->_helper->reportContextSwitch()->setReport($report);
    }
        
    /**
     * Break down of all incidents by status
     */
    public function categoryAction()
    {
        // Base query gets category names and joins to incidents
        $categoryQuery = Doctrine_Query::create()
                         ->from('IrCategory c')
                         ->innerJoin('c.SubCategories sc')
                         ->leftJoin('sc.Incident i')
                         ->select('c.category, sc.name')
                         ->groupBy('c.id, sc.id')
                         ->orderBy('c.category, sc.name')
                         ->setHydrationMode(Doctrine::HYDRATE_SCALAR);
        
        // Create the base report object -- additional columns are added below
        $report = new Fisma_Report();

        $report->setTitle('Incidents Reported By Category (Previous 12 Months)')
               ->addColumn(new Fisma_Report_Column('Category', true))
               ->addColumn(new Fisma_Report_Column('Sub Category', true));
                       
        // Now add one column for each of last 12 months (including current month)
        $startDate = Zend_Date::now()->setDay(1)->subMonth(12);

        for ($monthOffset = 0; $monthOffset < 12; $monthOffset++) {
            $startDate->addMonth(1);
            
            // Get month number without leading zero
            $month = $startDate->get(Zend_Date::MONTH_SHORT);
            
            // Get year number
            $year = $startDate->get(Zend_Date::YEAR);
                        
            $report->addColumn(
                new Fisma_Report_Column(
                    $startDate->get(Zend_Date::MONTH_NAME_SHORT),
                    true,
                    null,
                    null,
                    false,
                    'number'
                )
            );

            // Column name must be unique for each month
            $columnName = "month_{$month}_year_{$year}";

            /*
             * Notice interpolated parameters in the addSelect()... There is no way to bind parameters in a select() or 
             * addSelect() so they must be interpolated. This is safe, however, because the interpolated parameters 
             * are generated based off of internal dates and then filtered through Zend_Date.
             */
            $select = "SUM(IF(MONTH(i.reportTs) = $month AND YEAR(i.reportTs) = $year, 1, 0)) AS $columnName";
            
            $categoryQuery->addSelect($select);
        }

        $categories = $categoryQuery->execute();

        $report->setData($categories);

        $this->_helper->reportContextSwitch()->setReport($report);
    }
    
    /**
     * Show incidents by Bureau
     */
    public function bureauAction()
    {
        // Base query gets category names and joins to incidents
        $bureauQuery = Doctrine_Query::create()
                       ->from('Organization bureau')
                       ->select('bureau.nickname')
                       ->leftJoin('Organization child')
                       ->leftJoin('child.Incidents i')
                       ->where('bureau.orgType = ?', 'bureau')
                       ->andWhere('child.lft BETWEEN bureau.lft AND bureau.rgt')
                       ->groupBy('bureau.id')
                       ->setHydrationMode(Doctrine::HYDRATE_SCALAR);

        // Create the base report object -- additional columns are added below
        $report = new Fisma_Report();

        $report->setTitle('Incidents Reported Per Bureau (Previous 12 Months)')
               ->addColumn(new Fisma_Report_Column('Bureau', true));
                       
        // Now add one column for each of last 12 months (including current month)
        $startDate = Zend_Date::now()->setDay(1)->subMonth(12);

        for ($monthOffset = 0; $monthOffset < 12; $monthOffset++) {
            $startDate->addMonth(1);
            
            // Get month number without leading zero
            $month = $startDate->get(Zend_Date::MONTH_SHORT);
            
            // Get year number
            $year = $startDate->get(Zend_Date::YEAR);
                        
            $report->addColumn(
                new Fisma_Report_Column(
                    $startDate->get(Zend_Date::MONTH_NAME_SHORT),
                    true,
                    null,
                    null,
                    false,
                    'number'
                )
            );

            // Column name must be unique for each month
            $columnName = "month_{$month}_year_{$year}";

            /*
             * Notice interpolated parameters in the addSelect()... There is no way to bind parameters in a select() or 
             * addSelect() so they must be interpolated. This is safe, however, because the interpolated parameters 
             * are generated based off of internal dates and then filtered through Zend_Date.
             */
            $select = "SUM(IF(MONTH(i.reportTs) = $month AND YEAR(i.reportTs) = $year, 1, 0)) AS $columnName";
            
            $bureauQuery->addSelect($select);
        }

        $bureaus = $bureauQuery->execute();

        $report->setData($bureaus);

        $this->_helper->reportContextSwitch()->setReport($report);
    }
}
