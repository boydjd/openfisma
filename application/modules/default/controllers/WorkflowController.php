<?php
/**
 * Copyright (c) 2013 Endeavor Systems, Inc.
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
 * A controller for workflows across all modules
 *
 * @author     Duy K. Bui <duy.bui@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2013 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Controller
 */
class WorkflowController extends Fisma_Zend_Controller_Action_Security
{
    /**
     * Set up the JSON contexts used in this controller
     */
    public function init()
    {
        $this->_helper->ajaxContext()
            ->addActionContext('list', 'html')
            ->addActionContext('new', 'html')
            ->addActionContext('save-step', 'html')
            ->addActionContext('transition-form', 'html')
            ->addActionContext('workflow', 'html')
            ->addActionContext('complete-step', 'json')
            ->initContext();

        parent::init();
    }

    /**
     * Redirects to the manageAction
     *
     * @GETAllowed
     */
    public function indexAction()
    {
        $this->_redirect('/workflow/manage');
    }

    /**
     * Create the Tab View
     *
     * @GETAllowed
     */
    public function manageAction()
    {
        $this->view->tabView = new Fisma_Yui_TabView('WorkflowManage');
        $this->view->tabView->addTab("Finding", '/workflow/list/format/html/type/finding');
        $this->view->tabView->addTab("Incident", '/workflow/list/format/html/type/incident');
        $this->view->tabView->addTab("Vulnerability", '/workflow/list/format/html/type/vulnerability');
        $this->view->toolbarButtons = $this->getToolbarButtons();
    }


    /**
     * Add a new workflow
     *
     * @GETAllowed
     */
    public function newAction()
    {
        $this->_validateModule();
        $this->_requireManagePrivilege();

        if ($this->getRequest()->isPost()) {
            $workflowArray = $this->getRequest()->getPost('workflow');

            if (empty($workflowArray['name'])) {
                $this->view->priorityMessenger(
                    'You cannot create a workflow with an empty name.',
                    'warning'
                );
            } else {
                $workflow = new Workflow();
                $workflow->name = $workflowArray['name'];
                $workflow->description = $workflowArray['description'];
                $workflow->module = $this->view->module; //has to set module before setting default
                $workflow->isDefault = $workflowArray['isDefault'];
                $workflow->creatorId = CurrentUser::getAttribute('id');
                $workflow->save();
            }

            $this->_redirect('/workflow');
        }
    }

    /**
     * Add a new workflow step or save an existing one
     *
     * @GETAllowed
     */
    public function saveStepAction()
    {
        $this->_requireManagePrivilege();

        $id = $this->getRequest()->getParam('id');
        if ($id) {
            $this->view->step = Doctrine::getTable('WorkflowStep')->find($id);
            $this->view->workflow = $this->view->step->Workflow;
        } else {
            $this->_validateWorkflowId();
        }
        $this->view->availableWorkflows = Doctrine::getTable('Workflow')->listArray($this->view->workflow->module);

        $this->view->editableFields = Doctrine::getTable(ucfirst($this->view->workflow->module))->getEditableFields();

        $this->view->prerequisites = new Fisma_Yui_Form_JsonMultiselect('prerequisites');
        $this->view->prerequisites->setMultiOptions($this->view->editableFields);
        if ($this->view->step && $this->view->step->prerequisites) {
            $this->view->prerequisites->setValue($this->view->step->prerequisites);
        }

        $this->view->restrictedFields = new Fisma_Yui_Form_JsonMultiselect('restrictedFields');
        $this->view->restrictedFields->setMultiOptions($this->view->editableFields);
        if ($this->view->step && $this->view->step->restrictedFields) {
            $this->view->restrictedFields->setValue($this->view->step->restrictedFields);
        }

        if ($this->getRequest()->isPost()) {
            $stepArray = $this->getRequest()->getPost('step');
            $prerequisites = $this->getRequest()->getPost('prerequisites');
            $restrictedFields = $this->getRequest()->getPost('restrictedFields');

            if (empty($stepArray['name'])) {
                $this->view->priorityMessenger(
                    'You cannot create a workflow step with an empty name.',
                    'warning'
                );
            } else {
                if ($id) {
                    $step = $this->view->step;
                } else {
                    $previousStepCount = Doctrine_Query::create()
                        ->from('WorkflowStep ws')
                        ->where('ws.workflowId = ?', $this->view->workflow->id)
                        ->count();

                    $step = new WorkflowStep();
                    $step->cardinality = $previousStepCount + 1;
                    $step->workflowId = $this->view->workflow->id;
                }

                $step->name = $stepArray['name'];
                $step->label = $stepArray['label'];
                $step->description = $stepArray['description'];
                $step->isResolved = !empty($stepArray['isResolved']);
                $step->allottedTime = $stepArray['allottedTime'];
                $step->allottedDays = $stepArray['allottedDays'];
                $step->autoTransition = !empty($stepArray['autoTransition']);
                $step->attachmentEditable = !empty($stepArray['attachmentEditable']);
                $step->prerequisites = Zend_Json::decode($prerequisites);
                $step->restrictedFields = Zend_Json::decode($restrictedFields);
                if (!empty($stepArray['autoTransitionDestination'])) {
                    $step->autoTransitionDestination = $stepArray['autoTransitionDestination'];
                }
                $step->transitions = Zend_Json::decode($stepArray['transitions']);
                $step->save();
            }

            $this->_redirect('/workflow/view/id/' . $this->view->workflow->id);
        }
    }

