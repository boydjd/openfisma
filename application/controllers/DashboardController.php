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
     * preDispatch() - invoked before each Actions
     */
    function preDispatch()
    {
        parent::preDispatch();
        $contextSwitch = $this->_helper->getHelper('contextSwitch');
        $contextSwitch->addActionContext('totalStatus', 'xml')
                      ->addActionContext('totalType', 'xml')
                      ->initContext();
    }

    /**
     * The user dashboard displays important system-wide metrics, charts, and graphs
     */
    public function indexAction()
    {
        Fisma_Acl::requirePrivilege('dashboard', 'read');
        $user = User::currentUser();
        
        // Check to see if we got passed a "dismiss" parameter to dismiss notifications
        $dismiss = $this->getRequest()->getParam('dismiss');
        if (isset($dismiss) && 'notifications' == $dimiss) {
            $user->Notifications->delete();
            $user->notifyTs = new Zend_Date();
            $user->save();
        }

        // Calculate the dashboard statistics
        $totalFindingsQuery = Doctrine_Query::create()
                            ->select('COUNT(*) as count')
                            ->from('Finding');
        $result = $totalFindingsQuery->fetchOne();
        $alert['TOTAL']  = $result['count'];
        
        $newFindingsQuery = Doctrine_Query::create()
                            ->select('COUNT(*) as count')
                            ->from('Finding')
                            ->where('status = ?', 'NEW');
        $result = $newFindingsQuery->fetchOne();
        $alert['NEW']  = $result['count'];
        
        $draftFindingsQuery = Doctrine_Query::create()
                            ->select('COUNT(*) as count')
                            ->from('Finding')
                            ->where('status = ?', 'DRAFT');
        $result = $draftFindingsQuery->fetchOne();
        $alert['DRAFT']  = $result['count'];
        
        $enFindingsQuery = Doctrine_Query::create()
                            ->select('COUNT(*) as count')
                            ->from('Finding')
                            ->where('status = ? and nextDueDate >= NOW()', 'EN');
        $result = $draftFindingsQuery->fetchOne();
        $alert['EN']  = $result['count'];

        $eoFindingsQuery = Doctrine_Query::create()
                            ->select('COUNT(*) as count')
                            ->from('Finding')
                            ->where('status = ? AND nextDueDate < NOW()', 'DRAFT');
        $result = $draftFindingsQuery->fetchOne();
        $alert['EO']  = $result['count'];
        
        $url = '/panel/remediation/sub/searchbox/s/search/status/';

        $this->view->url = $url;
        $this->view->alert = $alert;
        
        // Look up the user's last login information. If it's their first time logging in, then the view
        // script will show a different message.
        if (isset($user->lastLoginTs)) {
            $lastLoginDate = new Zend_Date($this->_me->lastLoginTs, Zend_Date::ISO_8601);
            $this->view->lastLogin = $lastLoginDate->toString('l, M j, g:i a');
            $this->view->lastLoginIp = $this->_me->lastLoginIp;
            $this->view->failureCount = $this->_me->failureCount;
        } else {
            $this->view->applicationName = Configuration::getConfig('system_name');
        }
        
        // Alert the user if there are notifications pending
        $user = User::currentUser();
        if ($user->Notifications->count() > 0) {
            $this->view->notifications = $user->Notifications;
            $this->view->dismissUrl = "/panel/dashboard/dismiss/notifications";
        }
    }
    
    /**
     * statistics per status 
     */
    public function totalStatusAction()
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
    public function totalTypeAction()
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
