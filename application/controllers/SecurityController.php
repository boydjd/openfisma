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
 * ???
 *
 * @package   Controller
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/mw/index.php?title=License
 *
 * @todo Write a description of what this class does in the comments.
 * @todo This isn't a controller! Why is it called Security Controller?
 */
class SecurityController extends MessageController
{
    /**
     authenticated user instance
     */
    protected $_me = null;
    /**
     * role instance
     *
     */
    protected $_acl = null;
    /**
     * rules to sanity check the data
     */
    protected $_validator = null;
    /**
     * Sanity check set
     *
     * data  name the parameters
     * filter rules for filter
     * validator rules for validation
     * flag jump to error handling or not
     */
    protected $_sanity = array(
        'data' => null,
        'filter' => null,
        'validator' => null,
        'flag' => TRUE
    );
    public static $now = null;
    protected $_auth = null;
    /**
     * Authentication check and ACL initialization
     * @todo cache the acl
     */
    public function init()
    {
        if (empty(self::$now)) {
            self::$now = Zend_Date::now();
        }
        $this->_auth = Zend_Auth::getInstance();
        if ($this->_auth->hasIdentity()) {
            $this->_me = $this->_auth->getIdentity();
            $store = $this->_auth->getStorage();
            // refresh the expiring timer
            $exps = new Zend_Session_Namespace($store->getNamespace());
            $exps->setExpirationSeconds(Config_Fisma::readSysConfig('expiring_seconds'));
            $this->_acl = $this->initializeAcl($this->_me->id);
            $user = new User();
            $this->_me->systems = $user->getMySystems($this->_me->id);
            if (isset($this->_sanity['data'])) {
                $this->_validator = new Zend_Filter_Input(
                    $this->_sanity['filter'], $this->_sanity['validator'],
                    $this->_request->getParam($this->_sanity['data']));
            }
        }
        $this->_notification = new Notification();
        $this->view->assign('acl', $this->_acl);
    }

    public function preDispatch()
    {
        if (empty($this->_me)) {
            // throw exception and redirect the page to login.
            ///@todo English
            throw new Exception_InvalidAuthentication('not login or the session expire');
            $this->_forward('login', 'User');
        } else {
            $this->view->identity = $this->_me->account;
            $input = $this->_validator;
            if (isset($input) && $this->_sanity['flag'] &&
                    ($input->hasInvalid() || $input->hasMissing())) {
                $this->_forward('inputerror', 'error', null,
                    array('inputerror' => $input->getMessages()));
            }
        }
    }
    protected function initializeAcl($uid)
    {
        if (!Zend_Registry::isRegistered('acl')) {
            $acl = new Fismacl();
            $db = Zend_Registry::get('db');
            $query = $db->select()->from(array(
                'r' => 'roles'
            ), array(
                'nickname' => 'r.nickname'
            ))->where('nickname != ?', 'auto_role');
            $roleArray = $db->fetchAll($query);
            foreach ($roleArray as $row) {
                $acl->addRole(new Zend_Acl_Role($row['nickname']));
            }
            $query->reset();
            $query = $db->select()->distinct()->from(array(
                'f' => 'functions'
            ), array(
                'screen' => 'screen'
            ));
            $resource = $db->fetchAll($query);
            foreach ($resource as $row) {
                $acl->add(new Zend_Acl_Resource($row['screen']));
            }
            $query->reset();
            $query = $db->select()->from(array(
                'u' => 'users'
            ), array(
                'account'
            ))->join(array(
                'ur' => 'user_roles'
            ), 'u.id = ur.user_id', array())->join(array(
                'r' => 'roles'
            ), 'ur.role_id = r.id', array(
                'nickname' => 'r.nickname',
                'role_name' => 'r.name'
            ))->join(array(
                'rf' => 'role_functions'
            ), 'r.id = rf.role_id', array())->join(array(
                'f' => 'functions'
            ), 'rf.function_id = f.id', array(
                'screen' => 'f.screen',
                'action' => 'f.action'
            ))->where('u.id=?', $uid)->where('r.nickname != ?', 'auto_role');
            $res = $db->fetchAll($query);
            foreach ($res as $row) {
                $acl->allow($row['nickname'], $row['screen'], $row['action']);
            }
            $query->reset(Zend_Db_Select::WHERE);
            $query->where('u.id = ?', $uid)
                ->where('r.nickname = ?', 'auto_role');
            $res = $db->fetchAll($query);
            if (!empty($res)) {
                $autoRole = $res[0]['role_name'];
                $acl->addRole(new Zend_Acl_Role($autoRole));
                foreach ($res as $row) {
                    $acl->allow($autoRole, $row['screen'], $row['action']);
                }
            }
            Zend_Registry::set('acl', $acl);
        } else {
            $acl = Zend_Registry::get('acl');
        }
        return $acl;
    }
    /**
     *  utility to retrieve parameters in batch.
     */
    public function &retrieveParam($req, $params, $default = null)
    {
        assert($req instanceof Zend_Controller_Request_Abstract);
        $crit = array();
        foreach ($params as $k => & $v) {
            $crit[$k] = $req->getParam($v);
        }
        return $crit;
    }
}
