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
 */
class OrganizationController extends Fisma_Zend_Controller_Action_Object
{
    /**
     * The main name of the model.
     *
     * This model is the main subject which the controller operates on.
     *
     * @var string
     */
    protected $_modelName = 'Organization';

    /**
     * Invoked before each Action
     *
     * @return void
     */
    public function preDispatch()
    {
        parent::preDispatch();
        $req = $this->getRequest();
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
        $this->_helper->ajaxContext()
                      ->addActionContext('convert-to-system-form', 'html')
                      ->initContext();
    }

    /**
     * Returns the standard form for creating, reading, and
     * updating organizations.
     *
     * @param string|null $formName The name of the specified form
     * @return Zend_Form The specified form of the subject model
     */
    public function getForm($formName = null)
    {
        $form = parent::getForm($formName);

        if (empty($formName)) {
            // The parent menu should show all organizations and systems (irregardless of user's ACL)
            $organizationTreeObject = Doctrine::getTable('Organization')->getTree();
            $organizationTree = $organizationTreeObject->fetchTree();

            if (!empty($organizationTree)) {
                foreach ($organizationTree as $organization) {
                    $value = $organization['id'];
                    $text = str_repeat("--", $organization['level']) 
                          . ' '
                          . $organization['nickname'] 
                          . ' - '
                          . $organization['name'];
                          
                    $parent = $form->getElement('parent');
                    if ($parent) {
                        $form->getElement('parent')->addMultiOptions(array($value => $text));
                    }
                }
            } else {
                // If there are no other organizations, the parent only shows the option "None"
                // (Notice that '0' is a special value which no primary key can actually take)
                $form->getElement('parent')->addMultiOptions(array(0 => 'None'));
            }
         
            // The type menu should display all types of organization EXCEPT system
            $orgTypeArray = Doctrine::getTable('OrganizationType')->getOrganizationTypeArray(false);
            $form->getElement('orgTypeId')->addMultiOptions($orgTypeArray);
        } 

        return $form;
    }

    /**
     * Override the hook to handle the "parent" field
     *
     * @param Doctrine_Record $subject The specified subject model
     * @param Zend_Form $form The specified form
     * @return Zend_Form The manipulated form
     */
    protected function setForm($subject, $form)
    {
        parent::setForm($subject, $form);

        // The root node cannot have it's parent changed
        $parent = $subject->getNode()->getParent();
        if (empty($parent)) { //temporary change: isRoot() -> empty(getParent())
            $form->removeElement('parent');
        } else {
            $form->getElement('parent')->setValue($parent->id);
        }

        return $form;
    }
    
