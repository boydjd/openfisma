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
 * The remediation controller handles CRUD for findings in remediation.
 *
 * @package   Controller
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/mw/index.php?title=License
 *
 * @todo As part of the ongoing refactoring, this class should probably be
 * merged with the FindingController.
 */
class RemediationController extends PoamBaseController
{
    //define the events of notification
    private $_notificationArray =
        array('action_suggested'=>Notification::UPDATE_FINDING_RECOMMENDATION,
              'type'=>Notification::UPDATE_COURSE_OF_ACTION,
              'action_planned'=>Notification::UPDATE_COURSE_OF_ACTION,
              'action_est_date'=>Notification::UPDATE_EST_COMPLETION_DATE,
              'threat_level'=>Notification::UPDATE_THREAT,
              'threat_source'=>Notification::UPDATE_THREAT,
              'threat_justification'=>Notification::UPDATE_THREAT,
              'cmeasure_effectiveness'=>Notification::UPDATE_COUNTERMEASURES,
              'cmeasure'=>Notification::UPDATE_COUNTERMEASURES,
              'cmeasure_justification'=>Notification::UPDATE_COUNTERMEASURES,
              'system_id'=>Notification::UPDATE_FINDING_ASSIGNMENT,
              'blscr_id'=>Notification::UPDATE_CONTROL_ASSIGNMENT,
              'action_status'=>Notification::MITIGATION_STRATEGY_APPROVED,
              'action_resources'=>Notification::UPDATE_FINDING_RESOURCES);
    /**
     *  Default action.
     *
     *  It combines the searching and summary into one page.
     */
    public function indexAction()
    {
        Config_Fisma::requirePrivilege('remediation', 'read');
        
        $this->_helper->actionStack('searchbox', 'Remediation');
        $this->_helper->actionStack('summary', 'Remediation');
    }

