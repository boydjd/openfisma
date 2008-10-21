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
 * @author    Ryan Yang <ryan@users.sourceforge.net>
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/mw/index.php?title=License
 * @version   $Id$
 */

/**
 * The report controller creates the multitude of reports available in
 * OpenFISMA.
 *
 * @package   Controller
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/mw/index.php?title=License
 */
class ReportController extends PoamBaseController
{
    /**
     * init() - Create the additional pdf and xls contexts for this class.
     */
    public function init() {
        parent::init();
        $swCtx = $this->_helper->contextSwitch();
        if (!$swCtx->hasContext('pdf')) {
            $swCtx->addContext('pdf', array(
                'suffix' => 'pdf',
                'headers' => array(
                    'Content-Disposition' => 
                        'attachement;filename="export.pdf"',
                    'Content-Type' => 'application/pdf'
                )
            ));
        }
        if (!$swCtx->hasContext('xls')) {
            $swCtx->addContext('xls', array(
                'suffix' => 'xls'
            ));
        }
    }
    
    /**
     * preDispatch() - Add the action contexts for this controller.
     */
    public function preDispatch() {
        parent::preDispatch();
        $this->req = $this->getRequest();
        $swCtx = $this->_helper->contextSwitch();
        $swCtx->addActionContext('poam', array('pdf', 'xls'))
              ->addActionContext('fisma', array('pdf', 'xls'))
              ->addActionContext('blscr', array('pdf', 'xls'))
              ->addActionContext('fips', array('pdf', 'xls'))
              ->addActionContext('prods', array('pdf', 'xls'))
              ->addActionContext('swdisc', array('pdf', 'xls'))
              ->addActionContext('total', array('pdf', 'xls'))
              ->addActionContext('overdue', array('pdf', 'xls'))
              ->addActionContext('pluginreport', array('pdf', 'xls'))
              ->initContext();
    }

