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
 
require_once CONTROLLERS . DS . 'SecurityController.php';
require_once MODELS . DS . 'network.php';
require_once 'Pager.php';
require_once 'Zend/Filter/Input.php';

/**
 * The network controller handles searching, displaying, creating, and updating
 * network objects.
 *
 * @package   Controller
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/mw/index.php?title=License
 */
class NetworkController extends SecurityController
{
    private $_network = null;
    private $_paging = array(
        'mode' => 'Sliding',
        'append' => false,
        'urlVar' => 'p',
        'path' => '',
        'currentPage' => 1,
        'perPage' => 20
    );
    protected $_sanity = array(
        'data' => 'network',
        'filter' => array(
            '*' => array(
                'StringTrim',
                'StripTags'
            )
        ) ,
        'validator' => array(
            'name' => array('Alnum' => true),
            'nickname' => array('Alnum' => true),
            'desc' => array(
                'allowEmpty' => TRUE
            )
        ) ,
        'flag' => TRUE
    );
    public function init()
    {
        parent::init();
        $this->_network = new Network();
    }
    public function preDispatch()
    {
        $this->_paging_base_path = $this->_request->getBaseUrl() .
            '/panel/network/sub/list';
        $this->_paging['currentPage'] = $this->_request->getParam('p', 1);
        if (!in_array($this->_request->getActionName(), array(
            'login',
            'logout'
        ))) {
            // by pass the authentication when login
            parent::preDispatch();
        }
    }
    /**
     *  render the searching boxes and keep the searching criteria
     */
    public function searchboxAction()
    {
        $fid = $this->_request->getParam('fid');
        $qv = $this->_request->getParam('qv');
        $query = $this->_network->select()->from(array(
            'n' => 'networks'
        ), array(
            'count' => 'COUNT(n.id)'
        ))->order('n.name ASC');
        $res = $this->_network->fetchRow($query)->toArray();
        $count = $res['count'];
        $this->_paging['totalItems'] = $count;
        $this->_paging['fileName'] = "{$this->_paging_base_path}/p/%d";
        $pager = & Pager::factory($this->_paging);
        $this->view->assign('fid', $fid);
        $this->view->assign('qv', $qv);
        $this->view->assign('total', $count);
        $this->view->assign('links', $pager->getLinks());
        $this->render();
    }
    /**
     * List all the Networks
     *
     */
    public function listAction()
    {
        $field = $this->_request->getParam('fid');
        $value = trim($this->_request->getParam('qv'));
        $query = $this->_network->select()->from('networks', '*');
        if (!empty($value)) {
            $query->where("$field = ?", $value);
        }
        $query->order('name ASC')->limitPage($this->_paging['currentPage'],
            $this->_paging['perPage']);
        $network_list = $this->_network->fetchAll($query)->toArray();
        $this->view->assign('network_list', $network_list);
        $this->render();
    }
    /**
     * Create a network
     */
    public function createAction()
    {
        if ('save' == $this->_request->getParam('s')) {
            $network_data = $this->_request->getParam('network');
            $networkId = $this->_network->insert($network_data);
            if (!$networkId) {
                $msg = "Failed to create the network";
                $model = self::M_WARNING;
            } else {
                $this->_notification
                     ->add(Notification::NETWORK_CREATED,
                         $this->me->account, $networkId);

                $msg = "network successfully created";
                $model = self::M_NOTICE;
            }
            $this->message($msg, $model);
        }
        $this->render();
    }
    /**
     * Delete a network
     */
    public function deleteAction()
    {
        $id = $this->_request->getParam('id');
        $db = $this->_network->getAdapter();
        $qry = $db->select()->from('assets')->where('network_id = ' . $id);
        $result = $db->fetchCol($qry);
        if (!empty($result)) {
            $msg = 'This network can not be deleted because it is'.
                   ' already associated with one or more ASSETS';
            $model = self::M_WARNING;
        } else {
            $res = $this->_network->delete('id = ' . $id);
            if (!$res) {
                $msg = "Failed to delete the network";
                $model = self::M_WARNING;
            } else {
                $this->_notification
                     ->add(Notification::NETWORK_DELETED,
                         $this->me->account, $id);

                $msg = "network deleted successfully";
                $model = self::M_NOTICE;
            }
        }
        $this->message($msg, $model);
        $this->_forward('list');
    }
    /**
     *  view the network's detail information
     */
    public function viewAction()
    {
        $id = $this->_request->getParam('id');
        $result = $this->_network->find($id)->toArray();
        foreach ($result as $v) {
            $network_list = $v;
        }
        $this->view->assign('id', $id);
        $this->view->assign('network', $network_list);
        if ('edit' == $this->_request->getParam('v')) {
            $this->render('edit');
        } else {
            $this->render();
        }
    }
    /**
     *  update network
     */
    public function updateAction()
    {
        $id = $this->_request->getParam('id');
        $network_data = $this->_request->getParam('network');
        $res = $this->_network->update($network_data, 'id = ' . $id);
        if (0 == $res) {
            $msg = "Network has no updated";
            $model = self::M_NOTICE;
        } else {
            $this->_notification->add(Notification::NETWORK_MODIFIED,
                $$this->me->account, $id);

            $msg = "Network edited successfully";
            $model = self::M_NOTICE;
        }
        $this->message($msg, $model);
        $this->_forward('view', null, 'id = ' . $id);
    }
}
