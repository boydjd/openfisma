<?php
/**
 * RemediationController.php
 *
 * Remediation Controller
 *
 * @package    Controller
 * @author     Xhorse   xhorse at users.sourceforge.net
 * @copyright  (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license    http://www.openfisma.org/mw/index.php?title=License
 * @version    $Id$
 */
require_once CONTROLLERS . DS . 'PoamBaseController.php';
require_once MODELS . DS . 'user.php';
require_once MODELS . DS . 'evaluation.php';
require_once APPS . DS . 'Exception.php';
require_once 'Pager.php';
/**
 * Remediation Controller
 * @package Controller
 * @author     Xhorse   xhorse at users.sourceforge.net
 * @copyright  (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license    http://www.openfisma.org/mw/index.php?title=License
 */
class RemediationController extends PoamBaseController
{
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
        require_once MODELS . DS . 'system.php';
        $req = $this->getRequest();
        $today = parent::$now->toString('Ymd');
        $summary_tmp = array(
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
        // mock array_fill_key in 5.2.0
        $count = count($this->me->systems);
        $sum = array_fill(0, $count, $summary_tmp);
        $summary = array_combine($this->me->systems, $sum);
        $total = $summary_tmp;
        $ret = $this->_poam->search($this->me->systems, array(
            'count' => array(
                'status',
                'system_id'
            ) ,
            'status',
            'type',
            'system_id'
        ));
        $sum = array();
        foreach($ret as $s) {
            $sum[$s['system_id']][$s['status']] = $s['count'];
        }
        foreach($sum as $id => & $s) {
            $summary[$id] = $summary_tmp;
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
        $eo_count = $this->_poam->search($this->me->systems, array(
            'count' => 'system_id',
            'system_id'
        ) , array(
            'status' => 'EN',
            'est_date_end' => parent::$now
        ));
        foreach($eo_count as $eo) {
            $summary[$eo['system_id']]['EO'] = $eo['count'];
            $total['EO']+= $summary[$eo['system_id']]['EO'];
        }
        $en_count = $this->_poam->search($this->me->systems, array(
            'count' => 'system_id',
            'system_id'
        ) , array(
            'status' => 'EN',
            'est_date_begin' => parent::$now
        ));
        foreach($en_count as $en) {
            $summary[$en['system_id']]['EN'] = $en['count'];
            $total['EN']+= $summary[$en['system_id']]['EN'];
        }
        $spsso = $this->_poam->search($this->me->systems, array(
            'count' => 'system_id',
            'system_id'
        ) , array(
            'ep' => 0
        ));
        foreach($spsso as $sp) {
            $summary[$sp['system_id']]['EP_SSO'] = $sp['count'];
            $total['EP_SSO']+= $sp['count'];
        }
        $spsnp = $this->_poam->search($this->me->systems, array(
            'count' => 'system_id',
            'system_id'
        ) , array(
            'ep' => 1
        ));
        foreach($spsnp as $sp) {
            $summary[$sp['system_id']]['EP_SNP'] = $sp['count'];
            $total['EP_SNP']+= $sp['count'];
        }
        $this->view->assign('total', $total);
        $this->view->assign('systems', $this->_system_list);
        $this->view->assign('summary', $summary);
        $this->render('summary');
    }
    /**
     *  Do the real searching work. It's a thin wrapper of poam model's search method.
     */
    protected function _search($criteria)
    {
        //refer to searchbox.tpl for a complete status list
        $internal_crit = & $criteria;
        if (!empty($criteria['status'])) {
            $now = clone parent::$now;
            switch ($criteria['status']) {
            case 'NEW':
                $internal_crit['status'] = 'NEW';
                break;

            case 'OPEN':
                $internal_crit['status'] = 'OPEN';
                $internal_crit['type'] = array(
                    'CAP',
                    'FP',
                    'AR'
                );
                break;

            case 'EN':
                $internal_crit['status'] = 'EN';
                //Should we include EO status in?
                $internal_crit['est_date_begin'] = $now;
                break;

            case 'EO':
                $internal_crit['status'] = 'EN';
                $internal_crit['est_date_end'] = $now;
                break;

            case 'EP-SSO':
                ///@todo EP searching needed
                $internal_crit['status'] = 'EP';
                $internal_crit['ep'] = 0; //level
                break;

            case 'EP-SNP':
                $internal_crit['status'] = 'EP';
                $internal_crit['ep'] = 1; //level
                break;

            case 'ES':
                $internal_crit['status'] = 'ES';
                break;

            case 'CLOSED':
                $internal_crit['status'] = 'CLOSED';
                break;

            case 'NOT-CLOSED':
                $internal_crit['status'] = array(
                    'OPEN',
                    'EN',
                    'EP',
                    'ES'
                );
                break;

            case 'NOUP-30':
                $internal_crit['status'] = array(
                    'OPEN',
                    'EN',
                    'EP',
                    'ES'
                );
                $internal_crit['modify_ts'] = $now->sub(30, Zend_Date::DAY);
                break;

            case 'NOUP-60':
                $internal_crit['status'] = array(
                    'OPEN',
                    'EN',
                    'EP',
                    'ES'
                );
                $internal_crit['modify_ts'] = $now->sub(60, Zend_Date::DAY);
                break;

            case 'NOUP-90':
                $internal_crit['status'] = array(
                    'OPEN',
                    'EN',
                    'EP',
                    'ES'
                );
                $internal_crit['modify_ts'] = $now->sub(90, Zend_Date::DAY);
                break;
            }
        }
        $list = $this->_poam->search($this->me->systems, array(
            'id',
            'source_id',
            'system_id',
            'type',
            'status',
            'finding_data',
            'action_est_date',
            'count' => 'count(*)'
        ) , $internal_crit, $this->_paging['currentPage'], $this->_paging['perPage']);
        $total = array_pop($list);
        $this->_paging['totalItems'] = $total;
        $this->_paging['fileName'] = "{$this->_paging_base_path}/p/%d";
        $lastSearch_url = str_replace('%d', $this->_paging['currentPage'], $this->_paging['fileName']);
        $urlNamespace = new Zend_Session_Namespace('urlNamespace');
        $urlNamespace->lastSearch = $lastSearch_url;
        $pager = & Pager::factory($this->_paging);
        $this->view->assign('list', $list);
        $this->view->assign('systems', $this->_system_list);
        $this->view->assign('sources', $this->_source_list);
        $this->view->assign('total_pages', $total);
        $this->view->assign('links', $pager->getLinks());
        $this->render('search');
    }
    public function searchboxAction()
    {
        $req = $this->getRequest();
        $this->_paging_base_path.= '/panel/remediation/sub/searchbox/s/search';
        // parse the params of search
        $criteria['system_id'] = $req->getParam('system_id');
        $criteria['source_id'] = $req->getParam('source_id');
        $criteria['type'] = $req->getParam('type');
        $criteria['status'] = $req->getParam('status');
        $criteria['ids'] = $req->getParam('ids');
        $criteria['asset_owner'] = $req->getParam('asset_owner', 0);
        $criteria['order'] = array();
        if ($req->getParam('sortby') != null && $req->getParam('order') != null) {
            array_push($criteria['order'], $req->getParam('sortby'));
            array_push($criteria['order'], $req->getParam('order'));
        }
        $tmp = $req->getParam('est_date_begin');
        if (!empty($tmp)) {
            $criteria['est_date_begin'] = new Zend_Date($tmp, Zend_Date::DATES);
        }
        $tmp = $req->getParam('est_date_end');
        if (!empty($tmp)) {
            $criteria['est_date_end'] = new Zend_Date($tmp, Zend_Date::DATES);
        }
        $tmp = $req->getParam('created_date_begin');
        if (!empty($tmp)) {
            $criteria['created_date_begin'] = new Zend_Date($tmp, Zend_Date::DATES);
        }
        $tmp = $req->getParam('created_date_end');
        if (!empty($tmp)) {
            $criteria['created_date_end'] = new Zend_Date($tmp, Zend_Date::DATES);
        }
        $this->makeUrl($criteria);
        $this->view->assign('url', $this->_paging_base_path);
        $this->view->assign('criteria', $criteria);
        $this->view->assign('systems', $this->_system_list);
        $this->view->assign('sources', $this->_source_list);
        $this->render();
        if ('search' == $req->getParam('s')) {
            $this->_paging_base_path = $req->getBaseUrl() . '/panel/remediation/sub/searchbox/s/search';
            $this->_paging['currentPage'] = $req->getParam('p', 1);
            foreach($criteria as $key => $value) {
                if (!empty($value)) {
                    if ($value instanceof Zend_Date) {
                        $this->_paging_base_path.= '/' . $key . '/' . $value->toString('Ymd') . '';
                    } else {
                        $this->_paging_base_path.= '/' . $key . '/' . $value . '';
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
        $poam_detail = $this->_poam->getDetail($id);
        if (empty($poam_detail)) {
            throw new fisma_Exception("POAM($id) is not found, Make sure a valid ID is inputed");
        }
        $ev_evaluation = $this->_poam->getEvEvaluation($id);
        // currently we don't need to support the comments for est_date change
        //$act_evaluation = $this->_poam->getActEvaluation($id);
        $evs = array();
        foreach($ev_evaluation as $ev_eval) {
            $evid = & $ev_eval['id'];
            if (!isset($evs[$evid]['ev'])) {
                $evs[$evid]['ev'] = array_slice($ev_eval, 0, 5);
            }
            $evs[$evid]['eval'][$ev_eval['eval_name']] = array_slice($ev_eval, 5);
        }
        $this->view->assign('poam', $poam_detail);
        $this->view->assign('logs', $this->_poam->getLogs($id));
        $this->view->assign('ev_evals', $evs);
        $this->view->assign('system_list', $this->_system_list);
        $this->view->assign('network_list',$this->_network_list);
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
                throw new fisma_Exception('incorrect ID specified for poam');
            } else {
                $oldpoam = $oldpoam[0];
            }
            $where = $this->_poam->getAdapter()->quoteInto('id = ?', $id);
            $log_content = "Changed:";
            //@todo sanity check
            //@todo this should be encapsulated in a single transaction
            foreach($poam as $k => $v) {
                if ($k == 'type' && $oldpoam['status'] == 'NEW') {
                    assert(empty($poam['status']));
                    $poam['status'] = 'OPEN';
                    $poam['modify_ts'] = self::$now->toString('Y-m-d H:i:s');
                }
                if ($k == 'action_status' && $v == 'APPROVED') {
                    $poam['status'] = 'EN';
                } elseif ($k == 'action_status' && $v == 'DENIED') {
                    // If the SSO denies, then put back into OPEN status to make the POAM
                    // editable again.
                    $poam['status'] = 'OPEN';
                }
                ///@todo SSO can only approve the action after all the required info provided
            }
            $result = $this->_poam->update($poam, $where);
            
            // Generate audit log records if the update is successful
            if( $result > 0 ) {
                foreach($poam as $k => $v) {
                    $log_content = "Update: $k\nOriginal: \"{$oldpoam[$k]}\" New: \"$v\"";
            	    $this->_poam->writeLogs($id, $this->me->id, self::$now->toString('Y-m-d H:i:s'), 'MODIFICATION', $log_content);
                }
            }
        }
        //throw new fisma_Excpection('POAM not updated for some reason');
        $this->_redirect('/panel/remediation/sub/view/id/' . $id);
    }
    public function uploadevidenceAction()
    {
        $req = $this->getRequest();
        $id = $req->getParam('id');
        define('EVIDENCE_PATH', WEB_ROOT . DS . 'evidence');
        if ($_FILES && $id > 0) {
            $user_id = $this->me->id;
            $now_str = self::$now->toString('Y-m-d-his');
            if (!file_exists(EVIDENCE_PATH)) {
                mkdir(EVIDENCE_PATH, 0755);
            }
            if (!file_exists(EVIDENCE_PATH . DS . $id)) {
                mkdir(EVIDENCE_PATH . DS . $id, 0755);
            }
            $count = 0;
            $filename = preg_replace('/^([^.]*)\.([^.]*)$/', '$1-' . $now_str . '.$2', $_FILES['evidence']['name'], 2, $count);
            $abs_file = EVIDENCE_PATH . DS . $id . DS . $filename;
            if ($count > 0) {
                $result_move = move_uploaded_file($_FILES['evidence']['tmp_name'], $abs_file);
                if ($result_move) {
                    chmod($abs_file, 0755);
                } else {
                    throw new fisma_Exception('Failed in move_uploaded_file(). ' . $abs_file . $_FILES['evidence']['error']);
                }
            } else {
                throw new fisma_Exception('The filename is not valid');
            }
            $today = substr($now_str, 0, 10);
            $data = array(
                'poam_id' => $id,
                'submission' => $filename,
                'submitted_by' => $user_id,
                'submit_ts' => $today
            );
            $db = Zend_Registry::get('db');
            $result = $db->insert('evidences', $data);
            $update_data = array(
                'status' => 'EP',
                'action_actual_date' => $today
            );
            $result = $this->_poam->update($update_data, "id = $id");
            if ($result > 0) {
                $log_content = "Changed: status: EP . Upload evidence: $filename OK";
                $this->_poam->writeLogs($id, $user_id, self::$now->toString('Y-m-d H:i:s') , 'UPLOAD EVIDENCE', $log_content);
            }
        }
        $this->_redirect('/panel/remediation/sub/view/id/' . $id);
    }
    /**
     *  Handle the evidence evaluations
     */
    public function evidenceAction()
    {
        require_once MODELS . DS . 'evidence.php';
        require_once MODELS . DS . 'comments.php';
        $req = $this->getRequest();
        $eval_id = $req->getParam('evaluation');
        $decision = $req->getParam('decision');
        $eid = $req->getParam('id');
        $ev = new Evidence();
        $ev_detail = $ev->find($eid);
        if (empty($ev_detail)) {
            throw new fisma_Exception('Wrong evidence id:' . $eid);
        }
        if ($decision == 'APPROVE') {
            $decision = 'APPROVED';
        } else if ($decision == 'DENY') {
            $decision = 'DENIED';
        } else {
            throw new fisma_Exception('Wrong decision:' . $decision);
        }
        $poam_id = $ev_detail->current()->poam_id;
        $log_content = "";
        if (in_array($decision, array(
            'APPROVED',
            'DENIED'
        ))) {
            $log_content = "";
            $evv_id = $this->_poam->reviewEv($eid, array(
                'decision' => $decision,
                'eval_id' => $eval_id,
                'user_id' => $this->me->id,
                'date' => self::$now->toString('Y-m-d')
            ));
            $log_content.= " Decision: $decision.";
            if ($decision == 'DENIED') {
                $this->_poam->update(array(
                    'status' => 'EN'
                ) , 'id=' . $poam_id);
                $topic = $req->getParam('topic');
                $body = $req->getParam('reject');
                $comm = new Comments();
                $comm->insert(array(
                    'poam_evaluation_id' => $evv_id,
                    'user_id' => $this->me->id,
                    'date' => 'CURDATE()',
                    'topic' => $topic,
                    'content' => $body
                ));
                $log_content.= " Status: EN. Topic: $topic. Content: $body.";
            }
            if ($decision == 'APPROVED' && $eval_id == 2) {
                $log_content.= " Status: ES";
                $this->_poam->update(array(
                    'status' => 'ES'
                ) , 'id=' . $poam_id);
            }
            if ($decision == 'APPROVED' && $eval_id == 3) {
                $log_content.= " Status: CLOSED";
                $this->_poam->update(array(
                    'status' => 'CLOSED'
                ) , 'id=' . $poam_id);
            }
            if (!empty($log_content)) {
                $log_content = "Changed: $log_content";
                $this->_poam->writeLogs($poam_id, $this->me->id, self::$now->toString('Y-m-d H:i:s') , 'EVIDENCE EVALUATION', $log_content);
            }
        }
        $this->_redirect('/panel/remediation/sub/view/id/' . $poam_id, array(
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
                'Content-Disposition' => "attachement;filename=\"{$id}_raf.pdf\"",
                'Content-Type' => 'application/pdf'
            )
        ))->addActionContext('raf', array(
            'pdf'
        ))->initContext();
        $poam_detail = $this->_poam->getDetail($id);
        if (empty($poam_detail)) {
            throw new fisma_Exception("Not able to get details for this POAM ID ($id)");
        }
        $this->view->assign('poam', $poam_detail);
        $this->view->assign('system_list', $this->_system_list);
        $this->view->assign('source_list', $this->_source_list);
        $this->render();
    }
}
