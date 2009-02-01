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

    /**
     * Returns the standard form for creating, reading, and updating roles.
     *
     * @return Zend_Form
     */
    public function getRoleForm()
    {
        $form = Form_Manager::loadForm('role');
        return Form_Manager::prepareForm($form);
    }

    /**
     *  render the searching boxes and keep the searching criteria
     */
    public function searchboxAction()
    {
        $this->_acl->requirePrivilege('admin_roles', 'read');
        
        $qv = trim($this->_request->getParam('qv'));
        if (!empty($qv)) {
            //@todo english  if role index dosen't exist, then create it.
            if (!is_dir(APPLICATION_ROOT . '/data/index/role/')) {
                $this->createIndex();
            }
            $ret = Config_Fisma::searchQuery($qv, 'role');
        } else {
            $ret = $this->_role->getList('name');
        }

        $count = count($ret);
        $this->_paging['totalItems'] = $count;
        $this->_paging['fileName'] = "{$this->_pagingBasePath}/p/%d";
        $pager = & Pager::factory($this->_paging);
        $this->view->assign('qv', $qv);
        $this->view->assign('total', $count);
        $this->view->assign('links', $pager->getLinks());
    }

    /**
     * List the roles according to search criterias.
     */
    public function listAction()
    {
        $this->_acl->requirePrivilege('admin_roles', 'read');
        
        $value = trim($this->_request->getParam('qv'));

        $query = $this->_role->select()->from('roles', '*')
                                         ->order('name ASC')
                                         ->limitPage($this->_paging['currentPage'],
                                                     $this->_paging['perPage']);

        if (!empty($value)) {
            $cache = Zend_Registry::get('cache');
            //@todo english  get search results in ids
            $roleIds = $cache->load('role');
            if (!empty($roleIds)) {
                $ids = implode(',', $roleIds);
            } else {
                //@todo english  set ids as a not exist value in database if search results is none.
                $ids = -1;
            }
            $query->where('id IN (' . $ids . ')');
        }
        $roleList = $this->_role->fetchAll($query)->toArray();
        $this->view->assign('role_list', $roleList);
    }

    /**
     * Display a single role record with all details.
     */
    public function viewAction()
    {
        $this->_acl->requirePrivilege('admin_roles', 'read');
        
        $form = $this->getRoleForm();
        $id = $this->_request->getParam('id');
        $v = $this->_request->getParam('v');

        $res = $this->_role->find($id)->toArray();
        $role = $res[0];
        if ($v == 'edit') {
            $this->view->assign('viewLink', "/panel/role/sub/view/id/$id");
            $form->setAction("/panel/role/sub/update/id/$id");
        } else {
            // In view mode, disable all of the form controls
            $this->view->assign('editLink', "/panel/role/sub/view/id/$id/v/edit");
            foreach ($form->getElements() as $element) {
                $element->setAttrib('disabled', 'disabled');
            }
        }
        $form->setDefaults($role);
        $this->view->form = $form;
        $this->view->assign('id', $id);
        $this->render($v);
    }

     /**
     * Display the form for creating a new role.
     */
    public function createAction()
    {
        $this->_acl->requirePrivilege('admin_roles', 'create');

        // Get the role form
        $form = $this->getRoleForm();
        $form->setAction('/panel/role/sub/save');

        // If there is data in the _POST variable, then use that to
        // pre-populate the form.
        $post = $this->_request->getPost();
        $form->setDefaults($post);

        // Assign view outputs.
        $this->view->form = Form_Manager::prepareForm($form);
    }


    /**
     * Saves information for a newly created role.
     */
    public function saveAction()
    {
        $this->_acl->requirePrivilege('admin_roles', 'update');
        
        $form = $this->getRoleForm();
        $post = $this->_request->getPost();
        $formValid = $form->isValid($post);
        if ($form->isValid($post)) {
            $role = $form->getValues();
            unset($role['submit']);
            unset($role['reset']);
            $roleId = $this->_role->insert($role);
            if (! $roleId) {
                $msg = "Failure in creation";
                $model = self::M_WARNING;
            } else {
                $this->_notification
                     ->add(Notification::ROLE_CREATED, $this->_me->account, $roleId);

                //Create a role index
                if (is_dir(APPLICATION_ROOT . '/data/index/role/')) {
                    Config_Fisma::updateIndex('role', $roleId, $role);
                }

                $msg = "The role is created";
                $model = self::M_NOTICE;
            }
            $this->message($msg, $model);
            $this->_forward('view', null, null, array('id' => $roleId));
        } else {
            $errorString = Form_Manager::getErrors($form);
            // Error message
            $this->message("Unable to create role:<br>$errorString", self::M_WARNING);
            $this->_forward('create');
        }
    }

    /**
     * Delete a role
     */
    public function deleteAction()
    {
        $this->_acl->requirePrivilege('admin_roles', 'delete');
        
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

                //Delete this role index
                if (is_dir(APPLICATION_ROOT . '/data/index/role/')) {
                    Config_Fisma::deleteIndex('role', $id);
                }

                $msg = "Successfully Delete a Role.";
                $model = self::M_NOTICE;
            }
        }
        $this->message($msg, $model);
        $this->_forward('list');
    }
    
    /**
     * Updates role information after submitting an edit form.
     */
    public function updateAction ()
    {
        $this->_acl->requirePrivilege('admin_roles', 'update');
        
        $form = $this->getRoleForm();
        $post = $this->_request->getPost();
        $formValid = $form->isValid($post);
        $role = $form->getValues();

        $id = $this->_request->getParam('id');
        if ($formValid) {
            unset($role['submit']);
            unset($role['reset']);
            $res = $this->_role->update($role, 'id = ' . $id);
            if ($res) {
                $this->_notification
                     ->add(Notification::ROLE_MODIFIED, $this->_me->account, $id);

                //Update this role index
                if (is_dir(APPLICATION_ROOT . '/data/index/role/')) {
                    Config_Fisma::updateIndex('role', $id, $role);
                }

                $msg = "The role is saved";
                $model = self::M_NOTICE;
            } else {
                $msg = "Nothing changes";
                $model = self::M_WARNING;
            }
            $this->message($msg, $model);
            $this->_forward('view', null, null, array('id' => $id));
        } else {
            $errorString = Form_Manager::getErrors($form);
            // Error message
            $this->message("Unable to update role<br>$errorString", self::M_WARNING);
            // On error, redirect back to the edit action.
            $this->_forward('view', null, null, array('id' => $id, 'v' => 'edit'));
        }
    }

    /**
     * assign privileges to a single role
     */
    public function rightAction()
    {
        $this->_acl->requirePrivilege('admin_roles', 'definition');
        
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

    /**
     * Create roles Lucene Index
     */
    protected function createIndex()
    {
        $index = new Zend_Search_Lucene(APPLICATION_ROOT . '/data/index/role', true);
        $list = $this->_role->getList(array('name', 'nickname', 'desc'));
        set_time_limit(0);
        if (!empty($list)) {
            foreach ($list as $id=>$row) {
                $doc = new Zend_Search_Lucene_Document();
                $doc->addField(Zend_Search_Lucene_Field::UnStored('key', md5($id)));
                $doc->addField(Zend_Search_Lucene_Field::UnIndexed('rowId', $id));
                $doc->addField(Zend_Search_Lucene_Field::UnStored('name', $row['name']));
                $doc->addField(Zend_Search_Lucene_Field::UnStored('nickname', $row['nickname']));
                $doc->addField(Zend_Search_Lucene_Field::UnStored('desc', $row['desc']));
                $index->addDocument($doc);
            }
            $index->optimize();
        }
    }

}