    /**
     * Move a workflow step
     */
    public function moveStepAction()
    {
        $this->_validateWorkflowId();
        $this->_requireManagePrivilege();

        if ($this->getRequest()->isPost()) {
            $id = $this->getRequest()->getParam('id');
            $direction = $this->getRequest()->getParam('direction');
            $step = Doctrine::getTable('WorkflowStep')->find($id);

            switch ($direction) {
                case 'first':
                    $step->moveFirst();
                    break;
                case 'up':
                    $step->moveUp();
                    break;
                case 'down':
                    $step->moveDown();
                    break;
                case 'last':
                    $step->moveLast();
                    break;
            }

            $this->_redirect('/workflow/view/id/' . $this->view->workflow->id);
        }
    }

    /**
     * Set a workflow as default
     */
    public function setDefaultAction()
    {
        $this->_requireManagePrivilege();

        if ($this->getRequest()->isPost()) {
            $id = $this->getRequest()->getParam('id');
            $workflow = Doctrine::getTable('Workflow')->find($id);
            $workflow->isDefault = true;
            $workflow->save();
            $this->_redirect('/workflow');
        }
    }

    /**
     * Delete a workflow
     */
    public function deleteAction()
    {
        $this->_requireManagePrivilege();

        if ($this->getRequest()->isPost()) {
            $id = $this->getRequest()->getParam('id');
            $workflow = Doctrine::getTable('Workflow')->find($id);

            if (!$workflow->isDefault) {
                $workflow->delete();
            } else {
                $this->view->priorityMessenger(
                    'Please set another workflow as default before deleting ' . $workflow->name .'.',
                    'warning'
                );
            }

            $this->_redirect('/workflow');
        }
    }

    /**
     * Delete a workflow step
     */
    public function deleteStepAction()
    {
        $this->_validateWorkflowId();
        $this->_requireManagePrivilege();

        if ($this->getRequest()->isPost()) {
            $id = $this->getRequest()->getParam('id');
            $step = Doctrine::getTable('WorkflowStep')->find($id);
            $step->delete();
            $this->_redirect('/workflow/view/id/' . $this->view->workflow->id);
        }
    }

    protected function _validateModule()
    {
        $module = $this->getRequest()->getParam('type');
        $table = Doctrine::getTable('Workflow');
        if (!in_array($module, $table->getEnumValues('module'))) {
            throw new Fisma_Zend_Exception_User("Invalid module provided.");
        }

        $this->view->module = $module;
        $this->view->table = $table;
    }

    protected function _validateWorkflowId()
    {
        $workflowId = $this->getRequest()->getParam('workflowId');
        $workflow = Doctrine::getTable('Workflow')->find($workflowId);
        if (!$workflow) {
            throw new Fisma_Zend_Exception_User("Invalid workflow ID provided.");
        }

        $this->view->workflow = $workflow;
    }

    protected function _requireManagePrivilege()
    {
        $this->_acl->requirePrivilegeForClass('manage', 'Workflow');
    }