    /**
     * fismaAction() - Genenerate fisma report
     */
    public function fismaAction()
    {
        $req = $this->getRequest();
        $criteria['year'] = $req->getParam('y');
        $criteria['quarter'] = $req->getParam('q');
        $criteria['systemId'] = $systemId = $req->getParam('system');
        $criteria['startdate'] = $req->getParam('startdate');
        $criteria['enddate'] = $req->getParam('enddate');
        $this->view->assign('system_list', $this->_systemList);
        $this->view->assign('criteria', $criteria);
        $dateBegin = '';
        $dateEnd = '';
        if ('search' == $req->getParam('s') || 
            'pdf' == $req->getParam('format') || 
            'xls' == $req->getParam('format')) {
            if (!empty($criteria['startdate']) && 
                !empty($criteria['enddate'])) {
                $dateBegin = new 
                    Zend_Date($criteria['startdate'], Zend_Date::DATES);
                $dateEnd = new 
                    Zend_Date($criteria['enddate'], Zend_Date::DATES);
            }
            if (!empty($criteria['year'])) {
                if (!empty($criteria['quarter'])) {
                    switch ($criteria['quarter']) {
                    case 1:
                        $startdate = $criteria['year'] . '-01-01';
                        $enddate = $criteria['year'] . '-03-31';
                        break;

                    case 2:
                        $startdate = $criteria['year'] . '-04-01';
                        $enddate = $criteria['year'] . '-06-30';
                        break;

                    case 3:
                        $startdate = $criteria['year'] . '-07-01';
                        $enddate = $criteria['year'] . '-09-30';
                        break;

                    case 4:
                        $startdate = $criteria['year'] . '-10-01';
                        $enddate = $criteria['year'] . '-12-31';
                        break;
                    }
                } else {
                    $startdate = $criteria['year'] . '-01-01';
                    $enddate = $criteria['year'] . '-12-31';
                }
                $dateBegin = new Zend_Date($startdate, Zend_Date::DATES);
                $dateEnd = new Zend_Date($enddate, Zend_Date::DATES);
            }
            $systemArray = array(
                'system_id' => $systemId
            );
            $aawArray = array(
                'created_date_end' => $dateBegin,
                'closed_date_begin' => $dateEnd
            ); //or close_ts is null
            $bawArray = array(
                'created_date_end' => $dateEnd,
                'est_date_end' => $dateEnd,
                'actual_date_begin' => $dateBegin,
                'action_date_end' => $dateEnd
            );
            $cawArray = array(
                'created_date_end' => $dateEnd,
                'est_date_begin' => $dateEnd
            ); // and actual_date_begin is null
            $dawArray = array(
                'est_date_end' => $dateEnd,
                'actual_date_begin' => $dateEnd
            ); //or action_actual_date is null
            $eawArray = array(
                'created_date_begin' => $dateBegin,
                'created_date_end' => $dateEnd
            );
            $fawArray = array(
                'created_date_end' => $dateEnd,
                'closed_date_begin' => $dateEnd
            ); //or close_ts is null
            $criteriaAaw = array_merge($systemArray, $aawArray);
            $criteriaBaw = array_merge($systemArray, $bawArray);
            $criteriaCaw = array_merge($systemArray, $cawArray);
            $criteriaDaw = array_merge($systemArray, $dawArray);
            $criteriaEaw = array_merge($systemArray, $eawArray);
            $criteriaFaw = array_merge($systemArray, $fawArray);
            $summary = array(
                'AAW' => 0,
                'AS' => 0,
                'BAW' => 0,
                'BS' => 0,
                'CAW' => 0,
                'CS' => 0,
                'DAW' => 0,
                'DS' => 0,
                'EAW' => 0,
                'ES' => 0,
                'FAW' => 0,
                'FS' => 0
            );
            $summary['AAW'] = $this->_poam->search($this->_me->systems, array(
                'count' => 'count(*)'
            ), $criteriaAaw);
            $summary['BAW'] = $this->_poam->search($this->_me->systems, array(
                'count' => 'count(*)'
            ), $criteriaBaw);
            $summary['CAW'] = $this->_poam->search($this->_me->systems, array(
                'count' => 'count(*)'
            ), $criteriaCaw);
            $summary['DAW'] = $this->_poam->search($this->_me->systems, array(
                'count' => 'count(*)'
            ), $criteriaDaw);
            $summary['EAW'] = $this->_poam->search($this->_me->systems, array(
                'count' => 'count(*)'
            ), $criteriaEaw);
            $summary['FAW'] = $this->_poam->search($this->_me->systems, array(
                'count' => 'count(*)'
            ), $criteriaFaw);
            $this->view->assign('summary', $summary);
        }
        $this->render();
    }
    
    /**
     * poamAction() - Generate poam report
     */
    public function poamAction() {
        $req = $this->getRequest();
        $params = array(
            'systemId' => 'system_id',
            'sourceId' => 'source_id',
            'type' => 'type',
            'year' => 'year',
            'status' => 'status'
        );
        $criteria = $this->retrieveParam($req, $params);
        $this->view->assign('source_list', $this->_sourceList);
        $this->view->assign('system_list', $this->_systemList);
        $this->view->assign('network_list', $this->_networkList);
        $this->view->assign('criteria', $criteria);
        $isExport = $req->getParam('format');
        if ('search' == $req->getParam('s') || isset($isExport)) {
            $this->_pagingBasePath.= '/panel/report/sub/poam/s/search';
            if (isset($isExport)) {
                $this->_paging['currentPage'] = 
                    $this->_pagging['perPage'] = null;
            }
            $this->makeUrl($criteria);
            if (!empty($criteria['year'])) {
                $criteria['createdDateBegin'] = new 
                    Zend_Date($criteria['year'], Zend_Date::YEAR);
                $criteria['createdDateEnd'] = clone 
                    $criteria['created_date_begin'];
                $criteria['createdDateEnd']->add(1, Zend_Date::YEAR);
                unset($criteria['year']);
            }
            $list = & $this->_poam->search($this->_me->systems, array(
                'id',
                'finding_data',
                'system_id',
                'network_id',
                'source_id',
                'asset_id',
                'type',
                'ip',
                'port',
                'status',
                'action_suggested',
                'action_planned',
                'action_est_date',
                'cmeasure',
                'threat_source',
                'threat_level',
                'threat_source',
                'threat_justification',
                'cmeasure',
                'cmeasure_effectiveness',
                'cmeasure_justification',
                'blscr_id',
                'count' => 'count(*)'), 
                $criteria, $this->_paging['currentPage'], 
                $this->_paging['perPage']);
            $total = array_pop($list);
            $this->_paging['totalItems'] = $total;
            $this->_paging['fileName'] = "{$this->_pagingBasePath}/p/%d";
            $pager = & Pager::factory($this->_paging);
            $this->view->assign('poam_list', $list);
            $this->view->assign('links', $pager->getLinks());
        }
        $this->render();
    }
    
