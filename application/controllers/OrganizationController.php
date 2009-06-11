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
        'startIndex' => 0,
        'count' => 20,
    );
    
    /**
     * @todo english
     * Invoked before each Action
     */
    public function preDispatch()
    {
        $req = $this->getRequest();
        $this->_paging['startIndex'] = $req->getParam('startIndex', 0);
    }
    
    /**
     * Returns the standard form for creating, reading, and
     * updating organizations.
     * 
     * @param Object $currOrg current recode of organization
     * @return Zend_Form
     */
    private function _getOrganizationForm($currOrg = null)
    {
        $form = Fisma_Form_Manager::loadForm('organization');
        
        // build base query
        $q = Doctrine_Query::create()
                ->select('o.*')
                ->from('Organization o')
                ->where('o.orgType != "system"');

        if ($currOrg == null) {
            $currOrg = new Organization();
        } else {
            $orgArray = $currOrg->toArray();
            // filter the organizations which belongs to the current organization and itself
            $q->andWhere('o.lft < ? OR o.rgt > ?', array($orgArray['lft'], $orgArray['rgt']));
            // if the organization is specifted, than set the parent node.
            if ($currOrg->getNode()->getParent()) {
                $form->getElement('parent')->setValue($currOrg->getNode()->getParent()->id);
            }
        }
        
        // if the organization is root, then you haven't chance to change its parent
        if ($currOrg->getNode()->isRoot()) {
            // remove the column
            $form->removeElement('parent');
        } else {
            $organizationTreeObject = Doctrine::getTable('organization')->getTree();
            $organizationTreeObject->setBaseQuery($q);
            $organizationTree = $organizationTreeObject->fetchTree();
            if (!empty($organizationTree)) {
                foreach ($organizationTree as $organization) {
                    $value = $organization['id'];
                    $text = str_repeat('--', $organization['level']) . $organization['name'];
                    $form->getElement('parent')->addMultiOptions(array($value => $text));
                }
            } else {
                // condition: no organization in DB
                $form->getElement('parent')->addMultiOptions(array(0 => 'NONE'));
            }
        }
        
        // get all kinds of orgType
        $orgTypeArray = $currOrg->getTable()->getEnumValues('orgType');
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
        //Fisma_Acl::requirePrivilege('admin_organizations', 'read'); 
        
        $keywords = trim($this->_request->getParam('keywords'));
        $this->view->assign('keywords', $keywords);
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
        //Fisma_Acl::requirePrivilege('admin_organizations', 'read'); 
        $value = trim($this->_request->getParam('keywords'));
        $format = $this->_request->getParam('format');
        $link = '';
        
        // switch normal response or ajax response
        if ($format == 'json') {
            $sortBy = $this->_request->getParam('sortby', 'name');
            $order = $this->_request->getParam('order', 'ASC');

            $q = Doctrine_Query::create()
                 ->select('*')
                 ->from('Organization o')
                 ->where('o.orgType IS NULL')
                 ->orWhere('o.orgType != ?', 'system')
                 ->orderBy("o.$sortBy $order")
                 ->limit($this->_paging['count'])
                 ->offset($this->_paging['startIndex']);

            if (!empty($value)) {
                $this->_helper->searchQuery($value, 'organization');
                $cache = $this->getHelper('SearchQuery')->getCacheInstance();
                //@todo english  get search results in ids
                $organizationIds = $cache->load($this->_me->id . '_organization');
                if (empty($organizationIds)) {
                    //@todo english  set ids as a not exist value in database if search results is none.
                    $organizationIds = array(-1);
                }
                $q->whereIn('o.id', $organizationIds);
            }
            $totalRecords = $q->count();
            $organizations = $q->execute();
            
            $tableData = array('table' => array(
                'recordsReturned' => count($organizations->toArray()),
                'totalRecords' => $totalRecords,
                'startIndex' => $this->_paging['startIndex'],
                'sort' => $sortBy,
                'dir' => $order,
                'pageSize' => $this->_paging['count'],
                'records' => $organizations->toArray()
            ));
            
            $this->_helper->layout->setLayout('ajax');
            $this->_helper->viewRenderer->setNoRender();
            echo json_encode($tableData);
        } else {
            if (!empty($value)) {
                $link .= '/keywords/' . $value;
            }
            // Display searchbox template
            $this->searchbox();
            $this->view->assign('pageInfo', $this->_paging);
            $this->view->assign('link', $link);
            $this->render('list');
        }
    }

    /**
     * Display a single organization record with all details.
     */
    public function viewAction()
    {
        /**
         * @todo add acl control
         */
        //Fisma_Acl::requirePrivilege('admin_organizations', 'read'); 
        //Display searchbox template
        $this->searchbox();
        
        $id = $this->_request->getParam('id');
        $v = $this->_request->getParam('v', 'view');
        
        $organization = new Organization();
        $organization = $organization->getTable()->find($id);
        
        $form = $this->_getOrganizationForm($organization);
        
        if (!$organization) {
            /**
             * @todo english 
             */
            throw new Fisma_Exception_General('The organization is not existed.');
        } else {
            $organization = $organization->toArray();
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
        //Fisma_Acl::requirePrivilege('admin_organizations', 'create'); 
        $form = $this->_getOrganizationForm();
        $orgValues = $this->_request->getPost();
        
        if ($orgValues) {
            if ($form->isValid($orgValues)) {
                $orgValues = $form->getValues();
                $organization = new Organization();
                $organization->merge($orgValues);
                
                // save the data, if failure then return false
                if (!$organization->trySave()) {
                    $msg = "Failure in creation";
                    $model = self::M_WARNING;
                } else {
                    // the organization hasn't parent, so it is a root
                    if ((int)$orgValues['parent'] == 0) {
                        $treeObject = Doctrine::getTable('Organization')->getTree();
                        $treeObject->createRoot($organization);
                    // the organization which has parent
                    } else {
                        // insert as a child to a specify parent organization
                        $organization->getNode()->insertAsLastChildOf($organization->getTable()->find($orgValues['parent']));
                    }
                    
                    $this->_helper->addNotification(Notification::ORGANIZATION_CREATED,
                                                    $this->_me->username, $organization->id);
                    //Create a organization index
                    if (is_dir(Fisma_Controller_Front::getPath('data') . '/index/organization/')) {
                        $this->_helper->updateIndex('organization', $organization->id, $organization->toArray());
                    }
                    $msg = "The organization is created";
                    $model = self::M_NOTICE;
                }
                $this->message($msg, $model);
                $this->_forward('view', null, null, array('id' => $organization->id));
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
     */
    public function deleteAction()
    {
        //Fisma_Acl::requirePrivilege('admin_organizations', 'delete');
        $id = $this->_request->getParam('id');
        $organization = new Organization();
        $organization = $organization->getTable()->find($id);
        if ($organization) {
            if ($organization->delete()) {
                $this->_helper->addNotification(Notification::ORGANIZATION_DELETED, $this->_me->username, $id);
                //Delete this organization index
                if (is_dir(Fisma_Controller_Front::getPath('data') . '/index/organization/')) {
                    $this->_helper->deleteIndex('organization', $id);
                }
                /**
                 * @todo english
                 */
                $msg = "Organization deleted successfully";
                $model = self::M_NOTICE;
            } else {
                /**
                 * @todo english
                 */
                $msg = "Failed to delete the Organization";
                $model = self::M_WARNING;
            }
            $this->message($msg, $model);
        }
        $this->_forward('list');
    }

    /**
     * Update organization information after submitting an edit form.
     *
     * @todo cleanup this function
     */
    public function updateAction()
    {
        /**
         * @todo add acl control
         */
        //Fisma_Acl::requirePrivilege('admin_organizations', 'update'); 
        $id = $this->_request->getParam('id', 0);
        $organization = new Organization();
        $organization = $organization->getTable()->find($id);
        
        if (!$organization) {
            /**
             * @todo english 
             */
            throw new Exception_General("The organization posted is not a valid organization");
        }
        
        $form = $this->_getOrganizationForm($organization);
        $orgValues = $this->_request->getPost();
        
        if ($form->isValid($orgValues)) {
            $isModify = false;
            $orgValues = $form->getValues();
            $organization->merge($orgValues);

            if ($organization->isModified()) {
                $organization->save();
                $isModify = true;
            }
            // if the organization is not the root and 
            // its parent id is not equal the value submited
            
            if (!$organization->getNode()->isRoot() && 
                    (int)$orgValues['parent'] != $organization->getNode()->getParent()->id) {
                // then move this organization to an other parent node
                $organization->getNode()
                ->moveAsLastChildOf(Doctrine::getTable('Organization')->find($orgValues['parent']));
                $isModify = true;
            }
            
            if ($isModify) {
                $this->_helper->addNotification(Notification::ORGANIZATION_MODIFIED, 
                                                $this->_me->username, $organization->id);
                //Update this organization index
                if (is_dir(Fisma_Controller_Front::getPath('data') . '/index/organization/')) {
                    $this->_helper->updateIndex('organization', $id, $organization->toArray());
                }
                $msg = "The organization is saved";
                $model = self::M_NOTICE;
            } else {
                $msg = "Nothing changes";
                $model = self::M_WARNING;
            }
            $this->message($msg, $model);
            $this->_forward('view', null, null, array('id' => $organization->id));
        } else {
            $errorString = Fisma_Form_Manager::getErrors($form);
            // Error message
            $this->message("Unable to update organization<br>$errorString", self::M_WARNING);
            // On error, redirect back to the edit action.
            $this->_forward('view', null, null, array('id' => $id, 'v' => 'edit'));
        }
    }
}
