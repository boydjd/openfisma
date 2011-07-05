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
 * Create charts
 * 
 * @author     Mark E. Haase
 * @copyright  (c) Endeavor Systems, Inc. 2010 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Controller
 */
class IncidentChartController extends Fisma_Zend_Controller_Action_Security
{
    /**
     * Set contexts for this controller's actions
     */
    public function init()
    {
        parent::init();
        
        $this->_helper->fismaContextSwitch()
            ->setActionContext('history', 'json')
            ->setActionContext('category', 'json')
            ->setActionContext('bureau', 'json')
            ->initContext();
    }
    
    /**
     * Verify that this module is enabled
     */
    public function preDispatch()
    {
        parent::preDispatch();
        
        $module = Doctrine::getTable('Module')->findOneByName('Incident Reporting');
        
        if (!$module->enabled) {
            throw new Fisma_Zend_Exception('This module is not enabled.');
        }

        $this->_acl->requireArea('incident');
    }
    
    /**
     * A bar chart which shows how many incidents were reported/resolved/rejected on a month-by-month basis 
     * in recent history
     */
    public function historyAction()
    {
        /**
         * $period is the number of months of history to limit the results to. It's limited to 12 due to the way
         * the query is structured (indexed by month number, which would wrap around with a 12+ month period)
         */
        $period = $this->getRequest()->getParam('period');
        $period = substr($period, 0, 1) * 1;    // converts "5 months of history" to 5
        
        $rtnChart = new Fisma_Chart();
        $rtnChart
            ->setLayerLabels(
                array(
                    'Reported',
                    'Resolved',
                    'Rejected'
                )
            )
            ->setColors(
                array(
                    '#FF0000',
                    '#FF6600',
                    '#FFC000'
                )
            )
            ->setThreatLegendVisibility(true)
            ->setChartType('stackedbar')
            ->setTitle('Incidents reported, resolved, and rejected (past ' . $period . ' months)');
        
        if (!is_int((int)$period) || $period > 12) {
            $message = "Incident status chart period parameter must be an integer less than or equal to 12.";
            throw new Fisma_Zend_Exception($message);
        }
        
        // Calculate the cutoff date based on the period        
        $cutoffDate = Zend_Date::now()->sub($period, Zend_Date::MONTH)->get(Fisma_Date::FORMAT_DATE);

        // Get chart data. This is done in two queries because one groups by reportTs and the other groups by closedTs
        $reportedIncidentsQuery = Doctrine_Query::create()
                                  ->addSelect('COUNT(i.id) AS reported')
                                  ->addSelect('MONTH(i.reportTs) AS monthNumber')
                                  ->from('Incident i INDEXBY monthNumber')
                                  ->where("i.reportTs > '$cutoffDate'")
                                  ->groupBy('monthNumber')
                                  ->setHydrationMode(Doctrine::HYDRATE_ARRAY);

        $reportedIncidents = $reportedIncidentsQuery->execute();

        $closedIncidentsQuery = Doctrine_Query::create()
                                ->addSelect("SUM(IF(i.resolution = 'resolved', 1, 0)) AS resolved")
                                ->addSelect("SUM(IF(i.resolution = 'rejected', 1, 0)) AS rejected")
                                ->addSelect('MONTH(i.closedTs) AS monthNumber')
                                ->from('Incident i INDEXBY monthNumber')
                                ->where("i.closedTs > '$cutoffDate'")
                                ->groupBy('monthNumber')
                                ->setHydrationMode(Doctrine::HYDRATE_ARRAY);

        $closedIncidents = $closedIncidentsQuery->execute();

        // Merge results and fill in placeholders for months that have no data
        $mergedData = array();
        $firstMonth = Zend_Date::now()->sub($period, Zend_Date::MONTH);

        $chartData = array('reported' => array(), 'resolved' => array(), 'rejected' => array());

        for ($monthOffset = 1; $monthOffset <= $period; $monthOffset++) {
            $currentMonth = clone $firstMonth;
            $currentMonth->add($monthOffset, Zend_Date::MONTH);
            
            // Fill in default values in case one or both queries had no matching records for this month
            $reportedCount = 0;
            $resolvedCount = 0;
            $rejectedCount = 0;
            $thisMonthName = $currentMonth->get(Zend_Date::MONTH_NAME_SHORT); // short name for month
            $thisYear = $currentMonth->get(Zend_Date::YEAR);
            
            // Merge reported counts with rejected/resolved counts for each month
            
            // Current month as number with no leading zero
            $currentMonthNumber = $currentMonth->get(Zend_Date::MONTH_SHORT);
            
            if (isset($reportedIncidents[$currentMonthNumber])) {
                $reportedCount = $reportedIncidents[$currentMonthNumber]['reported'];
            }

            if (isset($closedIncidents[$currentMonthNumber])) {
                $resolvedCount = $closedIncidents[$currentMonthNumber]['resolved'];
                $rejectedCount = $closedIncidents[$currentMonthNumber]['rejected'];
            }

            $rtnChart->addColumn(
                $thisMonthName,
                array($reportedCount, $resolvedCount, $rejectedCount)
            );
                
        }
        
        $this->view->chart = $rtnChart->export('array');
    }
    