    /**
     * overdueAction() - Overdue report
     */
    public function overdueAction() {
        // Get request variables
        $req = $this->getRequest();
        $params = array(
            'systemId' => 'system_id',
            'sourceId' => 'source_id',
            'overdueType' => 'overdue_type',
            'overdueDay' => 'overdue_day',
            'year' => 'year'
        );
        $criteria = $this->retrieveParam($req, $params);
        $this->view->assign('source_list', $this->_sourceList);
        $this->view->assign('system_list', $this->_systemList);
        $this->view->assign('criteria', $criteria);
        $isExport = $req->getParam('format');
        
        if ('search' == $req->getParam('s') || isset($isExport)) {
            // Setup the paging if necessary
            $this->_pagingBasePath.= '/panel/report/sub/overdue/s/search';
            if (isset($isExport)) {
                $this->_paging['currentPage'] = null;
                $this->_paging['perPage'] = null;
            }
            $this->makeUrl($criteria);
            $this->view->assign('url', $this->_pagingBasePath);
            
            // Interpret the search criteria
            if (isset($criteria['overdueType'])) {
                $criteria['overdue']['type'] = $criteria['overdueType'];
            }
            if (isset($criteria['overdueDay'])) {
                $criteria['overdue']['day'] = $criteria['overdueDay'];
            }
            if (!empty($criteria['year'])) {
                $criteria['createdDateBegin'] =
                    new Zend_Date($criteria['year'], Zend_Date::YEAR);
                $criteria['createdDateEnd'] = 
                    clone $criteria['createdDateBegin'];
                $criteria['createdDateEnd']->add(1, Zend_Date::YEAR);
                unset($criteria['year']);
            }
            if (!empty($criteria['overdue'])) {
                $date = clone self::$now;
                $date->sub(($criteria['overdue']['day'] - 1) * 30,
                    Zend_Date::DAY);
                $criteria['overdue']['end_date'] = clone $date;
                $date->sub(30, Zend_Date::DAY);
                $criteria['overdue']['begin_date'] = $date;
                if ($criteria['overdue']['day'] == 5) {
                    ///@todo hardcode greater than 120
                    unset($criteria['overdue']['begin_date']);
                }
            }
            
            // Search for overdue items according to the criteria
            $list = $this->_poam->search(
                $this->_me->systems,
                array(
                    'id',
                    'finding_data',
                    'system_id',
                    'network_id',
                    'source_id',
                    'asset_id',
                    'type',
                    'ip',
                    'port',
                    'status',
                    'action_suggested',
                    'action_planned',
                    'threat_level',
                    'action_est_date',
                    'count' => 'count(*)'
                ),
                $criteria,
                $this->_paging['currentPage'],
                $this->_paging['perPage']
            );
                
            // Last result is the total
            $total = array_pop($list);
            
            // Assign view outputs
            $this->_paging['totalItems'] = $total;
            $this->_paging['fileName'] = "{$this->_pagingBasePath}/p/%d";
            $pager = & Pager::factory($this->_paging);
            $this->view->assign('poam_list', $list);
            $this->view->assign('links', $pager->getLinks());
        }
        $this->render();
    }

