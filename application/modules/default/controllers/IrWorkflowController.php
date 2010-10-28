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
 * CRUD behavior for incident workflows
 *
 * @author     Mark E. Haase
 * @copyright  (c) Endeavor Systems, Inc. 2010 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Controller
 * @version    $Id$
 */
class IRWorkflowController extends Fisma_Zend_Controller_Action_Object
{
    /**
     * Invoked before each Action
     */
    public function preDispatch()
    {
        parent::preDispatch();

        $module = Doctrine::getTable('Module')->findOneByName('Incident Reporting');

        if (!$module->enabled) {
            throw new Fisma_Zend_Exception('This module is not enabled.');
        }

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
        $this->_acl->requirePrivilegeForClass('read', 'IrWorkflowDef');

        $this->view->readIrWorkflowDefPrivilege = $this->_acl->hasPrivilegeForClass('read', 'IrWorkflowDef');

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
        $this->_acl->requirePrivilegeForClass('read', 'IrWorkflowDef');

        $this->createIrWorkflowDefPrivilege = $this->_acl->hasPrivilegeForClass('create', 'IrWorkflowDef');

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
        $this->_acl->requirePrivilegeForClass('read', 'IrWorkflowDef');

        $value = trim($this->_request->getParam('keywords'));

        $this->_helper->layout->setLayout('ajax');
        $this->_helper->viewRenderer->setNoRender();
        $sortBy = $this->_request->getParam('sortby', 'name');
        $order = $this->_request->getParam('order');

        $organization = Doctrine::getTable('IrWorkflowDef');
        if (!in_array(strtolower($sortBy), $organization->getColumnNames())) {
            throw new Fisma_Zend_Exception('Invalid "sortBy" parameter');
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
        $this->_acl->requirePrivilegeForClass('read', 'IrWorkflowDef');

        $this->searchbox();
        $this->render('tree');
    }

    /**
     * Returns a JSON object that describes the workflows and workflow steps
     */
    public function treeDataAction()
    {
        $this->_acl->requirePrivilegeForClass('read', 'IrWorkflowDef');

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
            $wfs[$key]['name'] =  $this->view->escape($wfs[$key]['name'], 'html');
            foreach ($wfs[$key]['children'] as $key2 => $val2) {
                $wfs[$key]['children'][$key2]['children'] = array();
                $wfs[$key]['children'][$key2]['name'] = $this->view->escape($wfs[$key]['children'][$key2]['name'], 'html');
            }
        }

        $this->view->treeData = $wfs;
    }

