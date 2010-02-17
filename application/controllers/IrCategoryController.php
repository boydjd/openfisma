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
 * @author    Nathan Harris <nathan.harris@endeavorsystems.com>
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/mw/index.php?title=License
 * @version   $Id: IRCategoryController.php 2149 2009-08-25 23:34:02Z nathanrharris $
 * @package   Controller
 */

/**
 * Handles CRUD for incident category objects.
 *
 * @package   Controller
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/mw/index.php?title=License
 */
class IrCategoryController extends SecurityController
{
    private $_paging = array(
        'startIndex' => 0,
        'count' => 20,
    );
    
    /**
     * Invoked before each Action
     */
    public function preDispatch()
    {
        parent::preDispatch();
        $req = $this->getRequest();
        $this->_paging['startIndex'] = $req->getParam('startIndex', 0);
    }

    public function init()
    {
        parent::init();
        $this->_helper->contextSwitch()
                      ->addActionContext('tree-data', 'json')
                      ->initContext();
    }

    public function listAction() 
    {
        Fisma_Acl::requirePrivilegeForClass('read', 'IrCategory'); 
        
        $value = trim($this->_request->getParam('keywords'));
        empty($value) ? $link = '' : $link = '/keywords/' . $value;
        $this->searchbox();
        $this->view->assign('pageInfo', $this->_paging);
        $this->view->assign('link', $link);
        
        $this->view->initialRequestUrl = $link
                                       . '/sortby/name/order/asc/startIndex/0/count/'
                                       . $this->_paging['count'];
        
        $this->render('list');
    }

    /**
     * list the ir_categories from the search, 
     * if search none, it list all categories
     * 
     */
    public function searchAction()
    {
        Fisma_Acl::requirePrivilegeForClass('read', 'IrCategory'); 
        
        $value = trim($this->_request->getParam('keywords'));

        $this->_helper->layout->setLayout('ajax');
        $this->_helper->viewRenderer->setNoRender();
        $sortBy = $this->_request->getParam('sortby', 'name');
        $order = $this->_request->getParam('order');
        
        $organization = Doctrine::getTable('IrCategory');
        if (!in_array(strtolower($sortBy), $organization->getColumnNames())) {
            throw new Fisma_Exception('Invalid "sortBy" parameter');
        }
        
        $order = strtoupper($order);
        if ($order != 'DESC') {
            $order = 'ASC'; //ignore other values
        }
        
        $q = Doctrine_Query::create()
             ->select('*')
             ->from('IrCategory irc')
             ->orderBy("irc.$sortBy $order")
             ->limit($this->_paging['count'])
             ->offset($this->_paging['startIndex']);

        if (!empty($value)) {
            $catIds = Fisma_Lucene::search($value, 'ircategory');
            if (empty($catIds)) {
                $catIds = array(-1);
            }
            $q->whereIn('irc.id', $catIds);
        }
        $totalRecords = $q->count();
        $cats = $q->execute();
        
        $tableData = array('table' => array(
            'recordsReturned' => count($cats->toArray()),
            'totalRecords' => $totalRecords,
            'startIndex' => $this->_paging['startIndex'],
            'sort' => $sortBy,
            'dir' => $order,
            'pageSize' => $this->_paging['count'],
            'records' => $cats->toArray()
        ));
        
        echo json_encode($tableData);
    }
    
    /**
     *  Render the form for searching the ircategories.
     */
    public function searchbox()
    {
        Fisma_Acl::requirePrivilegeForClass('read', 'IrCategory'); 
        
        $keywords = trim($this->_request->getParam('keywords'));
        $this->view->assign('keywords', $keywords);
        $this->render('searchbox');
    }
    
    /**
     * Display categories and sub categories tree mode 
     */
    public function treeAction() 
    {
        Fisma_Acl::requirePrivilegeForClass('read', 'IrCategory'); 
        
        $this->searchbox();
        $this->render('tree');        
    }
    
