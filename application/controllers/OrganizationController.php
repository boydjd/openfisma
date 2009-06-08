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
 * @version   $Id: OrganizationController.php 940 2008-09-27 13:40:22Z ryanyang $
 * @package   Controller
 */

/**
 * Handles CRUD for organization objects.
 *
 * @package   Controller
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/mw/index.php?title=License
 */
class OrganizationController extends SecurityController
{
    private $_paging = array(
        'mode' => 'Sliding',
        'append' => false,
        'urlVar' => 'p',
        'path' => '',
        'currentPage' => 1,
        'perPage' => 20,
        'totalItems' => 0
    );

    /**
     * @todo english
     * init() - Initialize 
     */
    public function init()
    {
        parent::init();
        $this->_organization = new Organization();
    }

    /**
     * @todo english
     * Invoked before each Action
     */
    public function preDispatch()
    {
        $req = $this->getRequest();
        $this->_pagingBasePath = $req->getBaseUrl()
                                   . '/panel/organization/sub/list';
        $this->_paging['currentPage'] = $req->getParam('p', 1);
    }

    /**
     * Returns the standard form for creating, reading, and
     * updating organizations.
     * @todo filter the organizations belong to current organization
     * 
     * @param Object $organization current recode of organization
     * @return Zend_Form
     */
    private function _getOrganizationForm($organization = null)
    {
        $form = Fisma_Form_Manager::loadForm('organization');
        
        $organizationTreeObject = Doctrine::getTable('organization')->getTree();
        $q = Doctrine_Query::create()
                ->select('o.*')
                ->from('Organization o')
                ->where('o.orgType IS NULL OR o.orgType != "system"');
        $organizationTreeObject->setBaseQuery($q);
        $organizationTree = $organizationTreeObject->fetchTree();
        if (!empty($organizationTree)) {
            foreach ($organizationTree as $organization) {
                $value = $organization['id'];
                $text = str_repeat('--', $organization['level']) . $organization['name'];
                $form->getElement('parent')->addMultiOptions(array($value => $text));
            }
        } else {
            $form->getElement('parent')->addMultiOptions(array(0 => 'NONE'));
        }
        // get all kinds of orgType
        $orgTypeArray = $this->_organization->getTable()->getEnumValues('orgType');
        // except 'system' type
        unset($orgTypeArray[array_search('system', $orgTypeArray)]);
        $form->getElement('orgType')->addMultiOptions(array_combine($orgTypeArray, $orgTypeArray));
        
        return Fisma_Form_Manager::prepareForm($form);
    }

    /**
     *  Render the form for searching the organizations.
     */
    public function searchbox()
    {
        /**
         * @todo add acl control
         */
        //$this->_acl->requirePrivilege('admin_organizations', 'read');
        $this->_paging['fileName'] = "{$this->_pagingBasePath}/p/%d";

        $qv = trim($this->_request->getParam('qv'));
        if (!empty($qv)) {
            $this->_paging['fileName'] .= '/qv/'.$qv;
        }
        $pager = & Pager::factory($this->_paging);
        $this->view->assign('qv', $qv);
        $this->view->assign('total', $this->_paging['totalItems']);
        $this->view->assign('links', $pager->getLinks());
        $this->render('searchbox');
    }

    /**
     * list the organizations from the search, if search none, it list all organizations
     */     
    public function listAction()
    {
        /**
         * @todo add acl control
         */
        //$this->_acl->requirePrivilege('admin_organizations', 'read');
        $value = trim($this->_request->getParam('qv'));

        $q = Doctrine_Query::create()
             ->select('o.*, s.*')
             ->from('Organization o')
             ->where('o.orgType IS NULL OR ')
             ->orWhere('o.orgType != ?', 'system')
             ->orderBy('o.name ASC')
             ->limit($this->_paging['perPage'])
             ->offset(($this->_paging['currentPage']-1) * $this->_paging['perPage']);

        if (!empty($value)) {
            $cache = $this->getHelper('SearchQuery')->getCacheInstance();
            //@todo english  get search results in ids
            $organizationIds = $cache->load($this->_me->id . '_organization');
            if (empty($organizationIds)) {
                //@todo english  set ids as a not exist value in database if search results is none.
                $organizationIds = array(-1);
            }
            $q->whereIn('o.id', $organizationIds);
        }
        $this->_paging['totalItems'] = $q->count();
        $organizations = $q->execute();
        
        $this->view->assign('total', $this->_paging['totalItems']);
        $this->view->assign('organization_list', $organizations);
        
        //Display searchbox template
        $this->searchbox();
        $this->render('list');
    }

