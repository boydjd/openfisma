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
 * Base controller to handle CRUD 
 *
 * @author     Jim Chen <xhorse@users.sourceforge.net>
 * @copyright  (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Controller
 * @version    $Id$
 */
abstract class Fisma_Zend_Controller_Action_Object extends Fisma_Zend_Controller_Action_Security
{
    /**
     * Default pagination parameters
     * 
     * @var array
     */
    protected $_paging = array(
        'startIndex' => 0,
        'count' => 10
    );
    
    /**
     * The main name of the model.
     * 
     * This model is the main subject which the controller operates on.
     * 
     * @var string
     */
    protected $_modelName = null;

    /**
     * The name of the module the controller is in. 
     * 
     * @var string
     * @access protected
     */
    protected $_moduleName = '';

    /**
     * _controllerName 
     * 
     * @var string
     * @access protected
     */
    protected $_controllerName = '';

    /**
     * The name of the class which this class's ACL is based off of. 
     * 
     * For example, system document objects don't have their own ACL items, instead they are based on the privileges 
     * the user has to the parent system objects which own those documents.
     * 
     * If null, then the ACL resource is based on the _modelName
     * 
     * @var string
     * @see getAclResourceName
     */
    protected $_aclResource;

    /**
     * Subclasses should override this if they want to use different buttons
     * 
     * Default buttons are (subject to ACL):
     * 
     * 1) <model name> List
     * 2) Create New <List>
     * 
     * @return array Array of Fisma_Yui_Form_Button
     */
    public function getToolbarButtons()
    {
        $buttons = array();

        //
        $buttons[] = new Fisma_Yui_Form_Button_Link(
            'toolbarListButton', 
            array(
                'value' => 'List All ' . $this->getPluralModelName(), 
                'href' => $this->getBaseUrl() . '/list'
            )
        );

        if ($this->_acl->hasPrivilegeForClass('create', 'Source')) {
            $buttons[] = new Fisma_Yui_Form_Button_Link(
                'toolbarCreateButton', 
                array(
                    'value' => 'Create New ' . $this->_modelName, 
                    'href' => $this->getBaseUrl() . '/create'
                )
            );
        }
         
        return $buttons;
    }

    /**
     * Return a plural form of the model name. 
     * 
     * This is used for UI purposes. Subclasses should override for model names 
     * which do not pluralize by adding an 's' to the end.
     */
    public function getPluralModelName()
    {
        return $this->_modelName . 's';
    }

    /**
     *  Initialize model and make sure the model has been properly set
     * 
     * @return void
     * @throws Fisma_Zend_Exception if model name is null
     */
    public function init()
    {
        parent::init();

        $this->_moduleName = $this->getModuleNameForLink();
        $this->_controllerName = $this->getRequest()->getControllerName(); 
        if (is_null($this->_modelName)) {
            //Actually user should not be able to see this error message
            throw new Fisma_Zend_Exception('Internal error. Subclasses of the BaseController'
                                    . ' must specify the _modelName field');
        }
    }
    
    /**
     * Invoked before each Actions
     * 
     * @return void
     */
    public function preDispatch()
    {
        /* Setting the first index of the page/table */
        $this->_paging['startIndex'] = $this->_request->getParam('startIndex', 0);
        parent::preDispatch();
    }

    /**
     * Get the specified form of the subject model
     * 
     * @param string|null $formName The name of the specified form
     * @return Zend_Form The specified form of the subject model
     */
    public function getForm($formName=null)
    {
        static $form = null;
        if (is_null($form)) {
            if (is_null($formName)) {
                $formName = strtolower((string) $this->_modelName);
            }
            $form = Fisma_Zend_Form_Manager::loadForm($formName);
            $form = Fisma_Zend_Form_Manager::prepareForm(
                $form, 
                array(
                    'formName' => ucfirst($formName), 
                    'view' => $this->view, 
                    'request' => $this->_request, 
                    'acl' => $this->_acl, 
                    'user' => $this->_me
                )
            );
        }
        return $form;
    }

    /**
     * Hooks for manipulating the values before setting to a form
     *
     * @param Doctrine_Record $subject The specified subject model
     * @param Zend_Form $form The specified form
     * @return Zend_Form The manipulated form
     */
    protected function setForm($subject, $form)
    {
        $form->setDefaults($subject->toArray());
        return $form;
    }

    /**
     * Hooks for manipulating and saving the values retrieved by Forms
     *
     * @param Zend_Form $form The specified form
     * @param Doctrine_Record|null $subject The specified subject model
     * @return integer ID of the object saved. 
     * @throws Fisma_Zend_Exception if the subject is not instance of Doctrine_Record
     */
    protected function saveValue($form, $subject=null)
    {
        if (is_null($subject)) {
            $subject = new $this->_modelName();
        } elseif (!$subject instanceof Doctrine_Record) {
            throw new Fisma_Zend_Exception('Expected a Doctrine_Record object');
        }
        $subject->merge($form->getValues());
        $subject->save();

        return $subject->id;
    }

    /**
     * View detail information of the subject model
     * 
     * @return void
     * @throws Fisma_Zend_Exception if the model id is invalid
     */
    public function viewAction()
    {
        $id     = $this->_request->getParam('id');
        $subject = Doctrine::getTable($this->_modelName)->find($id);
        if (!$subject) {
            throw new Fisma_Zend_Exception("Invalid {$this->_modelName} ID");
        }
        $this->_acl->requirePrivilegeForObject('read', $subject);

        $form   = $this->getForm();

        $this->view->assign('editLink', "{$this->_moduleName}/{$this->_controllerName}/edit/id/$id");
        $form->setReadOnly(true);            
        $this->view->assign('deleteLink', "{$this->_moduleName}/{$this->_controllerName}/delete/id/$id");
        $this->setForm($subject, $form);
        $this->view->form = $form;
        $this->view->id   = $id;
        $this->view->subject = $subject;

        $this->view->searchForm = $this->getSearchForm();
        $this->view->modelName = $this->_modelName;
        $this->view->toolbarButtons = $this->getToolbarButtons();

        // Setup ACL view variables
        $this->view->canEditObject = $this->_acl->hasPrivilegeForObject('update', $subject);
        $this->view->canDeleteObject = $this->_acl->hasPrivilegeForObject('delete', $subject);

        $this->renderScript('object/view.phtml');
    }

    /**
     * Create a subject model/record
     * 
     * @return void
     */
    public function createAction()
    {
        $this->_acl->requirePrivilegeForClass('create', $this->getAclResourceName());
        
        // Get the subject form
        $form   = $this->getForm();
        $form->setAction("{$this->_moduleName}/{$this->_controllerName}/create");
        if ($this->_request->isPost()) {
            $post = $this->_request->getPost();
            if ($form->isValid($post)) {
                try {
                    Doctrine_Manager::connection()->beginTransaction();
                    $objectId = $this->saveValue($form);
                    Doctrine_Manager::connection()->commit();
                    $this->_redirect("{$this->_moduleName}/{$this->_controllerName}/view/id/$objectId");
                } catch (Doctrine_Validator_Exception $e) {
                    Doctrine_Manager::connection()->rollback();
                    $msg   = $e->getMessage();
                    $model = 'warning';
                    $this->view->priorityMessenger($msg, $model);
                }
            } else {
                $errorString = Fisma_Zend_Form_Manager::getErrors($form);
                $this->view->priorityMessenger("Unable to create the {$this->_modelName}:<br>$errorString", 'warning');
            }
        }

        $this->view->form = $form;

        $this->view->searchForm = $this->getSearchForm();
        $this->view->modelName = $this->_modelName;
        $this->view->toolbarButtons = $this->getToolbarButtons();

        $this->renderScript('object/create.phtml');
    }

    /**
     * Edit a subject model
     * 
     * @return void
     * @throws Fisma_Zend_Exception if the model id is invalid
     */
    public function editAction()
    {
        $id     = $this->_request->getParam('id');
        $subject = Doctrine::getTable($this->_modelName)->find($id);
        if (!$subject) {
            throw new Fisma_Zend_Exception("Invalid {$this->_modelName} ID");
        }
        $this->_acl->requirePrivilegeForObject('update', $subject);
        $this->view->subject = $subject;
        $form   = $this->getForm();

        $this->view->assign('viewLink', "{$this->_moduleName}/{$this->_controllerName}/view/id/$id");
        $form->setAction("{$this->_moduleName}/{$this->_controllerName}/edit/id/$id");
        $this->view->assign('deleteLink', "{$this->_moduleName}/{$this->_controllerName}/delete/id/$id");
        // Update the model
        if ($this->_request->isPost()) {
            $post = $this->_request->getPost();
            if ($form->isValid($post)) {
                try {
                    $result = $this->saveValue($form, $subject);
                    $msg   = "{$this->_modelName} updated successfully";
                    $type = 'notice';

                    // Refresh the form, in case the changes to the model affect the form
                    $form   = $this->getForm();
                    $this->_redirect("{$this->_moduleName}/{$this->_controllerName}/view/id/$id");
                } catch (Doctrine_Exception $e) {
                    //Doctrine_Manager::connection()->rollback();
                    $msg  = "Error while trying to save: ";
                        $msg .= $e->getMessage();
                    $type = 'warning';
                }
                $this->view->priorityMessenger($msg, $type);
            } else {
                $errorString = Fisma_Zend_Form_Manager::getErrors($form);
                $error = "Error while trying to save: {$this->_modelName}: <br>$errorString";
                $this->view->priorityMessenger($error, 'warning');
            }
        }

        $this->view->searchForm = $this->getSearchForm();
        $this->view->modelName = $this->_modelName;
        $this->view->toolbarButtons = $this->getToolbarButtons();

        $form = $this->setForm($subject, $form);
        $this->view->form = $form;
        $this->view->id   = $id;
        
        $this->renderScript('object/edit.phtml');
    }

    /**
     * Delete a subject model
     * 
     * @return void
     */
    public function deleteAction()
    {
        $id = $this->_request->getParam('id');
        $subject = Doctrine::getTable($this->_modelName)->find($id);
        $this->_acl->requirePrivilegeForObject('delete', $subject);

        if (!$subject) {
            $msg   = "Invalid {$this->_modelName} ID";
            $type = 'warning';
        } else {
            try {
                Doctrine_Manager::connection()->beginTransaction();
                $subject->delete();
                Doctrine_Manager::connection()->commit();
                $msg   = "{$this->_modelName} deleted successfully";
                $type = 'notice';
            } catch (Fisma_Zend_Exception_User $e) {
                $msg  = $e->getMessage();
                $type = 'warning';
            } catch (Doctrine_Exception $e) {
                Doctrine_Manager::connection()->rollback();
                if (Fisma::debug()) {
                    $msg .= $e->getMessage();
                }
                $type = 'warning';
            }
        }
        $this->view->priorityMessenger($msg, $type);
        $this->_redirect("{$this->_moduleName}/{$this->_controllerName}/list");
    }

    /**
     * List the subjects
     * 
     * @return void
     */
    public function listAction()
    {
        $this->_acl->requirePrivilegeForClass('read', $this->getAclResourceName());

        $keywords = trim($this->_request->getParam('keywords'));

        // Create the YUI table that will display results
        $searchResultsTable = new Fisma_Yui_DataTable_Remote();
        
        $searchResultsTable->setResultVariable('records') // Matches searchAction()
                           ->setDataUrl($this->getBaseUrl() . '/search')
                           ->setInitialSortColumn('name')
                           ->setSortAscending(true)
                           ->setRowCount($this->_paging['count'])
                           ->setClickEventBaseUrl($this->getBaseUrl() . '/view/id/')
                           ->setClickEventVariableName('id');

        // Look up searchable columns and add them to the table
        $table = Doctrine::getTable($this->_modelName);
        $searchableFields = $table->getSearchableFields();
        $searchEngine = Fisma_Search_BackendFactory::getSearchBackend();
        
        foreach ($searchableFields as $fieldName => $searchParams) {

            $displayName = $searchParams['displayName'];
            $sortable = $searchEngine->isColumnSortable($table->getColumnDefinition($table->getColumnName($fieldName)));

            $column = new Fisma_Yui_DataTable_Column($displayName, $sortable, null, $fieldName);

            $searchResultsTable->addColumn($column);
        }

        $this->view->toolbarButtons = $this->getToolbarButtons();
        $this->view->pluralModelName = $this->getPluralModelName();
        $this->view->searchForm = $this->getSearchForm();
        $this->view->searchResultsTable = $searchResultsTable;

        $advancedSearchOptions = array(
            array('name' => 'name', 'label' => 'Name', 'type' => 'text'),
            array('name' => 'nickname', 'label' => 'Nickname', 'type' => 'text'),
            array('name' => 'description', 'label' => 'Description', 'type' => 'text')
        );

        $this->view->advancedSearchOptions = json_encode($advancedSearchOptions);

        $this->renderScript('object/list.phtml');
    }

    /** 
     * Search the subject
     * 
     * @return string The encoded table data in json format
     */
    public function searchAction()
    {
        $this->_acl->requirePrivilegeForClass('read', $this->getAclResourceName());
        
        //initialize the data rows
        $searchResults = array(
            'startIndex'      => $this->_paging['startIndex'],
            'sort'            => $sortBy,
            'dir'             => $order,
            'pageSize'        => $this->_paging['count']
        );

        // Setup search parameters
        $sortColumn = $this->getRequest()->getParam('sort');
        
        if (empty($sortColumn)) {
            // Pick the first searchable column as the default sort column
            $searchableFields = array_keys(Doctrine::getTable($this->_modelName)->getSearchableFields());
            
            $sortColumn = $searchableFields[0];
        }
        
        $sortDirection = $this->getRequest()->getParam('dir', 'asc');
        $sortBoolean = ('asc' == $sortDirection);
        $start = $this->getRequest()->getParam('start', $this->_paging['startIndex']);
        $rows = $this->getRequest()->getParam('count', $this->_paging['count']);

        // Execute simple search (default) or advanced search (if explicitly requested)
        $searchEngine = Fisma_Search_BackendFactory::getSearchBackend();

        $queryType = $this->getRequest()->getParam('queryType');

        if ('advanced' == $queryType) {
            
            // Extract search criteria from URL query string
            $searchCriteria = new Fisma_Search_Criteria;
            
            $urlParams = $this->getRequest()->getParams();
            
            foreach ($urlParams as $parameterName => $operand) {

                // Only interested in parameters that have a dot in the name -- these indicate search criteria
                if (false === strpos($parameterName, '.')) {
                    continue;
                }
                
                // Use the dot to split the parameter name into its 2 parts: field name and operator
                $parts = explode('.', $parameterName);
                
                if (2 != count($parts)) {
                    throw new Fisma_Zend_Exception("Invalid search criteria: " . $parameterName);
                }

                $fieldName = $parts[0];
                $operator = $parts[1];
                
                $searchCriteria->add($fieldName, $operator, $operand);
            }

            // Run advanced search
            $result = $searchEngine->searchByCriteria(
                $this->_modelName, 
                $searchCriteria,
                $sortColumn,
                $sortBoolean,
                $start,
                $rows
            );
        } else {
            $keywords = $this->getRequest()->getParam('keywords');

            $searchEngine = Fisma_Search_BackendFactory::getSearchBackend();

            // Run simple search
            $result = $searchEngine->searchByKeyword(
                $this->_modelName, 
                $keywords,
                $sortColumn,
                $sortBoolean,
                $start,
                $rows
            );       
        }

        $searchResults['recordsReturned'] = $result->getNumberReturned();
        $searchResults['totalRecords'] = $result->getNumberFound();
        $searchResults['records'] = $result->getTableData();

        return $this->_helper->json($searchResults);
    }

    /**
     * Returns the ACL class name for this controller.
     * 
     * This is based on the _modelName and _aclResource variables defined by child classes.
     * 
     * @return string
     */
    public function getAclResourceName()
    {
        return is_null($this->_aclResource) ? $this->_modelName : $this->_aclResource;
    }

    /**
     * getModuleNameForLink 
     * 
     * @access public
     * @return string 
     */
    public function getModuleNameForLink()
    {
        if ($this->getRequest()->getModuleName() != 'default') {
            return '/' . $this->getRequest()->getModuleName();
        } else {
            return '';
        }
    }
    
    /**
     * Get a base URL that points to this module and controller
     * 
     * return string
     */
    public function getBaseUrl()
    {
        $module = $this->getModuleNameForLink();
        
        $controller = $this->getRequest()->getControllerName();
        
        return $module . '/' . $controller;
    }
    
    /**
     * Get the search form and decorate it
     * 
     * @return Zend_Form
     */
    public function getSearchForm()
    {
        $searchForm = Fisma_Zend_Form_Manager::loadForm('search');
        
        $searchForm->setDecorators(
            array(
                'FormElements',
                array('HtmlTag', array('tag' => 'span')),
                'Form'
            )
        );
        
        $searchForm->setElementDecorators(array('ViewHelper', 'RenderSelf'));
        
        return $searchForm;
    }
}