    /**
     * Returns a JSON object that describes the categories and sub categories
     */
    public function treeDataAction() 
    {
        Fisma_Acl::requirePrivilegeForClass('read', 'IrCategory'); 
               
        /* Get all categories */ 
        $q = Doctrine_Query::create()
             ->select('c.name, c.category')
             ->from('IrCategory c');
        
        $cats = $q->execute()->toArray();        

        /* For each category, get the related subcategories and format them so they will work as a tree */
        foreach ($cats as $key => $val) {
            $cats[$key]['children'] =  ''; 

            $q2 = Doctrine_Query::create()
                  ->select('sc.name')
                  ->from('IrSubCategory sc')
                  ->where('sc.categoryId = ?', $val['id']);  

            $cats[$key]['children'] = $q2->execute()->toArray();
            foreach ($cats[$key]['children'] as $key2 => $val2) {
                $cats[$key]['children'][$key2]['children'] = array();
            }
        }
        
        $this->view->treeData = $cats;
    }

    /**
     * Display a single ircategory record with all details.
     */
    public function viewAction()
    {
        Fisma_Acl::requirePrivilegeForClass('read', 'IrCategory'); 
        
        $this->searchbox();
        $id = $this->_request->getParam('id');
        $v = $this->_request->getParam('v', 'view');
        
        $ircategory = Doctrine::getTable('IrCategory')->find($id);
        
        $form = $this->_getCategoryForm($ircategory);
        
        if (!$ircategory) {
            throw new Fisma_Exception('Invalid category ID');
        } else {
            $ircategory = $ircategory->toArray();
        }

        if ($v == 'edit') {
            $this->view->assign('viewLink', "/panel/ircategory/sub/view/id/$id");
            $form->setAction("/panel/ircategory/sub/update/id/$id");
        } else {
            // In view mode, disable all of the form controls
            $this->view->assign('editLink', "/panel/ircategory/sub/view/id/$id/v/edit");
            $form->setReadOnly(true);
        }
        $this->view->assign('deleteLink', "/panel/ircategory/sub/delete/id/$id");
        $form->setDefaults($ircategory);
        $this->view->form = $form;
        $this->view->assign('id', $id);
        $this->render($v);
    }
    
    /**
     * Display the form for creating a new category.
     */
    public function createAction()
    {
        Fisma_Acl::requirePrivilegeForClass('create', 'IrCategory'); 
        
        $form = $this->_getCategoryForm();
        
        $catValues = $this->_request->getPost();

        if ($catValues) {
            if ($form->isValid($catValues)) {
                $catValues = $form->getValues();
                $ircategory = new IrCategory();
                $ircategory->merge($catValues);
                
                // save the data, if failure then return false
                if (!$ircategory->trySave()) {
                    $msg = "Failure in creation";
                    $model = self::M_WARNING;
                } else {
                    /* TODO: ask mark to explain this */
                    $ircategory->getTable()->getRecordListener()->setOption('disabled', true);
                    
                    $msg = "The category is created";
                    $model = self::M_NOTICE;
                }
                $this->message($msg, $model);
                $this->_forward('view', null, null, array('id' => $ircategory->id));
                return;

            } else {
                $errorString = Fisma_Form_Manager::getErrors($form);
                // Error message
                $this->message("Unable to create category:<br>$errorString", self::M_WARNING);
            }
        }
        
        //Display searchbox template
        $this->searchbox();

        $this->view->title = "Create ";
        $this->view->form = $form;
        $this->render('create');
    }
 
    /**
     * Update category information after submitting an edit form.
     *
     * @todo cleanup this function
     */
    public function updateAction()
    {
        Fisma_Acl::requirePrivilegeForClass('update', 'IrCategory'); 
        
        $id = $this->_request->getParam('id', 0);
        $ircategory = new IrCategory();
        $ircategory = $ircategory->getTable()->find($id);

        if (!$ircategory) {
            throw new Exception_General("Invalid category ID");
        }
        
        $form = $this->_getCategoryForm($ircategory);
        $catValues = $this->_request->getPost();
        
        if ($form->isValid($catValues)) {
            $isModify = false;
            $catValues = $form->getValues();
            $ircategory->merge($catValues);

            if ($ircategory->isModified()) {
                $ircategory->save();
                $isModify = true;
            }
            
            if ($isModify) {
                $msg = "The category is saved";
                $model = self::M_NOTICE;
            } else {
                $msg = "Nothing changed";
                $model = self::M_WARNING;
            }
            $this->message($msg, $model);
            $this->_forward('view', null, null, array('id' => $ircategory->id));
        } else {
            $errorString = Fisma_Form_Manager::getErrors($form);
            // Error message
            $this->message("Unable to update category<br>$errorString", self::M_WARNING);
            // On error, redirect back to the edit action.
            $this->_forward('view', null, null, array('id' => $id, 'v' => 'edit'));
        }
    }

