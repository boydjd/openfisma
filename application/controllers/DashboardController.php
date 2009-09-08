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
 * @author    Jim Chen <xhorse@users.sourceforge.net>
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/mw/index.php?title=License
 * @version   $Id$
 * @package   Controller
 */
 
/**
 * The dashboard controller displays the user dashboard when the user first logs
 * in. This controller also produces graphical charts in conjunction with the SWF Charts
 * package.
 *
 * @package   Controller
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/mw/index.php?title=License
 */
class DashboardController extends SecurityController
{
    /**
     * my OrgSystem ids
     *
     * @var array
     */
    private $_myOrgSystemIds = null;
    
    public function init()
    {
        parent::init();
        $orgSystems = $this->_me->getOrganizations()->toArray();
        $orgSystemIds = array(0);
        foreach ($orgSystems as $orgSystem) {
            $orgSystemIds[] = $orgSystem['id'];
        }
        $this->_myOrgSystemIds = $orgSystemIds;
    }
    
    /**
     * preDispatch() - invoked before each Actions
     */
    function preDispatch()
    {
        parent::preDispatch();
        $contextSwitch = $this->_helper->getHelper('contextSwitch');
        // Headers Required for IE+SSL (see bug #2039290) to stream XML
        $contextSwitch->addHeader('xml', 'Pragma', 'private')
                      ->addHeader('xml', 'Cache-Control', 'private')
                      ->addActionContext('totalstatus', 'xml')
                      ->addActionContext('totaltype', 'xml')
                      ->initContext();
    }

    /**
     * The user dashboard displays important system-wide metrics, charts, and graphs
     */
    public function indexAction()
    {
        Fisma_Acl::requirePrivilege('area', 'dashboard');
        $user = new User();
        $user = $user->getTable()->find($this->_me->id);
        // Check to see if we got passed a "dismiss" parameter to dismiss notifications
        $dismiss = $this->_request->getParam('dismiss');
        if (isset($dismiss) && 'notifications' == $dismiss) {
            $user->Notifications->delete();
            $user->mostRecentNotifyTs = Zend_Date::now()->toString('Y-m-d H:i:s');
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
        
        $url = '/panel/remediation/sub/searchbox/status/';

        $this->view->url = $url;
        $this->view->alert = $alert;
        
        // Look up the user's last login information. If it's their first time logging in, then the view
        // script will show a different message.
        if (isset($user->lastLoginTs)) {
            $lastLoginDate = new Zend_Date($this->_me->lastLoginTs, Zend_Date::ISO_8601);
            $this->view->lastLoginTs = $lastLoginDate->toString('l, M j, g:i a');
            $this->view->lastLoginIp = $this->_me->lastLoginIp;
            $this->view->oldFailureCount = $this->_me->oldFailureCount;
        } else {
            $this->view->applicationName = Configuration::getConfig('system_name');
        }
        
        if ($user->Notifications->count() > 0) {
            $this->view->notifications = $user->Notifications;
            $this->view->dismissUrl = "/panel/dashboard/dismiss/notifications";
        }
    }
    
    /**
     * statistics per status 
     */
    public function totalstatusAction()
    {
        Fisma_Acl::requirePrivilege('area', 'dashboard');
        
        $q = Doctrine_Query::create()
             ->select('f.status, e.nickname')
             ->addSelect('COUNT(f.status) AS statusCount, COUNT(e.nickname) AS subStatusCount')
             ->from('Finding f')
             ->leftJoin('f.CurrentEvaluation e')
             ->whereIn('f.responsibleOrganizationId ', $this->_myOrgSystemIds)
             ->groupBy('f.status, e.nickname')
             ->setHydrationMode(Doctrine::HYDRATE_ARRAY);
        $results = $q->execute();
        
        // initialize 3 basic status
        $arrTotal = array('NEW' => 0, 'DRAFT' => 0);
        // initialize current evaluation status
        $q = Doctrine_Query::create()
             ->select()
             ->from('Evaluation e')
             // keep the the 'action' approvalGroup is first fetched
             ->orderBy('e.approvalGroup ASC')
             ->setHydrationMode(Doctrine::HYDRATE_ARRAY);
        $evaluations = $q->execute();

        foreach ($evaluations as $evaluation) {
            if ($evaluation['approvalGroup'] == 'evidence') {
                $arrTotal['EN'] = 0;
            }
            $arrTotal[$evaluation['nickname']] = 0;
        }

        foreach ($results as $result) {
            if (in_array($result['status'], array_keys($arrTotal))) {
                $arrTotal[$result['status']] = $result['statusCount'];
            } else {
                $arrTotal[$result['CurrentEvaluation']['nickname']] = $result['subStatusCount'];
            }
        }

        $this->view->summary = $arrTotal;
    }

    /**
     * statitics per type 
     */
    public function totaltypeAction()
    {
        Fisma_Acl::requirePrivilege('area', 'dashboard');
        $this->view->summary = array(
            'NONE' => 0,
            'CAP' => 0,
            'FP' => 0,
            'AR' => 0
        );
        
        $q = Doctrine_Query::create()
            ->select('f.type')
            ->addSelect('COUNT(f.type) as typeCount')
            ->from('Finding f')
            ->whereIn('f.responsibleOrganizationId ', $this->_myOrgSystemIds)
            ->groupBy('f.type');
        $results =$q->execute()->toArray();
        $types = array_keys($this->view->summary);
        foreach ($results as $result) {
            if (in_array($result['type'], $types)) {
                $this->view->summary["{$result['type']}"] = $result['typeCount'];
            }
        }
    }
}