    /**
     * generalAction() - Generate general report
     */
    public function generalAction()
    {
        $req = $this->getRequest();
        $type = $req->getParam('type', '');
        $this->view->assign('type', $type);
        $this->render();
        if (!empty($type) && ('search' == $req->getParam('s'))) {
            define('REPORT_GEN_BLSCR', 1);
            define('REPORT_GEN_FIPS', 2);
            define('REPORT_GEN_PRODS', 3);
            define('REPORT_GEN_SWDISC', 4);
            define('REPORT_GEN_TOTAL', 5);
            
            if (REPORT_GEN_BLSCR == $type) {
                $this->_forward('blscr');
            }
            if (REPORT_GEN_FIPS == $type) {
                $this->_forward('fips');
            }
            if (REPORT_GEN_PRODS == $type) {
                $this->_forward('prods');
            }
            if (REPORT_GEN_SWDISC == $type) {
                $this->_forward('swdisc');
            }
            if (REPORT_GEN_TOTAL == $type) {
                $this->_forward('total');
            }
        }
    }
    
    /**
     * blscrAction() - Generate BLSCR report
     */
    public function blscrAction() {
        $db = $this->_poam->getAdapter();
        $system = new system();
        $rpdata = array();
        $query = $db->select()->from(array(
            'p' => 'poams'
        ), array(
            'num' => 'count(p.id)'
        ))->join(array(
            'b' => 'blscrs'
        ), 'b.code = p.blscr_id', array(
            'blscr' => 'b.code'
        ))->where("b.class = 'MANAGEMENT'")->group("b.code");
        $rpdata[] = $db->fetchAll($query);
        $query->reset();
        $query = $db->select()->from(array(
            'p' => 'poams'
        ), array(
            'num' => 'count(p.id)'
        ))->join(array(
            'b' => 'blscrs'
        ), 'b.code = p.blscr_id', array(
            'blscr' => 'b.code'
        ))->where("b.class = 'OPERATIONAL'")->group("b.code");
        $rpdata[] = $db->fetchAll($query);
        $query->reset();
        $query = $db->select()->from(array(
            'p' => 'poams'
        ), array(
            'num' => 'count(p.id)'
        ))->join(array(
            'b' => 'blscrs'
        ), 'b.code = p.blscr_id', array(
            'blscr' => 'b.code'
        ))->where("b.class = 'TECHNICAL'")->group("b.code");
        $rpdata[] = $db->fetchAll($query);
        $this->view->assign('rpdata', $rpdata);
        $this->render();
    }
    
    /**
     * fipsAction() - FIPS report
     */
    public function fipsAction() {
        require_once('RiskAssessment.php');
        $system = new system();
        $systems = $system->getList(array(
            'name' => 'name',
            'type' => 'type',
            'conf' => 'confidentiality',
            'avail' => 'availability',
            'integ' => 'availability'
        ));
        $fipsTotals = array();
        $fipsTotals['LOW'] = 0;
        $fipsTotals['MODERATE'] = 0;
        $fipsTotals['HIGH'] = 0;
        $fipsTotals['n/a'] = 0;
        foreach ($systems as $sid => & $system) {
            if (strtolower($system['conf']) != 'none') {
                $riskObj = new RiskAssessment($system['conf'],
                    $system['avail'], $system['integ'], null, null, null);
                $fips = $riskObj->get_data_sensitivity();
            } else {
                $fips = 'n/a';
            }
            $qry = $this->_poam->select()->from('poams', array(
                'last_update' => 'MAX(modify_ts)'
            ))->where('poams.system_id = ?', $sid);
            $result = $this->_poam->fetchRow($qry);
            if (!empty($result)) {
                $ret = $result->toArray();
                $system['last_update'] = $ret['last_update'];
            }
            $system['fips'] = $fips;
            $fipsTotals[$fips]+= 1;
            $system['crit'] = $system['avail'];
        }
        $rpdata = array();
        $rpdata[] = $systems;
        $rpdata[] = $fipsTotals;
        $this->view->assign('rpdata', $rpdata);
        $this->render();
    }
    
