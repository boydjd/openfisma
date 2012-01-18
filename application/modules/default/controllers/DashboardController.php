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
                      ->addActionContext('chart-finding', 'json')
                      ->initContext();
    }

    /**
     * The user dashboard displays important system-wide metrics, charts, and graphs
     * 
     * @GETAllowed
     * @return void
     */
    public function indexAction()
    {
        $user = new User();
        $user = $user->getTable()->find($this->_me->id);

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

        $this->view->alert = $alert;

        // URLs for "Alerts" panel
        $baseUrl = '/finding/remediation/list?q=';

        $this->view->newFindingUrl = $baseUrl . '/denormalizedStatus/textExactMatch/NEW';
        $this->view->draftFindingUrl = $baseUrl . '/denormalizedStatus/textExactMatch/DRAFT';

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
            $msg = 'Last successful login at ' 
                   . $lastLoginDate->toString(Fisma_Date::FORMAT_WEEKDAY_MONTH_NAME_SHORT_DAY_TIME) 
                   . ' from IP address '
                   . $lastLoginInfo->lastLoginIp
                   . '.';
  
            if ('database' == Fisma::configuration()->getConfig('auth_type')) {
                if ($lastLoginInfo->failureCount > 0) {
                    $attempt = (1==$lastLoginInfo->failureCount) ? 'attempt' : 'attempts';
                    $be = (1==$lastLoginInfo->failureCount) ? 'was' : 'were';
                    $warningMsg = "There $be " 
                                  . $lastLoginInfo->failureCount
                                  . " bad login $attempt since your last login.";
 
                    $msgs[] = array('warning' => $warningMsg);
                    $msgs[] = array('warning' => $msg);
                } else {
                    $msgs[] = array('notice' => $msg);
                }
            } else {
                $msgs[] = array('notice' => $msg);
            } 
        
            $this->view->priorityMessenger($msgs);
        } else {
            $this->view->applicationName = Fisma::configuration()->getConfig('system_name');
        }

        if ($user->Notifications->count() > 0) {
            $this->view->notifications = $user->Notifications;
            $this->view->csrfToken = $this->_helper->csrf->getToken();
            $this->view->submitUrl = "javascript:Fisma.Util.formPostAction('', '/dashboard/dismiss/', " 
                                     . $this->_me->id . ')';
        }
        
        // left-side chart (bar) - Finding Status chart
        $extSrcUrl = '/dashboard/chart-finding/format/json';

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
     * Calculate the finding statistics by status
     *
     * @GETAllowed
     * @return void
     */    
    public function chartFindingAction()
    {
        $findingType = urldecode($this->getRequest()->getParam('findingType'));

        $thisChart = new Fisma_Chart();
        $thisChart->setChartType('stackedbar')
            ->setThreatLegendVisibility(true)
            ->setColors(
                array(
                    "#FF0000",
                    "#FF6600",
                    "#FFC000"
                )
            )
            ->setLayerLabels(
                array(
                    'Null',
                    'High',
                    'Moderate',
                    'Low'
                )
            );

        // Dont query if there are no organizations this user can see
        $visibleOrgs = FindingTable::getOrganizationIds();
        if (empty($visibleOrgs)) {
            // Export as array, the context switch will translate it to a JSON responce
            $this->view->chart = $thisChart->export('array');
            return;
        }

        $q = Doctrine_Query::create()
            ->select('count(f.id), threatlevel, denormalizedstatus')
            ->from('Finding f')
            ->where('f.status <> "CLOSED"')
            ->whereIn('f.responsibleOrganizationId ', FindingTable::getOrganizationIds())
            ->groupBy('f.denormalizedstatus, f.threatlevel')
            ->orderBy('f.denormalizedstatus, f.threatlevel')
            ->setHydrationMode(Doctrine::HYDRATE_ARRAY);
        $rslts = $q->execute();

        // Sort results into $sortedRslts[FindingStatusName][High/Mod/Low], where sortedRslts[][] = TheCount
        $sortedRslts = array();
        foreach ($rslts as $thisRslt) {

            if (empty($sortedRslts[$thisRslt['denormalizedStatus']])) {
                $sortedRslts[$thisRslt['denormalizedStatus']] = array();
            }

            if ($thisRslt['threatLevel'] === NULL || $thisRslt['threatLevel'] === '') {
                $thisRslt['threatLevel'] = 'NULL';
            }

            $sortedRslts[$thisRslt['denormalizedStatus']][$thisRslt['threatLevel']] = $thisRslt['count'];
        }
        
        $nonStackedLinks = array();
        
        // Go in order adding columns to chart; New,Draft,MS ISSO, MS IV&V, EN, EV ISSO, EV IV&V
        $statusArray = Finding::getAllStatuses();
        
        // Removed the string element 'CLOSED' from the $statusArray array
        if ($statusArray[count($statusArray) - 1] === 'CLOSED') {
            array_pop($statusArray);
        }
        
        foreach ($statusArray as $thisStatus) {

            // get Counts of High,MOd,Low. Also MySQL may not return 0s, assume 0 on empty
            if (!empty($sortedRslts[$thisStatus]['HIGH'])) {
                $highCount = $sortedRslts[$thisStatus]['HIGH'];
            } else {
                $highCount = 0;
            }
            
            if (!empty($sortedRslts[$thisStatus]['MODERATE'])) {
                $modCount = $sortedRslts[$thisStatus]['MODERATE'];
            } else {
                $modCount = 0;
            }
            
            if (!empty($sortedRslts[$thisStatus]['LOW'])) {
                $lowCount = $sortedRslts[$thisStatus]['LOW'];
            } else {
                $lowCount = 0;
            }
            
            if (!empty($sortedRslts[$thisStatus]['NULL'])) {
                $nullCount = $sortedRslts[$thisStatus]['NULL'];
            } else {
                $nullCount = 0;
            }

            // Prepare for a stacked-bar chart (these are the counts on each stack within the column)
            $addColumnCounts = array($nullCount, $highCount, $modCount, $lowCount);

            // Make each area of the chart link
            $basicLink = '/finding/remediation/list?q=' .
                '/denormalizedStatus/textExactMatch/#ColumnLabel#';
            $nonStackedLinks[] = $basicLink;
            $stackedLinks = array(
                '',
                $basicLink . '/threatLevel/enumIs/HIGH',
                $basicLink . '/threatLevel/enumIs/MODERATE',
                $basicLink . '/threatLevel/enumIs/LOW'
            );
            
            // Create this column as a stacked-bar chart for now (filtration later in function)
            $thisChart->addColumn(
                $thisStatus,
                $addColumnCounts,
                $stackedLinks
            );
        }

        // Show, hide and filter chart data as requested
        switch (strtolower($findingType)) {
            case "totals":
                // Crunch numbers
                $thisChart->convertFromStackedToRegular()
                    ->setLinks($nonStackedLinks)
                    ->setThreatLegendVisibility(false)
                    ->setColors(
                        array(
                            '#CECECE',
                            '#67F967',
                            '#FFCACA',
                            '#FF2424',
                            '#FF9E3D',
                            '#CACAFF',
                            '#2424FF'
                        )
                    );
                break;
            case "high, moderate, and low":
                // Remove null-count layer/stack in this stacked bar chart
                $thisChart->deleteLayer(0);
                break;
            case "high":
                // Remove null-count layer/stack in this stacked bar chart
                $thisChart->deleteLayer(0);
                // Remove the Low and Moderate columns/layers
                $thisChart->deleteLayer(2);
                $thisChart->deleteLayer(1);
                $thisChart->setColors(array('#FF0000'));
                break;
            case "moderate":
                // Remove null-count layer/stack in this stacked bar chart
                $thisChart->deleteLayer(0);
                // Remove the Low and High columns/layers
                $thisChart->deleteLayer(2);
                $thisChart->deleteLayer(0);
                $thisChart->setColors(array('#FF6600'));
                break;
            case "low":
                // Remove null-count layer/stack in this stacked bar chart
                $thisChart->deleteLayer(0);
                // Remove the Moderate and High columns/layers
                $thisChart->deleteLayer(1);
                $thisChart->deleteLayer(0);
                $thisChart->setColors(array('#FFC000'));
                break;
        }

        // Export as array, the context switch will translate it to a JSON responce
        $this->view->chart = $thisChart->export('array');
    }
    
    /**
     * Calculate the statistics by type
     * 
     * @GETAllowed
     * @return void
     */
    public function totalTypeAction()
    {
        $thisChart = new Fisma_Chart();
        $thisChart->setChartType('pie')
            ->setColors(
                array(
                    '#75FF75',
                    '#FFA347',
                    '#FF2B2B',
                    '#47D147'
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
                
                // State what the abbreviation means in the tooltip
                switch ($result['type']) {
                    case "NONE":
                        $pieSliceTooltip = 'Uncategorized Type';
                        break;
                    case "CAP":
                        $pieSliceTooltip = 'Corrective Action Plan';
                        break;
                    case "FP":
                        $pieSliceTooltip = 'False Positive';
                        break;
                    case "AR":
                        $pieSliceTooltip = 'Accepted Risk';
                        break;
                }
                
                // Formate the tooltip
                $pieSliceTooltip = '<b>' . $pieSliceTooltip . '</b><hr/>';
                $pieSliceTooltip .= '#count# total findings<br/>';
                $pieSliceTooltip .= '#percent#% of all findings are ' . $result['type'];
                
                $thisChart->addColumn(
                    $result['type'],
                    $result['typeCount'],
                    '/finding/remediation/list?q=/type/enumIs/' . $result['type'],
                    $pieSliceTooltip
                );
                
            }
        }
        
        // export as array, the context switch will translate it to a JSON responce
        $this->view->chart = $thisChart->export('array');
    }

    /**
     * Delete user's notification.
     * 
     * @return void
     */
    public function dismissAction()
    {
        $id = $this->getRequest()->getParam('id');

        $user = Doctrine::getTable('User')->find($id);
        if (empty($user)) {
            throw new Fisma_Zend_Exception('Invalid user ID');
        }
 
        $user->Notifications->delete();
        $user->mostRecentNotifyTs = Fisma::now();
        $user->save();

        $this->_redirect("/index/index");
    }
}
