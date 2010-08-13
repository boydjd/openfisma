<?php
/**
 * Copyright (c) 2008 Endeavor Systems, Inc.
 *
 * This file is part of OpenFISMA.
 *
 * OpenFISMA is free software: you can redistribute it and/or modify it under the terms of the GNU General Public 
 * License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later
 * version.
 *
 * OpenFISMA is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied 
 * warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for more 
 * details.
 *
 * You should have received a copy of the GNU General Public License along with OpenFISMA.  If not, see 
 * {@link http://www.gnu.org/licenses/}.
 */

/**
 * Handles CRUD for organization objects.
 *
 * @author     Ryan Yang <ryan@users.sourceforge.net>
 * @copyright  (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Controller
 * @version    $Id$
 */
class OrganizationController extends Fisma_Zend_Controller_Action_Security
{
    /**
     * A type constant of drag operation of organization tree which defines the operation that move 
     * the specified organization node as previous of the target organization node among their siblings.
     */
    const DRAG_ABOVE = 0;
    
    /**
     * A type constant of drag operation of organization tree which defines the operation that move 
     * the specified organization node as child of the target organization node in organization tree.
     */
    const DRAG_ONTO = 1;
    
    /**
     * A type constant of drag operation of organization tree which defines the operation that move 
     * the specified organization node as next of the target node among their siblings.
     */
    const DRAG_BELOW = 2;
    
    /**
     *  Default pagination parameters
     * 
     * @var array
     */
    private $_paging = array(
        'startIndex' => 0,
        'count' => 20,
    );
    
    /**
     * Invoked before each Action
     * 
     * @return void
     */
    public function preDispatch()
    {
        parent::preDispatch();
        $req = $this->getRequest();
        $this->_paging['startIndex'] = $req->getParam('startIndex', 0);
    }
    
    /**
     * Initialize internal members.
     * 
     * @return void
     */
    public function init()
    {
        parent::init();
        $this->_helper->contextSwitch()
                      ->addActionContext('tree-data', 'json')
                      ->initContext();
    }
    
    /**
     * Returns the standard form for creating, reading, and
     * updating organizations.
     * 
     * @param Organization|null $currOrg The current record of organization
     * @return Zend_Form The standard form for organization operations
     */
    private function _getOrganizationForm($currOrg = null)
    {
        $form = Fisma_Zend_Form_Manager::loadForm('organization');
        
        // build base query
        $q = CurrentUser::getInstance()->getOrganizationsByPrivilegeQuery('organization', 'read');

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
            $organizationTreeObject = Doctrine::getTable('Organization')->getTree();
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
        
        return Fisma_Zend_Form_Manager::prepareForm($form);
    }

    /**
     * Render the form for searching the organizations.
     * 
     * @return void
     */
    public function searchbox()
    {
        $this->_acl->requirePrivilegeForClass('read', 'Organization');
        $keywords = trim($this->_request->getParam('keywords'));
        $this->view->assign('keywords', $keywords);
        $this->render('searchbox');
    }

    /**
     * Show the list page, not for data
     * 
     * @return void
     */
    public function listAction()
    {
        $this->_acl->requirePrivilegeForClass('read', 'Organization');
        $value = htmlentities(trim($this->_request->getParam('keywords')));
        empty($value) ? $link = '' : $link = '/keywords/' . $value;
        $this->searchbox();
        $this->view->assign('pageInfo', $this->_paging);
        $this->view->assign('link', $link);
        $this->render('list');
    }
    
