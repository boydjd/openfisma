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
 * The dashboard controller displays the user dashboard when the user first logs
 * in. This controller also produces graphical charts in conjunction with the SWF Charts
 * package.
 *
 * @author     Jim Chen <xhorse@users.sourceforge.net>
 * @copyright  (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Controller
 * @version    $Id$
 */
class DashboardController extends Fisma_Zend_Controller_Action_Security
{
    /**
     * My OrgSystem ids
     *
     * Not initialized until preDispatch
     *
     * @var array
     */
    private $_myOrgSystemIds = null;
    
    /**
     * Invoked before each Actions
     * 
     * @return void
     */
    function preDispatch()
    {
        parent::preDispatch();

        $this->_acl->requireArea('dashboard');

        $orgSystems = $this->_me->getOrganizationsByPrivilege('finding', 'read')->toArray();
        $orgSystemIds = array(0);
        foreach ($orgSystems as $orgSystem) {
            $orgSystemIds[] = $orgSystem['id'];
        }
        $this->_myOrgSystemIds = $orgSystemIds;

        $this->_helper->fismaContextSwitch()
                      ->addActionContext('total-type', 'json')
                      ->initContext();
    }

    /**
     * The user dashboard displays important system-wide metrics, charts, and graphs
     * 
     * @return void
     */
    public function indexAction()
    {
        $user = new User();
        $user = $user->getTable()->find($this->_me->id);
        // Check to see if we got passed a "dismiss" parameter to dismiss notifications
        $dismiss = $this->_request->getParam('dismiss');
        if (isset($dismiss) && 'notifications' == $dismiss) {
            $user->Notifications->delete();
            $user->mostRecentNotifyTs = Fisma::now();
            $user->save();
        }

        // Calculate the dashboard statistics
        $totalFindingsQuery = Doctrine_Query::create()
                            ->select('COUNT(*) as count')
                            ->from('Finding f')
                            ->whereIn('f.responsibleorganizationid', $this->_myOrgSystemIds);
        $result = $totalFindingsQuery->fetchOne();
        $alert['TOTAL']  = $result['count'];
        
        $newFindingsQuery = Doctrine_Query::create()
                            ->select('COUNT(*) as count')
                            ->from('Finding f')
                            ->where('f.status = ?', 'NEW')
                            ->andWhereIn('f.responsibleorganizationid', $this->_myOrgSystemIds);
        $result = $newFindingsQuery->fetchOne();
        $alert['NEW']  = $result['count'];
        
        $draftFindingsQuery = Doctrine_Query::create()
                            ->select('COUNT(*) as count')
                            ->from('Finding f')
                            ->where('f.status = ?', 'DRAFT')
                            ->andWhereIn('f.responsibleorganizationid', $this->_myOrgSystemIds);
        $result = $draftFindingsQuery->fetchOne();
        $alert['DRAFT']  = $result['count'];

        $enFindingsQuery = Doctrine_Query::create()
                            ->select('COUNT(*) as count')
                            ->from('Finding f')
                            ->where('f.status = ? AND DATEDIFF(NOW(), f.nextDueDate) <= 0', 'EN')
                            ->andWhereIn('f.responsibleorganizationid', $this->_myOrgSystemIds);
        $result = $enFindingsQuery->fetchOne();
        $alert['EN']  = $result['count'];

        $eoFindingsQuery = Doctrine_Query::create()
                            ->select('COUNT(*) as count')
                            ->from('Finding f')
                            ->where('f.status = ? AND DATEDIFF(NOW(), f.nextDueDate) > 0', 'EN')
                            ->andWhereIn('f.responsibleorganizationid', $this->_myOrgSystemIds);
        $result = $eoFindingsQuery->fetchOne();
        $alert['EO']  = $result['count'];

        if ($this->_acl->hasPrivilegeForClass('approve', 'Finding')) {
            $pendingFindingsQuery = Doctrine_Query::create()
                                    ->select('COUNT(*) as count')
                                    ->from('Finding f')
                                    ->where('f.status = ?', 'PEND')
                                    ->andWhereIn('f.responsibleorganizationid', $this->_myOrgSystemIds);
            $result = $pendingFindingsQuery->fetchOne();
            $alert['PEND'] = $result['count'];
        }

        $this->view->alert = $alert;

        // URLs for "Alerts" panel
        $baseUrl = '/finding/remediation/list/queryType/advanced';

        $this->view->newFindingUrl = $baseUrl . '/denormalizedStatus/textExactMatch/NEW';
        $this->view->draftFindingUrl = $baseUrl . '/denormalizedStatus/textExactMatch/DRAFT';
        $this->view->pendingFindingUrl = '/finding/index/approve';
        
        $today = Zend_Date::now()->toString('yyyy-MM-dd');        
        $this->view->evidenceNeededOntimeUrl = $baseUrl 
                                             . '/denormalizedStatus/textExactMatch/EN'
                                             . '/nextDueDate/dateAfter/'
                                             . $today;
        $this->view->evidenceNeededOverdueUrl = $baseUrl 
                                             . '/denormalizedStatus/textExactMatch/EN'
                                             . '/nextDueDate/dateBefore/'
                                             . $today;
        
        // Look up the last login information. If it's their first time logging in, then the view
        // script will show a different message.
        $lastLoginInfo = new Zend_Session_Namespace('last_login_info');
        
        if (isset($lastLoginInfo->lastLoginTs)) {
            $lastLoginDate = new Zend_Date($lastLoginInfo->lastLoginTs, Zend_Date::ISO_8601);
            $this->view->lastLoginTs = $lastLoginDate->toString(Fisma_Date::FORMAT_WEEKDAY_MONTH_NAME_SHORT_DAY_TIME);
            $this->view->lastLoginIp = $lastLoginInfo->lastLoginIp;
            $this->view->failureCount = $lastLoginInfo->failureCount;
        } else {
            $this->view->applicationName = Fisma::configuration()->getConfig('system_name');
        }
        
        if ($user->Notifications->count() > 0) {
            $this->view->notifications = $user->Notifications;
            $this->view->dismissUrl = "/dashboard/index/dismiss/notifications";
        }
        
        // left-side chart (bar) - Finding Status chart
        $extSrcUrl = '/finding/dashboard/chart-finding/format/json';

        $chartTotalStatus = new Fisma_Chart(380, 275, 'chartTotalStatus', $extSrcUrl);
        $chartTotalStatus
            ->setTitle('Finding Status Distribution')
            ->addWidget(
                'findingType',
                'Threat Level:',
                'combo',
                'Totals',
                array(
                    'Totals',
                    'High, Moderate, and Low',
                    'High',
                    'Moderate',
                    'Low'
                )
            );

        $this->view->chartTotalStatus = $chartTotalStatus->export();
        
        // right-side chart (pie) - Mit Strategy Distribution chart
        $chartTotalType = new Fisma_Chart(380, 275, 'chartTotalType', '/dashboard/total-type/format/json');
        $chartTotalType
            ->setTitle('Mitigation Strategy Distribution');

        $this->view->chartTotalType = $chartTotalType->export();
        
    }
    
    /**
     * Calculate the statistics by type
     * 
     * @return void
     */
    public function totalTypeAction()
    {
        $thisChart = new Fisma_Chart();
        $thisChart
            ->setTitle('Mitigation Strategy Distribution')
            ->setChartType('pie')
            ->setColors(
                array(
                    '#FFA347',
                    '#75FF75',
                    '#47D147',
                    '#FF2B2B'
                )
            );
            
        $summary = array(
            'NONE' => 0,
            'CAP' => 0,
            'FP' => 0,
            'AR' => 0
        );
        
        $q = Doctrine_Query::create()
            ->select('f.type')
            ->addSelect('COUNT(f.type) as typeCount')
            ->from('Finding f')
            ->whereIn('f.responsibleOrganizationId ', FindingTable::getOrganizationIds())
            ->groupBy('f.type');
        $results =$q->execute()->toArray();
        $types = array_keys($summary);
        foreach ($results as $result) {
            if (in_array($result['type'], $types)) {
                $thisChart->addColumn(
                    $result['type'],
                    $result['typeCount'],
                    '/finding/remediation/list/queryType/advanced/type/enumIs/' . $result['type']
                );
            }
        }
        
        // export as array, the context switch will translate it to a JSON responce
        $this->view->chart = $thisChart->export('array');
    }
}