    /**
     *  Display the summary page of remediation, per systems.
     */
    public function summaryAction()
    {
        Config_Fisma::requirePrivilege('remediation', 'read');
        
        $criteria['sourceId'] = $this->_request->getParam('source_id', 0);
        $criteria['type'] = $this->_request->getParam('type');
        $criteria['notStatus'] = 'PEND'; //exclude pending findings from the search criteria
        $criteria['aging'] = $this->_request->getParam('aging');
        $criteria['created_date_begin'] = $this->_request->getParam('created_date_begin');
        $criteria['created_date_end'] = $this->_request->getParam('created_date_end');

        if (!empty($criteria['created_date_begin'])) {
            $criteria['createdDateBegin'] = new Zend_Date($criteria['created_date_begin'], 'Y-m-d');
        }
        if (!empty($criteria['created_date_end'])) {
            $criteria['createdDateEnd'] = new Zend_Date($criteria['created_date_end'], 'Y-m-d');
        }
        
        $criteriaUrl = '';
        if (!empty($criteria['sourceId'])) {
            $criteriaUrl = '/source_id/'.$criteria['sourceId'];
        }
        if (!empty($criteria['type'])) {
            $criteriaUrl .='/type/'.$criteria['type'];
        }
        if (!empty($criteria['aging'])) {
            $criteriaUrl .='/aging/'.$criteria['aging'];
        }
        if (!empty($criteria['createdDateBegin'])) {
            $criteriaUrl .='/created_date_begin/'.$criteria['created_date_begin'];
        }
        if (!empty($criteria['created_date_end'])) {
            $criteriaUrl .='/created_date_end/'.$criteria['created_date_end'];
        }

        $eval = new Evaluation();
        $mpEvalList = $eval->getEvalList('ACTION');
        $epEvalList = $eval->getEvalList('EVIDENCE');
        foreach ($mpEvalList as $row) {
            $mpStatus[$row['nickname']] = $row['precedence_id'];
            $mpSummaryTmp[$row['nickname']] = 0;
        }
        $overduePeriod['EN'] = 0;
        foreach ($epEvalList as $row) {
            $epStatus[$row['nickname']] = $row['precedence_id'];
            $epSummaryTmp[$row['nickname']] = 0;
        }
    
        $summaryTmp = array_merge(array('NEW'=>0, 'OPEN'=>0), $mpSummaryTmp);
        $summaryTmp = array_merge($summaryTmp, array('EN'=>0));
        $summaryTmp = array_merge($summaryTmp, $epSummaryTmp);
        $summaryTmp = array_merge($summaryTmp, array('CLOSED'=>0, 'TOTAL'=>0));
        // mock array_fill_key in 5.2.0
        $count = count($this->_me->systems);
        if ( 0 == $count ) {
            $summary = array();
        } else {
            $sum = array_fill(0, $count, $summaryTmp);
            $summary = array_combine($this->_me->systems, $sum);
        }
        $total = $summaryTmp;
        $ret = $this->_poam->search($this->_me->systems, array(
            'count' => array(
                'status',
                'system_id'
            ) ,
            'status',
            'type',
            'system_id'
        ), $criteria);
        $sum = array();
        foreach ($ret as $s) {
            $sum[$s['system_id']][$s['status']] = $s['count'];
        }
        foreach ($sum as $id => & $s) {
            $summary[$id] = $summaryTmp;
            $summary[$id]['NEW'] = isset($s['NEW'])?$s['NEW']: 0;
            $summary[$id]['OPEN'] = isset($s['OPEN'])?$s['OPEN']: 0;
            $summary[$id]['EN'] = isset($s['EN'])?$s['EN']: 0;
            $summary[$id]['CLOSED'] = isset($s['CLOSED'])?$s['CLOSED']: 0;
            $summary[$id]['TOTAL'] = array_sum($s);
            $total['NEW']+= $summary[$id]['NEW'];
            $total['OPEN']+= $summary[$id]['OPEN'];
            $total['EN']+= $summary[$id]['EN'];
            $total['CLOSED']+= $summary[$id]['CLOSED'];
            $total['TOTAL']+= $summary[$id]['TOTAL'];
        }

        foreach ($mpEvalList as $row) {
            $mp = $this->_poam->search($this->_me->systems, array(
                'count' => 'system_id',
                'system_id'
            ), array_merge(array('mp' => $row['precedence_id']), $criteria));
            foreach ($mp as $v) {
                $summary[$v['system_id']][$row['nickname']] = $v['count'];
                $total[$row['nickname']]+= $v['count'];
            }
        }

        foreach ($epEvalList as $row) {
            $ep = $this->_poam->search($this->_me->systems, array(
                'count' => 'system_id',
                'system_id'
            ), array_merge(array('ep' => $row['precedence_id']), $criteria));
            foreach ($ep as $v) {
                $summary[$v['system_id']][$row['nickname']] = $v['count'];
                $total[$row['nickname']]+= $v['count'];
            }
        }

        // count the Overdue stauts
        $statusArray = array_keys($summaryTmp);
        $statusArray = array_slice($statusArray, 0, -2);
        foreach ($statusArray as $status) {
            $overdueTime = new Zend_Date();
            $overdueCriteria = array('status' => $status);
            if (in_array($status, array('NEW', 'OPEN'))) {
                $overdueTime->sub(30, Zend_Date::DAY);
                $overdueCriteria['createdDateEnd'] = $overdueTime;
            }
            if (array_key_exists($status, $mpStatus)) {
                unset($overdueCriteria['status']);
                $overdueTime->sub(21, Zend_Date::DAY);
                $overdueCriteria['mp'] = $mpStatus[$status];                
                $overdueCriteria['mssDateEnd'] = $overdueTime;
            }
            if ('EN' == $status) {
                $overdueTime->sub(0, Zend_Date::DAY);
                $overdueCriteria['estDateEnd'] = $overdueTime;
            }
            if (array_key_exists($status, $epStatus)) {
                unset($overdueCriteria['status']);
                $overdueTime->sub(14, Zend_Date::DAY);
                $overdueCriteria['ep'] = $epStatus[$status];
                $overdueCriteria['estDateEnd'] = $overdueTime;
            }
            $overdueCount = $this->_poam->search($this->_me->systems, array(
                'count' => 'system_id',
                'system_id'
            ), array_merge($criteria, $overdueCriteria));
            foreach ($overdueCount as $row) {
                $summary[$row['system_id']][$status.'overdue'] = $row['count'];
            }
        }

        $this->view->assign('total', $total);
        $this->view->assign('systems', $this->_systemList);
        $this->view->assign('sources', $this->_sourceList);
        $this->view->assign('mpEvalList', $mpEvalList);
        $this->view->assign('epEvalList', $epEvalList);
        $this->view->assign('summary', $summary);
        $this->view->assign('criteria', $criteria);
        $this->view->assign('criteriaUrl', $criteriaUrl);
        $this->render('summary');
        // Disabling the search box for now because it is not working as
        // intended
        //$this->_helper->actionStack('searchbox', 'Remediation', null,
        //    array('action'=>'summary'));
    }

