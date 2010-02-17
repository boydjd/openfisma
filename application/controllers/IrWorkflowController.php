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
 * @version   $Id: IrWorkflowController.php 2149 2009-08-25 23:34:02Z nathanrharris $
 * @package   Controller
 */

/**
 * Handles CRUD for incident workflow objects.
 *
 * @package   Controller
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/mw/index.php?title=License
 */
class IRWorkflowController extends SecurityController
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
        Fisma_Acl::requirePrivilege('irworkflowdef', 'read'); 
        $value = trim($this->_request->getParam('keywords'));
        empty($value) ? $link = '' : $link = '/keywords/' . $value;
        $this->searchbox();
        $this->view->assign('pageInfo', $this->_paging);
        $this->view->assign('link', $link);
        
        $this->view->initialReqestUrl = $link
                                      . '/sortby/name/order/asc/startIndex/0/count/'
                                      . $this->_paging['count'];

        $this->render('list');
    }
    
    /**
     *  Render the form for searching the ir workflows
     */
    public function searchbox()
    {
        Fisma_Acl::requirePrivilege('irworkflowdef', 'read');
        $keywords = trim($this->_request->getParam('keywords'));
        $this->view->assign('keywords', $keywords);
        $this->render('searchbox');
    }
    
    /**
     * list workflows from the search, 
     * if search none, it list all categories
     * 
     */
    public function searchAction()
    {
        Fisma_Acl::requirePrivilege('irworkflowdef', 'read');
        $value = trim($this->_request->getParam('keywords'));

        $this->_helper->layout->setLayout('ajax');
        $this->_helper->viewRenderer->setNoRender();
        $sortBy = $this->_request->getParam('sortby', 'name');
        $order = $this->_request->getParam('order');
        
        $organization = Doctrine::getTable('IrWorkflowDef');
        if (!in_array(strtolower($sortBy), $organization->getColumnNames())) {
            throw new Fisma_Exception('Invalid "sortBy" parameter');
        }        
        
        $order = strtoupper($order);
        if ($order != 'DESC') {
            $order = 'ASC'; //ignore other values
        }
        
        $q = Doctrine_Query::create()
             ->select('*')
             ->from('IrWorkflowDef w')
             ->orderBy("w.$sortBy $order")
             ->limit($this->_paging['count'])
             ->offset($this->_paging['startIndex']);

        if (!empty($value)) {
            $wfIds = Fisma_Lucene::search($value, 'irworkflow');
            if (empty($catIds)) {
                $catIds = array(-1);
            }
            $q->whereIn('irc.id', $wfIds);
        }
        $totalRecords = $q->count();
        $wfs = $q->execute();
        
        $tableData = array('table' => array(
            'recordsReturned' => count($wfs->toArray()),
            'totalRecords' => $totalRecords,
            'startIndex' => $this->_paging['startIndex'],
            'sort' => $sortBy,
            'dir' => $order,
            'pageSize' => $this->_paging['count'],
            'records' => $wfs->toArray()
        ));
        
        echo json_encode($tableData);
    }
    
    /**
     * Display workflows and steps in tree mode 
     */
    public function treeAction() 
    {
        $this->searchbox();
        $this->render('tree');        
    }
    
    /**
     * Returns a JSON object that describes the workflows and workflow steps
     */
    public function treeDataAction() 
    {
        Fisma_Acl::requirePrivilege('irworkflowdef', 'read');
       
        /* Get all categories */ 
        $q = Doctrine_Query::create()
             ->select('w.name')
             ->from('IrWorkflowDef w');
        
        $wfs = $q->execute()->toArray();        

        /* For each workflow, get the related workflow steps and format them so they will work as a tree */
        foreach ($wfs as $key => $val) {
            $wfs[$key]['children'] =  ''; 

            $q2 = Doctrine_Query::create()
                  ->select('s.name, s.cardinality, s.workflowId')
                  ->from('IrStep s')
                  ->where('s.workflowId = ?', $val['id'])
                  ->orderBy('s.cardinality');

            $wfs[$key]['children'] = $q2->execute()->toArray();
            foreach ($wfs[$key]['children'] as $key2 => $val2) {
                $wfs[$key]['children'][$key2]['children'] = array();
            }
        }
        
        $this->view->treeData = $wfs;
    }
    
    /**
     * Display the form for creating a new workflow.
     */
    public function createAction()
    {
        Fisma_Acl::requirePrivilege('irworkflowdef', 'create'); 
        $form = $this->_getWorkflowForm();
        
        $wfValues = $this->_request->getPost();

        if ($wfValues) {
            if ($form->isValid($wfValues)) {
                $wfValues = $form->getValues();
                $irworkflow = new IrWorkflowDef();
                $irworkflow->merge($wfValues);
                
                // save the data, if failure then return false
                if (!$irworkflow->trySave()) {
                    $msg = "Failure in creation";
                    $model = self::M_WARNING;
                } else {
                    /* TODO: ask mark to explain this */
                    $irworkflow->getTable()->getRecordListener()->setOption('disabled', true);
                    
                    $msg = "The workflow is created";
                    $model = self::M_NOTICE;
                }
                $this->message($msg, $model);
                $this->_forward('view', null, null, array('id' => $irworkflow->id));
                return;

            } else {
                $errorString = Fisma_Form_Manager::getErrors($form);
                // Error message
                $this->message("Unable to create workflow:<br>$errorString", self::M_WARNING);
            }
        }
        
        //Display searchbox template
        $this->searchbox();

        $this->view->title = "Create ";
        $this->view->form = $form;
        $this->render('create');

    }
    
    /**
     * Returns the standard form for creating, reading, and
     * updating workflows.
     * 
     * @return Zend_Form
     */
    private function _getWorkflowForm()
    {
        $form = Fisma_Form_Manager::loadForm('irworkflow');
        return Fisma_Form_Manager::prepareForm($form);
    }
    
    /**
     * Display a single irworkflow record with all details.
     */
    public function viewAction()
    {
        Fisma_Acl::requirePrivilege('irworkflowdef', 'read'); 
        $this->searchbox();
        $id = $this->_request->getParam('id');
        $v = $this->_request->getParam('v', 'view');
        
        $irworkflow = Doctrine::getTable('IrWorkflowDef')->find($id);
        
        $form = $this->_getWorkflowForm($irworkflow);
        
        if (!$irworkflow) {
            throw new Fisma_Exception('Invalid workflow ID');
        } else {
            $irworkflow = $irworkflow->toArray();
        }

        if ($v == 'edit') {
            $this->view->assign('viewLink', "/panel/irworkflow/sub/view/id/$id");
            $form->setAction("/panel/irworkflow/sub/update/id/$id");
        } else {
            // In view mode, disable all of the form controls
            $this->view->assign('editLink', "/panel/irworkflow/sub/view/id/$id/v/edit");
            $form->setReadOnly(true);
        }
        $this->view->assign('deleteLink', "/panel/irworkflow/sub/delete/id/$id");
        $form->setDefaults($irworkflow);
        $this->view->form = $form;
        $this->view->assign('id', $id);
        $this->render($v);
    }
    
    /**
     * Update workflow information after submitting an edit form.
     *
     * @todo cleanup this function
     */
    public function updateAction()
    {
        Fisma_Acl::requirePrivilege('irworkflowdef', 'update'); 
        $id = $this->_request->getParam('id', 0);
        $irworkflow = new IrWorkflowDef();
        $irworkflow = $irworkflow->getTable()->find($id);

        if (!$irworkflow) {
            throw new Exception_General("Invalid workflow ID");
        }
        
        $form = $this->_getWorkflowForm($irworkflow);
        $wfValues = $this->_request->getPost();
        
        if ($form->isValid($wfValues)) {
            $isModify = false;
            $wfValues = $form->getValues();
            $irworkflow->merge($wfValues);

            if ($irworkflow->isModified()) {
                $irworkflow->save();
                $isModify = true;
            }
            
            if ($isModify) {
                $msg = "The workflow is saved";
                $model = self::M_NOTICE;
            } else {
                $msg = "Nothing changed";
                $model = self::M_WARNING;
            }
            $this->message($msg, $model);
            $this->_forward('view', null, null, array('id' => $irworkflow->id));
        } else {
            $errorString = Fisma_Form_Manager::getErrors($form);
            // Error message
            $this->message("Unable to update workflow<br>$errorString", self::M_WARNING);
            // On error, redirect back to the edit action.
            $this->_forward('view', null, null, array('id' => $id, 'v' => 'edit'));
        }
    }
    
    /**
     * Display the form for creating a new workflow step.
     * 
     * @todo there is a bug in this method. it doesn't move other steps around in order to make room for adding
     * this one into the middle of a workflow
     */
    public function stepcreateAction()
    {
        Fisma_Acl::requirePrivilege('irworkflowdef', 'create'); 
        $form = $this->_getWorkflowStepForm();
        
        $wfsValues = $this->_request->getPost();

        if ($wfsValues) {
            if ($form->isValid($wfsValues)) {
                $wfsValues = $form->getValues();
                $irworkflowstep = new IrStep();
                if (empty($wfsValues['roleId'])) {
                    unset($wfsValues['roleId']);
                }
                $irworkflowstep->merge($wfsValues);
                
                // save the data, if failure then return false
                if (!$irworkflowstep->trySave()) {
                    $msg = "Failure in creation";
                    $model = self::M_WARNING;
                } else {
                    /* TODO: ask mark to explain this */
                    $irworkflowstep->getTable()->getRecordListener()->setOption('disabled', true);
                    
                    $msg = "The workflow step is created";
                    $model = self::M_NOTICE;
                }
                $this->message($msg, $model);
                $this->_forward('stepview', null, null, array('id' => $irworkflowstep->id));
                return;

            } else {
                $errorString = Fisma_Form_Manager::getErrors($form);
                // Error message
                $this->message("Unable to create workflow step:<br>$errorString", self::M_WARNING);
            }
        }
        
        //Display searchbox template
        $this->searchbox();

        $this->view->title = "Create ";
        $this->view->form = $form;
        $this->render('stepcreate');

    }
    
    /**
     * Returns the standard form for creating, reading, and
     * updating workflow steps.
     * 
     * @return Zend_Form
     */
    private function _getWorkflowStepForm()
    {
        $form = Fisma_Form_Manager::loadForm('irworkflowstep');
        
        /* Get all workflows */ 
        $q = Doctrine_Query::create()
             ->select('w.id, w.name')
             ->from('IrWorkflowDef w')
             ->orderby('w.name');          
 
        $wfs = $q->execute()->toArray();        

        foreach ($wfs as $key => $val) {
            $workflows[$val['id']] = $val['name']; 
        } 
        
        $form->getElement('workflowId')->addMultiOptions($workflows);

        /* Get roles*/
        $q = Doctrine_Query::create()
             ->select('r.id, r.name')
             ->from('Role r')
             ->where('r.nickname IN ("ISSO", "ISO", "OIG", "ED-CIRC", "IRC", "DBR", "PA", "IRS")')
             ->orderby('r.name');          
 
        $role = $q->execute()->toArray();        

        foreach ($role as $key => $val) {
            $roles[$val['id']] = $val['name']; 
        } 
        
        $form->getElement('roleId')
             ->addMultiOptions(array('' => ''))
             ->addMultiOptions($roles);

        /** @todo bug here also... it gets the table's max cardinality, not the workflow's */ 
        $q = Doctrine_Query::create()
             ->select('max(s.cardinality) as cardinality')
             ->from('IrStep s');
 
        $max = $q->execute()->toArray();        

        for ($x=1; $x <= $max[0]['cardinality']; $x+=1) {
            $cardinalitys[$x] = $x;
        }

        foreach ($wfs as $key => $val) {
            $workflows[$val['id']] = $val['name']; 
        } 
        
        $form->getElement('cardinality')->addMultiOptions($cardinalitys);

        return Fisma_Form_Manager::prepareForm($form);
    }
    
    /**
     * Display a single irworkflow step record with all details.
     */
    public function stepviewAction()
    {
        Fisma_Acl::requirePrivilege('irworkflowdef', 'read'); 
        $this->searchbox();
        $id = $this->_request->getParam('id');
        $v = $this->_request->getParam('v', 'stepview');
        
        $irworkflowstep = Doctrine::getTable('IrStep')->find($id);
        
        $form = $this->_getWorkflowStepForm($irworkflowstep);
        
        if (!$irworkflowstep) {
            throw new Fisma_Exception('Invalid workflow step ID');
        } else {
            $irworkflowstep = $irworkflowstep->toArray();
        }

        if ($v == 'stepedit') {
            $this->view->assign('viewLink', "/panel/irworkflow/sub/stepview/id/$id");
            $form->setAction("/panel/irworkflow/sub/stepupdate/id/$id");
        } else {
            // In view mode, disable all of the form controls
            $this->view->assign('editLink', "/panel/irworkflow/sub/stepview/id/$id/v/stepedit");
            $form->setReadOnly(true);
        }
        $this->view->assign('deleteLink', "/panel/irworkflow/sub/stepdelete/id/$id");
        $form->setDefaults($irworkflowstep);
        $this->view->form = $form;
        $this->view->assign('id', $id);
        $this->render($v);
    }
    
    /**
     * Update workflow step information after submitting an edit form.
     *
     * @todo cleanup this function
     */
    public function stepupdateAction()
    {
        Fisma_Acl::requirePrivilege('irworkflowdef', 'update'); 
        $id = $this->_request->getParam('id', 0);
        $irworkflowstep = new IrStep();
        $irworkflowstep = $irworkflowstep->getTable()->find($id);

        if (!$irworkflowstep) {
            throw new Exception_General("Invalid workflow step ID");
        }
        
        $form = $this->_getWorkflowForm($irworkflowstep);
        $wfsValues = $this->_request->getPost();
       
        if ($form->isValid($wfsValues)) {
            $isModify = false;
            if (empty($wfsValues['roleId'])) {
                unset($wfsValues['roleId']);
            }
            $irworkflowstep->merge($wfsValues);

            if ($irworkflowstep->isModified()) {
                $irworkflowstep->save();
                $isModify = true;
            }
            
            if ($isModify) {
                $msg = "The workflow step is saved";
                $model = self::M_NOTICE;
            } else {
                $msg = "Nothing changed";
                $model = self::M_WARNING;
            }
            $this->message($msg, $model);

            $this->_sortSteps();
        
            $this->_forward('stepview', null, null, array('id' => $irworkflowstep->id));
        } else {
            $errorString = Fisma_Form_Manager::getErrors($form);
            // Error message
            $this->message("Unable to update workflow step<br>$errorString", self::M_WARNING);
            // On error, redirect back to the edit action.
            $this->_forward('stepview', null, null, array('id' => $id, 'v' => 'stepedit'));
        }
    }
    
    /**
     * Delete a specified workflow.
     * 
     */
    public function deleteAction()
    {
        Fisma_Acl::requirePrivilege('irworkflowdef', 'delete');
        $id = $this->_request->getParam('id');
        $irworkflow = Doctrine::getTable('IrWorkflowDef')->find($id);
        if ($irworkflow) {
            $irworkflow->Steps->delete();
            $irworkflow->unlink('SubCategories');
            if ($irworkflow->delete()) {
                $msg = "Workflow deleted successfully";
                $model = self::M_NOTICE;
            } else {
                $msg = "Failed to delete the Workflow";
                $model = self::M_WARNING;
            }
            $this->message($msg, $model);
        }
        $this->_forward('list');
    }
    
    /**
     * Delete a specified workflow step
     * 
     */
    public function stepdeleteAction()
    {
        Fisma_Acl::requirePrivilege('irworkflowdef', 'delete');
        $id = $this->_request->getParam('id');
        $irworkflow = Doctrine::getTable('IrStep')->find($id);
        if ($irworkflow) {
            if ($irworkflow->delete()) {
                $msg = "Workflow Step deleted successfully";
                $model = self::M_NOTICE;
            } else {
                $msg = "Failed to delete the Workflow Step";
                $model = self::M_WARNING;
            }
            $this->message($msg, $model);
            $this->_sortSteps();
        }
        $this->_forward('tree');
    }

    /**
     * Resort all the workflow steps so that the sort orders start at 1 and don't skip any numbers
     */
    private function _sortSteps() 
    {
        $q = Doctrine_Query::create()
             ->select('s.id, s.workflowId')
             ->from('IrStep s')
             ->orderBy('s.workflowId, s.cardinality');

        $wfs = $q->execute()->toArray();

        $count = 1;
        $oldWf = -1;

        foreach ($wfs as $key => $val) {
            if (!($oldWf == $val['workflowId'])) {
                $oldWf = $val['workflowId'];
                $count = 1;
            }

            $updates[$val['id']] = $count;
            
            $count += 1;
        }
           
        foreach ($updates as $key => $val) {
            $irworkflow = Doctrine::getTable('IrStep')->find($key);
            $irworkflow->cardinality = $val;
            $irworkflow->save();
        }
    }
}