    /**
     * Display a single organization record with all details.
     */
    public function viewAction()
    {
        /**
         * @todo add acl control
         */
        //$this->_acl->requirePrivilege('admin_organizations', 'read');
        //Display searchbox template
        $this->searchbox();
        
        $form = $this->getOrganizationForm();
        $id = $this->_request->getParam('id');
        $v = $this->_request->getParam('v', 'view');

        $organizationObj = $this->_organization->getTable()->find($id);
        if ($organizationObj->getNode()->isRoot()) {
            $form->removeElement('parent');
        }
        
        if (!$organizationObj) {
            throw new Fisma_Exception_General('The system is not existed.');
        } else {
            $organization = $organizationObj->toArray();
        }
        
        if ($v == 'edit') {
            $this->view->assign('viewLink',
                                "/panel/organization/sub/view/id/$id");
            $form->setAction("/panel/organization/sub/update/id/$id");
        } else {
            // In view mode, disable all of the form controls
            $this->view->assign('editLink',
                                "/panel/organization/sub/view/id/$id/v/edit");
            $form->setReadOnly(true);
        }
        $this->view->assign('deleteLink',"/panel/organization/sub/delete/id/$id");
        $form->setDefaults($organization);
        $this->view->form = $form;
        $this->view->assign('id', $id);
        $this->render($v);
    }

    /**
     * Display the form for creating a new organization.
     */
    public function createAction()
    {
        /**
         * @todo add acl control
         */
        //$this->_acl->requirePrivilege('admin_organizations', 'create');
        
        $form = $this->getOrganizationForm();
        $organization = $this->_request->getPost();
        if ($organization) {
            if ($form->isValid($organization)) {
                $organization = $form->getValues();
                $this->_organization->name = $organization['name'];
                $this->_organization->nickname = $organization['nickname'];
                $this->_organization->orgType = $organization['orgType'];
                $this->_organization->description = $organization['description'];
                $this->_organization->save();
                
                if ((int)$organization['parent'] == 0) {
                    $treeObject = Doctrine::getTable('Organization')->getTree();
                    $treeObject->createRoot($this->_organization);
                } else {
                    $this->_organization->getNode()
                         ->insertAsLastChildOf($this->_organization->getTable()->find($organization['parent']));
                }
                if (empty($this->_organization->id)) {
                    $msg = "Failure in creation";
                    $model = self::M_WARNING;
                } else {
                    $this->_helper->addNotification(Notification::ORGANIZATION_CREATED,
                                                    $this->_me->username, $this->_organization->id);
                    //Create a organization index
                    if (is_dir(Fisma_Controller_Front::getPath('data') . '/index/organization/')) {
                        $this->_helper->updateIndex('organization', $this->_organization->id, $this->_organization->toArray());
                    }
                    $msg = "The organization is created";
                    $model = self::M_NOTICE;
                }
                $this->message($msg, $model);
                $this->_forward('view', null, null, array('id' => $this->_organization->id));
                return;
            } else {
                $errorString = Fisma_Form_Manager::getErrors($form);
                // Error message
                $this->message("Unable to create organization:<br>$errorString", self::M_WARNING);
            }
        }
        //Display searchbox template
        $this->searchbox();

        $this->view->title = "Create ";
        $this->view->form = $form;
        $this->render('create');
    }

    /**
     * Delete a specified organization.
     * 
     * @todo The organizations are related with system, 
     *       and the systems are related with others things.
     *       So We should discuss the logic of this delete action and implement later
     */
    public function deleteAction()
    {
    }

    /**
     * Updates account information after submitting an edit form.
     *
     * @todo cleanup this function
     */
    public function updateAction()
    {
        /**
         * @todo add acl control
         */
        //$this->_acl->requirePrivilege('admin_organizations', 'update');
        
        $id = $this->_request->getParam('id');
        if (empty($id)) {
            throw new Exception_General("The organization posted is not a valid organization");
        }
        
        $this->_organization = $this->_organization->getTable()->find($id);
        $form = $this->getOrganizationForm();
        $formValid = $form->isValid($_POST);
        
        if ($formValid) {
            $isModify = false;
            $organization = $form->getValues();
            $this->_organization->name = $organization['name'];
            $this->_organization->nickname = $organization['nickname'];
            $this->_organization->orgType = $organization['orgType'];
            $this->_organization->description = $organization['description'];

            if ($this->_organization->isModified()) {
                $this->_organization->save();
                $isModify = true;
            }
            
            if (!$this->_organization->getNode()->isRoot() && 
                    (int)$organization['parent'] != $this->_organization->getNode()->getParent()->id) {
                $this->_organization->getNode()
                ->moveAsLastChildOf(Doctrine::getTable('Organization')->find($organization['parent']));
                $isModify = true;
            }
            
            if ($isModify) {
                $this->_helper->addNotification(Notification::ORGANIZATION_MODIFIED, 
                                                $this->_me->username, $this->_organization->id);
                //Update this organization index
                if (is_dir(Fisma_Controller_Front::getPath('data') . '/index/organization/')) {
                    $this->_helper->updateIndex('organization', $id, $organization);
                }
                $msg = "The organization is saved";
                $model = self::M_NOTICE;
            } else {
                $msg = "Nothing changes";
                $model = self::M_WARNING;
            }
            $this->message($msg, $model);
            $this->_forward('view', null, null, array('id' => $id));
        } else {
            $errorString = Fisma_Form_Manager::getErrors($form);
            // Error message
            $this->message("Unable to update organization<br>$errorString", self::M_WARNING);
            // On error, redirect back to the edit action.
            $this->_forward('view', null, null, array('id' => $id, 'v' => 'edit'));
        }
    }
}