    /**
     * Returns the standard form for creating, reading, and
     * updating categories.
     * 
     * @param Object $currCat current record of category
     * @return Zend_Form
     */
    private function _getCategoryForm($currCat = null)
    {
        $form = Fisma_Form_Manager::loadForm('ircategory');
        
        /* TODO: this may need to be edited to only include categories that have not been used */    
        $categories = array(    'CAT0' => 'CAT0', 
                                'CAT1' => 'CAT1', 
                                'CAT2' => 'CAT2', 
                                'CAT3' => 'CAT3', 
                                'CAT4' => 'CAT4', 
                                'CAT5' => 'CAT5', 
                                'CAT6' => 'CAT6',
                        );

        $form->getElement('category')->addMultiOptions($categories);
        
        return Fisma_Form_Manager::prepareForm($form);
    }
    
    /**
     * Delete a specified category.
     * 
     */
    public function deleteAction()
    {
        Fisma_Acl::requirePrivilegeForClass('delete', 'IrCategory'); 
        
        $id = $this->_request->getParam('id');
        $ircategory = Doctrine::getTable('IrCategory')->find($id);
        if ($ircategory) {
            if ($ircategory->delete()) {
                $msg = "Category deleted successfully";
                $model = self::M_NOTICE;
            } else {
                $msg = "Failed to delete the Category";
                $model = self::M_WARNING;
            }
            $this->message($msg, $model);
        }
        $this->_forward('list');
    }
    
    /**
     * Display the form for creating a new sub category.
     */
    public function subcreateAction()
    {
        Fisma_Acl::requirePrivilegeForClass('create', 'IrCategory'); 
        
        $form = $this->_getSubCategoryForm();
        
        $subCatValues = $this->_request->getPost();

        if ($subCatValues) {
            if ($form->isValid($subCatValues)) {
                $subCatValues = $form->getValues();
                $irsubcategory = new IrSubCategory();
                $irsubcategory->merge($subCatValues);                

                // save the data, if failure then return false
                if (!$irsubcategory->trySave()) {
                    $msg = "Failure in creation";
                    $model = self::M_WARNING;
                } else {
                    /* TODO: ask mark to explain this */
                    $irsubcategory->getTable()->getRecordListener()->setOption('disabled', true);
                    
                    $msg = "The category is created";
                    $model = self::M_NOTICE;
                }
                $this->message($msg, $model);
                $this->_forward('subview', null, null, array('id' => $irsubcategory->id));
                return;

            } else {
                $errorString = Fisma_Form_Manager::getErrors($form);
                // Error message
                $this->message("Unable to create sub category:<br>$errorString", self::M_WARNING);
            }
        }
        
        //Display searchbox template
        $this->searchbox();

        $this->view->title = "Create ";
        $this->view->form = $form;
        $this->render('subcreate');

    }

