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
 * Handles CRUD for finding source objects.
 *
 * @package   Controller
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/mw/index.php?title=License
 */
class SourceController extends SecurityController
{
    private $_paging = array(
        'mode' => 'Sliding',
        'append' => false,
        'urlVar' => 'p',
        'path' => '',
        'currentPage' => 1,
        'perPage' => 20
    );
    public function init()
    {
        parent::init();
        $this->_source = new Source();
    }
    public function preDispatch()
    {
        $req = $this->getRequest();
        $this->_pagingBasePath = $req->getBaseUrl() . '/panel/source/sub/list';
        $this->_paging['currentPage'] = $req->getParam('p', 1);
        if (!in_array($req->getActionName(), array(
            'login',
            'logout'
        ))) {
            // by pass the authentication when login
            parent::preDispatch();
        }
    }
    public function searchboxAction()
    {
        $this->_helper->requirePrivilege('admin_sources', 'read');
        
        $req = $this->getRequest();
        $fid = $req->getParam('fid');
        $qv = $req->getParam('qv');
        $query = $this->_source->select()->from(array(
            's' => 'sources'
        ), array(
            'count' => 'COUNT(s.id)'
        ))->order('s.name ASC');
        if (!empty($qv)) {
            $query->where("$fid = ?", $qv);
            $this->_pagingBasePath .= '/fid/'.$fid.'/qv/'.$qv;
        }
        $res = $this->_source->fetchRow($query)->toArray();
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
        $this->_helper->requirePrivilege('admin_sources', 'read');
        
        $req = $this->getRequest();
        $field = $req->getParam('fid');
        $value = trim($req->getParam('qv'));
        $query = $this->_source->select()->from('sources', '*');
        if (!empty($value)) {
            $query->where("$field = ?", $value);
        }
        $query->order('name ASC')->limitPage($this->_paging['currentPage'],
            $this->_paging['perPage']);
        $sourceList = $this->_source->fetchAll($query)->toArray();
        $this->view->assign('source_list', $sourceList);
    }
    public function createAction()
    {
        $this->_helper->requirePrivilege('admin_sources', 'create');
        
        $req = $this->getRequest();
        if ('save' == $req->getParam('s')) {
            $post = $req->getPost();
            foreach ($post as $k => $v) {
                if ('source_' == substr($k, 0, 7)) {
                    $k = substr($k, 7);
                    $data[$k] = $v;
                }
            }
            $sourceId = $this->_source->insert($data);
            if (!$sourceId) {
                $msg = "Failed to create the finding source";
                $model = self::M_WARNING;
            } else {
                 $this->_notification
                      ->add(Notification::SOURCE_CREATED,
                          $this->_me->account, $sourceId);

                $msg = "Finding source successfully created";
                $model = self::M_NOTICE;
            }
            $this->message($msg, $model);
            $this->render('create');
        }
    }
    public function deleteAction()
    {
        $this->_helper->requirePrivilege('admin_sources', 'delete');
        
        $req = $this->getRequest();
        $id = $req->getParam('id');
        $db = $this->_source->getAdapter();
        $qry = $db->select()->from('poams')->where('source_id = ' . $id);
        $result = $db->fetchCol($qry);
        if (!empty($result)) {
            $msg = 'This finding source can not be deleted because it is'
                   .' already associated with one or more POAMS';
            $model = self::M_WARNING;
        } else {
            $res = $this->_source->delete('id = ' . $id);
            if (!$res) {
                $msg = "Failed to delete the finding source";
                $model = self::M_WARNING;
            } else {
                $this->_notification
                     ->add(Notification::SOURCE_DELETED,
                        $this->_me->account, $id);

                $msg = "Finding source deleted successfully";
                $model = self::M_NOTICE;
            }
        }
        $this->message($msg, $model);
        $this->_forward('list');
    }
    public function viewAction()
    {
        $this->_helper->requirePrivilege('admin_sources', 'read');
        
        $req = $this->getRequest();
        $id = $req->getParam('id');
        $result = $this->_source->find($id)->toArray();
        foreach ($result as $v) {
            $sourceList = $v;
        }
        $this->view->assign('id', $id);
        $this->view->assign('source', $sourceList);
        if ('edit' == $req->getParam('v')) {
            $this->render('edit');
        }
    }
    public function updateAction()
    {
        $this->_helper->requirePrivilege('admin_sources', 'update');
        
        $req = $this->getRequest();
        $id = $req->getParam('id');
        $post = $req->getPost();
        foreach ($post as $k => $v) {
            if ('source_' == substr($k, 0, 7)) {
                $k = substr($k, 7);
                $data[$k] = $v;
            }
        }
        $res = $this->_source->update($data, 'id = ' . $id);
        if (!$res) {
            $msg = "Failed to edit the finding source";
            $model = self::M_WARNING;
        } else {
            $this->_notification->add(Notification::SOURCE_MODIFIED,
                $this->_me->account, $id);

            $msg = "Finding source edited successfully";
            $model = self::M_NOTICE;
        }
        $this->message($msg, $model);
        $this->_forward('view', null, 'id = ' . $id);
    }
}