    /**
     * Display the form for creating a new organization.
     *
     * @param Zend_Form $form The specified form
     * @param Doctrine_Record|null $subject The specified subject model
     * @return integer ID of the object saved.
     * @throws Fisma_Zend_Exception if the subject is not instance of Doctrine_Record
     */
    protected function saveValue($form, $subject=null)
    {
        $form = $this->getForm();
        
        $objectId = null;

        if ($subject) {
            $this->setForm($subject, $form);
        }

        $orgValues = $this->_request->getPost();

        if ($form->isValid($orgValues)) {
            $orgValues = $form->getValues();

            // save the data, if failure then return false
            if (!$subject) {
                // Create new object
                $organization = new Organization();

                $organization->merge($orgValues);
                $organization->save();

                // 0 is a special value (see setForm()) that indicates a root node
                if ((int)$orgValues['parent'] == 0) {
                    $treeObject = Doctrine::getTable('Organization')->getTree();
                    $treeObject->createRoot($organization);
                } else {
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

            } else {
                $organization = $subject;
                
                $organization->merge($orgValues);
                if (isset($orgValues['parent']) && $orgValues['parent'] != $organization->getNode()->getParent()->id) {

                    // Check whether $parentOrg is in the subtree under $organization. If it is, show warning message   
                    // because it might break organization tree structure
                    $parentOrg = Doctrine::getTable('Organization')->find($orgValues['parent']);
                    if ($parentOrg->getNode()->isDescendantOf($organization)) {
                        $msg = "Unable to save: " . $parentOrg->nickname . " can't be parent organization";
                        $this->view->priorityMessenger($msg, 'warning');

                        return $objectId;
                    } else {
                        $organization->getNode()
                                     ->moveAsLastChildOf($parentOrg);
                    }    
                }
                $organization->save();
            }

            $objectId = $organization->id;
        } else {
            $errorString = Fisma_Zend_Form_Manager::getErrors($form);

            $this->view->priorityMessenger("Unable to save: $errorString", 'warning');
        }
        
        return $objectId;
    }

    public function _isDeletable()
    {
        return false;
    }

    /**
     * Override parent to check if the object is a system object, in which case the user is redirected.
     * 
     * This is a temporary crutch because we have some bugs popping up with objects being viewed by the wrong 
     * controller. It will write a log message for any bad URLs, so after some time in production we can see where
     * the other bad links are and eventually remove this crutch.
     */
    public function viewAction()
    {
        $organization = Doctrine::getTable('Organization')->find($this->getRequest()->getParam('id'));

        if ('system' == $organization->OrganizationType->nickname) {
            $message = "Organization controller: expected an organization object but got a system object. Referer: "
                     . (isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'n/a');

            $this->getInvokeArg('bootstrap')->getResource('log')->warn($message);

            $this->_redirect('/system/view/oid/' . $organization->id);
        }

        parent::viewAction();
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
                $parent = $organization->getNode()->getParent();
                //temporary change: isRoot() -> empty(getParent())
                if (!empty($parent) && (int)$orgValues['parent'] != $parent->id) {
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
                $this->_redirect("/organization/view/id/$id/v/edit");
            }
        } catch (Doctrine_Validator_Exception $e) {
            $msg = "Error while trying to save: " . $e->getMessage();
            $this->view->priorityMessenger($msg, 'warning');
        }
        // On error, redirect back to the edit action.
        $this->_redirect("/organization/view/id/$id/v/edit");
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

        $this->view->toolbarButtons = $this->getToolbarButtons();
        
        // "Return To Search Results" doesn't make sense on this screen, so rename that button:
        $this->view->toolbarButtons['list']->setValue("View Organization List");
        
        // We're already on the tree screen, so don't show a "view tree" button
        unset($this->view->toolbarButtons['tree']);

        $this->render('tree');
    }

