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
 * Create XML files for flash charts
 * 
 * @author     Mark E. Haase
 * @copyright  (c) Endeavor Systems, Inc. 2010 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Controller
 * @version    $Id$
 */
class IncidentChartController extends SecurityController
{
    /**
     * Set contexts for this controller's actions
     */
    public function init()
    {
        parent::init();
        
        $this->_helper->contextSwitch
                      ->setActionContext('reported', 'xml')
                      ->setActionContext('category', 'xml')
                      ->initContext();
    }
    
    /**
     * A bar chart which shows how many incidents were reported on a month-by-month basis in recent history
     */
    public function reportedAction()
    {
        // $period is the number of months of history to limit the results to
        $period = $this->getRequest()->getParam('period');
        
        if (!is_int((int)$period)) {
            throw new Fisma_Exception("Incident status chart period parameter must be an integer.");
        }
        
        // Calculate the cutoff date based on the period
        $today = Zend_Date::now();
        
        $cutoffDate = $today->sub($period, Zend_Date::MONTH)->get('Y-m-d');

        // Get chart data
        $reportedIncidentsQuery = Doctrine_Query::create()
                                  ->select('COUNT(i.id) AS count')
                                  ->addSelect('YEAR(i.reportTs) AS year')
                                  ->addSelect('MONTH(i.reportTs) AS monthNumber')
                                  ->addSelect("DATE_FORMAT(i.reportTs, '%b') AS month")
                                  ->from('Incident i')
                                  ->where("i.reportTs > '$cutoffDate'")
                                  ->groupBy('month')
                                  ->orderBy('year, monthNumber')
                                  ->setHydrationMode(Doctrine::HYDRATE_ARRAY);

        $this->view->results = $reportedIncidentsQuery->execute();
    }
    
    /**
     * A pie chart which shows how many incidents of each category are open
     */
    public function categoryAction()
    {
        $categoryQuery = Doctrine_Query::create()
                         ->select('category.name, category.category, COUNT(category.id) AS count')
                         ->from('IrCategory category INDEXBY category')
                         ->innerJoin('category.SubCategories subcategory')
                         ->innerJoin('subcategory.Incident i')
                         ->groupBy('category.id')
                         ->setHydrationMode(Doctrine::HYDRATE_ARRAY);
        
        $this->view->categoryCounts = $categoryQuery->execute();
    }
}
