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
        $this->_helper->actionStack('searchbox', 'Remediation');
        $this->_helper->actionStack('summary', 'Remediation');
    }
    /**
     *  Display the summary page of remediation, per systems.
     */
    public function summaryAction()
    {
        $criteria['systemId'] = $this->_request->getParam('system_id');
        $criteria['sourceId'] = $this->_request->getParam('source_id');
        $criteria['type'] = $this->_request->getParam('type');
        $criteria['status'] = $this->_request->getParam('status');
        $criteria['ids'] = $this->_request->getParam('ids');
        $criteria['assetOwner'] = $this->_request->getParam('asset_owner', 0);

        $tmp = $this->_request->getParam('est_date_begin');
        if (!empty($tmp)) {
            $criteria['estDateBegin'] = new Zend_Date($tmp,
                Zend_Date::DATES);
        }
        $tmp = $this->_request->getParam('est_date_end');
        if (!empty($tmp)) {
            $criteria['estDateEnd'] = new Zend_Date($tmp, Zend_Date::DATES);
        }
        $tmp = $this->_request->getParam('created_date_begin');
        if (!empty($tmp)) {
            $criteria['createdDateBegin'] = new Zend_Date($tmp,
                Zend_Date::DATES);
        }
        $tmp = $this->_request->getParam('created_date_end');
        if (!empty($tmp)) {
            $criteria['createdDateEnd'] = new Zend_Date($tmp,
                Zend_Date::DATES);
        }

        $today = parent::$now->toString('Ymd');
        $summaryTmp = array(
            'NEW' => 0,
            'OPEN' => 0,
            'EN' => 0,
            'EO' => 0,
            'EP' => 0,
            'EP_SNP' => 0,
            'EP_SSO' => 0,
            'ES' => 0,
            'CLOSED' => 0,
            'TOTAL' => 0
        );

        if ( !empty($criteria['systemId']) ) {
            $sum = array('0' => $summaryTmp);
            $summary = array($criteria['systemId'] => $summaryTmp);
        } else {
            // mock array_fill_key in 5.2.0
            $count = count($this->_me->systems);
            if ( 0 == $count ) {
                $summary = array();
            } else {
                $sum = array_fill(0, $count, $summaryTmp);
                $summary = array_combine($this->_me->systems, $sum);
            }
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
            $summary[$id]['NEW'] = nullGet($s['NEW'], 0);
            $summary[$id]['OPEN'] = nullGet($s['OPEN'], 0);
            $summary[$id]['ES'] = nullGet($s['ES'], 0);
            //$summary[$id]['EN'] = nullGet($s['EN'],0);
            $summary[$id]['EP'] = nullGet($s['EP'], 0); //temp placeholder
            $summary[$id]['CLOSED'] = nullGet($s['CLOSED'], 0);
            $summary[$id]['TOTAL'] = array_sum($s);
            $total['NEW']+= $summary[$id]['NEW'];
            //$total['EN'] += $summary[$id]['EN'];
            $total['CLOSED']+= $summary[$id]['CLOSED'];
            $total['OPEN']+= $summary[$id]['OPEN'];
            $total['ES']+= $summary[$id]['ES'];
            $total['TOTAL']+= $summary[$id]['TOTAL'];
        }
        $eoCount = $this->_poam->search($this->_me->systems, array(
            'count' => 'system_id',
            'system_id'
        ), array_merge($criteria, array(
            'status' => 'EN',
            'estDateEnd' => parent::$now
        )));
        foreach ($eoCount as $eo) {
            $summary[$eo['system_id']]['EO'] = $eo['count'];
            $total['EO']+= $summary[$eo['system_id']]['EO'];
        }
        $enCount = $this->_poam->search($this->_me->systems, array(
            'count' => 'system_id',
            'system_id'
        ), array_merge($criteria, array(
            'status' => 'EN',
            'estDateBegin' => parent::$now
        )));
        foreach ($enCount as $en) {
            $summary[$en['system_id']]['EN'] = $en['count'];
            $total['EN']+= $summary[$en['system_id']]['EN'];
        }
        $spsso = $this->_poam->search($this->_me->systems, array(
            'count' => 'system_id',
            'system_id'
        ), array_merge(array(
            'ep' => 0
        ), $criteria));
        foreach ($spsso as $sp) {
            $summary[$sp['system_id']]['EP_SSO'] = $sp['count'];
            $total['EP_SSO']+= $sp['count'];
        }
        $spsnp = $this->_poam->search($this->_me->systems, array(
            'count' => 'system_id',
            'system_id'
        ), array_merge(array(
            'ep' => 1
        ), $criteria));
        foreach ($spsnp as $sp) {
            $summary[$sp['system_id']]['EP_SNP'] = $sp['count'];
            $total['EP_SNP']+= $sp['count'];
        }
        $this->view->assign('total', $total);
        $this->view->assign('systems', $this->_systemList);
        $this->view->assign('summary', $summary);
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
    protected function _search($criteria)
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
                //Should we include EO status in?
                $internalCrit['estDateBegin'] = $now;
                break;

            case 'EO':
                $internalCrit['status'] = 'EN';
                $internalCrit['estDateEnd'] = $now;
                break;

            case 'EP-SSO':
                ///@todo EP searching needed
                $internalCrit['status'] = 'EP';
                $internalCrit['ep'] = 0; //level
                break;

            case 'EP-SNP':
                $internalCrit['status'] = 'EP';
                $internalCrit['ep'] = 1; //level
                break;

            case 'ES':
                $internalCrit['status'] = 'ES';
                break;

            case 'CLOSED':
                $internalCrit['status'] = 'CLOSED';
                break;

            case 'NOT-CLOSED':
                $internalCrit['status'] = array(
                    'OPEN',
                    'EN',
                    'EP',
                    'ES'
                );
                break;

            case 'NOUP-30':
                $internalCrit['status'] = array(
                    'OPEN',
                    'EN',
                    'EP',
                    'ES'
                );
                $internalCrit['modify_ts'] = $now->sub(30, Zend_Date::DAY);
                break;

            case 'NOUP-60':
                $internalCrit['status'] = array(
                    'OPEN',
                    'EN',
                    'EP',
                    'ES'
                );
                $internalCrit['modify_ts'] = $now->sub(60, Zend_Date::DAY);
                break;

            case 'NOUP-90':
                $internalCrit['status'] = array(
                    'OPEN',
                    'EN',
                    'EP',
                    'ES'
                );
                $internalCrit['modify_ts'] = $now->sub(90, Zend_Date::DAY);
                break;
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
            $this->_paging['perPage']);
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
        $req = $this->getRequest();
        $this->_pagingBasePath.= '/panel/remediation/sub/searchbox/s/search';
        // parse the params of search
        $criteria['systemId'] = $req->getParam('system_id');
        $criteria['sourceId'] = $req->getParam('source_id');
        $criteria['type'] = $req->getParam('type');
        $criteria['status'] = $req->getParam('status');
        $criteria['ids'] = $req->getParam('ids');
        $criteria['assetOwner'] = $req->getParam('asset_owner', 0);
        $criteria['order'] = array();
        if ($req->getParam('sortby') != null
            && $req->getParam('order') != null) {
            array_push($criteria['order'], $req->getParam('sortby'));
            array_push($criteria['order'], $req->getParam('order'));
        }
        $tmp = $req->getParam('est_date_begin');
        if (!empty($tmp)) {
            $criteria['estDateBegin'] = new Zend_Date($tmp, Zend_Date::DATES);
        }
        $tmp = $req->getParam('est_date_end');
        if (!empty($tmp)) {
            $criteria['estDateEnd'] = new Zend_Date($tmp, Zend_Date::DATES);
        }
        $tmp = $req->getParam('created_date_begin');
        if (!empty($tmp)) {
            $criteria['createdDateBegin'] = new Zend_Date($tmp,
                Zend_Date::DATES);
        }
        $tmp = $req->getParam('created_date_end');
        if (!empty($tmp)) {
            $criteria['createdDateEnd'] = new Zend_Date($tmp);
        }

        if ('summary' == $this->_request->getParam('action')) {
            $postAction = "/panel/remediation/sub/summary";
        } else {
            $postAction = "/panel/remediation/sub/searchbox/s/search";
        }

        $this->makeUrl($criteria);
        $this->view->assign('url', $this->_pagingBasePath);
        $this->view->assign('criteria', $criteria);
        $this->view->assign('systems', $this->_systemList);
        $this->view->assign('sources', $this->_sourceList);
        $this->view->assign('postAction', $postAction);
        $this->render();
        if ('search' == $req->getParam('s')) {
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
            $this->_search($criteria);
        }
    }
    /**
     Get remediation detail info
     *
     */
    public function viewAction()
    {
        $req = $this->getRequest();
        $id = $req->getParam('id');
        $poamDetail = $this->_poam->getDetail($id);
        if (empty($poamDetail)) {
            throw new FismaException("POAM($id) is not found,
                Make sure a valid ID is inputed");
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
        $this->view->assign('system_list', $this->_systemList);
        $this->view->assign('network_list', $this->_networkList);
        $this->render();
    }
    public function modifyAction()
    {
        $req = $this->getRequest();
        $id = $req->getParam('id');
        $poam = $req->getPost('poam');
        if (!empty($poam)) {
            $oldpoam = $this->_poam->find($id)->toArray();
            if (empty($oldpoam)) {
                throw new FismaException('incorrect ID specified for poam');
            } else {
                $oldpoam = $oldpoam[0];
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
                if ($k == 'action_status' && $v == 'APPROVED') {
                    $poam['status'] = 'EN';
                } elseif ($k == 'action_status' && $v == 'DENIED') {
                    // If the SSO denies, then put back into OPEN status to
                    // make the POAM
                    // editable again.
                    $poam['status'] = 'OPEN';
                }
                ///@todo SSO can only approve the action after all the required
                // info provided
            }
            $result = $this->_poam->update($poam, $where);
                        
            // Generate notifications and audit records if the update is
            // successful
            $notificationsSent = array();
            if ( $result > 0 ) {
                foreach ($poam as $k => $v) {
                    // We shouldn't send the same type of notification twice
                    // in one update. $notificationsSent is a set which
                    // tracks which notifications we have already created.
                    if (array_key_exists($k, $this->_notificationArray)
                        && !array_key_exists($this->_notificationArray[$k],
                                             $notificationsSent)) {
                        $this->_notification->add($this->_notificationArray[$k],
                            $this->_me->account,
                            "PoamID: $id",
                            nullGet($poam['system_id'], $oldpoam['system_id']));
                        $notificationsSent[$this->_notificationArray[$k]] = 1;
                    }

                    $logContent =
                        "Update: $k\nOriginal: \"{$oldpoam[$k]}\" New: \"$v\"";
            	    $this->_poam->writeLogs($id, $this->_me->id,
                        self::$now->toString('Y-m-d H:i:s'), 'MODIFICATION',
                        $logContent);
                }
            }
        }
        //throw new Fisma_Excpection('POAM not updated for some reason');
        $this->_redirect('/panel/remediation/sub/view/id/' . $id);
    }
    public function uploadevidenceAction()
    {
        $req = $this->getRequest();
        $id = $req->getParam('id');
        define('EVIDENCE_PATH', WEB_ROOT . DS . 'evidence');
        if ($_FILES && $id > 0) {
            $poam = $this->_poam->find($id)->toArray();
            if (empty($poam)) {
                throw new FismaException('incorrect ID specified for poam');
            } else {
                $poam = $poam[0];
            }
            
            $userId = $this->_me->id;
            $nowStr = self::$now->toString('Y-m-d-his');
            if (!file_exists(EVIDENCE_PATH)) {
                mkdir(EVIDENCE_PATH, 0755);
            }
            if (!file_exists(EVIDENCE_PATH . DS . $id)) {
                mkdir(EVIDENCE_PATH . DS . $id, 0755);
            }
            $count = 0;
            $filename = preg_replace('/^([^.]*)(\.[^.]*)?\.([^.]*)$/',
                '$1$2-' . $nowStr . '.$3', $_FILES['evidence']['name'],
                2, $count);
            $absFile = EVIDENCE_PATH . DS . $id . DS . $filename;
            if ($count > 0) {
                $resultMove =
                    move_uploaded_file($_FILES['evidence']['tmp_name'],
                        $absFile);
                if ($resultMove) {
                    chmod($absFile, 0755);
                } else {
                    throw new FismaException('Failed in move_uploaded_file(). '
                        . $absFile . $_FILES['evidence']['error']);
                }
            } else {
                throw new FismaException('The filename is not valid');
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
        $decision = $req->getParam('decision');
        $eid = $req->getParam('id');
        $ev = new Evidence();
        $evDetail = $ev->find($eid);

        // Get the poam data because we need system_id to generate the
        // notification
        $poam = $this->_poam->find($evDetail->current()->poam_id)->toArray();
        if (empty($poam)) {
            throw new FismaException('incorrect ID specified for poam');
        } else {
            $poam = $poam[0];
        }
        
        if (empty($evDetail)) {
            throw new FismaException('Wrong evidence id:' . $eid);
        }
        if ($decision == 'APPROVE') {
            $decision = 'APPROVED';
        } else if ($decision == 'DENY') {
            $decision = 'DENIED';
        } else {
            throw new FismaException('Wrong decision:' . $decision);
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
            if ( $evalId == 1 ) {
                $this->_notification
                     ->add(Notification::EVIDENCE_APPROVAL_2ND,
                        $this->_me->account,
                        "PoamId: $poamId",
                        $poam['system_id']);
            }

            $logContent.= " Decision: $decision.";
            if ($decision == 'DENIED') {
                $this->_poam->update(array(
                    'status' => 'EN'
                ), 'id=' . $poamId);
                $topic = $req->getParam('topic');
                $body = $req->getParam('reject');
                $comm = new Comments();
                $comm->insert(array(
                    'poam_evaluation_id' => $evvId,
                    'user_id' => $this->_me->id,
                    'date' => 'CURDATE()',
                    'topic' => $topic,
                    'content' => $body
                ));
                $logContent.= " Status: EN. Topic: $topic. Content: $body.";
                $this->_notification
                     ->add(Notification::EVIDENCE_DENIED,
                        $this->_me->account,
                        "PoamId: $poamId",
                        $poam['system_id']);
            }
            if ($decision == 'APPROVED' && $evalId == 2) {
                $logContent.= " Status: ES";
                $this->_poam->update(array(
                    'status' => 'ES'
                ), 'id=' . $poamId);

                $this->_notification
                     ->add(Notification::EVIDENCE_APPROVAL_3RD,
                        $this->_me->account,
                        "PoamId: $poamId",
                        $poam['system_id']);
            }
            if ($decision == 'APPROVED' && $evalId == 3) {
                $logContent.= " Status: CLOSED";
                $this->_poam->update(array(
                    'status' => 'CLOSED'
                ), 'id=' . $poamId);
            
                $this->_notification
                     ->add(Notification::POAM_CLOSED,
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
            throw new FismaException(
                "Not able to get details for this POAM ID ($id)");
        }
        $this->view->assign('poam', $poamDetail);
        $this->view->assign('system_list', $this->_systemList);
        $this->view->assign('source_list', $this->_sourceList);
        $this->render();
    }
}