    /**
     * Edit details and steps for a workflow
     *
     * @GETAllowed
     */
    public function viewAction()
    {
        $id = $this->getRequest()->getParam('id');
        $workflow = Doctrine::getTable('Workflow')->find($id);

        $this->view->workflow = $workflow;
        $this->view->toolbarButtons = $this->getToolbarButtons();

        $steps = Doctrine::getTable('WorkflowStep')->listArray($this->view->workflow);
        $stepRows = array();
        foreach ($steps as $step) {
            $stepRows[] = array(
                'id' => $step->id,
                'name' => $step->name,
                'label' => $step->label,
                'description' => $step->description,
                'actions' => Zend_Json::encode(array(
                    array(
                        'label'     => 'edit',
                        'icon'      => '/images/edit.png',
                        'handler'   => 'Fisma.Workflow.editStep'
                    ),
                    array(
                        'label'     => 'move first',
                        'icon'      => '/images/move_first.png',
                        'handler'   => 'Fisma.Workflow.moveStepFirst'
                    ),
                    array(
                        'label'     => 'move up',
                        'icon'      => '/images/move_up.png',
                        'handler'   => 'Fisma.Workflow.moveStepUp'
                    ),
                    array(
                        'label'     => 'move down',
                        'icon'      => '/images/move_down.png',
                        'handler'   => 'Fisma.Workflow.moveStepDown'
                    ),
                    array(
                        'label'     => 'move last',
                        'icon'      => '/images/move_last.png',
                        'handler'   => 'Fisma.Workflow.moveStepLast'
                    ),
                    array(
                        'label'     => 'delete',
                        'icon'      => '/images/trash_recyclebin_empty_open.png',
                        'handler'   => 'Fisma.Workflow.deleteStep'
                    )
                ))
            );
        }

        $this->view->dataTable = new Fisma_Yui_DataTable_Local();
        $this->view->dataTable->addColumn(
            new Fisma_Yui_DataTable_Column(
                'ID',
                true,
                null,
                null,
                'id',
                true
            )
        );
        $this->view->dataTable->addColumn(
            new Fisma_Yui_DataTable_Column(
                'Name',
                true,
                null,
                null,
                'name'
            )
        );
        $this->view->dataTable->addColumn(
            new Fisma_Yui_DataTable_Column(
                'Label',
                true,
                null,
                null,
                'label'
            )
        );
        $this->view->dataTable->addColumn(
            new Fisma_Yui_DataTable_Column(
                'Instruction(s)',
                false,
                'Fisma.TableFormat.formatHtml',
                null,
                'description'
            )
        );
        $this->view->dataTable->addColumn(
            new Fisma_Yui_DataTable_Column(
                'Actions',
                true,
                'Fisma.TableFormat.formatActions',
                null,
                'actions'
            )
        );
        $this->view->dataTable->setData($stepRows);
    }

    /**
     * Modify a workflow's metadata
     */
    public function editAction()
    {
        $this->_requireManagePrivilege();

        if ($this->getRequest()->isPost()) {
            $workflowArray = $this->getRequest()->getPost('workflow');
            $id = $this->getRequest()->getParam('id');
            $workflow = Doctrine::getTable('Workflow')->find($id);

            $workflow->merge($workflowArray);
            $workflow->save();

            $this->_redirect('/workflow/view/id/' . $id);
        }
    }

    /**
     * Manage the list of workflows according to module
     *
     * @GETAllowed
     */
    public function listAction()
    {
        $this->_validateModule();

        $workflows = $this->view->table->listArray($this->view->module);
        $workflowRows = array();
        foreach ($workflows as $workflow) {
            $workflowRows[] = array(
                'id' => $workflow->id,
                'isDefault' => ($workflow->isDefault) ? "YES" : "NO",
                'name' => $workflow->name,
                'description' => $workflow->description,
                'actions' => Zend_Json::encode(array(
                    array(
                        'label'     => 'Edit the workflow',
                        'icon'      => '/images/edit.png',
                        'handler'   => 'Fisma.Workflow.editWorkflow'
                    ),
                    array(
                        'label'     => 'Delete the workflow',
                        'icon'      => '/images/trash_recyclebin_empty_open.png',
                        'handler'   => 'Fisma.Workflow.deleteWorkflow'
                    ),
                    array(
                        'label'     => 'Set the default workflow for new ' . $this->view->module . '(s)',
                        'icon'      => '/images/default.png',
                        'handler'   => 'Fisma.Workflow.setDefaultWorkflow'
                    )
                ))
            );
        }

        $this->view->dataTable = new Fisma_Yui_DataTable_Local();
        $this->view->dataTable->addColumn(
            new Fisma_Yui_DataTable_Column(
                'ID',
                true,
                null,
                null,
                'id',
                true
            )
        );
        $this->view->dataTable->addColumn(
            new Fisma_Yui_DataTable_Column(
                'Default?',
                true,
                'Fisma.TableFormat.formatDefault',
                null,
                'isDefault'
            )
        );
        $this->view->dataTable->addColumn(
            new Fisma_Yui_DataTable_Column(
                'Name',
                true,
                null,
                null,
                'name'
            )
        );
        $this->view->dataTable->addColumn(
            new Fisma_Yui_DataTable_Column(
                'Description',
                false,
                'Fisma.TableFormat.formatHtml',
                null,
                'description'
            )
        );
        $this->view->dataTable->addColumn(
            new Fisma_Yui_DataTable_Column(
                'Actions',
                true,
                'Fisma.TableFormat.formatActions',
                null,
                'actions'
            )
        );
        $this->view->dataTable->setData($workflowRows);
    }

