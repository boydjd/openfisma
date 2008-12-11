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
        $this->_pagingBasePath = $this->_request->getBaseUrl() .
            '/panel/network/sub/list';
        $this->_paging['currentPage'] = $this->_request->getParam('p', 1);
    }

    /**
     * Returns the standard form for creating, reading, and updating networks.
     *
     * @return Zend_Form
     */
    public function getNetworkForm()
    {
        $form = Form_Manager::loadForm('network');
        return Form_Manager::prepareForm($form);
    }

    /**
     *  render the searching boxes and keep the searching criteria
     */
    public function searchboxAction()
    {
        $this->_acl->requirePrivilege('admin_networks', 'read');
        
        $fid = $this->_request->getParam('fid');
        $qv = $this->_request->getParam('qv');
        $query = $this->_network->select()->from(array(
            'n' => 'networks'
        ), array(
            'count' => 'COUNT(n.id)'
        ))->order('n.name ASC');
        if (!empty($qv)) {
            $query->where("$fid = ?", $qv);
            $this->_pagingBasePath .= '/fid/'.$fid.'/qv/'.$qv;
        }
        $res = $this->_network->fetchRow($query)->toArray();
        $count = $res['count'];
        $this->_paging['totalItems'] = $count;
        $this->_paging['fileName'] = "{$this->_pagingBasePath}/p/%d";
        $pager = & Pager::factory($this->_paging);
        $this->view->assign('fid', $fid);
        $this->view->assign('qv', $qv);
        $this->view->assign('total', $count);
        $this->view->assign('links', $pager->getLinks());
    }

    /**
     * List the networks according to search criterias.
     */
    public function listAction()
    {
        $this->_acl->requirePrivilege('admin_networks', 'read');
        
        $field = $this->_request->getParam('fid');
        $value = trim($this->_request->getParam('qv'));
        $query = $this->_network->select()->from('networks', '*');
        if (!empty($value)) {
            $query->where("$field = ?", $value);
        }
        $query->order('name ASC')->limitPage($this->_paging['currentPage'],
            $this->_paging['perPage']);
        $networkList = $this->_network->fetchAll($query)->toArray();
        $this->view->assign('network_list', $networkList);
    }

    /**
     * Display a single network record with all details.
     */
    public function viewAction()
    {
        $this->_acl->requirePrivilege('admin_networks', 'read');
        
        $form = $this->getNetworkForm();
        $id = $this->_request->getParam('id');
        $v = $this->_request->getParam('v');

        $res = $this->_network->find($id)->toArray();
        $network = $res[0];
        if ($v == 'edit') {
            $this->view->assign('viewLink', "/panel/network/sub/view/id/$id");
            $form->setAction("/panel/network/sub/update/id/$id");
        } else {
            // In view mode, disable all of the form controls
            $this->view->assign('editLink', "/panel/network/sub/view/id/$id/v/edit");
            foreach ($form->getElements() as $element) {
                $element->setAttrib('disabled', 'disabled');
            }
        }
        $form->setDefaults($network);
        $this->view->form = $form;
        $this->view->assign('id', $id);
        $this->render($v);
    }

     /**
     * Display the form for creating a new network.
     */
    public function createAction()
    {
        $this->_acl->requirePrivilege('admin_networks', 'create');

        // Get the network form
        $form = $this->getNetworkForm();
        $form->setAction('/panel/network/sub/save');

        // If there is data in the _POST variable, then use that to
        // pre-populate the form.
        $post = $this->_request->getPost();
        $form->setDefaults($post);

        // Assign view outputs.
        $this->view->form = Form_Manager::prepareForm($form);
    }


    /**
     * Saves information for a newly created network.
     */
    public function saveAction()
    {
        $this->_acl->requirePrivilege('admin_networks', 'update');
        
        $form = $this->getNetworkForm();
        $post = $this->_request->getPost();
        $formValid = $form->isValid($post);
        if ($form->isValid($post)) {
            $network = $form->getValues();
            unset($network['submit']);
            unset($network['reset']);
            $networkId = $this->_network->insert($network);
            if (! $networkId) {
                $msg = "Failure in creation";
                $model = self::M_WARNING;
            } else {
                $this->_notification
                     ->add(Notification::NETWORK_CREATED, $this->_me->account, $networkId);
                $msg = "The network is created";
                $model = self::M_NOTICE;
            }
            $this->message($msg, $model);
            $this->_forward('view', null, null, array('id' => $networkId));
        } else {
            /**
             * @todo this error display code needs to go into the decorator,
             * but before that can be done, the function it calls needs to be
             * put in a more convenient place
             */
            $errorString = '';
            foreach ($form->getMessages() as $field => $fieldErrors) {
                if (count($fieldErrors)>0) {
                    foreach ($fieldErrors as $error) {
                        $label = $form->getElement($field)->getLabel();
                        $errorString .= "$label: $error<br>";
                    }
                }
            }
            // Error message
            $this->message("Unable to create network:<br>$errorString", self::M_WARNING);
            $this->_forward('create');
        }
    }

    /**
     * Delete a network
     */
    public function deleteAction()
    {
        $this->_acl->requirePrivilege('admin_networks', 'delete');
        
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
                         $this->_me->account, $id);

                $msg = "network deleted successfully";
                $model = self::M_NOTICE;
            }
        }
        $this->message($msg, $model);
        $this->_forward('list');
    }

    /**
     * Updates network information after submitting an edit form.
     */
    public function updateAction ()
    {
        $this->_acl->requirePrivilege('admin_networks', 'update');
        
        $form = $this->getNetworkForm();
        $post = $this->_request->getPost();
        $formValid = $form->isValid($post);
        $network = $form->getValues();

        $id = $this->_request->getParam('id');
        if ($formValid) {
            unset($network['submit']);
            unset($network['reset']);
            $res = $this->_network->update($network, 'id = ' . $id);
            if ($res) {
                $this->_notification
                     ->add(Notification::NETWORK_MODIFIED, $this->_me->account, $id);

                $msg = "The network is saved";
                $model = self::M_NOTICE;
            } else {
                $msg = "Nothing changes";
                $model = self::M_WARNING;
            }
            $this->message($msg, $model);
            $this->_forward('view', null, null, array('id' => $id));
        } else {
            $errorString = '';
            foreach ($form->getMessages() as $field => $fieldErrors) {
                if (count($fieldErrors)>0) {
                    foreach ($fieldErrors as $error) {
                        $label = $form->getElement($field)->getLabel();
                        $errorString .= "$label: $error<br>";
                    }
                }
            }
            $errorString = addslashes($errorString);

            // Error message
            $this->message("Unable to update network<br>$errorString", self::M_WARNING);
            // On error, redirect back to the edit action.
            $this->_forward('view', null, null, array('id' => $id, 'v' => 'edit'));
        }
    }
}
