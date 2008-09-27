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
 * Handles CRUD for "system" objects.
 *
 * @package   Controller
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/mw/index.php?title=License
 */
class SystemController extends SecurityController
{
    private $_paging = array(
        'mode' => 'Sliding',
        'append' => false,
        'urlVar' => 'p',
        'path' => '',
        'currentPage' => 1,
        'perPage' => 20
    );
    private $_user = null;
    protected $_sanity = array(
        'data' => 'system',
        'filter' => array(
            '*' => array(
                'StringTrim',
                'StripTags'
            )
        ) ,
        'validator' => array(
            'name' => array('Alnum' => true),
            'nickname' => array('Alnum' => true),
            'primary_office' => 'Digits',
            'confidentiality' => 'NotEmpty',
            'integrity' => 'NotEmpty',
            'availability' => 'NotEmpty',
            'type' => 'NotEmpty',
            'desc' => array(
                'allowEmpty' => TRUE
            ) ,
            'criticality_justification' => array(
                'allowEmpty' => TRUE
            ) ,
            'sensitivity_justification' => array(
                'allowEmpty' => TRUE
            )
        ) ,
        'flag' => TRUE
    );
    public function init()
    {
        parent::init();
        $this->_system = new System();
    }
    public function preDispatch()
    {
        $req = $this->getRequest();
        $this->_pagingBasePath = $req->getBaseUrl() .
            '/panel/system/sub/list';
        $this->_paging['currentPage'] = $req->getParam('p', 1);
        if (!in_array($req->getActionName(), array(
            'login',
            'logout'
        ))) {
            // by pass the authentication when login
            parent::preDispatch();
        }
    }
    public function listAction()
    {
        $req = $this->getRequest();
        $field = $req->getParam('fid');
        $value = trim($req->getParam('qv'));
        $query = $this->_system->select()->from('systems', '*');
        if (!empty($value)) {
            $query->where("$field = ?", $value);
        }
        $query->order('name ASC')->limitPage($this->_paging['currentPage'],
            $this->_paging['perPage']);
        $systemList = $this->_system->fetchAll($query)->toArray();
        $this->view->assign('system_list', $systemList);
        $this->render();
    }
    public function searchboxAction()
    {
        $req = $this->getRequest();
        $fid = $req->getParam('fid');
        $qv = $req->getParam('qv');
        $query = $this->_system->select()->from(array(
            's' => 'systems'
        ), array(
            'count' => 'COUNT(s.id)'
        ));
        $res = $this->_system->fetchRow($query)->toArray();
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
    public function createAction()
    {
        $req = $this->getRequest();
        $db = $this->_system->getAdapter();
        $query = $db->select()->from(array(
            'sg' => 'system_groups'
        ), '*')->where('is_identity = ?', 0);
        $sgList = $db->fetchAll($query);
        $this->view->assign('sg_list', $sgList);
        if ('save' == $req->getParam('s')) {
            $errno = 0;
            $system = $req->getParam('system');
            $id = $this->_system->insert($system);
            $this->_user = new User();
            $this->_me->systems = $this->_user->getMySystems($this->_me->id);
            $systemGroups = array(
                'name' => $system['name'],
                'nickname' => $system['nickname'],
                'is_identity' => 1
            );
            $res = $db->insert('system_groups', $systemGroups);
            if (!$res) {
                $errno++;
            }
            $sysgroupId = $db->LastInsertId();
            $res = $db->delete('systemgroup_systems', 'system_id = ' . $id);
            $data = array(
                'system_id' => $id,
                'sysgroup_id' => $sysgroupId
            );
            $res = $db->insert('systemgroup_systems', $data);
            if (!$res) {
                $errno++;
            }
            $systemGroups = $this->_request->getParam('sysgroup');
            foreach ($systemGroups as $systemgroupId) {
                $data = array(
                    'system_id' => $id,
                    'sysgroup_id' => $systemgroupId
                );
                $res = $db->insert('systemgroup_systems', $data);
                if (!$res) {
                    $errno++;
                }
            }
            if ($errno > 0) {
                $msg = "Failed to create the system";
                $model = self::M_WARNING;
            } else {
                $this->_notification
                     ->add(Notification::SYSTEM_CREATED,
                         $this->_me->account, $id);

                $msg = "System created successfully";
                $model = self::M_NOTICE;
            }
            $this->message($msg, $model);
        }
        $this->render();
    }
    public function deleteAction()
    {
        $errno = 0;
        $req = $this->getRequest();
        $id = $req->getParam('id');
        $db = $this->_system->getAdapter();
        $qry = $db->select()->from('poams')
             ->where('system_id = ' . $id);
        $resultA = $db->fetchAll($qry);
        $qry->reset();
        $qry = $db->select()->from('assets')
            ->where('system_id = ' . $id);
        $resultB = $db->fetchAll($qry);
        if (!empty($resultA) || !empty($resultB)) {
            $msg = "This system cannot be deleted because it is already".
                   " associated with one or more POAMS or assets";
        } else {
            $res = $this->_system->delete('id = ' . $id);
            if (!$res) {
                $errno++;
            }
            $this->_user = new User();
            $this->_me->systems = $this->_user->getMySystems($this->_me->id);
            $res = $this->_system->getAdapter()
                ->delete('systemgroup_systems', 'system_id = ' . $id);
            if (!$res) {
                $errno++;
            }
            if ($errno > 0) {
                $msg = "Failed to delete the system";
                $model = self::M_WARNING;
            } else {
                $this->_notification
                     ->add(Notification::SYSTEM_DELETED,
                        $this->_me->account, $id);

                $msg = "System deleted successfully";
                $model = self::M_NOTICE;
            }
        }
        $this->message($msg, $model);
        $this->_forward('list');
    }
    
    public function viewAction()
    {
        $req = $this->getRequest();
        $db = $this->_system->getAdapter();
        $id = $req->getParam('id');
        $query = $this->_system->select()->from('systems', '*')
            ->where('id = ' . $id);
        $system = $this->_system->getAdapter()->fetchRow($query);
        $query->reset();
        $query = $db->select()->from(array(
            'sgs' => 'systemgroup_systems'
        ), array())->join(array(
            'sg' => 'system_groups'
        ), 'sg.id = sgs.sysgroup_id', '*')
            ->where('sgs.system_id = ?', $id)->where('sg.is_identity = 0');
        $userSysgroupList = $db->fetchAll($query);
        $this->view->assign('user_sysgroup_list', $userSysgroupList);
        $this->view->assign('system', $system);
        if ('edit' == $req->getParam('v')) {
            $query = $db->select()->from(array(
                'sg' => 'system_groups'
            ), '*')->where('is_identity = ?', 0);
            $sgList = $db->fetchAll($query);
            $this->view->assign('id', $id);
            $this->view->assign('sg_list', $sgList);
            $this->render('edit');
        } else {
            $this->render();
        }
    }
    public function updateAction()
    {
        $req = $this->getRequest();
        $db = $this->_system->getAdapter();
        $id = $req->getParam('id');
        $res = 0;
        $system = $this->_request->getParam('system');
        $res+= $this->_system->update($system, 'id = ' . $id);

        $sysgroupData['name'] = $system['name'];
        $sysgroupData['nickname'] = $system['nickname'];        
        $query = $db->select()
                    ->from(array('sgs' => 'systemgroup_systems'), array())
                    ->join(array('sg' => 'system_groups'),
                        'sgs.sysgroup_id = sg.id', 'id')
                    ->where('sgs.system_id = ?', $id)
                    ->where('sg.is_identity = 1');
        $result = $db->fetchRow($query);
        $res+= $db->update('system_groups',
            $sysgroupData, 'id = ' . $result['id']);
        $db->delete('systemgroup_systems',
            "system_id = $id and sysgroup_id <> {$result['id']} ");
        $systemGroups = $this->_request->getParam('sysgroup');
        foreach ($systemGroups as $systemgroupId) {
            $data = array(
                'system_id' => $id,
                'sysgroup_id' => $systemgroupId
            );
            $db->insert('systemgroup_systems', $data);
        }
        if ($res == 0) {
            $msg = "Nothing changed in system information".
                   " (except system groups)";
            $model = self::M_WARNING;
        } else {
            $this->_notification->add(Notification::SYSTEM_MODIFIED,
                $this->_me->account, $id);

            $msg = "System edited successfully";
            $model = self::M_NOTICE;
        }
        $this->message($msg, $model);
        $this->_forward('view', null, 'id=' . $id);
    }
}