    /**
     * A pie chart which shows how many incidents of each category are open
     */
    public function categoryAction()
    {
        $rtnChart = new Fisma_Chart();
        $rtnChart
            ->setChartType('pie')
            ->setColors(
                array(
                    '#b3b3b3',
                    '#ff3333',
                    '#ff9933',
                    '#eaed1e',
                    '#66ff66',
                    '#7197e1',
                    '#c385f1'
                )
            )
            ->setTitle('Breakdown of all open incidents by category');
    
        $categoryQuery = Doctrine_Query::create()
                         ->select('category.name, category.category, COUNT(category.id) AS count')
                         ->from('IrCategory category INDEXBY category')
                         ->innerJoin('category.SubCategories subcategory')
                         ->innerJoin('subcategory.Incident i')
                         ->where('i.status = \'open\'')
                         ->groupBy('category.id')
                         ->setHydrationMode(Doctrine::HYDRATE_ARRAY);
        $queryResults = $categoryQuery->execute();

        foreach ($queryResults as $rsltElement) {
            $colLabel = $rsltElement['category'] . ' - ' . $rsltElement['name'];
            $rtnChart->addColumn($colLabel, $rsltElement['count']);
        }
        
        $this->view->chart = $rtnChart->export('array');
    }
    
    /**
     * A bar chart which shows the number of incidents per bureau in the last 90 days
     */
    public function bureauAction()
    {
        $rtnChart = new Fisma_Chart();
        $rtnChart
            ->setChartType('bar')
            ->setColors(array('#416ed7'))
            ->setTitle('Incidents per bureau reported in the last 90 days');
    
        $cutoffDate = Zend_Date::now()->subDay(90)->toString(Fisma_Date::FORMAT_DATETIME);

        $bureauQuery = Doctrine_Query::create()
                       ->from('Incident i')
                       ->select('i.id, COUNT(*) AS count, bureau.nickname')
                       ->leftJoin('i.Organization o')
                       ->leftJoin('Organization bureau')
                       ->where('i.reportTs > ?', $cutoffDate)
                       ->andWhere('bureau.orgType = ?', array('bureau'))
                       ->andWhere('o.lft BETWEEN bureau.lft and bureau.rgt')
                       ->orderBy('bureau.nickname')
                       ->groupBy('bureau.id')
                       ->setHydrationMode(Doctrine::HYDRATE_SCALAR);
        
        $bureauQueryResult = $bureauQuery->execute();
        
        foreach ($bureauQueryResult as $thisElement) {
            $rtnChart->addColumn($thisElement['bureau_nickname'], $thisElement['i_count']);
        }

        $this->view->chart = $rtnChart->export('array'); 
    }
}
