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
 * @version    $Id$
 */
class IncidentDashboardController extends SecurityController
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
    }

    /**
     * Verify that this module is enabled
     */
    public function preDispatch()
    {
        $module = Doctrine::getTable('Module')->findOneByName('Incident Reporting');
        
        if (!$module->enabled) {
            throw new Fisma_Exception('This module is not enabled.');
        }
        
        Fisma_Acl::requireArea('incident');
    }

    /**
     * The default dashboard view, displays a tabview
     */
    public function indexAction()
    {
        $this->_helper->layout->setLayout('layout');

        $this->_helper->actionStack('header', 'panel');

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
        $this->view->statusChart = new Fisma_Chart('/incident-chart/history/period/6/format/xml', 450, 300);
        $this->view->categoryChart = new Fisma_Chart('/incident-chart/category/format/xml', 450, 300);
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
        $sortBy = $this->getRequest()->getParam('sort-by', 'id');
        $order = $this->getRequest()->getParam('order', 'asc');
        $limit = $this->getRequest()->getParam('limit', 10);
        $offset = $this->getRequest()->getParam('offset', 0);
            
        $newIncidentsQuery = IncidentController::getUserIncidentQuery()
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
        $sortBy = $this->getRequest()->getParam('sort-by', 'modifiedTs');
        $order = $this->getRequest()->getParam('order', 'desc');
        $limit = $this->getRequest()->getParam('limit', 10);
        $offset = $this->getRequest()->getParam('offset', 0);
        
        // Calculate the timestamp for 48 hours ago
        $now = Zend_Date::now();
        $cutoffTime = $now->sub(48, Zend_Date::HOUR)->get(Zend_Date::ISO_8601);
            
        $newIncidentsQuery = IncidentController::getUserIncidentQuery()
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
        $sortBy = $this->getRequest()->getParam('sort-by', 'closedTs');
        $order = $this->getRequest()->getParam('order', 'desc');
        $limit = $this->getRequest()->getParam('limit', 10);
        $offset = $this->getRequest()->getParam('offset', 0);
        
        // Calculate the timestamp for 5 days ago
        $now = Zend_Date::now();
        $cutoffTime = $now->sub(5, Zend_Date::DAY)->get(Zend_Date::ISO_8601);
            
        $newIncidentsQuery = IncidentController::getUserIncidentQuery()
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
        $sortBy = $this->getRequest()->getParam('sort-by', 'id');
        $order = $this->getRequest()->getParam('order', 'desc');
        $limit = $this->getRequest()->getParam('limit', 10);
        $offset = $this->getRequest()->getParam('offset', 0);
        
        // Calculate the timestamp for 48 hours ago
        $now = Zend_Date::now();
        $cutoffTime = $now->sub(48, Zend_Date::HOUR)->get(Zend_Date::ISO_8601);
            
        $newIncidentsQuery = IncidentController::getUserIncidentQuery()
                             ->select('i.id, i.additionalInfo, count(c.id) AS count')
                             ->innerJoin('i.Comments c')
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