    /**
     * Returns the standard form for creating, reading, and
     * updating sub categories.
     * 
     * @param Object $currOrg current recode of organization
     * @return Zend_Form
     */
    private function _getSubCategoryForm($currCat = null)
    {
        $form = Fisma_Form_Manager::loadForm('irsubcategory');
       
        /* Get all categories */ 
        $q = Doctrine_Query::create()
             ->select('c.name, c.category')
             ->from('IrCategory c')
             ->orderby('c.category');          
 
        $cats = $q->execute()->toArray();        

        foreach ($cats as $key => $val) {
            $categories[$val['id']] = $val['category'] . ' - ' . $val['name']; 
        }

        /* Get all workflows */ 
        $q = Doctrine_Query::create()
             ->select('w.id, w.name ')
             ->from('IrWorkflowDef w')
             ->orderby('w.name');          
 
        $wfs = $q->execute()->toArray();        

        foreach ($wfs as $key => $val) {
            $workflows[$val['id']] = $val['name']; 
        } 

        $form->getElement('categoryId')->addMultiOptions($categories);
        $form->getElement('workflowId')->addMultiOptions($workflows);
        
        return Fisma_Form_Manager::prepareForm($form);
    }
    
    /**
     * Display a single ir sub category record with all details.
     */
    public function subviewAction()
    {
        Fisma_Acl::requirePrivilegeForClass('read', 'IrCategory'); 
        
        $this->searchbox();
        $id = $this->_request->getParam('id');
        $v = $this->_request->getParam('v', 'subview');
        
        $irsubcategory = Doctrine::getTable('IrSubCategory')->find($id);
        
        $form = $this->_getSubCategoryForm($irsubcategory);
        
        if (!$irsubcategory) {
            throw new Fisma_Exception('Invalid sub category ID');
        } else {
            $irsubcategory = $irsubcategory->toArray();
        }

        if ($v == 'subedit') {
            $this->view->assign('viewLink', "/panel/ircategory/sub/subview/id/$id");
            $form->setAction("/panel/ircategory/sub/subupdate/id/$id");
        } else {
            // In view mode, disable all of the form controls
            $this->view->assign('editLink', "/panel/ircategory/sub/subview/id/$id/v/subedit");
            $form->setReadOnly(true);
        }
        $this->view->assign('deleteLink', "/panel/ircategory/sub/subdelete/id/$id");
        $form->setDefaults($irsubcategory);
        $this->view->form = $form;
        $this->view->assign('id', $id);
        $this->render($v);
    }
    
    /**
     * Update sub category information after submitting an edit form.
     *
     * @todo cleanup this function
     */
    public function subupdateAction()
    {
        Fisma_Acl::requirePrivilegeForClass('update', 'IrCategory'); 
        
        $id = $this->_request->getParam('id', 0);
        $irsubcategory = Doctrine::getTable('IrSubCategory')->find($id);

        if (!$irsubcategory) {
            throw new Exception_General("Invalid category ID");
        }
        
        $form = $this->_getSubCategoryForm($irsubcategory);
        $subCatValues = $this->_request->getPost();
        
        if ($form->isValid($subCatValues)) {
            $isModify = false;
            $subCatValues = $form->getValues();
            $irsubcategory->merge($subCatValues);

            if ($irsubcategory->isModified()) {
                $irsubcategory->save();
                $isModify = true;
            }
            
            if ($isModify) {
                $msg = "The category is saved";
                $model = self::M_NOTICE;
            } else {
                $msg = "Nothing changed";
                $model = self::M_WARNING;
            }
            $this->message($msg, $model);
            $this->_forward('subview', null, null, array('id' => $irsubcategory->id));
        } else {
            $errorString = Fisma_Form_Manager::getErrors($form);
            // Error message
            $this->message("Unable to update category<br>$errorString", self::M_WARNING);
            // On error, redirect back to the edit action.
            $this->_forward('subview', null, null, array('id' => $id, 'v' => 'subedit'));
        }
    }
    
    /**
     * Delete a specified sub category.
     * 
     */
    public function subdeleteAction()
    {
        Fisma_Acl::requirePrivilegeForClass('delete', 'IrCategory'); 
        
        $id = $this->_request->getParam('id');
        $irsubcategory = Doctrine::getTable('IrSubCategory')->find($id);
        if ($irsubcategory) {
            if ($irsubcategory->delete()) {
                $msg = "Sub Category deleted successfully";
                $model = self::M_NOTICE;
            } else {
                $msg = "Failed to delete the Sub Category";
                $model = self::M_WARNING;
            }
            $this->message($msg, $model);
        }
        $this->_forward('tree');
    }
}
