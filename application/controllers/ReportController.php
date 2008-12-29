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
     *
     * @todo Why are the contexts duplicated in init() and predispatch()? I think the init() method is the right place
     * for it.
     */
    public function init()
    {
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
    public function preDispatch()
    {
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
              ->addActionContext('plugin-report', array('pdf', 'xls'))
              ->initContext();
    }

    /**
     * fismaAction() - Genenerate fisma report
     */
    public function fismaAction()
    {
        $this->_acl->requirePrivilege('report', 'generate_fisma_report');

        $req = $this->getRequest();
        $criteria['year'] = $req->getParam('y');
        $criteria['quarter'] = $req->getParam('q');
        $criteria['systemId'] = $systemId = $req->getParam('system');
        $criteria['startdate'] = $req->getParam('startdate');
        $criteria['enddate'] = $req->getParam('enddate');
        $this->view->assign('system_list', $this->_systemList);
        $this->view->assign('criteria', $criteria);
        $this->view->assign('year', empty($criteria['year'])?date('Y'):$criteria['year']);
        $dateBegin = '';
        $dateEnd = '';
        if ('search' == $req->getParam('s') || 
            'pdf' == $req->getParam('format') || 
            'xls' == $req->getParam('format')) {
            if (!empty($criteria['startdate']) && 
                !empty($criteria['enddate'])) {
                $dateBegin = new 
                    Zend_Date($criteria['startdate'], 'Y-m-d');
                $dateEnd = new 
                    Zend_Date($criteria['enddate'], 'Y-m-d');
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
                'systemId' => $systemId
            );
            $aawArray = array(
                'createdDateEnd' => $dateBegin,
                'closedDateBegin' => $dateEnd
            ); //or close_ts is null
            $bawArray = array(
                'createdDateEnd' => $dateEnd,
                'estDateEnd' => $dateEnd,
                'actualDateBegin' => $dateBegin,
                'actionDateEnd' => $dateEnd
            );
            $cawArray = array(
                'createdDateEnd' => $dateEnd,
                'estDateBegin' => $dateEnd
            ); // and actual_date_begin is null
            $dawArray = array(
                'estDateEnd' => $dateEnd,
                'actualDateBegin' => $dateEnd
            ); //or action_actual_date is null
            $eawArray = array(
                'createdDateBegin' => $dateBegin,
                'createdDateEnd' => $dateEnd
            );
            $fawArray = array(
                'createdDateEnd' => $dateEnd,
                'closedDateBegin' => $dateEnd
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
    }
    
    /**
     * poamAction() - Generate poam report
     */
    public function poamAction()
    {
        $this->_acl->requirePrivilege('report', 'generate_poam_report');
        
        $req = $this->getRequest();
        $params['system_id'] = $req->getParam('system_id');
        $params['source_id'] = $req->getParam('source_id');
        $params['type'] = $req->getParam('type');
        $params['year'] = $req->getParam('year');
        $params['status'] = $req->getParam('status');
        $this->view->assign('source_list', $this->_sourceList);
        $this->view->assign('system_list', $this->_systemList);
        $this->view->assign('network_list', $this->_networkList);
        $this->view->assign('params', $params);
        $isExport = $req->getParam('format');

        if ('search' == $req->getParam('s') || isset($isExport)) {
            $criteria = array();
            if (!empty($params['system_id'])) {
                $criteria['systemId'] = $params['system_id'];
            }
            if (!empty($params['source_id'])) {
                $criteria['sourceId'] = $params['source_id'];
            }
            if (!empty($params['type'])) {
                $criteria['type'] = $params['type'];
            }
            if (!empty($params['status'])) {
                if ('OPEN' == $params['status']) {
                    $criteria['status'] = array('NEW', 'DRAFT', 'MSA', 'EN', 'EP');
                } else {
                    $criteria['status'] = $params['status'];
                }
            }
            $this->_pagingBasePath.= '/panel/report/sub/poam/s/search';
            if (isset($isExport)) {
                $this->_paging['currentPage'] = 
                    $this->_pagging['perPage'] = null;
            }
            $this->makeUrl($params);
            if (!empty($params['year'])) {
                $criteria['createdDateBegin'] = new 
                    Zend_Date($params['year'], Zend_Date::YEAR);
                $criteria['createdDateEnd'] = clone $criteria['createdDateBegin'];
                $criteria['createdDateEnd']->add(1, Zend_Date::YEAR);
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
                'action_current_date',
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
                'duetime',
                'count' => 'count(*)'), 
                $criteria, $this->_paging['currentPage'], 
                $this->_paging['perPage'],
                false);
            $total = array_pop($list);
            $this->_paging['totalItems'] = $total;
            $this->_paging['fileName'] = "{$this->_pagingBasePath}/p/%d";
            $pager = & Pager::factory($this->_paging);

            $this->view->assign('poam', $this->_poam);
            $this->view->assign('poam_list', $list);
            $this->view->assign('links', $pager->getLinks());
        }
    }
    
    /**
     * overdueAction() - Overdue report
     */
    public function overdueAction()
    {
        $this->_acl->requirePrivilege('report', 'generate_overdue_report');
        
        // Get request variables
        $req = $this->getRequest();
        $params['system_id'] = $req->getParam('system_id');
        $params['source_id'] = $req->getParam('source_id');
        $params['overdue_type'] = $req->getParam('overdue_type');
        $params['overdue_day'] = $req->getParam('overdue_day');
        $params['year'] = $req->getParam('year');

        $this->view->assign('source_list', $this->_sourceList);
        $this->view->assign('system_list', $this->_systemList);
        $this->view->assign('network_list', $this->_networkList);
        $this->view->assign('params', $params);
        $isExport = $req->getParam('format');
        
        if ('search' == $req->getParam('s') || isset($isExport)) {
            $criteria = array();
            if (!empty($params['system_id'])) {
                $criteria['systemId'] = $params['system_id'];
            }
            if (!empty($params['source_id'])) {
                $criteria['sourceId'] = $params['source_id'];
            }
            // Setup the paging if necessary
            $this->_pagingBasePath.= '/panel/report/sub/overdue/s/search';
            if (isset($isExport)) {
                $this->_paging['currentPage'] = null;
                $this->_paging['perPage'] = null;
            }
            $this->makeUrl($params);
            $this->view->assign('url', $this->_pagingBasePath);
            
            // Interpret the search criteria
            if (!empty($params['year'])) {
                $criteria['createdDateBegin'] = new Zend_Date($params['year'], Zend_Date::YEAR);
                $criteria['createdDateEnd']   = clone $criteria['createdDateBegin'];
                $criteria['createdDateEnd']->add(1, Zend_Date::YEAR);
            }
            if (!empty($params['overdue_type'])) {
                $dateEnd = clone self::$now;
                $dateEnd->sub(($params['overdue_day'] -1) * 30, Zend_Date::DAY);
                $dateBegin = clone $dateEnd;
                $dateBegin->sub(30, Zend_Date::DAY);

                if ('sso' == $params['overdue_type']) {
                    if ($params['overdue_day'] != 5) {
                        $criteria['actualDateBegin'] = $dateBegin;
                    }
                    $criteria['actualDateEnd'] = $dateEnd;
                } else if ('action' == $params['overdue_type']) {
                    if ($params['overdue_day'] != 5) {
                        $criteria['estDateBegin'] = $dateBegin;
                    }
                    $criteria['estDateEnd'] = $dateEnd;
                }
            }
            
            // Search for overdue items according to the criteria
            $list = $this->_poam->search($this->_me->systems,
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
                    'action_current_date',
                    'action_est_date',
                    'count' => 'count(*)'
                ),
                $criteria,
                $this->_paging['currentPage'],
                $this->_paging['perPage'],
                false);
                
            // Last result is the total
            $total = array_pop($list);
            
            // Assign view outputs
            $this->_paging['totalItems'] = $total;
            $this->_paging['fileName'] = "{$this->_pagingBasePath}/p/%d";
            $pager = & Pager::factory($this->_paging);
            $this->view->assign('poam_list', $list);
            $this->view->assign('links', $pager->getLinks());
        }
    }

    /**
     * generalAction() - Generate general report
     */
    public function generalAction()
    {
        $this->_acl->requirePrivilege('report', 'generate_general_report');
        
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
        $this->_acl->requirePrivilege('report', 'generate_general_report');
        
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
    }
    
    /**
     * fipsAction() - FIPS report
     */
    public function fipsAction()
    {
        $this->_acl->requirePrivilege('report', 'generate_general_report');
        
        $sysObj = new System();
        $systems = $sysObj->getList(array(
            'name' => 'name',
            'type' => 'type',
            'conf' => 'confidentiality',
            'avail' => 'availability',
            'integ' => 'integrity'
        ));
        $fipsTotals = array();
        $fipsTotals['LOW'] = 0;
        $fipsTotals['MODERATE'] = 0;
        $fipsTotals['HIGH'] = 0;
        $fipsTotals['n/a'] = 0;
        foreach ($systems as $sid => & $system) {
            if (strtolower($system['conf']) != 'none') {
                $fips = $sysObj->calcSecurityCategory($system['conf'], $system['integ'], $system['avail']);
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
    }
    
    /**
     * prodsAction() - Generate products report
     */
    public function prodsAction()
    {
        $this->_acl->requirePrivilege('report', 'generate_general_report');
        
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
        ), 'p.status IN ("DRAFT","MSA", "EN","EP")', array())->join(array(
            'a' => 'assets'
        ), 'a.id = p.asset_id AND a.prod_id = prod.id', array())
            ->group("prod.vendor")->group("prod.name")->group("prod.version");
        $rpdata = $db->fetchAll($query);
        $this->view->assign('rpdata', $rpdata);
    }
    
    /**
     * swdiscAction() - Software discovered report
     */
    public function swdiscAction()
    {
        $this->_acl->requirePrivilege('report', 'generate_general_report');
        
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
    }
    
    /**
     * totalAction() - ???
     */
    public function totalAction()
    {
        $this->_acl->requirePrivilege('report', 'generate_general_report');
        
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
            p.status IN ("DRAFT", "MSA", "EN", "EP") AND p.system_id = sys.id',
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
    }
    /**
     * rafsAction() - Batch generate RAFs for each system
     */
    public function rafsAction()
    {
        $this->_acl->requirePrivilege('report', 'generate_system_rafs');
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
                $fname = tempnam('/tmp/', "RAFs");
                @unlink($fname);
                $rafs = new Archive_Tar($fname, true);
                $path = $this->_helper->viewRenderer
                        ->getViewScript('raf', array(
                                        'controller' => 'remediation',
                                        'suffix' => 'pdf.phtml'));
                try {
                    $system = new System();
                    foreach ($poamIds as $id) {
                        $poamDetail = & $this->_poam->getDetail($id);
                        $this->view->assign('poam', $poamDetail);
                        $ret = $system->find($poamDetail['system_id']);
                        $actOwner = $ret->current()->toArray();
                        $securityCategorization = $system->calcSecurityCategory($actOwner['confidentiality'],
                                                                                $actOwner['integrity'],
                                                                                $actOwner['availability']);
                        if (NULL == $securityCategorization) {
                            throw new Exception_General('The security categorization for ('.$actOwner['id'].')'.
                                $actOwner['name'].' is not defined. An analysis of risk cannot be generated '.
                                'unless these values are defined.');
                        }
                        $this->view->assign('securityCategorization', $securityCategorization);
                        $rafs->addString("raf_{$id}.pdf", $this->view->render($path));
                    }
                    $this->_helper->layout->disableLayout(true);
                    $this->_helper->viewRenderer->setNoRender();
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
                } catch (Exception_General $e) {
                    if ($e instanceof Exception_General) {
                        $message = $e->getMessage();
                    }
                    $this->message($message, self::M_WARNING);
                }
            } else {
                /** @todo english */
                $this->message('No finding', self::M_WARNING);
            }
        }
    }
    
    /**
     * pluginAction() - Display the available plugin reports
     *
     * @todo Use Zend_Cache for the report menu
     */         
    public function pluginAction() 
    {
        $this->_acl->requirePrivilege('report', 'read');
        
        // Build up report menu
        $reportsConfig = new Zend_Config_Ini(APPLICATION_ROOT . '/application/config/reports.conf');
        $reports = $reportsConfig->toArray();
        $this->view->assign('reports', $reports);
    }

    /**
     * pluginReportAction() - Execute and display the specified plug-in report
     *
     * @todo Need to implement Excel and PDF export options
     */         
    public function pluginReportAction()
    {
        $this->_acl->requirePrivilege('report', 'read');
        
        // Verify a plugin report name was passed to this action
        $reportName = $this->_req->getParam('name');
        if (!isset($reportName)) {
            $this->_forward('plugin');
            return;
        }
        
        // Verify that the user has permission to run this report
        $reportConfig = new Zend_Config_Ini(APPLICATION_ROOT . '/application/config/reports.conf', $reportName);
        if ($this->_me->account != 'root') {
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
                throw new Exception_General("User \"{$this->_me->account}\" does not have permission to view"
                                          . " the \"$reportName\" plug-in report.");
            }
        }
        
        // Execute the report script
        $reportScriptFile = APPLICATION_ROOT . "/application/config/reports/$reportName.sql";
        $reportScriptFileHandle = fopen($reportScriptFile, 'r');
        if (!$reportScriptFileHandle) {
            throw new Exception_General("Unable to load plug-in report SQL file: $reportScriptFile");
        }
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
            $msg = "The report could not be created because the report query did not return any data.";
            $this->message($msg, self::M_WARNING);
            $this->_forward('plugin');
            return;
        } 
        $this->view->assign('title', $reportConfig->title);
        $this->view->assign('columns', $columns);
        $this->view->assign('rows', $reportData);
    }
}
