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
 * An object which represents a user in OpenFISMA.
 *
 * @package   Model
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/mw/index.php?title=License
 */
class User extends FismaModel
{
    protected $_name = 'users';
    protected $_primary = 'id';
    protected $_log_name = 'account_logs';
    protected $_logger = null;
    protected $_log_map = array('priority' => 'priority' , 'timestamp' => 'timestamp' , 'user_id' => 'uid' , 'event' => 'type' , 'message' => 'message' , 'priority_name' => 'priorityName');
    protected $_map = array(self::SYS => array('table' => 'user_systems' , 'field' => 'system_id') , self::ROLE => array('table' => 'user_roles' , 'field' => 'role_id'));
    const SYS = 'system';
    const ROLE = 'role';
    const CREATION = 'creation';
    const MODIFICATION = 'modification';
    const DISABLING = 'disabling';
    const TERMINATION = 'termination';
    const LOGINFAILURE = 'loginfailure';
    const LOGIN = 'login';
    const LOGOUT = 'logout';
    public function init ()
    {
        $writer = new Zend_Log_Writer_Db($this->_db, $this->_log_name, $this->_log_map);
        if (empty($this->_logger)) {
            $this->_logger = new Zend_Log($writer);
        }
    }
    /**
        Get specified user's roles

        @param $id the user id
        @return array of role nickname
     */
    public function getRoles ($id, $fields = array('nickname'=>'nickname'))
    {
        $roleArray = array();
        $db = $this->_db;
        $qry = $db->select()->from(array('u' => 'users'), array())->join(array('ur' => 'user_roles'), 'u.id = ur.user_id', array())->join(array('r' => 'roles'), 'r.id = ur.role_id', $fields)->where("u.id = $id and r.nickname != 'auto_role'");
        return $db->fetchAll($qry);
    }
    /**
        Retrieve the systems that the user belongs to

        @param $id user id
        @return array the system ids

     */
    public function getMySystems ($id)
    {
        assert(! empty($id));
        $db = $this->_db;
        $origin_mode = $db->getFetchMode();
        $qry = $db->select()->from($this->_name, 'account')->where('id = ?', $id);
        $user = $db->fetchOne($qry);
        $db->setFetchMode(Zend_Db::FETCH_OBJ);
        if ($user == 'root') {
            $sys = $db->fetchCol('SELECT id from systems where 1 ORDER BY `systems`.`nickname`');
        } else {
            $qry->reset();
            $qry = $db->select()->distinct()->from(array('us' => 'user_systems'), 'system_id')->join('systems', 'systems.id = us.system_id', array())->where("user_id = $id")->order('systems.nickname ASC');
            $sys = $db->fetchCol($qry);
        }
        $db->setFetchMode($origin_mode);
        return $sys;
    }
    /** 
     * Log any creation, modification, disabling and termination of account.
     *
     * @param $type constant {CREATION,MODIFICATION,DISABLING,TERMINATION,
     *                  LOGIN,LOGINFAILURE,LOGOUT}
     * @param $uid int action taken user id
     * @param $extra_msg string extra message to be logged.
     *
     * @todo This "log" function modifies the object!! This is extremely bad.
     * Move the object modification into the controller and take it out of this
     * function. It is completely unmaintainable.
     */
    public function log ($type, $uid, $msg = null) {
        assert(
            in_array(
                $type,
                array(
                    self::CREATION,
                    self::MODIFICATION,
                    self::DISABLING,
                    self::TERMINATION,
                    self::LOGINFAILURE,
                    self::LOGIN,
                    self::LOGOUT
                )
            )
        );
        assert(is_string($msg));
        assert($this->_logger);
        if ( !empty($uid) ) {
            $rows = $this->find($uid);
            $row = $rows->current();
            $now = new Zend_Date();
            $nowSqlString = $now->get('Y-m-d H:i:s');
            if ($type == self::LOGINFAILURE) {
                $row->failure_count++;
                if ($row->failure_count >= readSysConfig('failure_threshold')) {
                    $row->termination_ts = $nowSqlString;
                    $row->is_active = 0;
                }
                $row->save();
            } else if ($type == self::LOGIN) {
                $row->failure_count = 0;
                $row->last_login_ts = $nowSqlString;
                $row->last_login_ip = $_SERVER["REMOTE_ADDR"];
                $row->most_recent_notify_ts = $nowSqlString;
                $row->save();
            }
        }
        $this->_logger->setEventItem('uid', $uid);
        $this->_logger->setEventItem('type', $type);
        $this->_logger->info($msg);
    }
    
    /**
        Associate systems to a user.

        @param uid int the user id
        @param type type of associated data, one of system, role.
        @param data array|int system or role id or array of them
        @param reverse bool to associate or delete
     */
    public function associate ($uid, $type, $data, $reverse = false)
    {
        assert(! empty($uid) && (is_numeric($data) || is_array($data)));
        assert(in_array($type, array(self::SYS , self::ROLE)));
        if (is_numeric($data)) {
            $data = array($data);
        }
        $ret = 0;
        $ins_data['user_id'] = $uid;
        if ($reverse) {
            $where[] = "user_id=$uid";
            if (! empty($data)) {
                $where[] = "{$this->_map[$type]['field']} IN(" . makeSqlInStmt($data) . ")";
                $ret = $this->_db->delete($this->_map[$type]['table'], $where);
            }
        } else {
            foreach ($data as $id) {
                $ins_data[$this->_map[$type]['field']] = $id;
                $ret += $this->_db->insert($this->_map[$type]['table'], $ins_data);
            }
        }
        return $ret;
    }
}
