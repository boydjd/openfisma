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
        $this->_acl->requirePrivilege('dashboard', 'read');
        
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
        $draftCount = $this->_poam->search($this->_allSystems, array(
            'count' => 'count(*)'), array('status' => 'DRAFT'));
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
        $alert['DRAFT'] = $draftCount;
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
        $this->_acl->requirePrivilege('dashboard', 'read');
        
        $poam = $this->_poam;
        $req = $this->getRequest();
        $type = $req->getParam('type', 'pie');
        if (!in_array($type, array(
            '3d column',
            'pie'
        ))) {
            $type = 'pie';
        }
        $this->view->chart_type = $type;
        
        //count normal status ( NEW, DRAFT, EN )
        $arrPoamInfo = $this->_poam->search($this->_me->systems, array(
            'count' => array(
                'status'
            ) ,
            'status',
            'type',
            'system_id'
        ));

        $arrTotal = array('NEW'=>0, 'DRAFT'=>0, 'EN'=>0);
        foreach ($arrPoamInfo as $arrPoam) {
            if (in_array($arrPoam['status'], array_keys($arrTotal))) {
                $arrTotal[$arrPoam['status']] = $arrPoam['count'];
            }
        }
        $arrTmpTotal = array('NEW'=>$arrTotal['NEW'], 'DRAFT'=>$arrTotal['DRAFT']);
        $objEval = new Evaluation();
        //count mitigation strategy status 
        $arrMsaEvalList = $objEval->getEvalList('ACTION');
        foreach ($arrMsaEvalList as $arrMsaEvalRow) {
            $arrMsaPoam = $this->_poam->search($this->_me->systems,
                array('count' => 'count(*)'),
                array('mp' => $arrMsaEvalRow['precedence_id'], 'name'));

            $description[$arrMsaEvalRow['nickname']] = $arrMsaEvalRow['name'];
            if (!empty($arrMsaPoam)) {
                $arrTmpTotal = array_merge($arrTmpTotal,
                               array($arrMsaEvalRow['nickname']=>$arrMsaPoam));
            } else {
                $arrTmpTotal = array_merge($arrTmpTotal, array($arrMsaEvalRow['nickname']=>0));
            }
        }
        $arrTmpTotal = array_merge($arrTmpTotal, array('EN'=>$arrTotal['EN']));
        //count evidence status
        $arrEpEvalList = $objEval->getEvalList('EVIDENCE');
        foreach ($arrEpEvalList as $arrEpEvalRow) {
            $arrEpPoam = $this->_poam->search($this->_me->systems,
                array('count' => 'count(*)'),
                array('ep' => $arrEpEvalRow['precedence_id'], 'name'));

            $description[$arrEpEvalRow['nickname']] = $arrEpEvalRow['name'];
            if (!empty($arrEpPoam)) {
                $arrTmpTotal = array_merge($arrTmpTotal,
                               array($arrEpEvalRow['nickname']=>$arrEpPoam));
            } else {
                $arrTmpTotal = array_merge($arrTmpTotal, array($arrEpEvalRow['nickname']=>0));
            }
        }
        $this->view->summary = $arrTmpTotal;

        $description['EN'] = 'Evidence Needed';
        $this->view->description = $description;
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
        $this->_acl->requirePrivilege('dashboard', 'read');
        
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