    /**
     * Return a form to add / edit transitions, all changes are processed at client-side
     *
     * @GETAllowed
     */
    public function transitionFormAction()
    {
        $this->_validateWorkflowId();
        $this->view->availableWorkflows = Doctrine::getTable('Workflow')->listArray($this->view->workflow->module);
    }

    public function getToolbarButtons()
    {
        $action = $this->getRequest()->getActionName();
        $buttons = array();

        switch ($action) {
            case 'manage':
                $buttons['newWorkflow'] = new Fisma_Yui_Form_Button(
                    'newWorkflow',
                    array(
                        'label' => 'New',
                        'onClickFunction' => 'Fisma.Workflow.addWorkflow',
                        'imageSrc' => '/images/create.png'
                    )
                );
                break;
            case 'view':
                $id = $this->getRequest()->getParam('id');
                $buttons['listButton'] = new Fisma_Yui_Form_Button_Link(
                    'listWorkflows',
                    array(
                        'value' => 'List',
                        'imageSrc' => '/images/list_view.png',
                        'href' => '/workflow'
                    )
                );

                //check manage privilege
                $buttons['editButton'] = new Fisma_Yui_Form_Button(
                    'editMode',
                    array(
                        'label' => 'Edit',
                        'onClickFunction' => 'Fisma.Editable.turnAllOn',
                        'imageSrc' => '/images/edit.png'
                    )
                );

                $buttons['submitButton'] = new Fisma_Yui_Form_Button(
                    'saveChanges',
                    array(
                        'label' => 'Save',
                        'onClickFunction' => 'Fisma.Util.submitFirstForm',
                        'imageSrc' => '/images/ok.png',
                        'hidden' => true
                    )
                );

                $buttons['discardButton'] = new Fisma_Yui_Form_Button_Link(
                    'discardChanges',
                    array(
                        'value' => 'Cancel',
                        'imageSrc' => '/images/no_entry.png',
                        'href' => '/workflow/view/id/' . $id,
                        'hidden' => true
                    )
                );

                $buttons['addStep'] = new Fisma_Yui_Form_Button(
                    'addStep',
                    array(
                        'label' => 'Add Step',
                        'onClickFunction' => 'Fisma.Workflow.addStep',
                        'imageSrc' => '/images/create.png'
                    )
                );
                break;
        }

        return $buttons;
    }

    /**
     * Workflow tab
     *
     * @GETAllowed
     */
    public function workflowAction()
    {
        $id = $this->_request->getParam('id');
        $model = $this->_request->getParam('model');
        $object = Doctrine::getTable(ucfirst($model))->find($id);

        // Check that the user is permitted to view this object
        $this->_acl->requirePrivilegeForObject('read', $object);

        $this->view->object = $object;

        $nextDueDate = new Zend_Date($object->nextDueDate, Fisma_Date::FORMAT_DATE);
        if (is_null($object->nextDueDate)) {
            $workflowOnTimeState = 'N/A';
        } else {
            $workflowCompare = $nextDueDate->compareDate(new Zend_Date());
            $workflowOnTimeState = (($workflowCompare >= 0)
                ? (($workflowCompare > 0)
                    ? ('On Time' . ', ' .
                        ceil(abs(($nextDueDate->getTimestamp() - time("now"))/(60*60*24))) .
                        ' day(s) remaining.')
                    : 'Due Today'
                )
                : (
                    'Overdue by ' .
                    floor(abs(($nextDueDate->getTimestamp() - time("now"))/(60*60*24))) .
                    ' day(s).'
                )
            );
        }
        $this->view->workflowOnTimeState = $workflowOnTimeState;
        $this->view->model = $model;
    }

    /**
     * Complete a workflow step
     */
    public function completeStepAction()
    {
        $id = $this->getRequest()->getParam('id');
        $model = $this->_request->getParam('model');
        $comment = $this->getRequest()->getParam('comment');
        $expirationDate = $this->getRequest()->getParam('expirationDate');
        $transitionName = $this->getRequest()->getParam('transitionName');
        $object = Doctrine::getTable(ucfirst($model))->find($id);

        if ($object) {
            try {
                WorkflowStep::completeOnObject(
                    $object,
                    $transitionName,
                    $comment,
                    CurrentUser::getAttribute('id'),
                    $expirationDate
                );
                $this->_redirect('/workflow/workflow/format/html/model/' . $model . '/id/' . $id);
            } catch (Fisma_Zend_Exception_User $e) {
                $this->view->err = $e->getMessage();
                if ($nextStep = $object->CurrentStep->getNextStep($transitionName)) {
                    $this->view->nextStepName = $nextStep->name;
                }
            }
        } else {
            $this->view->err = 'Invalid ID provided (' . $id . ').';
        }
    }
}
