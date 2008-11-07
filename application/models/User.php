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
    protected $_rowClass = 'Table_Rowlower';
    protected $_logName = 'account_logs';
    protected $_logger = null;
    protected $_logMap = array('priority' => 'priority',
                               'timestamp' => 'timestamp',
                               'user_id' => 'uid',
                               'event' => 'type',
                               'message' => 'message',
                               'priority_name' => 'priorityName');
    protected $_map = array(self::SYS => array('table' => 'user_systems',
                                               'field' => 'system_id'),
                            self::ROLE => array('table' => 'user_roles',
                                                'field' => 'role_id'));
    const SYS = 'system';
    const ROLE = 'role';
    const CREATION = 'creation';
    const MODIFICATION = 'modification';
    const DISABLING = 'disabling';
    const TERMINATION = 'termination';
    const LOGINFAILURE = 'loginfailure';
    const LOGIN = 'login';
    const LOGOUT = 'logout';
    const ROB_ACCEPT = 'rob_accept';
    public function init ()
    {
        $writer = new Zend_Log_Writer_Db($this->_db, $this->_logName,
            $this->_logMap);
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
        $qry = $db->select()
                  ->from(array('u' => 'users'), array())
                  ->join(array('ur' => 'user_roles'), 'u.id = ur.user_id',
                      array())
                  ->join(array('r' => 'roles'), 'r.id = ur.role_id',
                      $fields)
                  ->where("u.id = $id and r.nickname != 'auto_role'");
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
        $originMode = $db->getFetchMode();
        $qry = $db->select()
                  ->from($this->_name, 'account')
                  ->where('id = ?', $id);
        $user = $db->fetchOne($qry);
        $db->setFetchMode(Zend_Db::FETCH_OBJ);
        if ($user == 'root') {
            $sys = $db->fetchCol('SELECT id from systems where 1 ORDER BY `systems`.`nickname`');
        } else {
            $qry->reset();
            $qry = $db->select()->distinct()
                      ->from(array('us' => 'user_systems'), 'system_id')
                      ->join('systems', 'systems.id = us.system_id', array())
                      ->where("user_id = $id")->order('systems.nickname ASC');
            $sys = $db->fetchCol($qry);
        }
        $db->setFetchMode($originMode);
        return $sys;
    }
    /** 
     * Log any creation, modification, disabling and termination of account.
     *
     * @param $type constant {CREATION,MODIFICATION,DISABLING,TERMINATION,
     *                  LOGIN,LOGINFAILURE,LOGOUT, ROB_ACCEPT}
     * @param $uid int action taken user id
     * @param $extra_msg string extra message to be logged.
     *
     * @todo This "log" function modifies the object!! This is extremely bad.
     * Move the object modification into the controller and take it out of this
     * function. It is completely unmaintainable.
     */
    public function log ($type, $uid, $msg = null) {
        assert(in_array($type, array(self::CREATION,
                                     self::MODIFICATION,
                                     self::DISABLING,
                                     self::TERMINATION,
                                     self::LOGINFAILURE,
                                     self::LOGIN,
                                     self::LOGOUT,
                                     self::ROB_ACCEPT)));
        assert(is_string($msg));
        assert($this->_logger);
        if ( !empty($uid) ) {
            $rows = $this->find($uid);
            $row = $rows->current();
            $account = $row->account;
            
            $now = new Zend_Date();
            $notification = new Notification();
            $nowSqlString = $now->get('Y-m-d H:i:s');
            if ($type == self::LOGINFAILURE) {          
                $notification->add(Notification::ACCOUNT_LOGIN_FAILURE,
                                   null, "User: {$account}");
                $row->failureCount++;
                if ('database' ==  Config_Fisma::readSysConfig('auth_type')
                    && $row->failureCount >= Config_Fisma::readSysConfig('failure_threshold')) {
                    $row->terminationTs = $nowSqlString;
                    $row->isActive = 0;
                    $notification->add(Notification::ACCOUNT_LOCKED,
                        null, "User: {$account}");
                }
                $row->save();
            } else if ($type == self::LOGIN) {
                $row->failureCount = 0;
                $row->lastLoginTs = $nowSqlString;
                $row->lastLoginIp = $_SERVER["REMOTE_ADDR"];
                $row->mostRecentNotifyTs = $nowSqlString;
                $row->isActive = 1; // in case user is locked.
                $row->save();
                $notification->add(Notification::ACCOUNT_LOGIN_SUCCESS,
                   $account, "UserId: {$uid}");
            } else if ($type == self::TERMINATION) {
                $row->terminationTs = $nowSqlString;
                $row->isActive = 0;
                $row->failureCount = 0;
                $notification->add(Notification::ACCOUNT_LOCKED,
                        null, "User: {$account}");
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
        $insData['user_id'] = $uid;
        if ($reverse) {
            $where[] = "user_id=$uid";
            if (! empty($data)) {
                $where[] = "{$this->_map[$type]['field']} IN('" . implode("','", $data). "')";
                $ret = $this->_db->delete($this->_map[$type]['table'], $where);
            }
        } else {
            foreach ($data as $id) {
                $insData[$this->_map[$type]['field']] = $id;
                $ret += $this->_db
                            ->insert($this->_map[$type]['table'], $insData);
            }
        }
        return $ret;
    }
}
