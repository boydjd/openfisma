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
 */
 
/**
 * The dashboard controller displays the user dashboard when the user first logs
 * in.
 *
 * @package   Controller
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/mw/index.php?title=License
 */
class DashboardController extends SecurityController
{
    protected $_poam = null;
    protected $_allSystems = null;
    function init()
    {
        parent::init();
        $sys = new System();
        $this->_allSystems = $this->_me->systems;
    }
    function preDispatch()
    {
        parent::preDispatch();
        $contextSwitch = $this->_helper->getHelper('contextSwitch');
        $contextSwitch->addActionContext('totalstatus', 'xml')
                      ->addActionContext('totaltype', 'xml')
                      ->initContext();
        if (!isset($this->_poam)) {
            $this->_poam = new Poam();
        }
    }

    /**
     * The integrated dashboard which has three charts in total
     *
     * @todo fix the SQL injection at the beginning of this function
     */
    public function indexAction()
    {
        Config_Fisma::requirePrivilege('dashboard', 'read');
        
        // Check to see if we got passed a "dismiss" parameter to dismiss
        // notifications
        $request = $this->getRequest();
        $notificationsToDismiss = $request->getParam('dismiss');
        if (isset($notificationsToDismiss)) {
            $notification = new Notification();
            // Remove the notifications
            $deleteQuery = "DELETE FROM notifications
                                  WHERE id IN ($notificationsToDismiss)
                                    AND user_id = {$this->_me->id}";
                                  //  var_dump($this); die;
            $statement = $notification->getAdapter()->query($deleteQuery);

            // The most_recent_notify_ts is not updated here because no e-mails
            // are sent.
        }
        
        $newCount  = $this->_poam->search($this->_allSystems, array(
            'count' => 'count(*)'), array('status' => 'NEW'));
        $openCount = $this->_poam->search($this->_allSystems, array(
            'count' => 'count(*)'), array('status' => 'OPEN'));
        $enCount = $this->_poam->search($this->_allSystems, array(
            'count' => 'count(*)'
        ), array(
            'status' => 'EN',
            'estDateBegin' => parent::$now
        ));
        $eoCount = $this->_poam->search($this->_allSystems, array(
            'count' => 'count(*)'
        ), array(
            'status' => 'EN',
            'ontime' => 'overdue'
        ));
        $total = $this->_poam->search($this->_allSystems, array(
            'count' => 'count(*)'), array('notStatus' => 'PEND'));
        $alert = array();
        $alert['TOTAL'] = $total;
        $alert['NEW']  = $newCount;
        $alert['OPEN'] = $openCount;
        $alert['EN'] = $enCount;
        $alert['EO'] = $eoCount;
        $url = '/panel/remediation/sub/searchbox/s/search/status/';

        $this->view->url = $url;
        $this->view->alert = $alert;
        
        if (false !== strtotime($this->_me->last_login_ts)) {
            $lastLoginDate = new Zend_Date($this->_me->last_login_ts, Zend_Date::ISO_8601);
            $lastLogin = $lastLoginDate->toString('l, M j, g:i a');
            $this->view->lastLogin = $lastLogin;
            $this->view->lastLoginIp = $this->_me->last_login_ip;
            $this->view->failureCount = $this->_me->failure_count;
        } else {
            $this->view->applicationName = Config_Fisma::readSysConfig('system_name');
        }
        
        $notification = new Notification();
        $notifications = $notification->getNotifications($this->_me->id);
        if (count($notifications) > 0) {
            $this->view->notifications = $notifications;
        }
        $ids = array();
        foreach ($notifications as $notification) {
            $ids[] = $notification['id'];
        }
        $idString = urlencode(implode(',', $ids));
        $this->view->dismissUrl = "/panel/dashboard/dismiss/$idString";
        
    }
    
    /**
     * statistics per status 
     */
    public function totalstatusAction()
    {
        Config_Fisma::requirePrivilege('dashboard', 'read');
        
        $poam = $this->_poam;
        $req = $this->getRequest();
        $type = $req->getParam('type', 'pie');
        if (!in_array($type, array(
            '3d column',
            'pie'
        ))) {
            $type = 'pie';
        }
        $ret = $poam->search($this->_allSystems, array(
            'count' => 'status',
            'status'
        ));
        $eoCount = $poam->search($this->_allSystems, array(
            'count' => 'count(*)'
        ), array(
            'status' => 'EN',
            'est_date_end' => parent::$now
        ));
        $this->view->summary = array(
            'NEW' => 0,
            'OPEN' => 0,
            'EN' => 0,
            'EP' => 0,
            'ES' => 0,
            'CLOSED' => 0
        );
        foreach ($ret as $s) {
            $this->view->summary["{$s['status']}"] = $s['count'];
        }
        $this->view->summary["EO"] = $eoCount;
        $this->view->chart_type = $type;
        // Headers Required for IE+SSL (see bug #2039290) to stream XML
        header('Pragma:private');
        header('Cache-Control:private');
        $this->render($type);
    }

    /**
     * statitics per type 
     */
    public function totaltypeAction()
    {
        Config_Fisma::requirePrivilege('dashboard', 'read');
        
        $ret = $this->_poam->search($this->_allSystems, array(
            'count' => 'type',
            'type'
        ));
        $this->view->summary = array(
            'NONE' => 0,
            'CAP' => 0,
            'FP' => 0,
            'AR' => 0
        );
        foreach ($ret as $s) {
            $this->view->summary["{$s['type']}"] = $s['count'];
        }
        // Headers Required for IE+SSL (see bug #2039290) to stream XML
        header('Pragma:private');
        header('Cache-Control:private');
    }
}