    /**
     * prodsAction() - Generate products report
     */
    public function prodsAction() {
        $db = $this->_poam->getAdapter();
        $query = $db->select()->from(array(
            'prod' => 'products'
        ), array(
            'Vendor' => 'prod.vendor',
            'Product' => 'prod.name',
            'Version' => 'prod.version',
            'NumoOV' => 'count(prod.id)'
        ))->join(array(
            'p' => 'poams'
        ), 'p.status IN ("OPEN","EN","UP","ES")', array())->join(array(
            'a' => 'assets'
        ), 'a.id = p.asset_id AND a.prod_id = prod.id', array())
            ->group("prod.vendor")->group("prod.name")->group("prod.version");
        $rpdata = $db->fetchAll($query);
        $this->view->assign('rpdata', $rpdata);
        $this->render();
    }
    
    /**
     * swdiscAction() - Software discovered report
     */
    public function swdiscAction() {
        $db = $this->_poam->getAdapter();
        $query = $db->select()->from(array(
            'p' => 'products'
        ), array(
            'Vendor' => 'p.vendor',
            'Product' => 'p.name',
            'Version' => 'p.version'
        ))->join(array(
            'a' => 'assets'
        ), 'a.source = "SCAN" AND a.prod_id = p.id', array());
        $rpdata = $db->fetchAll($query);
        $this->view->assign('rpdata', $rpdata);
        $this->render();
    }
    
    /**
     * totalAction() - ???
     */
    public function totalAction() {
        $db = $this->_poam->getAdapter();
        $system = new system();
        $rpdata = array();
        $query = $db->select()->from(array(
            'sys' => 'systems'
        ), array(
            'sysnick' => 'sys.nickname',
            'vulncount' => 'count(sys.id)'
        ))->join(array(
            'p' => 'poams'
        ), 'p.type IN ("CAP","AR","FP") AND
            p.status IN ("OPEN","EN","EP","ES") AND p.system_id = sys.id',
            array())->join(array(
            'a' => 'assets'
        ), 'a.id = p.asset_id', array())->group("p.system_id");
        $sysVulncounts = $db->fetchAll($query);
        $sysNicks = $system->getList('nickname');
        $systemTotals = array();
        foreach ($sysNicks as $nickname) {
            $systemNick = $nickname;
            $systemTotals[$systemNick] = 0;
        }
        $totalOpen = 0;
        foreach ((array)$sysVulncounts as $svRow) {
            $systemNick = $svRow['sysnick'];
            $systemTotals[$systemNick] = $svRow['vulncount'];
            $totalOpen++;
        }
        $systemTotalArray = array();
        foreach (array_keys($systemTotals) as $key) {
            $val = $systemTotals[$key];
            $thisRow = array();
            $thisRow['nick'] = $key;
            $thisRow['num'] = $val;
            array_push($systemTotalArray, $thisRow);
        }
        array_push($rpdata, $totalOpen);
        array_push($rpdata, $systemTotalArray);
        $this->view->assign('rpdata', $rpdata);
        $this->render();
    }
    /**
     * rafsAction() - Batch generate RAFs for each system
     */
    public function rafsAction()
    {
        $sid = $this->_req->getParam('system_id');
        $this->view->assign('system_list', $this->_systemList);
        if (!empty($sid)) {
            $query = $this->_poam->select()->from($this->_poam, array(
                'id'
            ))->where('system_id=?', $sid)
                ->where('threat_level IS NOT NULL AND threat_level != \'NONE\'')
                ->where('cmeasure_effectiveness IS NOT NULL AND 
                                    cmeasure_effectiveness != \'NONE\'');
            $poamIds = $this->_poam->getAdapter()->fetchCol($query);
            $count = count($poamIds);
            if ($count > 0) {
                $this->_helper->layout->disableLayout(true);
                $fname = tempnam('/tmp/', "RAFs");
                @unlink($fname);
                $rafs = new Archive_Tar($fname, true);
                $this->view->assign('source_list', $this->_sourceList);
                $path = $this->_helper->viewRenderer
                    ->getViewScript('raf', array(
                    'controller' => 'remediation',
                    'suffix' => 'pdf.phtml'
                ));
                foreach ($poamIds as $id) {
                    $poamDetail = & $this->_poam->getDetail($id);
                    $this->view->assign('poam', $poamDetail);
                    $rafs->addString("raf_{$id}.pdf",
                        $this->view->render($path));
                }
                header("Content-type: application/octetstream");
                header('Content-Length: ' . filesize($fname));
                header("Content-Disposition: attachment; filename=RAFs.tgz");
                header("Content-Transfer-Encoding: binary");
                header("Expires: 0");
                header("Cache-Control: must-revalidate, post-check=0,".
                    " pre-check=0");
                header("Pragma: public");
                echo file_get_contents($fname);
                @unlink($fname);
            } else {
                $this->render();
            }
        } else {
            $this->render();
        }
    }
    
