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
 * @version    $Id$
 */
class IncidentReportController extends SecurityController
{
    /**
     * Check that the user has the privilege to run reports
     */
    public function preDispatch()
    {
        Fisma_Acl::requireArea('incident_report');

        $module = Doctrine::getTable('Module')->findOneByName('Incident Reporting');

        if (!$module->enabled) {
            throw new Fisma_Exception('This module is not enabled.');
        }

        // Add header/footer to any action which expects an HTML response
        if (!$this->_hasParam('format')) {
            $this->_helper->actionStack('header', 'panel');
        }
        
        // Add PDF and XLS contexts
        $this->_helper->contextSwitch()->addContext(
            'pdf', 
            array(
                'suffix' => 'pdf',
                'headers' => array(
                    'Content-Type' => 'application/pdf'
                )
            )
        );

        $this->_helper->contextSwitch()->addContext(
            'xls', 
            array(
                'suffix' => 'xls',
                'headers' => array(
                    'Content-type' => 'application/vnd.ms-excel'
                )
            )
        );

        $this->_helper->contextSwitch()
                      ->addActionContext('category', array('pdf', 'xls'))
                      ->addActionContext('history', array('pdf', 'xls'))
                      ->initContext();
    }

    /**
     * The rate of incident reports
     */
    public function historyAction()
    {
        /**
         * This data is gotten in 2 separate queries and then glued together in PHP. These are base queries which are 
         * extended below. First query gets the stats for reported incidents, second query gets stats for rejected and
         * resolved incidents. This can't be done in one query because one query operates on reportTs and the other 
         * operates on closedTs.
         */
        $reportedIncidentsQuery = Doctrine_Query::create()
                                  ->from('Incident i')
                                  ->setHydrationMode(Doctrine::HYDRATE_SCALAR);
        
        $closedIncidentsQuery = Doctrine_Query::create()
                                ->from('Incident i')
                                ->select('i.resolution')
                                ->whereIn('i.resolution', array('resolved', 'rejected'))
                                ->groupBy('i.resolution')
                                ->orderBy('i.resolution')
                                ->setHydrationMode(Doctrine::HYDRATE_SCALAR);
                       
        // Now add one column for each of last 12 months (including current month)
        $startDate = Zend_Date::now()->setDay(1)->subMonth(12);
        $months = array();

        for ($monthOffset = 0; $monthOffset < 12; $monthOffset++) {
            $startDate->addMonth(1);
            
            // Get month number without leading zero
            $month = $startDate->get('n');
            
            // Get year number
            $year = $startDate->get('Y');
            
            // Column name must be unique for each month
            $columnName = "month_{$month}_year_{$year}";
            
            /**
             * Generate an array that correlates names of months to column names so that the view can display this 
             * in a table
             */
            $months['i_' . $columnName] = $startDate->get('M');
            
            /**
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

        /**
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
            if ('rejected' != $closedIncidents[0]['i_resolution']) {
                // No rejected incidents were found but resolved incidents were found
                $history['Rejected'] = array_combine(array_keys($months), array_fill(0, 12, 0));

                $history['Resolved'] = $closedIncidents[0];
                unset($history['Resolved']['i_resolution']);
            } else {
                // Rejected and resolved incidents found. Rejected will always be in index 0 due to sort in query.
                $history['Rejected'] = $closedIncidents[0];
                unset($history['Rejected']['i_resolution']);
                
                $history['Resolved'] = $closedIncidents[1];
                unset($history['Resolved']['i_resolution']);
            }
        }

        $this->view->title = 'Incident Creation, Resolution, and Rejection (Previous 12 Months)';
        $this->view->history = $history;
        $this->view->months = $months;
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
                       
        // Now add one column for each of last 12 months (including current month)
        $startDate = Zend_Date::now()->setDay(1)->subMonth(12);
        $months = array();

        for ($monthOffset = 0; $monthOffset < 12; $monthOffset++) {
            $startDate->addMonth(1);
            
            // Get month number without leading zero
            $month = $startDate->get('n');
            
            // Get year number
            $year = $startDate->get('Y');
            
            // Column name must be unique for each month
            $columnName = "month_{$month}_year_{$year}";
            
            /**
             * Generate an array that correlates names of months to column names so that the view can display this 
             * in a table
             */
            $months[$columnName] = $startDate->get('M');
            
            /**
             * Notice interpolated parameters in the addSelect()... There is no way to bind parameters in a select() or 
             * addSelect() so they must be interpolated. This is safe, however, because the interpolated parameters 
             * are generated based off of internal dates and then filtered through Zend_Date.
             */
            $select = "SUM(IF(MONTH(i.reportTs) = $month AND YEAR(i.reportTs) = $year, 1, 0)) AS $columnName";
            
            $categoryQuery->addSelect($select);
        }

        $categories = $categoryQuery->execute();

        $this->view->title = 'Incidents Reported By Category (Previous 12 Months)';
        $this->view->categories = $categories;
        $this->view->months = $months;
    }
}
