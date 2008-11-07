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
 * @author    Ryan Yang <ryan@users.sourceforge.net>
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/mw/index.php?title=License
 * @version   $Id$
 */

/**
 * The role controller handles CRUD for role objects.
 *
 * @package   Controller
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/mw/index.php?title=License
 */
class RoleController extends SecurityController
{
    private $_paging = array(
        'mode' => 'Sliding',
        'append' => false,
        'urlVar' => 'p',
        'path' => '',
        'currentPage' => 1,
        'perPage' => 20
    );
    protected $_sanity = array(
        'data' => 'role',
        'filter' => array(
            '*' => array(
                'StringTrim',
                'StripTags'
            )
        ) ,
        'validator' => array(
            'name' => 'Alnum',
            'nickname' => 'Alnum',
            'desc' => array(
                'allowEmpty' => TRUE
            )
        ) ,
        'flag' => TRUE
    );
    public function init()
    {
        parent::init();
        $this->_role = new Role();
    }
    public function preDispatch()
    {
        parent::preDispatch();
        $req = $this->getRequest();
        $this->_pagingBasePath = $req->getBaseUrl() . '/panel/role/sub/list';
        $this->_paging['currentPage'] = $req->getParam('p', 1);
    }
    public function searchboxAction()
    {
        Config_Fisma::requirePrivilege('admin_roles', 'read');
        
        $req = $this->getRequest();
        $fid = $req->getParam('fid');
        $qv = $req->getParam('qv');
        $query = $this->_role->select()->from(array(
            'r' => 'roles'
        ), array(
            'count' => 'COUNT(r.id)'
        ));
        $res = $this->_role->fetchRow($query)->toArray();
        $count = $res['count'];
        $this->_paging['totalItems'] = $count;
        $this->_paging['fileName'] = "{$this->_pagingBasePath}/p/%d";
        $pager = & Pager::factory($this->_paging);
        $this->view->assign('fid', $fid);
        $this->view->assign('qv', $qv);
        $this->view->assign('total', $count);
        $this->view->assign('links', $pager->getLinks());
    }
    public function listAction()
    {
        Config_Fisma::requirePrivilege('admin_roles', 'read');
        
        $req = $this->getRequest();
        $field = $req->getParam('fid');
        $value = trim($req->getParam('qv'));
        $query = $this->_role->select()->from('roles', '*')
                                       ->where('nickname != "auto_role"');
        if (!empty($value)) {
            $query->where("$field = ?", $value);
        }
        $query->order('name ASC')->limitPage($this->_paging['currentPage'],
            $this->_paging['perPage']);
        $roleList = $this->_role->fetchAll($query)->toArray();
        $this->view->assign('role_list', $roleList);
    }
    public function createAction()
    {
        Config_Fisma::requirePrivilege('admin_roles', 'create');
        
        $req = $this->getRequest();
        if ('save' == $req->getParam('s')) {
            $role = $req->getPost('role');
            $roleId = $this->_role->insert($role);
            if (!$roleId) {
                $msg = "Error Create Role";
                $model = self::M_WARNING;
            } else {
                $this->_notification
                     ->add(Notification::ROLE_CREATED,
                         $this->_me->account, $roleId);

                $msg = "Successfully Create a Role.";
                $model = self::M_NOTICE;
            }
            $this->message($msg, $model);
        }
    }
    public function deleteAction()
    {
        Config_Fisma::requirePrivilege('admin_roles', 'delete');
        
        $req = $this->getRequest();
        $id = $req->getParam('id');
        $db = $this->_role->getAdapter();
        $qry = $db->select()->from('user_roles')->where('role_id = ' . $id);
        $result = $db->fetchCol($qry);
        if (!empty($result)) {
            $msg = 'This role have been used, You could not to delete';
        } else {
            $res = $this->_role->delete('id = ' . $id);
            if (!$res) {
                $msg = "Error for Delete Role";
                $model = self::M_WARNING;
            } else {
                $this->_notification
                     ->add(Notification::ROLE_DELETED,
                         $this->_me->account, $id);

                $msg = "Successfully Delete a Role.";
                $model = self::M_NOTICE;
            }
        }
        $this->message($msg, $model);
        $this->_forward('list');
    }
    public function viewAction()
    {
        Config_Fisma::requirePrivilege('admin_roles', 'read');
        
        $req = $this->getRequest();
        $id = $req->getParam('id');
        $result = $this->_role->find($id)->toArray();
        $roleList = $result[0];
        $this->view->assign('id', $id);
        $this->view->assign('role', $roleList);
        if ('edit' == $req->getParam('v')) {
            $this->render('edit');
        }
    }
    public function updateAction()
    {
        Config_Fisma::requirePrivilege('admin_roles', 'update');
        
        $req = $this->getRequest();
        $id = $req->getParam('id');
        $role = $req->getPost('role');
        $res = $this->_role->update($role, 'id = ' . $id);
        if (!$res) {
            $msg = "Edit Role Failed";
            $model = self::M_WARNING;
        } else {
            $this->_notification->add(Notification::ROLE_MODIFIED,
                $this->_me->account, $id);

            $msg = "Successfully Edit Role.";
            $model = self::M_NOTICE;
        }
        $this->message($msg, $model);
        $this->_forward('view', null, 'id = ' . $id);
    }
    public function rightAction()
    {
        Config_Fisma::requirePrivilege('admin_roles', 'definition');
        
        $req = $this->getRequest();
        $do = $req->getParam('do');
        $roleId = $req->getParam('id');
        $screenName = $req->getParam('screen_name');
        $db = $this->_role->getAdapter();
        $qry = $db->select()->from(array(
            'f' => 'functions'
        ), array(
            'function_id' => 'id',
            'function_name' => 'name'
        ))->join(array(
            'rf' => 'role_functions'
        ), 'f.id = rf.function_id', array())->where('rf.role_id = ?', $roleId);
        $existFunctions = $db->fetchAll($qry);
        if ('search_function' == $do) {
            $qry->reset();
            $qry->from('functions', array(
                'function_id' => 'id',
                'function_name' => 'name'
            ));
            if (!empty($screenName)) {
                $qry->where('screen = ?', $screenName);
            }
            $allFunctions = $db->fetchAll($qry);
            $availableFunctions = array();
            foreach ($allFunctions as $v) {
                if (!in_array($v, $existFunctions)) {
                    $availableFunctions[] = $v;
                }
            }
            $this->_helper->layout->setLayout('ajax');
            $this->view->assign('available_functions', $availableFunctions);
            $this->render('availablefunc');
        } elseif ('update' == $do) {
            $functionIds = $req->getParam('exist_functions');
            $errno = 0;
            $qry = "DELETE FROM `role_functions` WHERE role_id =" . $roleId;
            $res = $db->query($qry);
            if (!$res) {
                $errno++;
            }
            foreach ($functionIds as $fid) {
                $res = $db->insert('role_functions', array(
                    'role_id' => $roleId,
                    'function_id' => $fid
                ));
                if (!$res) {
                    $errno++;
                }
            }
            if ($errno > 0) {
                $msg = "Set right for role failed.";
                $model = self::M_WARNING;
            } else {
                $msg = "Successfully set right for role.";
                $model = self::M_NOTICE;
            }
            $this->message($msg, $model);
            $this->_redirect('panel/role/sub/right/id/' . $roleId);
        } else {
            $qry = $db->select()->from('roles', array(
                'id',
                'name'
            ))->where('id = ?', $roleId);
            $role = $db->fetchRow($qry);
            $qry = $db->select()->from('functions', array(
                'screen_name' => 'screen'
            ))->group('screen');
            $screenList = $db->fetchAll($qry);
            $this->view->assign('role', $role);
            $this->view->assign('screen_list', $screenList);
            $this->view->assign('exist_functions', $existFunctions);
        }
    }
}
