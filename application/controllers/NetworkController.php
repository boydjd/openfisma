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
 * @package   Controller
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
    private $_paging = array(
        'startIndex' => 0,
        'count' => 20
    );
    
    /**
     * Invoked before each Action
     */
    public function preDispatch()
    {
        $this->_paging['startIndex'] = $this->_request->getParam('startIndex', 0);
    }

    /**
     * Returns the standard form for creating, reading, and updating networks.
     *
     * @return Zend_Form
     */
    private function _getNetworkForm()
    {
        $form = Fisma_Form_Manager::loadForm('network');
        return Fisma_Form_Manager::prepareForm($form);
    }

    /**
     *  render the searching boxes and keep the searching criteria
     */
    public function searchbox()
    {
        Fisma_Acl::requirePrivilege('networks', 'read');
        $value = trim($this->_request->getParam('keywords'));
        $this->view->assign('keywords', $value);
        $this->render('searchbox');
    }

    /**
     * show the list page, not for data
     */
    public function listAction()
    {
        Fisma_Acl::requirePrivilege('networks', 'read');

        $value = trim($this->_request->getParam('keywords'));
        $this->searchbox();
        
        $link = '';
        empty($value) ? $link .='' : $link .= '/keywords/' . $value;
        $this->view->assign('pageInfo', $this->_paging);
        $this->view->assign('keywords', $value);
        $this->render('list');
    }
    
    /**
     * list the networks from the search, 
     * if search none, it list all networks
     *
     */
    public function searchAction()
    {
        Fisma_Acl::requirePrivilege('networks', 'read');
        $this->_helper->layout->setLayout('ajax');
        $this->_helper->viewRenderer->setNoRender();
        
        $sortBy = $this->_request->getParam('sortby', 'name');
        $order = $this->_request->getParam('order', 'ASC');
        
        $q = Doctrine_Query::create()
             ->select('*')->from('Network')
             ->orderBy("$sortBy $order")
             ->limit($this->_paging['count'])
             ->offset($this->_paging['startIndex']);

        if (!empty($value)) {
            $this->_helper->searchQuery($value, 'network');
            $cache = $this->getHelper('SearchQuery')->getCacheInstance();
            // get search results in ids
            $networkIds = $cache->load($this->_me->id . '_network');
            if (!empty($networkIds)) {
                $networkIds = implode(',', $networkIds);
            } else {
                // set ids as a not exist value in database if search results is none.
                $networkIds = -1;
            }
            $q->where('id IN (' . $networkIds . ')');
        }
        
        $totalRecords = $q->count();
        $networks = $q->execute();
        $tableData = array('table' => array(
            'recordsReturned' => count($networks->toArray()),
            'totalRecords' => $totalRecords,
            'startIndex' => $this->_paging['startIndex'],
            'sort' => $sortBy,
            'dir' => $order,
            'pageSize' => $this->_paging['count'],
            'records' => $networks->toArray()
        ));
        echo json_encode($tableData);
    }
    
    /**
     * Display a single network record with all details.
     */
    public function viewAction()
    {
        Fisma_Acl::requirePrivilege('networks', 'read');

        $this->searchbox();
        
        $form = $this->_getNetworkForm();
        $id = $this->_request->getParam('id');
        $v = $this->_request->getParam('v', 'view');
        $network = Doctrine::getTable('Network')->find($id);
        
        if ($v == 'edit') {
            $this->view->assign('viewLink', "/panel/network/sub/view/id/$id");
            $form->setAction("/panel/network/sub/update/id/$id");
        } else {
            // In view mode, disable all of the form controls
            $this->view->assign('editLink', "/panel/network/sub/view/id/$id/v/edit");
            $form->setReadOnly(true);            
        }
        $this->view->assign('deleteLink', "/panel/network/sub/delete/id/$id");
        $form->setDefaults($network->toArray());
        $this->view->form = $form;
        $this->view->assign('id', $id);
        $this->render($v);
    }

     /**
     * Display the form for creating a new network.
     */
    public function createAction()
    {
        Fisma_Acl::requirePrivilege('networks', 'create');

        $this->searchbox();

        // Get the network form
        $form = $this->_getNetworkForm();
        $form->setAction('/panel/network/sub/save');

        // If there is data in the _POST variable, then use that to
        // pre-populate the form.
        $post = $this->_request->getPost();
        $form->setDefaults($post);

        // Assign view outputs.
        $this->view->form = $form;
        $this->render('create');
    }


    /**
     * Saves information for a newly created network.
     */
    public function saveAction()
    {
        Fisma_Acl::requirePrivilege('networks', 'update');
        
        $form = $this->_getNetworkForm();
        $post = $this->_request->getPost();
        
        if ($form->isValid($post)) {
            $network = new Network();
            $network->merge($form->getValues());

            if (!$network->trySave()) {
                $msg = "Failure in creation";
                $model = self::M_WARNING;
            } else {
                $this->_helper->addNotification(Notification::NETWORK_CREATED, $this->_me->username, $network->id);
                //Create a network index
                if (is_dir(Fisma_Controller_Front::getPath('data') . '/index/network/')) {
                    $this->_helper->updateIndex('network', $network->id, $network->toArray());
                }
                $msg = "The network is created";
                $model = self::M_NOTICE;
            }
            $this->message($msg, $model);
            $this->_forward('view', null, null, array('id' => $network->id));
        } else {
            $errorString = Fisma_Form_Manager::getErrors($form);
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
        Fisma_Acl::requirePrivilege('networks', 'delete');
        
        $id = $this->_request->getParam('id', 0);
        $network = Doctrine::getTable('Network')->find($id);
        if (!$network) {
            /**
             * @todo english
             */
            $msg = 'Invalid network';
            $model = self::M_WARNING;
        } elseif (!empty($network->Assets->toArray())) {
            /**
             * @todo english
             */
            $msg = 'This network can not be deleted because it is'.
                   ' already associated with one or more ASSETS';
            $model = self::M_WARNING;
        } else {
            if (!$network->delete()) {
                /**
                 * @todo english
                 */
                $msg = "Failed to delete the network";
                $model = self::M_WARNING;
            } else {
                //Delete network index
                if (is_dir(Fisma_Controller_Front::getPath('data') . '/index/network/')) {
                    $this->_helper->deleteIndex('network', $network->id);
                }

                $this->_helper->addNotification(Notification::NETWORK_DELETED, $this->_me->username, $network->id);
                /**
                 * @todo english
                 */
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
    public function updateAction()
    {
        Fisma_Acl::requirePrivilege('networks', 'update');
        
        $form = $this->_getNetworkForm();
        $id = $this->_request->getParam('id');
        $post = $this->_request->getPost();
        $formValid = $form->isValid($post);
        
        if ($form->isValid($post)) {
            $network = new Network();
            $network = $network->getTable()->find($id);
            $network->merge($form->getValues());
            if ($network->trySave()) {
                $this->_helper->addNotification(Notification::NETWORK_MODIFIED, $this->_me->username, $network->id);
                //Update network index
                if (is_dir(Fisma_Controller_Front::getPath('data') . '/index/network/')) {
                    $this->_helper->updateIndex('network', $network->id, $network->toArray());
                }
                /**
                 * @todo english
                 */
                $msg = "The network is saved";
                $model = self::M_NOTICE;
            } else {
                /**
                 * @todo english
                 */
                $msg = "Nothing changes";
                $model = self::M_WARNING;
            }
            $this->message($msg, $model);
            $this->_forward('view', null, null, array('id' => $id));
        } else {
            $errorString = Fisma_Form_Manager::getErrors($form);
            // Error message
            $this->message("Unable to update network<br>$errorString", self::M_WARNING);
            // On error, redirect back to the edit action.
            $this->_forward('view', null, null, array('id' => $id, 'v' => 'edit'));
        }
    }
}