    /**
     * Display the form for creating a new workflow.
     */
    public function createAction()
    {
        $this->_acl->requirePrivilegeForClass('create', 'IrWorkflowDef');

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
                    $model = 'warning';
                } else {
                    /* TODO: ask mark to explain this */
                    $irworkflow->getTable()->getRecordListener()->setOption('disabled', true);

                    $msg = "The workflow is created";
                    $model = 'notice';
                }
                $this->view->priorityMessenger($msg, $model);
                $this->_redirect("/ir-workflow/view/id/{$irworkflow->id}");
                return;

            } else {
                $errorString = Fisma_Zend_Form_Manager::getErrors($form);
                // Error message
                $this->view->priorityMessenger("Unable to create workflow:<br>$errorString", 'warning');
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
        $form = Fisma_Zend_Form_Manager::loadForm('irworkflow');
        return Fisma_Zend_Form_Manager::prepareForm($form);
    }

    /**
     * Display a single irworkflow record with all details.
     */
    public function viewAction()
    {
        $this->_acl->requirePrivilegeForClass('read', 'IrWorkflowDef');

        $this->updateIrWorkflowDefPrivilege = $this->_acl->hasPrivilegeForClass('update', 'IrWorkflowDef');
        $this->deleteIrWorkflowDefPrivilege = $this->_acl->hasPrivilegeForClass('delete', 'IrWorkflowDef');

        $this->searchbox();
        $id = $this->_request->getParam('id');
        $v = $this->_request->getParam('v', 'view');

        $irworkflow = Doctrine::getTable('IrWorkflowDef')->find($id);

        $form = $this->_getWorkflowForm($irworkflow);

        if (!$irworkflow) {
            throw new Fisma_Zend_Exception('Invalid workflow ID');
        } else {
            $irworkflow = $irworkflow->toArray();
        }

        if ($v == 'edit') {
            $this->view->assign('viewLink', "/ir-workflow/view/id/$id");
            $form->setAction("/ir-workflow/update/id/$id");
        } else {
            // In view mode, disable all of the form controls
            $this->view->assign('editLink', "/ir-workflow/view/id/$id/v/edit");
            $form->setReadOnly(true);
        }
        $this->view->assign('deleteLink', "/ir-workflow/delete/id/$id");
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
        $this->_acl->requirePrivilegeForClass('update', 'IrWorkflowDef');

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
                $model = 'notice';
            } else {
                $msg = "Nothing changed";
                $model = 'warning';
            }
            $this->view->priorityMessenger($msg, $model);
            $this->_redirect("/ir-workflow/view/id/{$irworkflow->id}");
        } else {
            $errorString = Fisma_Zend_Form_Manager::getErrors($form);
            // Error message
            $this->view->priorityMessenger("Unable to update workflow<br>$errorString", 'warning');
            // On error, redirect back to the edit action.
            $this->_redirect("/ir-workflow/view/id/$id/v/edit");
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
        $this->_acl->requirePrivilegeForClass('create', 'IrWorkflowDef');

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
                    $model = 'warning';
                } else {
                    /* TODO: ask mark to explain this */
                    $irworkflowstep->getTable()->getRecordListener()->setOption('disabled', true);

                    $msg = "The workflow step is created";
                    $model = 'notice';
                }
                $this->view->priorityMessenger($msg, $model);
                $this->_redirect("/ir-workflow/stepview/id/{$irworkflowstep->id}");
                return;

            } else {
                $errorString = Fisma_Zend_Form_Manager::getErrors($form);
                // Error message
                $this->view->priorityMessenger("Unable to create workflow step:<br>$errorString", 'warning');
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
        $form = Fisma_Zend_Form_Manager::loadForm('irworkflowstep');

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

        return Fisma_Zend_Form_Manager::prepareForm($form);
    }

    /**
     * Display a single irworkflow step record with all details.
     */
    public function stepviewAction()
    {
        $this->_acl->requirePrivilegeForClass('read', 'IrWorkflowDef');

        $this->updateIrWorkflowDefPrivilege = $this->_acl->hasPrivilegeForClass('update', 'IrWorkflowDef');
        $this->deleteIrWorkflowDefPrivilege = $this->_acl->hasPrivilegeForClass('delete', 'IrWorkflowDef');

        $this->searchbox();
        $id = $this->_request->getParam('id');
        $v = $this->_request->getParam('v', 'stepview');

        $irworkflowstep = Doctrine::getTable('IrStep')->find($id);

        $form = $this->_getWorkflowStepForm($irworkflowstep);

        if (!$irworkflowstep) {
            throw new Fisma_Zend_Exception('Invalid workflow step ID');
        } else {
            $irworkflowstep = $irworkflowstep->toArray();
        }

        if ($v == 'stepedit') {
            $this->view->assign('viewLink', "/ir-workflow/stepview/id/$id");
            $form->setAction("/ir-workflow/stepupdate/id/$id");
        } else {
            // In view mode, disable all of the form controls
            $this->view->assign('editLink', "/ir-workflow/stepview/id/$id/v/stepedit");
            $form->setReadOnly(true);
        }
        $this->view->assign('deleteLink', "/ir-workflow/stepdelete/id/$id");
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
        $this->_acl->requirePrivilegeForClass('update', 'IrWorkflowDef');

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
                $model = 'notice';
            } else {
                $msg = "Nothing changed";
                $model = 'warning';
            }
            $this->view->priorityMessenger($msg, $model);

            $this->_redirect("/ir-workflow/stepview/id/{$irworkflowstep->id}");
        } else {
            $errorString = Fisma_Zend_Form_Manager::getErrors($form);
            // Error message
            $this->view->priorityMessenger("Unable to update workflow step<br>$errorString", 'warning');
            // On error, redirect back to the edit action.
            $this->_redirect("/ir-workflow/stepview/id/$id/v/stepedit");
        }
    }

    /**
     * Delete a specified workflow.
     */
    public function deleteAction()
    {
        $this->_acl->requirePrivilegeForClass('delete', 'IrWorkflowDef');

        $id = $this->_request->getParam('id');
        $irworkflow = Doctrine::getTable('IrWorkflowDef')->find($id);
        if ($irworkflow) {
            $irworkflow->Steps->delete();
            $irworkflow->unlink('SubCategories');
            if ($irworkflow->delete()) {
                $msg = "Workflow deleted successfully";
                $model = 'notice';
            } else {
                $msg = "Failed to delete the Workflow";
                $model = 'warning';
            }
            $this->view->priorityMessenger($msg, $model);
        }
        $this->_redirect('/ir-workflow/list');
    }

    /**
     * Delete a specified workflow step
     */
    public function stepdeleteAction()
    {
        $this->_acl->requirePrivilegeForClass('delete', 'IrWorkflowDef');

        $id = $this->_request->getParam('id');
        $irworkflow = Doctrine::getTable('IrStep')->find($id);
        if ($irworkflow) {
            if ($irworkflow->delete()) {
                $msg = "Workflow Step deleted successfully";
                $model = 'notice';
            } else {
                $msg = "Failed to delete the Workflow Step";
                $model = 'warning';
            }
            $this->view->priorityMessenger($msg, $model);
        }
        $this->_redirect('/ir-workflow/tree');
    }
}