    /**
     *  Do the real searching work. It's a thin wrapper
     *  of poam model's search method.
     */
    protected function _search($criteria, $html=true)
    {
        //refer to searchbox.tpl for a complete status list
        $internalCrit = & $criteria;
        if (!empty($criteria['status'])) {
            $now = clone parent::$now;
            switch ($criteria['status']) {
            case 'NEW':
                $internalCrit['status'] = 'NEW';
                break;

            case 'OPEN':
                $internalCrit['status'] = 'OPEN';
                $internalCrit['type'] = array(
                    'CAP',
                    'FP',
                    'AR'
                );
                break;

            case 'EN':
                $internalCrit['status'] = 'EN';
                break;

            case 'CLOSED':
                $internalCrit['status'] = 'CLOSED';
                break;

            case 'NOT-CLOSED':
                $internalCrit['status'] = array(
                    'OPEN',
                    'MSA',
                    'EN',
                    'EP'
                );
                break;

            case 'NOUP-30':
                $internalCrit['status'] = array(
                    'OPEN',
                    'MSA',
                    'EN',
                    'EP'
                );
                $internalCrit['modify_ts'] = $now->sub(30, Zend_Date::DAY);
                break;

            case 'NOUP-60':
                $internalCrit['status'] = array(
                    'OPEN',
                    'MSA',
                    'EN',
                    'EP'
                );
                $internalCrit['modify_ts'] = $now->sub(60, Zend_Date::DAY);
                break;

            case 'NOUP-90':
                $internalCrit['status'] = array(
                    'OPEN',
                    'MSA',
                    'EN',
                    'EP'
                );
                $internalCrit['modify_ts'] = $now->sub(90, Zend_Date::DAY);
                break;
            default :
                $evaluation = new Evaluation();
                $query = $evaluation->select()->from($evaluation, array('precedence_id', 'group'))
                                              ->where('nickname = ?', $criteria['status']);
                $ret = $evaluation->fetchRow($query)->toArray();
                if (!empty($ret)) {
                    $precedenceId = $ret['precedence_id'];
                    $group = $ret['group'];
                    if ('ACTION' == $group) {
                        $internalCrit['status'] = 'MSA';
                        $internalCrit['mp']     = $precedenceId;
                    }
                    if ('EVIDENCE' == $group) {
                        $internalCrit['status'] = 'EP';
                        $internalCrit['ep']     = $precedenceId;
                    }
                }
            }
        }
        $list = $this->_poam->search($this->_me->systems, array(
            'id',
            'source_id',
            'system_id',
            'type',
            'status',
            'finding_data',
            'action_est_date',
            'count' => 'count(*)'
        ), $internalCrit, $this->_paging['currentPage'],
            $this->_paging['perPage'], $html);
        $total = array_pop($list);
        $this->_paging['totalItems'] = $total;
        $this->_paging['fileName'] = "{$this->_pagingBasePath}/p/%d";
        $lastSearchUrl = str_replace('%d', $this->_paging['currentPage'],
            $this->_paging['fileName']);
        $urlNamespace = new Zend_Session_Namespace('urlNamespace');
        $urlNamespace->lastSearch = $lastSearchUrl;
        $pager = & Pager::factory($this->_paging);
        $this->view->assign('list', $list);
        $this->view->assign('systems', $this->_systemList);
        $this->view->assign('sources', $this->_sourceList);
        $this->view->assign('total_pages', $total);
        $this->view->assign('links', $pager->getLinks());
        $this->render('search');
    }
    public function searchboxAction()
    {
        Config_Fisma::requirePrivilege('remediation', 'read');
        
        $req = $this->getRequest();
        $this->_pagingBasePath.= '/panel/remediation/sub/searchbox/s/search';
        // parse the params of search
        $criteria['systemId'] = $req->getParam('system_id');
        $criteria['sourceId'] = $req->getParam('source_id');
        $criteria['type'] = $req->getParam('type');
        $criteria['status'] = $req->getParam('status');
        $criteria['ids'] = $req->getParam('ids');
        $criteria['assetOwner'] = $req->getParam('asset_owner', 0);
        $criteria['est_date_begin'] = $req->getParam('est_date_begin');
        $criteria['est_date_end'] = $req->getParam('est_date_end');
        // mitigation strategy submit date
        $criteria['mss_date_begin'] = $req->getParam('mss_date_begin');
        $criteria['mss_date_end'] = $req->getParam('mss_date_end');
        $criteria['created_date_begin'] = $req->getParam('created_date_begin');
        $criteria['created_date_end'] = $req->getParam('created_date_end');
        $criteria['order'] = array();
        if ($req->getParam('sortby') != null
            && $req->getParam('order') != null) {
            array_push($criteria['order'], $req->getParam('sortby'));
            array_push($criteria['order'], $req->getParam('order'));
        }

        $this->makeUrl($criteria);
        $this->view->assign('url', $this->_pagingBasePath);
        $this->view->assign('criteria', $criteria);
        $this->view->assign('systems', $this->_systemList);
        $this->view->assign('sources', $this->_sourceList);
        $this->render();
        if ('search' == $req->getParam('s')) {
            if (!empty($criteria['est_date_begin'])) {
                $criteria['estDateBegin'] = new Zend_Date($criteria['est_date_begin'], 'Y-m-d');
            }
            if (!empty($criteria['est_date_end'])) {
                $criteria['estDateEnd'] = new Zend_Date($criteria['est_date_end'], 'Y-m-d');
            }
            if (!empty($criteria['created_date_begin'])) {
                $criteria['createdDateBegin'] = new Zend_Date($criteria['created_date_begin'], 'Y-m-d');
            }
            if (!empty($criteria['created_date_end'])) {
                $criteria['createdDateEnd'] = new Zend_Date($criteria['created_date_end'], 'Y-m-d');
            }
            if (!empty($criteria['mss_date_begin'])) {
                $criteria['mssDateBegin'] = new Zend_Date($criteria['mss_date_begin'], 'Y-m-d');
            }
            if (!empty($criteria['mss_date_end'])) {
                $criteria['mssDateEnd'] = new Zend_Date($criteria['mss_date_end'], 'Y-m-d');
            }
            unset($criteria['est_date_begin'], $criteria['est_date_end'],
                $criteria['created_date_begin'], $criteria['created_date_end']);
            $this->_pagingBasePath = $req->getBaseUrl()
                . '/panel/remediation/sub/searchbox/s/search';
            $this->_paging['currentPage'] = $req->getParam('p', 1);
            foreach ($criteria as $key => $value) {
                if (!empty($value)) {
                    if ($value instanceof Zend_Date) {
                        $this->_pagingBasePath.=
                            '/' . $key . '/' . $value->toString('Ymd') . '';
                    } else {
                        $this->_pagingBasePath.=
                            '/' . $key . '/' . $value . '';
                    }
                }
            }
            $this->_search($criteria, false);
        }
    }
    /**
     Get remediation detail info
     *
     */
    public function viewAction()
    {
        Config_Fisma::requirePrivilege('remediation', 'read');
        
        $req = $this->getRequest();
        $id = $req->getParam('id');
        $poamDetail = $this->_poam->getDetail($id);
        if (empty($poamDetail)) {
            throw new Exception_General("POAM($id) is not found,
                Make sure a valid ID is inputed");
        }

        if (!empty($poamDetail['action_est_date'])
            && $poamDetail['action_est_date'] != $poamDetail['action_current_date']) {
            $query = $this->_poam->getAdapter()->select()
                          ->from(array('al'=>'audit_logs'), 'date_format(timestamp, "%Y-%m-%d") as time')
                          ->join(array('u'=>'users'), 'al.user_id = u.id',
                                 array('u.name_first','u.name_last'))
                          ->where('al.poam_id = ?', $id)
                          ->where('al.description like "%action_current_date%"')
                          ->order('al.id DESC');
            $justification = $this->_poam->getAdapter()->fetchRow($query);
            $this->view->assign('justification', $justification);
        }
        
        $msEvaluation = $this->_poam->getActEvaluation($id);
        $evalModel = new Evaluation();
        $msEvallist = $evalModel->getEvalList('ACTION');
        $mss = array();
        if (!empty($msEvaluation)) {
            $i = 0;
            foreach ($msEvaluation as $k=>$row) {
                if ($k != 0 && !($row['precedence_id'] > $msEvaluation[$k-1]['precedence_id'])) {
                    $i++;
                }
                $mss[$i][] = $row;
                if ($k == count($msEvaluation)-1) {
                    if ($row['decision'] == 'DENIED') {
                        $mss[$i+1] = $msEvallist;
                    } else {
                        if ($row['precedence_id'] < count($msEvallist)-1 ) {
                            // count($msEvallist)-1 is the max precedence_id
                            $flag = count($msEvallist)-1-$row['precedence_id'];
                            if ($flag == 1) {
                                $lastEval = array_slice($msEvallist, -1);
                            } else {
                                $lastEval = array_slice($msEvallist, $flag-1);
                            }
                            foreach ($lastEval as $v) {
                                $mss[$i][] = $v;
                            }
                        }
                    }
                }
            }
        } else {
            $mss[] = $msEvallist;
        }

        $evEvaluation = $this->_poam->getEvEvaluation($id);
        // currently we don't need to support the comments for est_date change
        //$act_evaluation = $this->_poam->getActEvaluation($id);
        $evs = array();
        foreach ($evEvaluation as $evEval) {
            $evid = & $evEval['id'];
            if (!isset($evs[$evid]['ev'])) {
                $evs[$evid]['ev'] = array_slice($evEval, 0, 5);
            }
            $evs[$evid]['eval'][$evEval['eval_name']] =
                array_slice($evEval, 5);
        }
        $this->view->assign('poam', $poamDetail);
        $this->view->assign('logs', $this->_poam->getLogs($id));
        $this->view->assign('ev_evals', $evs);
        $this->view->assign('ms_evals', $mss);
        $this->view->assign('system_list', $this->_systemList);
        $this->view->assign('network_list', $this->_networkList);
        $this->render();
    }
    
    /**
     * modifyAction() - ???
     *
     * @todo Do fine-grained access-control here
     */
    public function modifyAction()
    {
        Config_Fisma::requirePrivilege('remediation', 'update_finding');
        
        $req = $this->getRequest();
        $id = $req->getParam('id');
        $poam = $req->getPost('poam');
        if (!empty($poam)) {
            try {
                $oldpoam = $this->_poam->find($id)->toArray();
                if (empty($oldpoam)) {
                    throw new Exception_General("incorrect ID specified for poam");
                } else {
                    $oldpoam = $oldpoam[0];
                }
                if (isset($poam['ecd_justification']) && empty($poam['ecd_justification'])) {
                    // English check
                    throw new Exception_General("Please input your ECD change justification, ".
                        "and the ECD data is not changed");
                }
                $where = $this->_poam->getAdapter()->quoteInto('id = ?', $id);
                $logContent = "Changed:";
                //@todo sanity check
                //@todo this should be encapsulated in a single transaction
                foreach ($poam as $k => $v) {
                    if ($k == 'type' && $oldpoam['status'] == 'NEW') {
                        assert(empty($poam['status']));
                        $poam['status'] = 'OPEN';
                        $poam['modify_ts'] = self::$now->toString('Y-m-d H:i:s');
                    }
                    ///@todo SSO can only approve the action after all the required
                    // info provided
                }
                $result = $this->_poam->update($poam, $where);
                        
                // Generate notifications and audit records if the update is
                // successful
                $notificationsSent = array();
                if ( $result > 0 ) {
                    foreach ($poam as $k => &$v) {
                        // We shouldn't send the same type of notification twice
                        // in one update. $notificationsSent is a set which
                        // tracks which notifications we have already created.
                        if (array_key_exists($k, $this->_notificationArray)
                            && !array_key_exists($this->_notificationArray[$k],
                                                 $notificationsSent)) {
                            $this->_notification->add($this->_notificationArray[$k],
                                $this->_me->account,
                                "PoamID: $id",
                                isset($poam['system_id'])?$poam['system_id']: $oldpoam['system_id']);
                            $notificationsSent[$this->_notificationArray[$k]] = 1;
                        }

                        $logContent =
                            "Update: $k\nOriginal: \"{$oldpoam[$k]}\" New: \"$v\"";
                        $this->_poam->writeLogs($id, $this->_me->id,
                            self::$now->toString('Y-m-d H:i:s'), 'MODIFICATION',
                            $logContent);
                    }
                }
            } catch (Exception_General $e) {
                if ($e instanceof Exception_General) {
                    $message = $e->getMessage();
                } else {
                    $message = "Failed to modify the poam.";
                }
                $this->message($message, self::M_WARNING);
            }
        }
        
        //throw new Fisma_Excpection('POAM not updated for some reason');
        $this->_forward('remediation', 'Panel', null, array('sub'=>'view',
                                                            'id' => $id));
    }

    /**
     * Mitigation Strategy Approval Process
     */
    public function msaAction()
    {
        $poamId = $this->_request->getParam('id');
        $evalId = $this->_request->getParam('eval_id');
        $isMsa  = $this->_request->getParam('is_msa');
        $decision = $this->_request->getPost('decision');
        $oldpoam = $this->_poam->find($poamId)->toArray();
        if (empty($oldpoam)) {
            throw new Exception_General('incorrect ID specified for poam');
        } else {
                $oldpoam = $oldpoam[0];
        }
        if (isset($isMsa)) {
            if (!in_array($isMsa, array(0, 1))) {
                throw new Exception_General('incorrect mitigation strategy operate');
            }
            if (1 == $isMsa) {
                $poam['status'] = 'MSA';
                $poam['mss_ts'] = self::$now->toString('Y-m-d H:i:s');
            } else {
                $this->_poam->getAdapter()->delete('poam_evaluations', 'group_id = '.$poamId.' AND eval_id IN '.
                    '(SELECT id FROM `evaluations` WHERE `group` = "ACTION")');
                $poam['status'] = 'OPEN';
            }
        }

        if (!empty($decision)) {
            $poamEvalId = $this->_poam->reviewEv($poamId, array('decision' => $decision,
                                                               'eval_id'  => $evalId,
                                                               'user_id'  => $this->_me->id,
                                                               'date'     => self::$now->toString('Y-m-d')));
            $evaluation = new Evaluation();
            $msEvalList = $evaluation->getEvalList('ACTION');
            $ret = $evaluation->find($evalId);
            $evalNickname = $ret->current()->nickname;
            $logContent = "Update: $evalNickname\nOriginal: \"NONE\" New: \"".$decision."\"";
            $this->_poam->writeLogs($poamId, $this->_me->id, 
                self::$now->toString('Y-m-d H:i:s'), 'MODIFICATION', $logContent);

            $this->_notification->add($ret->current()->event_id,
                        $this->_me->account,
                        "PoamID: $poamId",
                        $oldpoam['system_id']);

            if ('APPROVED' == $decision) {
                if (empty($oldpoam['action_est_date'])) {
                    $poam['action_est_date'] = $oldpoam['action_current_date'];
                }                
                if ($evalId == $msEvalList[count($msEvalList)-1]['id']) {
                    $poam['status'] = 'EN';
                }
            } 
            if ('DENIED' == $decision) {
                $poam['status'] = 'OPEN';
                $topic = $this->_request->getParam('topic');
                $body = $this->_request->getParam('reject');
                $comm = new Comments();
                $comm->insert(array('poam_evaluation_id' => $poamEvalId,
                                    'user_id' => $this->_me->id,
                                    'date' => self::$now->toString('Y-m-d H:i:s'),
                                    'topic' => $topic));
            }

        }

        if (!empty($poam)) {
            $this->_poam->update($poam, 'id = '. $poamId);
        }
        $this->_redirect('/panel/remediation/sub/view/id/' . $poamId, array(
            'exit'
        ));
    }

    public function uploadevidenceAction()
    {
        Config_Fisma::requirePrivilege('remediation', 'update_evidence');
        
        $req = $this->getRequest();
        $id = $req->getParam('id');
        define('EVIDENCE_PATH', APPLICATION_ROOT . '/public/evidence');
        if ($_FILES && $id > 0) {
            $poam = $this->_poam->find($id)->toArray();
            if (empty($poam)) {
                throw new Exception_General('incorrect ID specified for poam');
            } else {
                $poam = $poam[0];
            }
            
            $userId = $this->_me->id;
            $nowStr = self::$now->toString('Y-m-d-his');
            if (!file_exists(EVIDENCE_PATH)) {
                mkdir(EVIDENCE_PATH, 0755);
            }
            if (!file_exists(EVIDENCE_PATH .'/'. $id)) {
                mkdir(EVIDENCE_PATH .'/'. $id, 0755);
            }
            $count = 0;
            $filename = preg_replace('/^([^.]*)(\.[^.]*)?\.([^.]*)$/',
                '$1$2-' . $nowStr . '.$3', $_FILES['evidence']['name'],
                2, $count);
            $absFile = EVIDENCE_PATH ."/{$id}/{$filename}";
            $absFile = EVIDENCE_PATH ."/{$id}/{$filename}";
            if ($count > 0) {
                $resultMove =
                    move_uploaded_file($_FILES['evidence']['tmp_name'],
                        $absFile);
                if ($resultMove) {
                    chmod($absFile, 0755);
                } else {
                    throw new Exception_General('Failed in move_uploaded_file(). '
                        . $absFile . $_FILES['evidence']['error']);
                }
            } else {
                throw new Exception_General('The filename is not valid');
            }
            $today = substr($nowStr, 0, 10);
            $data = array(
                'poam_id' => $id,
                'submission' => $filename,
                'submitted_by' => $userId,
                'submit_ts' => $today
            );
            $db = Zend_Registry::get('db');
            $result = $db->insert('evidences', $data);
            $evidenceId = $db->LastInsertId();
            $this->_notification->add(Notification::EVIDENCE_APPROVAL_1ST,
                $this->_me->account,
                "PoamId: $id",
                $poam['system_id']);

            $updateData = array(
                'status' => 'EP',
                'action_actual_date' => $today
            );
            $result = $this->_poam->update($updateData, "id = $id");
            if ($result > 0) {
                $logContent = "Changed: status: EP . Upload evidence:"
                              ." $filename OK";
                $this->_poam->writeLogs($id, $userId,
                    self::$now->toString('Y-m-d H:i:s'),
                    'UPLOAD EVIDENCE', $logContent);
            }
        }
        $this->_redirect('/panel/remediation/sub/view/id/' . $id);
    }
    /**
     *  Handle the evidence evaluations
     */
    public function evidenceAction()
    {
        $req = $this->getRequest();
        $evalId = $req->getParam('evaluation');
        $precedenceId = $req->getParam('precedence');
        $decision = $req->getParam('decision');
        $eid = $req->getParam('id');
        $ev = new Evidence();
        $evDetail = $ev->find($eid);

        $eval = new Evaluation();
        $evalList = $eval->getEvalList('EVIDENCE');

        // Get the poam data because we need system_id to generate the
        // notification
        $poam = $this->_poam->find($evDetail->current()->poam_id)->toArray();
        if (empty($poam)) {
            throw new Exception_General('incorrect ID specified for poam');
        } else {
            $poam = $poam[0];
        }
        
        if (empty($evDetail)) {
            throw new Exception_General('Wrong evidence id:' . $eid);
        }
        if ($decision == 'APPROVE') {
            $decision = 'APPROVED';
        } else if ($decision == 'DENY') {
            $decision = 'DENIED';
        } else {
            throw new Exception_General('Wrong decision:' . $decision);
        }
        $poamId = $evDetail->current()->poam_id;
        $logContent = "";
        if (in_array($decision, array(
            'APPROVED',
            'DENIED'
        ))) {
            $logContent = "";
            $evvId = $this->_poam->reviewEv($eid, array(
                'decision' => $decision,
                'eval_id' => $evalId,
                'user_id' => $this->_me->id,
                'date' => self::$now->toString('Y-m-d')
            ));

            $logContent.= $evalList[$precedenceId]['nickname'] ." Decision: $decision.";

            if ('APPROVED' == $decision) {
                $this->_notification->add($evalList[$precedenceId]['event_id'], $this->_me->account,
                                          "PoamId: $poamId", $poam['system_id']);

                
                if ($precedenceId == count($evalList)-1) {
                    $logContent.= " Status: CLOSED";
                    $this->_poam->update(array('status' => 'CLOSED'), 'id=' . $poamId);
            
                    $this->_notification->add(Notification::POAM_CLOSED, $this->_me->account, 
                                         "PoamId: $poamId", $poam['system_id']);
                }
            } else {
                $this->_poam->update(array('status' => 'EN'), 'id=' . $poamId);
                $topic = $req->getParam('topic');
                $body = $req->getParam('reject');
                $comm = new Comments();
                $comm->insert(array('poam_evaluation_id' => $evvId,
                                    'user_id' => $this->_me->id,
                                    'date' => 'CURDATE()',
                                    'topic' => $topic));

                $logContent .= " Status: EN. Justification: $topic";
                $this->_notification->add(Notification::EVIDENCE_DENIED,
                                          $this->_me->account,
                                          "PoamId: $poamId",
                                          $poam['system_id']);
            }
            if (!empty($logContent)) {
                $logContent = "Changed: $logContent";
                $this->_poam->writeLogs($poamId, $this->_me->id,
                    self::$now->toString('Y-m-d H:i:s'),
                    'EVIDENCE EVALUATION', $logContent);
            }
        }
        $this->_redirect('/panel/remediation/sub/view/id/' . $poamId, array(
            'exit'
        ));
    }

    /**
     *  Generate RAF report
     *
     *  It can handle different format of RAF report.
     */
    public function rafAction()
    {
        Config_Fisma::requirePrivilege('report', 'generate_system_rafs');
        
        $id = $this->_req->getParam('id');
        $this->_helper->layout->disableLayout(true);
        $this->_helper->contextSwitch()->addContext('pdf', array(
            'suffix' => 'pdf',
            'headers' => array(
                'Content-Disposition' =>
                    "attachement;filename=\"{$id}_raf.pdf\"",
                'Content-Type' => 'application/pdf'
            )
        ))->addActionContext('raf', array(
            'pdf'
        ))->initContext();
        $poamDetail = $this->_poam->getDetail($id);
        if (empty($poamDetail)) {
            throw new Exception_General(
                "Not able to get details for this POAM ID ($id)");
        }
        $this->view->assign('poam', $poamDetail);
        $this->view->assign('system_list', $this->_systemList);
        $this->view->assign('source_list', $this->_sourceList);
        $this->render();
    }
}