    /**
     * pluginAction() - Display the available plugin reports
     *
     * @todo Use Zend_Cache for the report menu
     */         
    public function pluginAction() 
    {
        // Build up report menu
        $reportsConfig = new Zend_Config_Ini(CONFIGS . '/reports.conf');
        $reports = $reportsConfig->toArray();
        $this->view->assign('reports', $reports);
        $this->render();
    }

    /**
     * pluginreportAction() - Execute and display the specified plug-in report
     */         
    public function pluginreportAction() 
    {
        // Verify a plugin report name was passed to this action
        $reportName = $this->_req->getParam('name');
        if (!isset($reportName)) {
            $this->forward('plugin');
        }
        
        // Verify that the user has permission to run this report
        $reportConfig = new Zend_Config_Ini(CONFIGS . '/reports.conf', $reportName);
        $reportRoles = $reportConfig->roles;
        $report = $reportConfig->toArray();
        $reportRoles = $report['roles'];
        if (!is_array($reportRoles)) {
            $reportRoles = array($reportRoles);
        }
        $user = new User();
        $role = $user->getRoles($this->_me->id);
        $role = $role[0]['nickname'];
        if (!in_array($role, $reportRoles)) {
            throw new FismaException("User \"{$this->_me->account}\" does not have permission to view"
                                     . " the \"$reportName\" plug-in report.");
        }
        
        // Execute the report script
        $reportScriptFile = CONFIGS . "/reports/$reportName.sql";
        $reportScriptFileHandle = fopen($reportScriptFile, 'r');
        $reportScript = '';
        while (!feof($reportScriptFileHandle)) {
            $reportScript .= fgets($reportScriptFileHandle);
        }
        $db = Zend_Db::factory(Zend_Registry::get('datasource'));
        $reportData = $db->fetchAll($reportScript);
        
        // Render the report results
        if (isset($reportData[0])) {
            $columns = array_keys($reportData[0]);
        } else {
            // @todo replace with a user level error message and forward to pluginAction()
            throw new FismaException("No data for plugin report \"$reportName\"");
        } 
        $this->view->assign('title', $reportConfig->title);
        $this->view->assign('columns', $columns);
        $this->view->assign('rows', $reportData);
        $this->render();

    }
}