    /**
     * List the organizations from the search. If search none, it list all organizations
     * 
     * @return void
     * @throws Fisma_Zend_Exception if the 'sortBy' parameter is invalid
     */
    public function searchAction()
    {
        $this->_acl->requirePrivilegeForClass('read', 'Organization');
        $keywords = html_entity_decode(trim($this->_request->getParam('keywords')));
        
        $this->_helper->layout->setLayout('ajax');
        $this->_helper->viewRenderer->setNoRender();
        $sortBy = $this->_request->getParam('sortby', 'name');
        $order = $this->_request->getParam('order');
        
        $organization = Doctrine::getTable('Organization');
        if (!in_array(strtolower($sortBy), $organization->getColumnNames())) {
            throw new Fisma_Zend_Exception('Invalid "sortBy" parameter');
        }
        
        $order = strtoupper($order);
        if ($order != 'DESC') {
            $order = 'ASC'; //ignore other values
        }
        
        $userOrgQuery = $this->_me->getOrganizationsByPrivilegeQuery('organization', 'read');
        $userOrgQuery->andWhere("o.orgType IS NULL")
                     ->orWhere("o.orgType != 'system'")
                     ->orderBy("o.$sortBy $order")
                     ->limit('?', $this->_paging['count'])
                     ->offset('?', $this->_paging['startIndex']);
        if (!empty($keywords)) {
            $index = new Fisma_Index('Organization');
            $organizationIds = $index->findIds($keywords);
            if (empty($organizationIds)) {
                $organizationIds = array(-1);
            }
            $implodedOrganizationIds = implode(',', $organizationIds);
            $userOrgQuery->andWhere("o.id IN ($implodedOrganizationIds)");
        }
        $totalRecords = $userOrgQuery->count();
        $organizations = $userOrgQuery->execute();
        
        $tableData = array('table' => array(
            'recordsReturned' => count($organizations->toArray()),
            'totalRecords' => $totalRecords,
            'startIndex' => $this->_paging['startIndex'],
            'sort' => $sortBy,
            'dir' => $order,
            'pageSize' => $this->_paging['count'],
            'records' => $organizations->toArray()
        ));
        
        echo json_encode($tableData);
    }
    
    /**
     * Display a single organization record with all details.
     * 
     * @return void
     * @throws Fisma_Zend_Exception if organization id is invalid
     */
    public function viewAction()
    {
        $id = $this->_request->getParam('id');
        $organization = Doctrine::getTable('Organization')->find($id);

        if ($organization->orgType == 'system') {
            $this->_forward('view', 'system');
            return;
        }

        $this->searchbox();
        $v = $this->_request->getParam('v', 'view');
        
        $form = $this->_getOrganizationForm($organization);
        
        if (!$organization) {
            throw new Fisma_Zend_Exception('Invalid organization ID');
        } else {
            $this->_acl->requirePrivilegeForObject('read', $organization);
            $this->view->organization = $organization;
            
            $organization = $organization->toArray();
        }

        if ($v == 'edit') {
            $this->view->assign('viewLink', "/organization/view/id/$id");
            $form->setAction("/organization/update/id/$id");
        } else {
            // In view mode, disable all of the form controls
            $this->view->assign('editLink', "/organization/view/id/$id/v/edit");
            $form->setReadOnly(true);
        }
        $this->view->assign('deleteLink', "/organization/delete/id/$id");
        $form->setDefaults($organization);
        $this->view->form = $form;
        $this->view->assign('id', $id);
        $this->render($v);
    }
    
