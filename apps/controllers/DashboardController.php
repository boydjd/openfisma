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
    protected $_all_systems = null;
    function init()
    {
        parent::init();
        $sys = new System();
        $this->_all_systems = $this->_me->systems;
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
     */
    public function indexAction()
    {
        $new_count  = $this->_poam->search($this->_all_systems, array(
            'count' => 'count(*)'), array('status' => 'NEW'));
        $open_count = $this->_poam->search($this->_all_systems, array(
            'count' => 'count(*)'), array('status' => 'OPEN'));
        $en_count = $this->_poam->search($this->_all_systems, array(
            'count' => 'count(*)'
        ), array(
            'status' => 'EN',
            'est_date_begin' => parent::$now
        ));
        $eo_count = $this->_poam->search($this->_all_systems, array(
            'count' => 'count(*)'
        ), array(
            'status' => 'EN',
            'est_date_end' => parent::$now
        ));
        $total = $this->_poam->search($this->_all_systems, array(
            'count' => 'count(*)'
        ));
        $alert = array();
        $alert['TOTAL'] = $total;
        $alert['NEW']  = $new_count;
        $alert['OPEN'] = $open_count;
        $alert['EN'] = $en_count;
        $alert['EO'] = $eo_count;
        $url = '/panel/remediation/sub/searchbox/s/search/status/';

        $this->view->url = $url;
        $this->view->alert = $alert;

        $lastLogin = new Zend_Date($this->_me->last_login_ts);
        $this->view->lastLogin = $lastLogin;
        $this->view->lastLoginIp = $this->_me->last_login_ip;
        $this->view->failureCount = $this->_me->failure_count;
        $this->render();
    }
    /**
     * statistics per status 
     */
    public function totalstatusAction()
    {
        $poam = $this->_poam;
        $req = $this->getRequest();
        $type = $req->getParam('type', 'pie');
        if (!in_array($type, array(
            '3d column',
            'pie'
        ))) {
            $type = 'pie';
        }
        $ret = $poam->search($this->_all_systems, array(
            'count' => 'status',
            'status'
        ));
        $eo_count = $poam->search($this->_all_systems, array(
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
        foreach($ret as $s) {
            $this->view->summary["{$s['status']}"] = $s['count'];
        }
        $this->view->summary["EO"] = $eo_count;
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
        $ret = $this->_poam->search($this->_all_systems, array(
            'count' => 'type',
            'type'
        ));
        $this->view->summary = array(
            'NONE' => 0,
            'CAP' => 0,
            'FP' => 0,
            'AR' => 0
        );
        foreach($ret as $s) {
            $this->view->summary["{$s['type']}"] = $s['count'];
        }
        // Headers Required for IE+SSL (see bug #2039290) to stream XML
        header('Pragma:private');
        header('Cache-Control:private');
        $this->render();
    }
}