    /**
     * Gets the organization tree for the current user.
     *
     * @param boolean $includeDisposal Whether display disposal system or not
     *
     * @return array The array representation of organization tree
     */
    public function getOrganizationTree($includeDisposal = false)
    {
        $userOrgQuery = $this->_me->getOrganizationsByPrivilegeQuery('organization', 'read', $includeDisposal);
        $userOrgQuery->select('o.name, o.nickname, ot.nickname, s.type, s.sdlcPhase')
                     ->leftJoin('o.OrganizationType ot')
                     ->leftJoin('o.System s')
                     ->orderBy('o.lft');

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

        $includeDisposalSystem = ('true' === $this->_request->getParam('displayDisposalSystem'));
        
        // Save preferences for this screen
        $userId = CurrentUser::getInstance()->id;
        $namespace = 'Organization.Tree';
        $storage = Doctrine::getTable('Storage')->getUserIdAndNamespaceQuery($userId, $namespace)->fetchOne();
        if (empty($storage)) {
            $storage = new Storage();
            $storage->userId = $userId;
            $storage->namespace = $namespace;
            $storage->data = array();
        }
        $data = $storage->data;
        $data['includeDisposalSystem'] = $includeDisposalSystem;
        $storage->data = $data;
        $storage->save();

        $this->view->treeData = $this->getOrganizationTree($includeDisposalSystem);
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

        // Ensure collection is a tree
        if (!empty($collection)) {
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
                } else {
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
                    case Fisma_Yui_DragDrop::DRAG_ABOVE:
                        $src->getNode()->moveAsPrevSiblingOf($dest);
                        break;
                    case Fisma_Yui_DragDrop::DRAG_ONTO:
                        $src->getNode()->moveAsLastChildOf($dest);
                        break;
                    case Fisma_Yui_DragDrop::DRAG_BELOW:
                        $src->getNode()->moveAsNextSiblingOf($dest);
                        break;
                    default:
                        $return['success'] = false;
                        $return['message'] = "Invalid dragLocation parameter ($dragLocation)";
                }

                // Get refreshed organization tree data
                $includeDisposalSystem = ('true' === $this->_request->getParam('displayDisposalSystem'));
            } else {
                $return['success'] = false;
                $return['message'] = 'Cannot move an organization or system into itself.';
            }
        } else {
            $return['success'] = false;
            $return['message'] = "Invalid src or dest parameter ($srcId, $destId)";
        }

        print Zend_Json::encode($return);
    }

    /**
     * Add the "Organization Tree" button
     *
     * @return array Array of Fisma_Yui_Form_Button
     */
    public function getToolbarButtons()
    {
        $buttons = array();

        if ($this->_acl->hasPrivilegeForClass('read', $this->getAclResourceName())) {
            $buttons['tree'] = new Fisma_Yui_Form_Button_Link(
                'organizationTreeButton',
                array(
                    'value' => 'View Organization Hierarchy',
                    'href' => $this->getBaseUrl() . '/tree'
                )
            );
        }
        
        $buttons = array_merge($buttons, parent::getToolbarButtons());
    
        $id = $this->getRequest()->getParam('id');
        if (
            !empty($id)
            && $this->_acl->hasPrivilegeForClass('create', 'Organization')
        ) {

            $buttons['convertToSystem'] = new Fisma_Yui_Form_Button(
                'convertToSys', 
                array(
                      'label' => 'Convert To System',
                      'onClickFunction' => 'Fisma.System.convertToOrgOrSystem',
                      'onClickArgument' => array(
                          'id' => $this->view->escape($id, 'url'),
                          'text' => "Are you sure you want to convert this organization to a system?",
                          'func' => 'Fisma.System.askForOrgToSysInput'
                          
                    ) 
                )
            );
            
        }
        
        return $buttons;
    }
    
    public function convertToSystemAction()
    {
        if (!$this->_acl->hasPrivilegeForClass('create', 'Organization')) {
            throw new Fisma_Zend_Exception('Insufficient privileges to convert organization to system - ' . 
                'cannot create Organization');            
        }
        
        $id = Inspekt::getDigits($this->getRequest()->getParam('id'));

        $form = $this->getForm('organization_converttosystem');
        if ($form->isValid($this->getRequest()->getPost())) {
            $organization = Doctrine::getTable('Organization')->find($id);
            $organization->convertToSystem(
                $form->getElement('type')->getValue(),
                $form->getElement('sdlcPhase')->getValue(),
                $form->getElement('confidentiality')->getValue(),
                $form->getElement('integrity')->getValue(),
                $form->getElement('availability')->getValue()
            );

            $this->view->priorityMessenger('Converted to system successfully', 'notice');
            $this->_redirect('/system/view/oid/' . $id);
        } else {
            $errorString = Fisma_Zend_Form_Manager::getErrors($form);
            $this->view->priorityMessenger("Unable to convert Organization to System:<br>$errorString", 'warning');
            $this->_redirect('/organization/view/id/' . $id);
        }
    }

    /**
     * AJAX action to render the form for converting an Organization to a System.
     *
     * @return void
     */
    public function convertToSystemFormAction()
    {
        $id = Inspekt::getDigits($this->getRequest()->getParam('id'));
        $this->view->form = $this->getForm('organization_converttosystem');
        $this->view->form->setAction('/organization/convert-to-system/id/' . $id);
    }
}
