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
 * A controller for the incident dashboard
 * 
 * @author     Mark E. Haase
 * @copyright  (c) Endeavor Systems, Inc. 2010 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Controller
 */
class IncidentDashboardController extends Fisma_Zend_Controller_Action_Security
{
    /**
     * Set up the JSON contexts used in this controller
     */
    public function init()
    {
        parent::init();
        
        $this->_helper->contextSwitch
                      ->setActionContext('new-incidents-data', 'json')
                      ->setActionContext('recently-updated-data', 'json')
                      ->setActionContext('recently-closed-data', 'json')
                      ->setActionContext('recent-comments-data', 'json')
                      ->initContext();

        $this->_helper->ajaxContext
                      ->setActionContext('charts', 'html')
                      ->setActionContext('new-incidents', 'html')
                      ->setActionContext('recently-updated', 'html')
                      ->setActionContext('recently-closed', 'html')
                      ->setActionContext('recent-comments', 'html')
                      ->initContext('html');
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
     * The default dashboard view, displays a tabview
     */
    public function indexAction()
    {
        $tabView = new Fisma_Yui_TabView('IncidentDashboardView');

        $tabView->addTab("Charts", "/incident-dashboard/charts");
        $tabView->addTab("New Incidents", "/incident-dashboard/new-incidents");
        $tabView->addTab("Recently Updated", "/incident-dashboard/recently-updated");
        $tabView->addTab("Recently Closed", "/incident-dashboard/recently-closed");
        $tabView->addTab("Recent Comments", "/incident-dashboard/recent-comments");

        $this->view->tabView = $tabView;
    }
    
    /**
     * Display summary charts
     */
    public function chartsAction()
    {
        $statusChart = new Fisma_Chart(450, 300, 'incidentHistory', '/incident-chart/history/format/json');
        $statusChart
            ->setTitle('Incident History')
            ->addWidget(
                'period',
                'Show:',
                'combo',
                '6 months of history',
                array(
                    '4 months of history',
                    '5 months of history',
                    '6 months of history',
                    '7 months of history',
                    '8 months of history'
                )
            );
            
        $this->view->statusChart = $statusChart->export('html');
        
        $categoryChart = new Fisma_Chart(450, 300, 'incidentCategories', '/incident-chart/category/format/json');
        $categoryChart->setTitle('Incident Categories');
        $this->view->categoryChart = $categoryChart->export('html');

        $bureauChart = new Fisma_Chart(900, 300, 'incidentBureau', '/incident-chart/bureau/format/json');
        $bureauChart->setTitle('Reported Incidents By Bureau');
        $this->view->bureauChart = $bureauChart->export('html');
    }
    
    /**
     * Show incidents in new status, with oldest "new" items sorted to the top
     * 
     * This action renders a YUI table which uses an XHR to populate its data
     */
    public function newIncidentsAction()
    {
        // Point the YUI table at the data provider action
        $this->view->baseUrl = '/incident-dashboard/new-incidents-data/format/json';
        $this->view->rowsPerPage = 10;
    }
    
    /**
     * Returns the data required for the newIncidentsAction in JSON format
     */
    public function newIncidentsDataAction()
    {
        $sortBy = Inspekt::getAlnum($this->getRequest()->getParam('sort-by', 'id'));
        $order  = Inspekt::getAlpha($this->getRequest()->getParam('order', 'asc'));
        $limit  = Inspekt::getInt($this->getRequest()->getParam('limit', 10));
        $offset = Inspekt::getInt($this->getRequest()->getParam('offset', 0));
            
        $newIncidentsQuery = Doctrine::getTable('Incident')->getUserIncidentQuery($this->_me, $this->_acl)
                                  ->select('i.id, i.reportTs, i.additionalInfo, i.piiInvolved')
                                  ->andWhere('i.status = ?', "new")
                                  ->orderBy("i.$sortBy $order")
                                  ->setHydrationMode(Doctrine::HYDRATE_ARRAY);

        // Get total count of results
        $this->view->count = $newIncidentsQuery->count();
        
        // Add limit/offset and execute
        $newIncidentsQuery->limit($limit)
                          ->offset($offset);
        
        $this->view->newIncidents = $newIncidentsQuery->execute();
    }

    /**
     * Show incidents which have been updated in the last 48 hours
     */
    public function recentlyUpdatedAction()
    {
        // Point the YUI table at the data provider action
        $this->view->baseUrl = '/incident-dashboard/recently-updated-data/format/json';
        $this->view->rowsPerPage = 10;
    }       
    
    /**
     * Returns the data required for the recentlyUpdatedAction in JSON format
     */
    public function recentlyUpdatedDataAction()
    {
        $sortBy = Inspekt::getAlnum($this->getRequest()->getParam('sort-by', 'modifiedTs'));
        $order  = Inspekt::getAlpha($this->getRequest()->getParam('order', 'desc'));
        $limit  = Inspekt::getInt($this->getRequest()->getParam('limit', 10));
        $offset = Inspekt::getInt($this->getRequest()->getParam('offset', 0));
        
        // Calculate the timestamp for 48 hours ago
        $now = Zend_Date::now();
        $cutoffTime = $now->sub(48, Zend_Date::HOUR)->get(Zend_Date::ISO_8601);
            
        $newIncidentsQuery = Doctrine::getTable('Incident')->getUserIncidentQuery($this->_me, $this->_acl)
                                  ->select('i.id, i.additionalInfo, i.modifiedTs')
                                  ->andWhere('i.modifiedTs > ?', $cutoffTime)
                                  ->orderBy("i.$sortBy $order")
                                  ->setHydrationMode(Doctrine::HYDRATE_ARRAY);

        // Get total count of results
        $this->view->count = $newIncidentsQuery->count();
        
        // Add limit/offset and execute
        $newIncidentsQuery->limit($limit)
                          ->offset($offset);
        
        $this->view->newIncidents = $newIncidentsQuery->execute();
    }
    
    /**
     * Show incidents which have been closed in the last 5 days
     */
    public function recentlyClosedAction()
    {
        // Point the YUI table at the data provider action
        $this->view->baseUrl = '/incident-dashboard/recently-closed-data/format/json';
        $this->view->rowsPerPage = 10;
    }
    
    /**
     * Returns the data required for the recentlyClosedAction in JSON format
     */
    public function recentlyClosedDataAction()
    {
        $sortBy = Inspekt::getAlnum($this->getRequest()->getParam('sort-by', 'closedTs'));
        $order  = Inspekt::getAlpha($this->getRequest()->getParam('order', 'desc'));
        $limit  = Inspekt::getInt($this->getRequest()->getParam('limit', 10));
        $offset = Inspekt::getInt($this->getRequest()->getParam('offset', 0));
        
        // Calculate the timestamp for 5 days ago
        $now = Zend_Date::now();
        $cutoffTime = $now->sub(5, Zend_Date::DAY)->get(Zend_Date::ISO_8601);
            
        $newIncidentsQuery = Doctrine::getTable('Incident')->getUserIncidentQuery($this->_me, $this->_acl)
                                  ->select('i.id, i.additionalInfo, i.closedTs, i.resolution')
                                  ->andWhere('i.status = ?', 'closed')
                                  ->andWhere('i.closedTs > ?', $cutoffTime)
                                  ->orderBy("i.$sortBy $order")
                                  ->setHydrationMode(Doctrine::HYDRATE_ARRAY);

        // Get total count of results
        $this->view->count = $newIncidentsQuery->count();
        
        // Add limit/offset and execute
        $newIncidentsQuery->limit($limit)
                          ->offset($offset);
        
        $this->view->newIncidents = $newIncidentsQuery->execute();
    }

    /**
     * Show incidents which have comments added in the last 48 hours
     */
    public function recentCommentsAction()
    {
        // Point the YUI table at the data provider action
        $this->view->baseUrl = '/incident-dashboard/recent-comments-data/format/json';
        $this->view->rowsPerPage = 10;
    }
    
    /**
     * Returns the data required for the recentCommentsAction in JSON format
     */
    public function recentCommentsDataAction()
    {
        $sortBy = Inspekt::getAlnum($this->getRequest()->getParam('sort-by', 'id'));
        $order  = Inspekt::getAlpha($this->getRequest()->getParam('order', 'desc'));
        $limit  = Inspekt::getInt($this->getRequest()->getParam('limit', 10));
        $offset = Inspekt::getInt($this->getRequest()->getParam('offset', 0));
        
        // Calculate the timestamp for 48 hours ago
        $now = Zend_Date::now();
        $cutoffTime = $now->sub(48, Zend_Date::HOUR)->get(Zend_Date::ISO_8601);
            
        $newIncidentsQuery = Doctrine::getTable('Incident')->getUserIncidentQuery($this->_me, $this->_acl)
                                  ->select('i.id, i.additionalInfo, count(c.id) AS count')
                                  ->innerJoin('i.IncidentComment c')
                                  ->andWhere('c.createdTs > ?', $cutoffTime)
                                  ->groupBy('i.id')
                                  ->orderBy("i.$sortBy $order")
                                  ->setHydrationMode(Doctrine::HYDRATE_ARRAY);

        // Get total count of results
        $this->view->count = $newIncidentsQuery->count();

        // Add limit/offset and execute
        $newIncidentsQuery->limit($limit)
                          ->offset($offset);
        
        $this->view->newIncidents = $newIncidentsQuery->execute();
    }
}
