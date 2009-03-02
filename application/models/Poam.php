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
 * @package   Model
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

    private static $_htmlFields =  array('finding_data',
                                          'action_suggested',
                                          'action_planned',
                                          'action_resources',
                                          'cmeasure',
                                          'cmeasure_justification',
                                          'threat_source',
                                          'threat_justification');
    //Threshold of overdue for various status
    private $_overdue = array('new' => 30, 'draft'=>30, 'mp'=>14, 'en'=>0, 'ep'=>21);
    /**
     * Parse the criterias and return the query object
     *
     * @param Object $query Zend_Db_Select
     * @param array $where the criteria
     *        The format are:
     *        <dl>
     *          <dt>'id'=>(int)</dt><dd> a poam id</dd>
     *          <dt>'sourceId'=>(int)</dt><dd> a source by id</dd>
     *          <dt>'systemId'=>(int)</dt><dd> a system by id</dd>
     *          <dt>'assetOwner'=>(int)</dt><dd> the asset's owner(a system id)</dd>
     *          <dt>'ids'=>(array)</dt><dd> some poam ids, in CSV format</dd>
     *          <dt>'actualDateBegin'=>(Zend_Date)</dt>
     *          <dd>The lower bound of date when a poam exiting EN and entering EA status.</dd>
     *          <dt>'actualDateEnd'=>(Zend_Date)</dt>
     *          <dd>The upper bound of date when a poam exiting EN and entering EA status.</dd>
     *          <dt>'estDateBegin'=>(Zend_Date)</dt>
     *          <dd>The lower bound of date to complete the mitigation strategy, i.e. uploading an evidence.</dd>
     *          <dt>'estDateEnd'=>(Zend_Date)</dt>
     *          <dd>The upper bound of date to complete the mitigation strategy, i.e. uploading an evidence.</dd>
     *          <dt>'createdDateBegin'=>(Zend_Date)</dt>
     *          <dd>The lower bound of date when a poam is created.</dd>
     *          <dt>'createdDateEnd'=>(Zend_Date)</dt>
     *          <dd>The upper bound of date when a poam is created.</dd>
     *          <dt>'discoveredDateBegin'=>(Zend_Date)</dt>
     *          <dd>The lower bound of discovering date of a finding(poam)</dd>
     *          <dt>'discoveredDateEnd'=>(Zend_Date)</dt><dd> an end date for poam discover_ts</dd>
     *          <dd>The upper bound of discovering date of a finding(poam)</dd>
     *          <dt>'mssDateBegin'=>(Zend_Date)</dt>
     *          <dd>The lower bound of date when a poam's mitigation strategy is submitted.</dd>
     *          <dt>'mssDateEnd'=>(Zend_Date)</dt><dd> an end date for poam mss_ts</dd>
     *          <dd>The upper bound of date when a poam's mitigation strategy is submitted.</dd>
     *          <dt>'closedDateBegin'=>(Zend_Date)</dt>
     *          <dd>The lower bound of closing a poam date</dd>
     *          <dt>'closedDateEnd'=>(Zend_Date)</dt><dd> an end date for poam close_ts</dd>
     *          <dd>The upper bound of closing a poam date</dd>
     *          <dt>'type'=>(string|array)</dt><dd>poam type(s), namely 'CAP', 'AR', 'FP'.</dd>
     *          <dt>'mp'=>precedence_id(int)</dt><dd>a Mitigation Strategy Evaluation noted by precedence_id </dd>
     *          <dt>'ep'=>(int)</dt><dd>an Evidence Evaluationnoted by precedence_id </dd>
     *          <dt>'status'=>(string|array)</dt>
     *          <dd>poam status(s), namely 'NEW', 'DRAFT', 'MSA', 'EN', 'EA', 'CLOSED'</dd>
     *          <dt>'ip'=>(string)</dt><dd>an asset's ip address</dd>
     *          <dt>'port'=>(int)</dt><dd> a service port of the asset</dd>
     *          <dt>'group'=>(string)</dt><dd>similar with SQL's GROUP BY. used in counting</dd>
     *          <dt>'dateModified'=>(Zend_Date)</dt><dd>The date when modifying a poam</dd>
     *        </dl>
     * @return Object Zend_Db_Select
     */
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

        /// @todo sanitize the $ids
        if (! empty($ids)) {
            $query->where("p.id IN (" . $ids . ")");
        }
        if (! empty($actualDateBegin)) {
            $query->where("DATE(p.action_actual_date) >= ?",
                $actualDateBegin->toString('Y-m-d'));
        }
        if (! empty($actualDateEnd)) {
            $query->where("DATE(p.action_actual_date) <= ?",
                $actualDateEnd->toString('Y-m-d'));
        }
        if (! empty($estDateBegin)) {
            $query->where("DATE(p.action_current_date) >= ?",
                $estDateBegin->toString('Y-m-d'));
        }
        if (! empty($estDateEnd)) {
            $query->where("DATE(p.action_current_date) <= ?",
                $estDateEnd->toString('Y-m-d'));
        }
        if (! empty($createdDateBegin)) {
            $query->where("DATE(p.create_ts) >= ?",
                $createdDateBegin->toString('Y-m-d'));
        }
        if (! empty($createdDateEnd)) {
            $query->where("DATE(p.create_ts) <=?",
                $createdDateEnd->toString('Y-m-d'));
        }
        if (! empty($discoveredDateBegin)) {
            $query->where("DATE(p.discover_ts) >=?",
                $discoveredDateBegin->toString('Y-m-d'));
        }
        if (! empty($discoveredDateEnd)) {
            $query->where("DATE(p.discover_ts) <=?",
                $discoveredDateEnd->toString('Y-m-d'));
        }
        // mitigation strategy submit date
        if (! empty($mssDateBegin)) {
            $query->where("DATE(p.mss_ts) >=?", $mssDateBegin->toString('Y-m-d'));
        }
        if (! empty($mssDateEnd)) {
            $query->where("DATE(p.mss_ts) <=?", $mssDateEnd->toString('Y-m-d'));
        }
        if (! empty($closedDateBegin)) {
            $query->where("DATE(p.close_ts) >= ?",
                $closedDateBegin->toString('Y-m-d'));
        }
        if (! empty($closedDateEnd)) {
            $query->where("DATE(p.close_ts) <=?", $closedDateEnd->toString('Y-m-d'));
        }
        if (! empty($type)) {
            if (is_array($type)) {
                $query->where("p.type IN ('" . implode("','", $type) . "')");
            } else {
                $query->where("p.type = ?", $type);
            }
        }
        if (isset($mp)) {
            if (!empty($status)) {
                $status = (array)$status;
                $status[] = 'MSA';
            } else {
                $status = 'MSA';
            }
            if ($mp > 0) {
                $mp --;
                $query->join(array('pev' => 'poam_evaluations'), 
                        "p.id=pev.group_id AND pev.decision='APPROVED'", array())
                    ->join(array('el' => 'evaluations'), 
                            "el.id=pev.eval_id AND el.precedence_id='$mp' ", array())
                    ->join(array('ev' => new Zend_Db_Expr("(
                          SELECT pe.group_id, MAX(pe.id) level
                          FROM `poam_evaluations` AS pe, `evaluations` AS eval
                          WHERE ( eval.id = pe.eval_id AND eval.group='ACTION' )
                          GROUP BY pe.group_id)")), "pev.id = ev.level", array());
            } else { //$mp == 0
                $query->joinLeft(array('pev' => 'poam_evaluations'), null, array())
                      ->join(array('el' => 'evaluations'), '(el.id=pev.eval_id AND el.group="ACTION")
                           ON pev.group_id = p.id', array())
                      ->where("ISNULL(pev.id) OR (pev.decision='DENIED' AND ROW(p.id,pev.id)= ".
                              "(SELECT t.group_id,MAX(t.id) FROM poam_evaluations AS t, evaluations AS el ".
                              " WHERE t.group_id=p.id AND t.eval_id = el.id AND el.group='ACTION'".
                              " GROUP BY t.group_id))");
            }
        }
        if (isset($ep)) {
            if (!empty($status)) {
                $status = (array)$status;
                $status[] = 'EA';
            } else {
                $status = 'EA';
            }
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
                    ->where("ev.level='$ep' AND pev.decision='APPROVED'")
                    ->where('pev.eval_id IN (SELECT id FROM `evaluations` WHERE `group` = "EVIDENCE")');
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
     *  search poam records using varous criteria
     *
     *  @param array $sysIds system id that limit the searching agency range
     *  @param array $fields fields information interested by the caller.
     *      The fields should be as following format:
     *          basic:   array('key' => 'value'). @see Zend_Db_Select
     *          count:   array('count') calc the count only (count is a reserved word).
     *                   array('count' => 'a_field') calc the count group by the a_field
     *                   array('count' => 'count(*)') 
     *          the count of the result is array_push into the returned variable;
     *          ontime:  array('status'=>'string',
                               'ontime'=>'overdue') @see Poam::_parseOnTime()
     *
     *  @param array $criteria @see Poam::_parseWhere
     *  @param integer $limit results number.
     *  @param integer $pageno search start shift
     *  @param boolean $html If set to false, then strip HTML from returned data.
     *  @return a list of record.
     */
    public function search ($sysIds,
                            $fields = '*',
                            $criteria = array(),
                            $currentPage = null,
                            $perPage = null,
                            $html = true)
    {
        static $extraFields = array(
                             'asset' => array('as.address_ip' => 'ip',
                                              'as.address_port' => 'port',
                                              'as.network_id' => 'network_id',
                                              'as.prod_id' => 'prod_id',
                                              'as.name' => 'asset_name',
                                              'as.system_id' => 'asset_owner'),
                             'source' => array('s.nickname' =>'source_nickname',
                                              's.name' => 'source_name'),
                             'system' => array('sys.nickname' => 'system_nickname',
                                               'sys.name' => 'system_name'));
        $ret = array();
        $count = 0;
        $countFields = true;
        $dueTimeColumn = "( CASE p.status
                        WHEN 'NEW'
                            THEN ADDDATE( p.create_ts, ".$this->_overdue['new']." )
                        WHEN 'DRAFT'
                            THEN ADDDATE( p.create_ts, ".$this->_overdue['draft']." )
                        WHEN 'EN'
                            THEN p.action_current_date
                        WHEN 'MSA'
                            THEN (
                               ADDDATE(p.mss_ts, ".$this->_overdue['mp']."))
                        WHEN 'EA'
                            THEN (
                               ADDDATE(p.action_current_date, ".$this->_overdue['ep']."))
                        ELSE 'N/A' END) AS duetime ";
        
        if ($fields == '*') {
            $fields = array_merge($this->_cols, $extraFields['asset'], $extraFields['source'],
                                  $extraFields['system']);
            array_push($fields, $dueTimeColumn);
        } else if (isset($fields['count'])) {
            if ($fields == 'count' || $fields == array('count' => 'count(*)')) {
                $fields = array(); //count only
            } else {
                if ($fields['count'] != 'count(*)') {
                    $countFields = false;
                    $criteria['group'] = $fields['count'];
                    $fields['count'] = 'count(*)';
                } else {
                    //The count will be calc separately
                    unset($fields['count']);
                }
            }
        }
        
        assert(is_array($fields));
        $tableFields = array_values($fields);
        $pFields = array_diff($fields, $extraFields['asset'], $extraFields['source'], $extraFields['system']);
        $asFields = array_flip(array_intersect($extraFields['asset'], $tableFields));
        $srcFields = array_flip(array_intersect($extraFields['source'], $tableFields));
        $sysFields = array_flip(array_intersect($extraFields['system'], $tableFields));
        if (in_array('duetime', $fields)) {
            unset($pFields[array_search('duetime', $pFields)]);
            array_push($pFields, $dueTimeColumn);
        }
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
        if (! empty($sysFields)) {
            $query->joinLeft(array('sys' => 'systems'), 'sys.id = p.system_id',
                $sysFields);
        }
        if (! empty($criteria['ontime'])) {
            $criteria = $this->_parseOnTime($criteria);
            //unset($criteria['ontime']);
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

        // If the caller doesn't want HTML, then strip HTML from the $_htmlFields
        if (!$html) {
            foreach ($ret as &$row) {
                foreach (self::$_htmlFields as $htmlField) {
                    if (isset($row[$htmlField])) {
                        $row[$htmlField] = strip_tags($row[$htmlField]);
                    }
                }
            }
        }
        
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
     * Parse the ontime searching criteria.
     * 
     * This requires the combination of status and ontime. These date range 
     * deducded from this function would overlap the date range explicitly set
     * in date criterias.
     * 
     * @param array $overdue
     *              $overdue = array ( ...
     *               'status'=>'my_status' should be string
     *               'overdue'=>'ontime'   could be either 'ontime' or 'overdue'.
     *              )
     * @return array
     */
    protected function _parseOnTime($criteria)
    {
        $eval = new Evaluation();
        $mpEvalList = $eval->getEvalList('ACTION');
        $epEvalList = $eval->getEvalList('EVIDENCE');
        foreach ($mpEvalList as $row) {
            $mpStatus[$row['nickname']] = $row['precedence_id'];
        }
        foreach ($epEvalList as $row) {
            $epStatus[$row['nickname']] = $row['precedence_id'];
        }
        
        $time = new Zend_Date();
        $status = $criteria['status'];
        assert(is_string($status));
        if ('ontime' == $criteria['ontime']) {
            if (in_array($status, array('NEW', 'DRAFT'))) {
                $time->sub($this->_overdue['draft'], Zend_Date::DAY);
                if (!isset($criteria['createdDateBegin']) || $time->isLater($criteria['createdDateBegin'])) {
                    $criteria['createdDateBegin'] = $time;
                }
            } else if (array_key_exists($status, $mpStatus)) {
                $time->sub($this->_overdue['mp'], Zend_Date::DAY);
                $criteria['mp'] = $mpStatus[$status];
                $criteria['mssDateBegin'] = $time;
                unset($criteria['status']);
            } else if ('EN' == $status) {
                $time->sub($this->_overdue['en'], Zend_Date::DAY);
                if (!isset($criteria['estDateBegin']) || $time->isLater($criteria['estDateBegin'])) {
                    $criteria['estDateBegin'] = $time;
                }
            } else if (array_key_exists($status, $epStatus)) {
                $time->sub($this->_overdue['ep'], Zend_Date::DAY);
                if (!isset($criteria['estDateBegin']) || $time->isLater($criteria['estDateBegin'])) {
                    $criteria['estDateBegin'] = $time;
                }
                $criteria['ep'] = $epStatus[$status];
                unset($criteria['status']);
            }
        } else if ('overdue' == $criteria['ontime']) {
            if (in_array($status, array('NEW', 'DRAFT'))) {
                $time->sub($this->_overdue['draft']+1, Zend_Date::DAY);
                if (!isset($criteria['createdDateEnd']) || $time->isEarlier($criteria['createdDateEnd'])) {
                    $criteria['createdDateEnd'] = $time;
                }
            } else if (array_key_exists($status, $mpStatus)) {
                $time->sub($this->_overdue['mp']+1, Zend_Date::DAY);
                $criteria['mp'] = $mpStatus[$status];
                $criteria['mssDateEnd'] = $time;
                unset($criteria['status']);
            } else if ('EN' == $status) {
                $time->sub($this->_overdue['en']+1, Zend_Date::DAY);
                if (!isset($criteria['estDateEnd']) || $time->isEarlier($criteria['estDateEnd'])) {
                    $criteria['estDateEnd'] = $time;
                }
            } else if (array_key_exists($status, $epStatus)) {
                $time->sub($this->_overdue['ep']+1, Zend_Date::DAY);
                if (!isset($criteria['estDateEnd']) || $time->isEarlier($criteria['estDateEnd'])) {
                    $criteria['estDateEnd'] = $time;
                }
                $criteria['ep'] = $epStatus[$status];
                unset($criteria['status']);
            }
        } else {
            throw new Exception_General('Parameters wrong in ontime ' . var_export($ontime, true));
        }
        
        return $criteria;
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
        } else if ('EA' == $ret->current()->status) {
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
            $product = $this->_db->fetchRow($query);
            if (! empty($product)) {
                $ret['product'] = $product;
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

    /**
     * writeLogs() - Write event information to the system-wide log.
     *
     * @param integer $poamId
     * @param integer $userId
     * @param integer $timestamp
     * @param integer $event
     * @param string $logContent
     *
     * @todo Document this function properly.
     */
    public function writeLogs ($poamId, $userId, $timestamp, $event, $logContent)
    {
        $data = array('poam_id' => $poamId,
                      'user_id' => $userId,
                      'timestamp' => $timestamp,
                      'event' => $event,
                      'description' => trim(strip_tags($logContent)));
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
                $query->where("DATE(p.create_ts) <= '$startdate'")
                      ->where("p.close_ts IS NULL
                         OR DATE(p.close_ts) >= '$startdate'");
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
                $query->where("DATE(p.create_ts) <= '$enddate'")
                      ->where("DATE(p.action_est_date) <= '$enddate'")
                      ->where("DATE(p.action_date_actual) >= '$startdate'")
                      ->where("DATE(p.action_date_actual) <= '$enddate'");
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
                $query->where("DATE(p.create_ts) <= '$enddate'")
                      ->where("p.action_est_date >= '$enddate'")
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
                          OR p.action_date_actual >= '$enddate'");
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
                $query->where("DATE(p.create_ts) >= '$startdate'")
                      ->where("DATE(p.create_ts) <= '$enddate'");
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
                      ->where("p.close_ts IS NULL OR DATE(p.close_ts) >= '$enddate'");
                break;
        }
        $result = $db->fetchRow($query);
        return $result['num_poams'];
    }
    
    /**
     * htmlFields() - Returns the names of fields which contain HTML in this table.
     *
     * @return array
     */
    public static function htmlFields() {
        return self::$_htmlFields;
    }

    /**
     * insert() - This function overrides the parent in order to write the creation event to the audit log, and create
     * user notification events.
     *
     * @param array $findingData An associative array of column data for the finding
     * @return int The id for the new finding
     */
    public function insert($findingData) {
        // I'm not sure if anybody would call this function with an array of arrays, but for now to be safe I'm
        // explicitly checking for that condition. -Mark
        if (array_key_exists(0, $findingData)) {
            throw new Exception_General('The $findingData parameter should be an associative array containing key/value
                                         pairs, but it appears to be a linear array.');
        }

        // Call the parent's insert() to do the actual insertion work
        $id = parent::insert($findingData);

        // Write audit log
        $now = new Zend_Date();
        $auth = Zend_Auth::getInstance();
        $user = $auth->getIdentity();
        $this->writeLogs($id,
                         $user->id, // @todo fix this, put the real user id
                         $now->toString('Y-m-d H:i:s'),
                         'CREATION',
                         'New finding created');

        // Create user notification
        $notification = new Notification();
        $notification->add(Notification::FINDING_CREATED,
                           $user->account,
                           "PoamID: $id",
                           $findingData['system_id']);

        return $id;
    }
}
