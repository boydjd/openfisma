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
    protected $_logMap = array('timestamp' => 'timestamp',
                               'ip' => 'ip',
                               'user_id' => 'uid',
                               'event' => 'type',
                               'message' => 'message');
    protected $_map = array(self::SYS => array('table' => 'user_systems',
                                               'field' => 'system_id'),
                            self::ROLE => array('table' => 'user_roles',
                                                'field' => 'role_id'));
    const SYS = 'SYSTEM';
    const ROLE = 'ROLE';

    /**
     * Initialize this Class
     */
    public function init ()
    {
        $writer = new Zend_Log_Writer_Db($this->_db, $this->_logName,
            $this->_logMap);
        if (empty($this->_logger)) {
            $this->_logger = new Zend_Log($writer);
        }
    }
    
    /**
     * Get specified user's roles
     * @param $id the user id
     * @return array of role nickname
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
     * Retrieve the systems that the user belongs to
     * @param $id user id
     * @return array the system ids
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
     * @param $type constant {ACCOUNT_CREATED,ACCOUNT_MODIFICATION,ACCOUNT_LOCKOUT,DISABLING,ACCOUNT_DELETED,
     *                  LOGIN,LOGINFAILURE,LOGOUT, ROB_ACCEPT}
     * @param $uid int action taken user id
     * @param $extra_msg string extra message to be logged.
     *
     * @todo This "log" function modifies the object!! This is extremely bad.
     * Move the object modification into the controller and take it out of this
     * function. It is completely unmaintainable.
     */
    public function log ($type, $uid, $msg = null) {
        $log = new Log();
        $types = $log->getEnumColumns('event');
        assert(in_array($type, $types));
        assert(is_string($msg));
        assert($this->_logger);
        if ( !empty($uid) ) {
            $rows = $this->find($uid);
            $row = $rows->current();
            $account = $row->account;
            
            $now = new Zend_Date();
            $notification = new Notification();
            $nowSqlString = $now->get('Y-m-d H:i:s');

            $this->_logger->setEventItem('ip', $_SERVER['REMOTE_ADDR']);
            $this->_logger->setEventItem('uid', $uid);
            
            if ($type == 'LOGIN') {
                $row->failureCount = 0;
                $row->lastLoginTs = $nowSqlString;
                $row->lastLoginIp = $_SERVER["REMOTE_ADDR"];
                $row->mostRecentNotifyTs = $nowSqlString;
                $row->isActive = 1; // in case user is locked.
                $row->save();
                $notification->add(Notification::ACCOUNT_LOGIN_SUCCESS,
                   $account, "UserId: {$uid}");
            } elseif ($type == 'LOGINFAILURE') {
                $type = 'LOGIN';
                $notification->add(Notification::ACCOUNT_LOGIN_FAILURE,
                                   null, "User: {$account}");
                $row->failureCount++;
                if ('database' ==  Fisma_Controller_Front::readSysConfig('auth_type')
                    && $row->failureCount >= Fisma_Controller_Front::readSysConfig('failure_threshold')) {
                    $row->terminationTs = $nowSqlString;
                    $row->isActive = 0;
                    $notification->add(Notification::ACCOUNT_LOCKED,
                        null, "User: {$account}");
                    $this->_logger->setEventItem('type', 'ACCOUNT_LOCKOUT');
                    $this->_logger->info("User Account $account Successfully Locked");
                }
                $row->save();
            } else if ($type == 'ACCOUNT_LOCKOUT') {
                $row->terminationTs = $nowSqlString;
                $row->isActive = 0;
                $row->failureCount = 0;
                $notification->add(Notification::ACCOUNT_LOCKED,
                        null, "User: {$account}");
                $row->save();
            }
            $this->_logger->setEventItem('type', $type); 
            $this->_logger->info($msg);
        }
    }
    
    /**
     * Associate systems to a user.
     *
     * @param uid int the user id
     * @param type type of associated data, one of system, role.
     * @param data array|int system or role id or array of them
     * @param reverse bool to associate or delete
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

   /**
    * Generate the desired hash of a password
    *
    * @param string $password password
    * @param string $account account name
    * @return string digest password
    */
    public function digest($password, $account=null) 
    {
        if ($account !== null) {
            $row = $this->fetchRow("account = '$account'");
            assert(count($row)==1);
            if ('md5' == $row->hash) {  //md5 hash always get 128 bits,i.e. 32 hex digits
                //keep the old hash algorithm
                return md5($password);
            }
        }
        $digestType = Fisma_Controller_Front::readSysConfig('encrypt');
        if ('sha1' == $digestType) {
            return sha1($password);
        }
        if ('sha256' == $digestType) {
            $key = self::readSysConfig('encryptKey');
            $cipherAlg = MCRYPT_TWOFISH;
            $iv = mcrypt_create_iv(mcrypt_get_iv_size($cipherAlg, MCRYPT_MODE_ECB), MCRYPT_RAND);
            $digestPassword = mcrypt_encrypt($cipherAlg, $key, $password, MCRYPT_MODE_CBC, $iv);
            return $digestPassword;
        }
    }
    
   /**
    * Sets a user's preference for which columns are visible on the finding search results page
    *
    * @param int $id The ID of the user
    * @param string $value Bitmask specifiying which columns are visible
    */
    public function setColumnPreference($id, $value)
    {
        $db = $this->_db;
        $where = $db->quoteInto('id = ?', $id);
        $this->_db->update($this->_name,
                           array('search_columns_pref' => $value),
                           $where);
    }
}
