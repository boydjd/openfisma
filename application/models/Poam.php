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
 * A business object which represents a plan of action and milestones related
 * to a particular finding.
 *
 * @package   Model
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/mw/index.php?title=License
 *
 * @todo This class should probably be merged with the finding class.
 */
class Poam extends Zend_Db_Table
{
    protected $_name = 'poams';
    protected $_primary = 'id';
    protected function _parseWhere ($query, $where)
    {
        assert($query instanceof Zend_Db_Select);
        if (is_array($where)) {
            extract($where);
        }
        if (isset($notStatus)) {
            $query->where("status <> ?", $notStatus);
        }
        if (! empty($id)) {
            $query->where("p.id = ?", $id);
        }
        if (! empty($sourceId)) {
            $query->where("p.source_id = " . $sourceId . "");
        }
        if (! empty($systemId)) {
            $query->where("p.system_id = " . $systemId . "");
        }
        if (! empty($assetOwner)) {
            $query->join('assets', 'p.asset_id = assets.id', array())
                  ->where('assets.system_id = ?', $assetOwner);
        }
        if (! empty($aging)) {
            $agingTime = new Zend_Date();
            $agingTime->sub($aging, Zend_Date::DAY);
            $query->where("p.create_ts > ?", $agingTime->toString('Y-m-d'));
        }
        /// @todo sanitize the $ids
        if (! empty($ids)) {
            $query->where("p.id IN (" . $ids . ")");
        }
        if (! empty($overdue)) {
            $query->where("status != 'CLOSED'");
            if ($overdue['type'] == 'sso') {
                if (isset($overdue['begin_date'])) {
                    $query->where("p.action_actual_date > ?",
                        $overdue['begin_date']->toString('Ymd'));
                }
                if (isset($overdue['end_date'])) {
                    $query->where("p.action_actual_date < ?",
                        $overdue['end_date']->toString('Ymd'));
                }
            } else if ($overdue['type'] == 'action') {
                if (isset($overdue['begin_date'])) {
                    $query->where("p.action_est_date > ?",
                                    $overdue['begin_date']->toString('Ymd'));
                }
                if (isset($overdue['end_date'])) {
                    $query->where("p.action_est_date < ?",
                                    $overdue['end_date']->toString('Ymd'));
                }
            } else {
                throw new Exception_General('Parameters wrong in overdue '
                    . var_export($overdue, true));
            }
        }
        if (! empty($actualDateBegin)) {
            $query->where("p.action_actual_date > ?",
                $actualDateBegin->toString('Y-m-d'));
        }
        if (! empty($actualDateEnd)) {
            $query->where("p.action_actual_date <= ?",
                $actualDateEnd->toString('Y-m-d'));
        }
        if (! empty($estDateBegin)) {
            $query->where("p.action_current_date > ?",
                $estDateBegin->toString('Y-m-d'));
        }
        if (! empty($estDateEnd)) {
            $query->where("p.action_current_date <= ?",
                $estDateEnd->toString('Y-m-d'));
        }
        if (! empty($createdDateBegin)) {
            $query->where("p.create_ts > ?",
                $createdDateBegin->toString('Y-m-d'));
        }
        if (! empty($createdDateEnd)) {
            $query->where("p.create_ts <=?",
                $createdDateEnd->toString('Y-m-d'));
        }
        if (! empty($discoveredDateBegin)) {
            $query->where("p.discover_ts >=?",
                $discoveredDateBegin->toString('Y-m-d'));
        }
        if (! empty($discoveredDateEnd)) {
            $query->where("p.discover_ts <=?",
                $discoveredDateEnd->toString('Y-m-d'));
        }
        // mitigation strategy submit date
        if (! empty($mssDateBegin)) {
            $query->where("p.mss_ts >=?", $mssDateBegin->toString('Y-m-d'));
        }
        if (! empty($mssDateEnd)) {
            $query->where("p.mss_ts <=?", $mssDateEnd->toString('Y-m-d'));
        }
        if (! empty($closedDateBegin)) {
            $query->where("p.close_ts > ?",
                $closedDateBegin->toString('Y-m-d'));
        }
        if (! empty($closedDateEnd)) {
            $query->where("p.close_ts <=?", $closedDateEnd->toString('Y-m-d'));
        }
        if (! empty($type)) {
            if (is_array($type)) {
                $query->where("p.type IN ('" . implode("','", $type) . "')");
            } else {
                $query->where("p.type = ?", $type);
            }
        }
        if (isset($mp)) {
            $query->where("p.status='MSA'");
            if ($mp > 0) {
                $mp --;
                $query->joinLeft(array('pev'=>'poam_evaluations'), 'p.id = pev.group_id', array())
                      ->joinLeft(array('el'=>'evaluations'), 'el.id=pev.eval_id', array())
                      ->where("el.precedence_id='$mp' AND pev.decision='APPROVED'")
                      ->where('ROW(p.id,pev.id)=(SELECT t.group_id,MAX(t.id) FROM poam_evaluations AS t '.
                      ' WHERE t.group_id=p.id GROUP BY t.group_id)');
            } else { //$mp == 0
                $query->joinLeft(array('pev' => 'poam_evaluations'), null, array())
                      ->join(array('el' => 'evaluations'), '(el.id=pev.eval_id AND el.group="ACTION")
                           ON pev.group_id = p.id', array())
                      ->where("ISNULL(pev.id) OR (pev.decision='DENIED' AND ROW(p.id,pev.id)= ".
                      "(SELECT t.group_id,MAX(t.id) FROM poam_evaluations AS t WHERE t.group_id=p.id ".
                      "GROUP BY t.group_id))");
            }
        }
        if (isset($ep)) {
            $query->where("p.status='EP'");
            if ($ep > 0) {
                $ep --;
                $query->join(array('e' => new Zend_Db_Expr("(SELECT MAX(id) as last_eid, poam_id ".
                                   "  FROM evidences GROUP BY poam_id)")), 'e.poam_id=p.id', array())
                    ->joinLeft(array('pev' => 'poam_evaluations'), 'e.last_eid=pev.group_id', array())
                    ->joinLeft(array('el' => 'evaluations'), 'el.id=pev.eval_id', array())
                    ->join(array('ev' => new Zend_Db_Expr("(
                        SELECT e1.id,MAX(eval.precedence_id) level
                        FROM `evidences` AS e1, `poam_evaluations` AS pe, `evaluations` AS eval
                        WHERE ( eval.id = pe.eval_id AND e1.id = pe.group_id 
                                AND eval.group='EVIDENCE' ) 
                        GROUP BY e1.id)")), "ev.id=e.last_eid AND el.precedence_id=ev.level", array())
                    ->where("ev.level='$ep' AND pev.decision='APPROVED'");
            } else { //$ep==0
                $query->join(array('e' => 'evidences'), 'e.poam_id=p.id', array())
                ->joinLeft(array('pev' => 'poam_evaluations'), null, array())
                ->join(array('el' => 'evaluations'), '(el.id=pev.eval_id AND el.group=\'EVIDENCE\') 
                             ON e.id=pev.group_id', array())->where("ISNULL(pev.id) ");
            }
        }
        if (! empty($status)) {
            if (is_array($status)) {
                $query->where("p.status IN ('" . implode("','", $status) . "')");
            } else {
                $query->where("p.status = ?", $status);
            }
        }
        if (! empty($ip) || ! empty($port)) {
            if (! empty($ip)) {
                $query->where('as.address_ip = ?', $ip);
            }
            if (! empty($port)) {
                $query->where('as.address_port = ?', $port);
            }
        }
        if (! empty($group)) {
            if (! is_array($group)) {
                $group = array($group);
            }
            foreach ($group as $g) {
                $query->group($g);
            }
        }
        if (! empty($dateModified)) {
            assert($dateModified instanceof Zend_Date);
            $query->where("p.modify_ts < $dateModified->toString('Y-m-d')");
        }
        return $query;
    }
    /** 
     *  search poam records.
     *  @param $sysIds array system id that limit the searching agency
     *  @param $fields array information contained in the return.
     *         array('key' => 'value'). $fields follow the sytax of
               Zend_Db_Select with 
              exception of 'count'. Here 'count' is an keyword.
              There are 3 cases for fields: 
              1.  $fields = array( 'count'=>'status' ...)
                  It means count and groupby status. 
                  The returned value contains 'count'... and more

     *)' )
                  It return the exact value of count.

     *)' , 'key'=> 'value' , ... ) 
           the count of the result is array_push into the returned variable;
     *  @param $criteria array 
     *  @param $limit integer results number.
     *  @param $pageno integer search start shift
     *  @return a list of record.
     */
    public function search ($sysIds, $fields = '*', $criteria = array(), $currentPage = null, $perPage = null)
    {
        static $extraFields = array(
                             'asset' => array('as.address_ip' => 'ip',
                                              'as.address_port' => 'port',
                                              'as.network_id' => 'network_id',
                                              'as.prod_id' => 'prod_id',
                                              'as.name' => 'asset_name',
                                              'as.system_id' => 'asset_owner'),
                             'source' => array('s.nickname' =>'source_nickname',
                                              's.name' => 'source_name'));
        $ret = array();
        $count = 0;
        $countFields = false;
        if ($fields == '*') {
            $fields = array_merge($this->_cols, $extraFields['asset'],
                                  $extraFields['source']);
        } else if (isset($fields['count'])) {
            $countFields = true;
            if ($fields == 'count' || $fields == array('count' => 'count(*)')) {
                $fields = array(); //count only
            } else {
                if ($fields['count'] != 'count(*)') {
                    $countFields = false;
                    $criteria['group'] = $fields['count'];
                    $fields['count'] = 'count(*)';
                } else {
                    //array_push count
                    unset($fields['count']);
                }
            }
        }
        assert(is_array($fields));
        $tableFields = array_values($fields);
        $pFields = array_diff($fields, $extraFields['asset'],
                              $extraFields['source']);
        $asFields = array_flip(array_intersect($extraFields['asset'], $tableFields));
        $srcFields = array_flip(array_intersect($extraFields['source'], $tableFields));
        $query = $this->_db->select()
                      ->from(array('p' => $this->_name), $pFields);
        if (! empty($sysIds)) {
            $query->where("p.system_id IN ('" . implode("','", $sysIds) . "')");
        }
        if (! empty($asFields)) {
            $query->joinLeft(array('as' => 'assets'), 'as.id = p.asset_id',
                $asFields);
        }
        if (! empty($srcFields)) {
            $query->joinLeft(array('s' => 'sources'), 's.id = p.source_id',
                $srcFields);
        }
        $query->where("p.status != 'DELETED'");
        $query = $this->_parseWhere($query, $criteria);
        if (! empty($criteria['order']) && is_array($criteria['order'])) {
            ///@todo check if all the order by have been in the search fields
            $query->order(implode(' ', $criteria['order']));
        }
        if ($countFields) {
            $countQuery = clone $query;
            $from = $countQuery->getPart(Zend_Db_Select::FROM);
            $countQuery->reset(Zend_Db_Select::COLUMNS);
            $countQuery->reset(Zend_Db_Select::GROUP);
            $countQuery->from(null, array('count' => 'count(*)'));
            $count = $this->_db->fetchOne($countQuery);
            if (empty($pFields)) {
                return $count;
            }
        }
        if (! empty($currentPage) && ! empty($perPage)) {
            $query->limitPage($currentPage, $perPage);
        }
        $ret = $this->_db->fetchAll($query);
        foreach ($ret as &$row) {
            if (! empty($row['status']) && ! empty($row['id'])) {
                $row['status'] = $this->getStatus($row['id']);
            }
        }
        if ($countFields && $count) {
            array_push($ret, $count);
        }
        return $ret;
    }

    /**
        Get poam status
        @param int $id primary key of poam
     */
    public function getStatus ($id)
    {
        if (! is_numeric($id)) {
            throw new Exception_General('Make sure a valid ID is inputed');
        }
        $ret = $this->find($id);
        if ('MSA' == $ret->current()->status) {
            $query = $this->_db->select()
                          ->from(array('pev'=>'poam_evaluations'), 'pev.*')
                          ->join(array('eval'=>'evaluations'), 'eval.id = pev.eval_id',
                              array('eval.nickname', 'precedence_id'))
                          ->where('pev.group_id = ?', $id)
                          ->where('pev.eval_id IN (SELECT id FROM `evaluations` WHERE `group` ="ACTION")')
                          ->order('pev.id DESC');
            $ret = $this->_db->fetchRow($query);
            $eval = new Evaluation();
            $msEvalList = $eval->getEvalList('ACTION');
            if (!empty($ret)) {
                if ('DENIED' == $ret['decision']) {
                    return $msEvalList[0]['nickname'];
                } else {
                    return $msEvalList[$ret['precedence_id']+1]['nickname'];
                }
            } else {
                return $msEvalList[0]['nickname'];
            }
        } else if ('EN' == $ret->current()->status
            && date('Y-m-d H:i:s') > $ret->current()->action_est_date) {
            return 'EO';
        } else if ('EP' == $ret->current()->status) {
             $query = $this->_db->select()
                          ->from(array('pev'=>'poam_evaluations'), 'pev.*')
                          ->join(array('ev'=>'evidences'), 'pev.group_id = ev.id', array())
                          ->join(array('p'=>'poams'), 'ev.poam_id = p.id', array())
                          ->join(array('eval'=>'evaluations'), 'eval.id = pev.eval_id',
                                 array('eval.nickname', 'eval.precedence_id'))
                          ->where('p.id = ?', $id)
                          ->where('pev.eval_id IN (SELECT id FROM `evaluations` WHERE `group` = "EVIDENCE")')
                          ->order('pev.id DESC');
            $ret = $this->_db->fetchRow($query);
            $eval = new Evaluation();
            $evalList = $eval->getEvalList('EVIDENCE');
            if (empty($ret)) {
                return $evalList['0']['nickname'];
            } else {
                if ('DENIED' == $ret['decision']) {
                    return $evalList['0']['nickname'];                
                } else {
                    return $evalList[$ret['precedence_id']+1]['nickname'];
                }
            }
        } else {
            return $ret->current()->status;
        }
    }

    /** 
        Get detail information of a remediation by Id

        @param int $id primary key of poam
     */
    public function &getDetail($id)
    {
        if (! is_numeric($id)) {
            throw new Exception_General('Make sure a valid ID is inputed');
        }
        $poamDetail = $this->search(null, '*', array('id' => $id));
        $ret = array();
        if (empty($poamDetail)) {
            return $ret;
        }
        $ret = $poamDetail[0];
        $query = $this->_db->select()
                      ->from(array('pv' => 'poam_vulns'),
                          array())->where("pv.poam_id = ?", $id)
                      ->join(array('v' => 'vulnerabilities'),
                          'v.type = pv.vuln_type AND v.seq = pv.vuln_seq',
                          array('type' => 'v.type', 'seq' => 'v.seq',
                                'description' => 'description'));
        $vuln = $this->_db->fetchAll($query);
        if (! empty($vuln)) {
            $ret['vuln'] = $vuln;
        }
        $query->reset();
        if (! empty($ret['network_id'])) {
            $query->from(array('n' => 'networks'),
                         array('network_name' => 'n.name'))
                  ->where('n.id = ?', $ret['network_id']);
            $networks = $this->_db->fetchRow($query);
            if (! empty($networks)) {
                $ret['network_name'] = $networks['network_name'];
            }
            $query->reset();
        }
        if (! empty($ret['prod_id'])) {
            $query->from(array('pr' => 'products'),
                array('prod_id' => 'pr.id', 'prod_vendor' => 'pr.vendor',
                      'prod_name' => 'pr.name', 'prod_version' => 'pr.version'))
                  ->where("pr.id = ?", $ret['prod_id']);
            $products = $this->_db->fetchRow($query);
            if (! empty($product)) {
                $ret['product'] = $products;
            }
        }
        if (! empty($ret['blscr_id'])) {
            $query->reset();
            $query->from(array('b' => 'blscrs'), '*')
                  ->where('b.code = ?', $ret['blscr_id']);
            $blscr = $this->_db->fetchRow($query);
            if (! empty($blscr)) {
                $ret['blscr'] = &$blscr;
            }
        }
        return $ret;
    }

    /** Get list of evaluations on evidence of specified poam

        @param $poamIds int|array poam id(s)
        @param $final boolean to get the final status or all the history
        @param $decision enum{'APPROVED','DENIED'}
        @return array list of evidences and their evaluation
     */
    public function getEvEvaluation ($poamId, $final = false, $decision = null)
    {
        if (is_numeric($poamId)) {
            $poamId = array($poamId);
        }
        $query = $this->_db->select()
                      ->from(array('ev' => 'evidences'))
                      ->where('ev.poam_id IN (\''. implode("','", $poamId) .'\')')
                      ->joinLeft(array('pvv' => 'poam_evaluations'),
                          'ev.id=pvv.group_id',
                          array('decision', 'date', 'eval_id' => 'pvv.id'))
                      ->joinLeft(array('pu' => 'users'),
                          'pu.id=ev.submitted_by',
                          array('submitted_by' => 'pu.account'))
                      ->joinLeft(array('u' => 'users'), 'u.id=pvv.user_id',
                          array('username' => 'u.account'))
                      ->joinLeft(array('el' => 'evaluations'),
                          'el.id=pvv.eval_id AND el.group = \'EVIDENCE\'',
                          array('eval_name' => 'el.name', 'el.group',
                                'level' => 'el.precedence_id'))
                      ->order(array('ev.poam_id' , 'ev.id' , 'level ASC'));
        if (! empty($decision)) {
            assert(in_array($decision, array('APPROVED' , 'DENIED')));
            $query->where('pvv.decision =?', $decision);
        }
        $ret = $this->_db->fetchAll($query);
        if ($final) {
            $final = array();
            $lastPid = null;
            foreach ($ret as $k => &$r) {
                if (isset($lastPid) && $r['poam_id'] == $lastPid) {
                    unset($ret[$k]);
                } else {
                    $lastPid = $r['poam_id'];
                }
            }
        }
        return $ret;
    }
    /** 
        Get action evaluations according to poam id(s).

        @param poam_id int|array poam id(s)
        @param decision enum{APPROVED, DENIED, EST_CHANCED}
        @return array list of evaluations
     */
    public function getActEvaluation ($poamId, $decision = null)
    {
        if (is_numeric($poamId)) {
            $poamId = array($poamId);
        }
        $query = $this->_db->select()
                      ->from(array('pev' => 'poam_evaluations'),
                          array('decision' , 'date' , 'pev_id' => 'pev.id'))
                      ->where('pev.group_id IN (\''.implode("','", $poamId).'\')')
                      ->join(array('el' => 'evaluations'),
                          'el.id=pev.eval_id AND el.group = \'ACTION\'', 'el.*')
                      ->joinLeft(array('f'=>'functions'), 'f.id = el.function_id', array('function'=>'f.action'))
                      ->joinLeft(array('u' => 'users'), 'u.id=pev.user_id',
                                 array('username' => 'account'))
                      ->order(array('pev.id', 'pev.date DESC', 'el.precedence_id DESC'));
        if (! empty($decision)) {
            assert(in_array($decision,
                array('APPROVED', 'DENIED', 'EST_CHANCED')));
            $query->where('pev.decision =?', $decision);
        }
        $ret = $this->_db->fetchAll($query);
        return $ret;
    }

    public function reviewEv ($eid, $review)
    {
        $data = array_merge(array('group_id' => $eid), $review);
        $this->_db->insert('poam_evaluations', $data);
        return $this->_db->lastInsertId();
    }
    /** 
        Get audit logs according to poam id

        @param poam_id int the poam id
        @return array list of audit logs sorted by time desc
     */
    public function getLogs ($poamId)
    {
        assert(is_numeric($poamId));
        $query = $this->_db->select()
                      ->from(array('al' => 'audit_logs'))
                      ->join(array('p' => 'poams'), 'p.id = al.poam_id',
                          array())
                      ->join(array('u' => 'users'), 'al.user_id = u.id',
                          array('username' => 'u.account'))
                      ->where("p.id =?", $poamId)
                      ->order("al.timestamp DESC");
        return $this->_db->fetchAll($query);
    }
    public function writeLogs ($poamId, $userId, $timestamp, $event, $logContent)
    {
        $data = array('poam_id' => $poamId, 'user_id' => $userId,
                      'timestamp' => $timestamp, 'event' => $event,
                      'description' => $logContent);
        $result = $this->_db->insert('audit_logs', $data);
    }
    public function fismasearch ($agency)
    {
        $flag = substr($agency, 0, 1);
        $db = $this->_db;
        $fsaSysgroupId = Zend_Registry::get('fsa_sysgroup_id');
        $fpSystemId = Zend_Registry::get('fsa_system_id');
        $startdate = Zend_Registry::get('startdate');
        $enddate = Zend_Registry::get('enddate');
        $query = $db->select()
                    ->from(array('sgs' => 'systemgroup_systems'),
                        array('system_id' => 'system_id'))
                    ->where("sgs.sysgroup_id = " . $fsaSysgroupId . "
                        AND sgs.system_id != " . $fpSystemId . "");
        $result = $db->fetchCol($query);
        $systemIds = implode(',', $result);
        $query = $db->select()->distinct()
                    ->from(array('p' => 'poams'),
                        array('num_poams' => 'count(p.id)'))
                    ->join(array('a' => 'assets'), 'a.id = p.asset_id',
                        array());
        switch ($flag) {
            case 'a':
                switch ($agency) {
                    case 'aaw':
                        $query->where("p.system_id = '$fpSystemId'");
                        break;
                    case 'as':
                        $query->where("p.system_id IN (" . $systemIds . ")");
                        break;
                }
                $query->where("p.create_ts < '$startdate'")
                      ->where("p.close_ts IS NULL
                         OR p.close_ts >= '$startdate'");
                break;
            case 'b':
                switch ($agency) {
                    case 'baw':
                        $query->where("p.system_id = '$fpSystemId'");
                        break;
                    case 'bs':
                        $query->where("p.system_id IN (" . $systemIds . ")");
                        break;
                }
                $query->where("p.create_ts <= '$enddate'")
                      ->where("p.action_est_date <= '$enddate'")
                      ->where("p.action_date_actual >= '$startdate'")
                      ->where("p.action_date_actual <= '$enddate'");
                break;
            case 'c':
                switch ($agency) {
                    case 'caw':
                        $query->where("p.system_id = '$fsaSystemId'");
                        break;
                    case 'cs':
                        $query->where("p.system_id IN (" . $systemIds . ")");
                        break;
                }
                $query->where("p.create_ts <= '$enddate'")
                      ->where("p.action_est_date > '$enddate'")
                      ->where("p.action_date_actual IS NULL");
                break;
            case 'd':
                switch ($agency) {
                    case 'daw':
                        $query->where("p.system_id = '$fsaSystemId'");
                        break;
                    case 'ds':
                        $query->where("p.system_id IN (" . $systemIds . ")");
                        break;
                }
                $query->where("p.action_est_date <= '$enddate'")
                      ->where("p.action_date_actual IS NULL 
                          OR p.action_date_actual > '$enddate'");
                break;
            case 'e':
                switch ($agency) {
                    case 'eaw':
                        $query->where("p.system_id = '$fsaSystemId'");
                        break;
                    case 'es':
                        $query->where("p.system_id IN (" . $systemIds . ")");
                        break;
                }
                $query->where("p.create_ts >= '$startdate'")
                      ->where("p.create_ts <= '$enddate'");
                break;
            case 'f':
                switch ($agency) {
                    case 'faw':
                        $query->where("p.system_id = '$fsaSystemId'");
                        break;
                    case 'fs':
                        $query->where("p.system_id IN (" . $systemIds . ")");
                        break;
                }
                $query->where("p.create_ts <= '$enddate'")
                      ->where("p.close_ts IS NULL OR p.close_ts > '$enddate'");
                break;
        }
        $result = $db->fetchRow($query);
        return $result['num_poams'];
    }
}
