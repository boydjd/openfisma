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
 * @todo This class is named incorrectly: not capitalized
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
        if (! empty($id)) {
            $query->where("p.id = ?", $id);
        }
        if (! empty($source_id)) {
            $query->where("p.source_id = " . $source_id . "");
        }
        if (! empty($system_id)) {
            $query->where("p.system_id = " . $system_id . "");
        }
        if (! empty($asset_owner)) {
            $query->join('assets', 'p.asset_id = assets.id', array())->where('assets.system_id = ?', $asset_owner);
        }
        /// @todo sanitize the $ids
        if (! empty($ids)) {
            $query->where("p.id IN (" . $ids . ")");
        }
        if (! empty($overdue)) {
            $query->where("status = 'OPEN'");
            if ($overdue['type'] == 'sso') {
                if (isset($overdue['begin_date'])) {
                    $query->where("p.action_actual_date > ?", $overdue['begin_date']->toString('Ymd'));
                }
                if (isset($overdue['end_date'])) {
                    $query->where("p.action_actual_date < ?", $overdue['end_date']->toString('Ymd'));
                }
            } else if ($overdue['type'] == 'action') {
                if (isset($overdue['begin_date'])) {
                    $query->where("(p.action_actual_date IS NULL OR p.action_actual_date = 0)
                                    AND p.action_est_date > ?", $overdue['begin_date']->toString('Ymd'));
                }
                if (isset($overdue['end_date'])) {
                    $query->where("(p.action_actual_date IS NULL OR p.action_actual_date = 0)
                                   AND p.action_est_date < ?", $overdue['end_date']->toString('Ymd'));
                }
            } else {
                throw new FismaException('Parameters wrong in overdue ' . var_export($overdue, true));
            }
        }
        if (! empty($actual_date_begin)) {
            $query->where("p.action_actual_date > ?", $actual_date_begin->toString('Y-m-d'));
        }
        if (! empty($actual_date_end)) {
            $query->where("p.action_actual_date <= ?", $actual_date_end->toString('Y-m-d'));
        }
        if (! empty($est_date_begin)) {
            $query->where("p.action_est_date > ?", $est_date_begin->toString('Y-m-d'));
        }
        if (! empty($est_date_end)) {
            $query->where("p.action_est_date <= ?", $est_date_end->toString('Y-m-d'));
        }
        if (! empty($created_date_begin)) {
            $query->where("p.create_ts > ?", $created_date_begin->toString('Y-m-d'));
        }
        if (! empty($created_date_end)) {
            $query->where("p.create_ts <=?", $created_date_end->toString('Y-m-d'));
        }
        if (! empty($discovered_date_begin)) {
            $query->where("p.discover_ts >=?", $discovered_date_begin->toString('Y-m-d'));
        }
        if (! empty($discovered_date_end)) {
            $query->where("p.discover_ts <=?", $discovered_date_end->toString('Y-m-d'));
        }
        if (! empty($closed_date_begin)) {
            $query->where("p.close_ts > ?", $closed_date_begin->toString('Y-m-d'));
        }
        if (! empty($closed_date_end)) {
            $query->where("p.close_ts <=?", $closed_date_end->toString('Y-m-d'));
        }
        if (! empty($type)) {
            if (is_array($type)) {
                $query->where("p.type IN (" . makeSqlInStmt($type) . ")");
            } else {
                $query->where("p.type = ?", $type);
            }
        }
        if (isset($ep)) {
            $query->where("p.status='EP'");
            if ($ep > 0) {
                $ep --;
                $query->join(array('e' => new Zend_Db_Expr("(SELECT MAX(id) as last_eid,poam_id FROM evidences GROUP BY poam_id)")), 'e.poam_id=p.id', array())->joinLeft(array('pvv' => 'poam_evaluations'), 'e.last_eid=pvv.group_id', array())->joinLeft(array('el' => 'evaluations'), 'el.id=pvv.eval_id', array())->join(array('ev' => new Zend_Db_Expr("(
                        SELECT e1.id,MAX(eval.precedence_id) level
                        FROM `evidences` AS e1, `poam_evaluations` AS pe, `evaluations` AS eval
                        WHERE ( eval.id = pe.eval_id AND e1.id = pe.group_id 
                                AND eval.group='EVIDENCE' ) 
                        GROUP BY e1.id)")), "ev.id=e.last_eid AND el.precedence_id=ev.level", array())->where("ev.level='$ep' AND pvv.decision='APPROVED'");
            } else { //$ep==0
                $query->join(array('e' => 'evidences'), 'e.poam_id=p.id', array())->joinLeft(array('pvv' => 'poam_evaluations'), null, array())->join(array('el' => 'evaluations'), '(el.id=pvv.eval_id AND el.group=\'EVIDENCE\') 
                             ON e.id=pvv.group_id', array())->where("ISNULL(pvv.id) ");
            }
        }
        if (! empty($status)) {
            if (is_array($status)) {
                $query->where("p.status IN (" . makeSqlInStmt($status) . ")");
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
        if (! empty($date_modified)) {
            assert($date_modified instanceof Zend_Date);
            $query->where("p.modify_ts < $date_modified->toString('Y-m-d')");
        }
        return $query;
    }
    /** 
     *  search poam records.
     *  @param $sys_ids array system id that limit the searching agency
     *  @param $fields array information contained in the return.
     *         array('key' => 'value'). $fields follow the sytax of Zend_Db_Select with 
              exception of 'count'. Here 'count' is an keyword. There are 3 cases for fields: 
              1.  $fields = array( 'count'=>'status' ...)
                  It means count and groupby status. The returned value contains 'count'... and more

     *)' )
                  It return the exact value of count.

     *)' , 'key'=> 'value' , ... ) 
                  the count of the result is array_push into the returned variable;
     *  @param $criteria array 
     *  @param $limit integer results number.
     *  @param $pageno integer search start shift
     *  @return a list of record.
     */
    public function search ($sys_ids, $fields = '*', $criteria = array(), $currentPage = null, $perPage = null)
    {
        static $EXTRA_FIELDS = array('asset' => array('as.address_ip' => 'ip' , 'as.address_port' => 'port' , 'as.network_id' => 'network_id' , 'as.prod_id' => 'prod_id' , 'as.name' => 'asset_name' , 'as.system_id' => 'asset_owner') , 'source' => array('s.nickname' => 'source_nickname' , 's.name' => 'source_name'));
        $ret = array();
        $count = 0;
        $count_fields = false;
        if ($fields == '*') {
            $fields = array_merge($this->_cols, $EXTRA_FIELDS['asset'], $EXTRA_FIELDS['source']);
        } else if (isset($fields['count'])) {
            $count_fields = true;
            if ($fields == 'count' || $fields == array('count' => 'count(*)')) {
                $fields = array(); //count only
            } else {
                if ($fields['count'] != 'count(*)') {
                    $count_fields = false;
                    $criteria['group'] = $fields['count'];
                    $fields['count'] = 'count(*)';
                } else {
                    //array_push count
                    unset($fields['count']);
                }
            }
        }
        assert(is_array($fields));
        $table_fields = array_values($fields);
        $p_fields = array_diff($fields, $EXTRA_FIELDS['asset'], $EXTRA_FIELDS['source']);
        $as_fields = array_flip(array_intersect($EXTRA_FIELDS['asset'], $table_fields));
        $src_fields = array_flip(array_intersect($EXTRA_FIELDS['source'], $table_fields));
        $query = $this->_db->select()->from(array('p' => $this->_name), $p_fields);
        if (! empty($sys_ids)) {
            $query->where("p.system_id IN (" . makeSqlInStmt($sys_ids) . ")");
        }
        if (! empty($as_fields)) {
            $query->joinLeft(array('as' => 'assets'), 'as.id = p.asset_id', $as_fields);
        }
        if (! empty($src_fields)) {
            $query->joinLeft(array('s' => 'sources'), 's.id = p.source_id', $src_fields);
        }
        $query->where("p.status != 'DELETED'");
        $query = $this->_parseWhere($query, $criteria);
        if (! empty($criteria['order']) && is_array($criteria['order'])) {
            ///@todo check if all the order by have been in the search fields
            $query->order(implode(' ', $criteria['order']));
        }
        if ($count_fields) {
            $count_query = clone $query;
            $from = $count_query->getPart(Zend_Db_Select::FROM);
            $count_query->reset(Zend_Db_Select::COLUMNS);
            $count_query->reset(Zend_Db_Select::GROUP);
            $count_query->from(null, array('count' => 'count(*)'));
            $count = $this->_db->fetchOne($count_query);
            if (empty($p_fields)) {
                return $count;
            }
        }
        if (! empty($currentPage) && ! empty($perPage)) {
            $query->limitPage($currentPage, $perPage);
        }
        $ret = $this->_db->fetchAll($query);
        if ($count_fields && $count) {
            array_push($ret, $count);
        }
        return $ret;
    }

    /**
        Get poam status
        @param $id int primary key of poam
     */
    public function getStatus ($id)
    {
        if (! is_numeric($id)) {
            throw new FismaException('Make sure a valid ID is inputed');
        }
        $ret = $this->find($id);
        if ('EN' == $ret->current()->status
            && date('Y-m-d H:i:s') > $ret->current()->action_est_date) {
            return 'EO';
        } else if ('EP' == $ret->current()->status) {
            $query = $this->_db->select()->from(array('pe'=>'poam_evaluations'), '*')
                               ->join(array('ev'=>'evidences'), 'pe.group_id = ev.id', array())
                               ->join(array('p'=>'poams'), 'ev.poam_id = p.id', array())
                               ->where('p.id = ?', $id)
                               ->where('pe.decision = "APPROVED"')
                               ->order('pe.id DESC');
            $row = $this->_db->fetchRow($query);
            if (!empty($row)) {
                return 'EP(S&P)';
            } else {
                return 'EP(SSO)';
            }
        } else {
            return $ret->current()->status;
        }
    }

    /** 
        Get detail information of a remediation by Id

        @param $id int primary key of poam
     */
    public function &getDetail ($id)
    {
        if (! is_numeric($id)) {
            throw new FismaException('Make sure a valid ID is inputed');
        }
        $poam_detail = $this->search(null, '*', array('id' => $id));
        $ret = array();
        if (empty($poam_detail)) {
            return $ret;
        }
        $ret = $poam_detail[0];
        $query = $this->_db->select()->from(array('pv' => 'poam_vulns'), array())->where("pv.poam_id = ?", $id)->join(array('v' => 'vulnerabilities'), 'v.type = pv.vuln_type AND v.seq = pv.vuln_seq', array('type' => 'v.type' , 'seq' => 'v.seq' , 'description' => 'description'));
        $vuln = $this->_db->fetchAll($query);
        if (! empty($vuln)) {
            $ret['vuln'] = $vuln;
        }
        $query->reset();
        if (! empty($ret['network_id'])) {
            $query->from(array('n' => 'networks'), array('network_name' => 'n.name'))->where('n.id = ?', $ret['network_id']);
            $networks = $this->_db->fetchRow($query);
            if (! empty($networks)) {
                $ret['network_name'] = $networks['network_name'];
            }
            $query->reset();
        }
        if (! empty($ret['prod_id'])) {
            $query->from(array('pr' => 'products'), array('prod_id' => 'pr.id' , 'prod_vendor' => 'pr.vendor' , 'prod_name' => 'pr.name' , 'prod_version' => 'pr.version'))->where("pr.id = ?", $ret['prod_id']);
            $products = $this->_db->fetchRow($query);
            if (! empty($product)) {
                $ret['product'] = $products;
            }
        }
        if (! empty($ret['blscr_id'])) {
            $query->reset();
            $query->from(array('b' => 'blscrs'), '*')->where('b.code = ?', $ret['blscr_id']);
            $blscr = $this->_db->fetchRow($query);
            if (! empty($blscr)) {
                $ret['blscr'] = &$blscr;
            }
        }
        return $ret;
    }
    /** Get list of evaluations on evidence of specified poam

        @param $poam_ids int|array poam id(s)
        @param $final boolean to get the final status or all the history
        @param $decision enum{'APPROVED','DENIED'}
        @return array list of evidences and their evaluation
     */
    public function getEvEvaluation ($poam_id, $final = false, $decision = null)
    {
        if (is_numeric($poam_id)) {
            $poam_id = array($poam_id);
        }
        $query = $this->_db->select()->from(array('ev' => 'evidences'))->where('ev.poam_id IN (' . makeSqlInStmt($poam_id) . ')')->joinLeft(array('pvv' => 'poam_evaluations'), 'ev.id=pvv.group_id', array('decision' , 'date' , 'eval_id' => 'pvv.id'))->joinLeft(array('pu' => 'users'), 'pu.id=ev.submitted_by', array('submitted_by' => 'pu.account'))->joinLeft(array('u' => 'users'), 'u.id=pvv.user_id', array('username' => 'u.account'))->joinLeft(array('el' => 'evaluations'), 'el.id=pvv.eval_id AND el.group = \'EVIDENCE\'', array('eval_name' => 'el.name' , 'el.group' , 'level' => 'el.precedence_id'))->order(array('ev.poam_id' , 'ev.id' , 'level ASC'));
        if (! empty($decision)) {
            assert(in_array($decision, array('APPROVED' , 'DENIED')));
            $query->where('pvv.decision =?', $decision);
        }
        $ret = $this->_db->fetchAll($query);
        if ($final) {
            $final = array();
            $last_pid = null;
            foreach ($ret as $k => &$r) {
                if (isset($last_pid) && $r['poam_id'] == $last_pid) {
                    unset($ret[$k]);
                } else {
                    $last_pid = $r['poam_id'];
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
    public function getActEvaluation ($poam_id, $decision = null)
    {
        if (is_numeric($poam_id)) {
            $poam_id = array($poam_id);
        }
        $query = $this->_db->select()->from(array('pvv' => 'poam_evaluations'), array('decision' , 'date' , 'eval_id' => 'pvv.id'))->where('pvv.group_id IN (' . makeSqlInStmt($poam_id) . ')')->join(array('el' => 'evaluations'), 'el.id=pvv.eval_id AND el.group = \'ACTION\'', array('eval_name' => 'el.name' , 'level' => 'el.precedence_id'))->joinLeft(array('u' => 'users'), 'u.id=pvv.user_id', array('username' => 'account'))->order(array('pvv.id' , 'pvv.date DESC' , 'level DESC'));
        if (! empty($decision)) {
            assert(in_array($decision, array('APPROVED' , 'DENIED' , 'EST_CHANCED')));
            $query->where('pvv.decision =?', $decision);
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
    public function getLogs ($poam_id)
    {
        assert(is_numeric($poam_id));
        $query = $this->_db->select()->from(array('al' => 'audit_logs'))->join(array('p' => 'poams'), 'p.id = al.poam_id', array())->join(array('u' => 'users'), 'al.user_id = u.id', array('username' => 'u.account'))->where("p.id =?", $poam_id)->order("al.timestamp DESC");
        return $this->_db->fetchAll($query);
    }
    public function writeLogs ($poam_id, $user_id, $timestamp, $event, $log_content)
    {
        $data = array('poam_id' => $poam_id , 'user_id' => $user_id , 'timestamp' => $timestamp , 'event' => $event , 'description' => $log_content);
        $result = $this->_db->insert('audit_logs', $data);
    }
    public function fismasearch ($agency)
    {
        $flag = substr($agency, 0, 1);
        $db = $this->_db;
        $fsa_sysgroup_id = Zend_Registry::get('fsa_sysgroup_id');
        $fp_system_id = Zend_Registry::get('fsa_system_id');
        $startdate = Zend_Registry::get('startdate');
        $enddate = Zend_Registry::get('enddate');
        $query = $db->select()->from(array('sgs' => 'systemgroup_systems'), array('system_id' => 'system_id'))->where("sgs.sysgroup_id = " . $fsa_sysgroup_id . " AND sgs.system_id != " . $fp_system_id . "");
        $result = $db->fetchCol($query);
        $system_ids = implode(',', $result);
        $query = $db->select()->distinct()->from(array('p' => 'poams'), array('num_poams' => 'count(p.id)'))->join(array('a' => 'assets'), 'a.id = p.asset_id', array());
        switch ($flag) {
            case 'a':
                switch ($agency) {
                    case 'aaw':
                        $query->where("p.system_id = '$fp_system_id'");
                        break;
                    case 'as':
                        $query->where("p.system_id IN (" . $system_ids . ")");
                        break;
                }
                $query->where("p.create_ts < '$startdate'")->where("p.close_ts IS NULL OR p.close_ts >= '$startdate'");
                break;
            case 'b':
                switch ($agency) {
                    case 'baw':
                        $query->where("p.system_id = '$fp_system_id'");
                        break;
                    case 'bs':
                        $query->where("p.system_id IN (" . $system_ids . ")");
                        break;
                }
                $query->where("p.create_ts <= '$enddate'")->where("p.action_est_date <= '$enddate'")->where("p.action_date_actual >= '$startdate'")->where("p.action_date_actual <= '$enddate'");
                break;
            case 'c':
                switch ($agency) {
                    case 'caw':
                        $query->where("p.system_id = '$fsa_system_id'");
                        break;
                    case 'cs':
                        $query->where("p.system_id IN (" . $system_ids . ")");
                        break;
                }
                $query->where("p.create_ts <= '$enddate'")->where("p.action_est_date > '$enddate'")->where("p.action_date_actual IS NULL");
                break;
            case 'd':
                switch ($agency) {
                    case 'daw':
                        $query->where("p.system_id = '$fsa_system_id'");
                        break;
                    case 'ds':
                        $query->where("p.system_id IN (" . $system_ids . ")");
                        break;
                }
                $query->where("p.action_est_date <= '$enddate'")->where("p.action_date_actual IS NULL OR p.action_date_actual > '$enddate'");
                break;
            case 'e':
                switch ($agency) {
                    case 'eaw':
                        $query->where("p.system_id = '$fsa_system_id'");
                        break;
                    case 'es':
                        $query->where("p.system_id IN (" . $system_ids . ")");
                        break;
                }
                $query->where("p.create_ts >= '$startdate'")->where("p.create_ts <= '$enddate'");
                break;
            case 'f':
                switch ($agency) {
                    case 'faw':
                        $query->where("p.system_id = '$fsa_system_id'");
                        break;
                    case 'fs':
                        $query->where("p.system_id IN (" . $system_ids . ")");
                        break;
                }
                $query->where("p.create_ts <= '$enddate'")->where("p.close_ts IS NULL OR p.close_ts > '$enddate'");
                break;
        }
        $result = $db->fetchRow($query);
        return $result['num_poams'];
    }
}