    /**
     * Display the form for creating a new organization.
     * 
     * @return void
     */
    public function createAction()
    {
        $this->_acl->requirePrivilegeForClass('create', 'Organization');
        
        $form = $this->_getOrganizationForm();
        $orgValues = $this->_request->getPost();
        
        if ($orgValues) {
            if ($form->isValid($orgValues)) {
                $orgValues = $form->getValues();
                $organization = new Organization();
                $organization->merge($orgValues);
                
                // save the data, if failure then return false
                try {
                    $organization->save();

                    // the organization hasn't parent, so it is a root
                    if ((int)$orgValues['parent'] == 0) {
                        $treeObject = Doctrine::getTable('Organization')->getTree();
                        $treeObject->createRoot($organization);
                    // the organization which has parent
                    } else {
                        // insert as a child to a specify parent organization
                        $organization->getNode()
                                     ->insertAsLastChildOf($organization->getTable()->find($orgValues['parent']));
                    }
                    
                    // Add this organization to the user's ACL so they can see it immediately
                    $userRoles = $this->_me->getRolesByPrivilege('organization', 'create');

                    foreach ($userRoles as $userRole) {
                        $userRole->Organizations[] = $organization;
                    }

                    $userRoles->save();
                    $this->_me->invalidateAcl();

                    $msg = "Organization created successfully";
                    $model = 'notice';
                    $this->view->priorityMessenger($msg, $model);
                    $this->_redirect("/organization/view/id/{$organization->id}");
                } catch (Doctrine_Validator_Exception $e) {
                    $msg = $e->getMessage();
                    $model = 'warning';
                }
                
                $this->view->priorityMessenger($msg, $model);
            } else {
                $errorString = Fisma_Zend_Form_Manager::getErrors($form);
                // Error message
                $this->view->priorityMessenger("Unable to create organization:<br>$errorString", 'warning');
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
     * @return void
     */
    public function deleteAction()
    {
        $id = $this->_request->getParam('id');
        $organization = Doctrine::getTable('Organization')->find($id);
        if ($organization) {
            $this->_acl->requirePrivilegeForObject('delete', $organization);
            
            if ($organization->delete()) {
                $msg = "Organization deleted successfully";
                $model = 'notice';
            } else {
                $msg = "Failed to delete the Organization";
                $model = 'warning';
            }
            $this->view->priorityMessenger($msg, $model);
        }
        $this->_redirect('/organization/list');
    }

    /**
     * Update organization information after submitting an edit form.
     * 
     * @return void
     * @throws Exception_General if organization id is invalid
     * @todo cleanup this function
     */
    public function updateAction()
    {
        $id = $this->_request->getParam('id', 0);
        $organization = new Organization();
        $organization = $organization->getTable()->find($id);
        
        if (!$organization) {
            throw new Exception_General("Invalid organization ID");
        }
        
        $this->_acl->requirePrivilegeForObject('update', $organization);
        
        $form = $this->_getOrganizationForm($organization);
        $orgValues = $this->_request->getPost();
       
        try { 
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
                    $msg = "The organization is saved";
                    $model = 'notice';
                } else {
                    $msg = "Nothing changes";
                    $model = 'warning';
                }
                $this->view->priorityMessenger($msg, $model);
                $this->_redirect("/organization/view/id/{$organization->id}");
            } else {
                $errorString = Fisma_Zend_Form_Manager::getErrors($form);
                // Error message
                $this->view->priorityMessenger("Unable to update organization<br>$errorString", 'warning');
                // On error, redirect back to the edit action.
                $this->_forward('view', null, null, array('id' => $id, 'v' => 'edit'));
            }
        } catch (Doctrine_Validator_Exception $e) {
            $msg = "Error while trying to save: " . $e->getMessage();
            $this->view->priorityMessenger($msg, 'warning');
        }
        // On error, redirect back to the edit action.
        $this->_forward('view', null, null, array('id' => $id, 'v' => 'edit'));
    }
    
    /**
     * Display organizations and systems in tree mode for quick restructuring of the
     * organizational hiearchy.
     * 
     * @return void
     */
    public function treeAction() 
    {
        $this->_acl->requirePrivilegeForClass('read', 'Organization');
        $this->searchbox();
        $this->render('tree');
    }

    /**
     * Gets the organization tree for the current user. 
     * 
     * This should be refactored into the user class, but I'm in a hurry.
     * 
     * @return array The array representation of organization tree
     */
    public function getOrganizationTree() 
    {
        $userOrgQuery = $this->_me->getOrganizationsByPrivilegeQuery('organization', 'read');
        $userOrgQuery->select('o.name, o.nickname, o.orgType, s.type')
            ->leftJoin('o.System s');
        $orgTree = Doctrine::getTable('Organization')->getTree();
        $orgTree->setBaseQuery($userOrgQuery);
        $organizations = $orgTree->fetchTree();
        $orgTree->resetBaseQuery();
        $organizations = $this->toHierarchy($organizations);
        return $organizations;
    }
    
    /**
     * Returns a JSON object that describes the organization tree, including systems
     * 
     * @return void
     */
    public function treeDataAction() 
    {
        $this->_acl->requirePrivilegeForClass('read', 'Organization');
        
        $this->view->treeData = $this->getOrganizationTree();        
    }

    /**
     * Transform the flat array returned from Doctrine's nested set into a nested array
     * 
     * Doctrine should provide this functionality in a future
     * 
     * @param Doctrine_Collection $collection The collection of organization record to hierarchy
     * @return array The array representation of organization tree
     * @todo review the need for this function in the future
     */
    public function toHierarchy($collection) 
    { 
        // Trees mapped 
        $trees = array(); 
        $l = 0; 
        if (count($collection) > 0) { 
            // Node Stack. Used to help building the hierarchy 
            $rootLevel = $collection[0]->level;

            $stack = array(); 
            foreach ($collection as $node) { 
                $item = ($node instanceof Doctrine_Record) ? $node->toArray() : $node;
                $item['level'] -= $rootLevel;
                $item['label'] = $item['nickname'] . ' - ' . $item['name'];
                $item['orgType'] = $node->getType();
                $item['orgTypeLabel'] = $node->getOrgTypeLabel();                
                $item['children'] = array();
                // Number of stack items 
                $l = count($stack); 
                // Check if we're dealing with different levels 
                while ($l > 0 && $stack[$l - 1]['level'] >= $item['level']) { 
                    array_pop($stack); 
                    $l--; 
                } 

                if ($l != 0) { 
                    if ($node->getNode()->getParent()->name == $stack[$l-1]['name']) {
                        // Add node to parent 
                        $i = count($stack[$l - 1]['children']); 
                        $stack[$l - 1]['children'][$i] = $item; 
                        $stack[] = & $stack[$l - 1]['children'][$i]; 
                    } else {
                        // Find where the node belongs
                        for ($j = $l; $j >= 0; $j--) {
                            if ($j == 0) {
                                $i = count($trees);
                                $trees[$i] = $item;
                                $stack[] = &$trees[$i];
                            } elseif ($node->getNode()->getParent()->name == $stack[$j-1]['name']) {
                                // Add node to parent
                                $i = count($stack[$j-1]['children']);
                                $stack[$j-1]['children'][$i] = $item;
                                $stack[] = &$stack[$j-1]['children'][$i];
                                break;
                            }
                        }
                    }
                } elseif ($l == 0) {
                    // Assigning the root node
                    $i = count($trees);
                    $trees[$i] = $item;
                    $stack[] = &$trees[$i];
                }
            }
        }
        return $trees;
    }    
    
    /**
     * Moves a tree node relative to another tree node. This is used by the YUI tree node to handle drag and drops
     * of organization nodes. It replies with a JSON object.
     * 
     * @return void
     */
    public function moveNodeAction() 
    {
        $this->_helper->viewRenderer->setNoRender(true);
        $this->_helper->layout()->disableLayout();
        $return = array('success' => true, 'message' => null);
        
        // Find the source and destination objects from the tree
        $srcId = $this->getRequest()->getParam('src');
        $src = Doctrine::getTable('Organization')->find($srcId);
        
        $destId = $this->getRequest()->getParam('dest');
        $dest = Doctrine::getTable('Organization')->find($destId);
        
        if ($src && $dest) {
            // Make sure that $dest is not in the subtree under $src... this leads to unpredictable results
            if (!$dest->getNode()->isDescendantOf($src)) {
                // Based on the dragLocation parameter, execute a corresponding tree move method
                $dragLocation = $this->getRequest()->getParam('dragLocation');
                switch ($dragLocation) {
                    case self::DRAG_ABOVE:
                        $src->getNode()->moveAsPrevSiblingOf($dest);
                        break;
                    case self::DRAG_ONTO:
                        $src->getNode()->moveAsLastChildOf($dest);
                        break;
                    case self::DRAG_BELOW:
                        $src->getNode()->moveAsNextSiblingOf($dest);
                        break;
                    default:
                        $return['success'] = false;
                        $return['message'] = "Invalid dragLocation parameter ($dragLocation)";
                }
                
                // Get refreshed organization tree data
                $return['treeData'] = $this->getOrganizationTree();
            } else {
                $return['success'] = false;
                $return['message'] = 'Cannot move an organization into itself.';
            }
        } else {
            $return['success'] = false;
            $return['message'] = "Invalid src or dest parameter ($srcId, $destId)";
        }
        
        print Zend_Json::encode($return);
    }
}
