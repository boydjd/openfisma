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
 * Handles CRUD for system group objects.
 *
 * @package   Controller
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/mw/index.php?title=License
 */
class SysgroupController extends SecurityController
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
        $this->_sysgroup = new Sysgroup();
    }
    public function preDispatch()
    {
        $req = $this->getRequest();
        $this->_pagingBasePath = $req->getBaseUrl()
                                   . '/panel/sysgroup/sub/list';
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
        $req = $this->getRequest();
        $fid = $req->getParam('fid');
        $qv = $req->getParam('qv');
        $query = $this->_sysgroup->select()->from(array(
            'sg' => 'system_groups'
        ), array(
            'count' => 'COUNT(sg.id)'
        ))->where('sg.is_identity = 0');
        $res = $this->_sysgroup->fetchRow($query)->toArray();
        $count = $res['count'];
        $this->_paging['totalItems'] = $count;
        $this->_paging['fileName'] = "{$this->_pagingBasePath}/p/%d";
        $pager = & Pager::factory($this->_paging);
        $this->view->assign('fid', $fid);
        $this->view->assign('qv', $qv);
        $this->view->assign('total', $count);
        $this->view->assign('links', $pager->getLinks());
        $this->render();
    }
    public function listAction()
    {
        $req = $this->getRequest();
        $field = $req->getParam('fid');
        $value = trim($req->getParam('qv'));
        $query = $this->_sysgroup->select()->from('system_groups', '*')
                                           ->where('is_identity = 0');
        if (!empty($value)) {
            $query->where("$field = ?", $value);
        }
        $query->order('name ASC')->limitPage($this->_paging['currentPage'],
                                             $this->_paging['perPage']);
        $sysgroupList = $this->_sysgroup->fetchAll($query)->toArray();
        $this->view->assign('sysgroup_list', $sysgroupList);
        $this->render();
    }
    public function createAction()
    {
        $form = $this->getForm('sysgroup');
        $sysGroup = $this->_request->getPost();
        if ($sysGroup) {
            if ($form->isValid($sysGroup)) {
                $sysGroup = $form->getValues();
                unset($sysGroup['submit']);
                unset($sysGroup['reset']);
                $sysGroup['is_identity'] = 0;
                $sysGroupId = $this->_sysgroup->insert($sysGroup);
                if (! $sysGroupId) {
                    //@REVIEW 3 lines
                    $msg = "Failure in creation";
                    $model = self::M_WARNING;
                } else {
                    $this->_notification
                         ->add(Notification::SYSGROUP_CREATED,
                             $this->_me->account, $sysGroupId);

                    $msg = "The system group is created";
                    $model = self::M_NOTICE;
                }
                $this->message($msg, $model);
                $form = $this->getForm('sysgroup');
            } else {
                $form->populate($sysGroup);
            }
        }
        $this->view->title = "Create ";
        $this->view->form = $form;
        $this->render('sysgroupform');
    }
    public function deleteAction()
    {
        $req = $this->getRequest();
        $id = $req->getParam('id');
        $db = $this->_sysgroup->getAdapter();
        $qry = $db->select()->from('systemgroup_systems')
            ->where('sysgroup_id = ' . $id);
        $result = $db->fetchCol($qry);
        $model = self::M_WARNING;
        if (!empty($result)) {
            //@REVIEW 3 lines
            $msg = 'Deletion aborted! One or more systems exist within it.';
        } else {
            $res = $this->_sysgroup->delete('id = ' . $id);
            if (!$res) {
                $msg = "Failure during deletion";
            } else {
                $this->_notification
                     ->add(Notification::SYSGROUP_DELETED,
                        $this->_me->account, $id);

                $msg = "The system group is deleted";
                $model = self::M_NOTICE;
            }
        }
        $this->message($msg, $model);
        $this->_forward('list');
    }
    public function viewAction()
    {
        $id = $this->_request->getParam('id');
        $res = $this->_sysgroup->find($id)->toArray();
        $sysgroup = $res[0];
        $this->view->assign('id', $id);
        $this->view->assign('sysgroup', $sysgroup);
        $this->render();
    }
    public function editAction ()
    {
        $form = $this->getForm('sysgroup');
        $id = $this->_request->getParam('id');
        $sysgroup = $this->_request->getPost();
        if ($sysgroup) {
            if ($form->isValid($sysgroup)) {
                $sysgroup = $form->getValues();
                unset($sysgroup['submit']);
                unset($sysgroup['reset']);
                $res = $this->_sysgroup->update($sysgroup, 'id = ' . $id);
                if ($res) {
                    //@REVIEW 3 lines
                    $this->_notification
                         ->add(Notification::SYSGROUP_MODIFIED,
                             $this->_me->account, $id);

                    $msg = "The system group is saved";
                    $model = self::M_NOTICE;
                } else {
                    $msg = "Nothing changes";
                    $model = self::M_WARNING;
                }
                $this->message($msg, $model);
            } else {
                $form->populate($sysgroup);
            }
        } else {
            $res = $this->_sysgroup->find($id)->toArray();
            $sysgroup = $res[0];
            $form->setDefaults($sysgroup);
        }
        $this->view->title = "Modify ";
        $this->view->form = $form;
        $this->render('sysgroupform');
    }
}
