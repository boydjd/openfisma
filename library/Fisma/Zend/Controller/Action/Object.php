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
 */
abstract class Fisma_Zend_Controller_Action_Object extends Fisma_Zend_Controller_Action_Security
{
    /**
     * The maximum number of records this controller will export during its search action when the format is PDF
     * or XLS
     * 
     * @var int
     */
    const MAX_EXPORT_RECORDS = 1000;

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
     * If true, then ACL checks are enforced in the controller. If false, ACL checks are skipped
     */
    protected $_enforceAcl = true;

    /**
     * Subclasses should override this if they want to use different buttons
     *
     * Default buttons are (subject to ACL):
     *
     * 1) List All <model name>s
     * 2) Create New <model name>
     *
     * @return array Array of Fisma_Yui_Form_Button
     */
    public function getToolbarButtons()
    {
        $buttons = array();
        $isList = $this->getRequest()->getActionName() === 'list';
        $resourceName = $this->getAclResourceName();

        if (!$isList && (!$this->_enforceAcl || $this->_acl->hasPrivilegeForClass('read', $resourceName))) {
            $buttons['list'] = new Fisma_Yui_Form_Button_Link(
                'toolbarListButton',
                array(
                    'value' => 'Return to Search Results',
                    'href' => $this->getBaseUrl() . '/list'
                )
            );
        }

        if (!$this->_enforceAcl || $this->_acl->hasPrivilegeForClass('create', $this->getAclResourceName())) {
            $buttons['create'] = new Fisma_Yui_Form_Button_Link(
                'toolbarCreateButton',
                array(
                    'value' => 'Create New ' . $this->getSingularModelName(),
                    'href' => $this->getBaseUrl() . '/create'
                )
            );
        }

        return $buttons;
    }

    /**
     * Return a human-readable, singular form of the model name.
     *
     * In many cases the physical model name is also a suitable human-readable model name, but in other cases
     * subclasses can override this method.
     */
    public function getSingularModelName()
    {
        return $this->_modelName;
    }

    /**
     * Return a human-readable, plural form of the model name.
     *
     * This is used for UI purposes. Subclasses should override for model names
     * which do not pluralize by adding an 's' to the end.
     */
    public function getPluralModelName()
    {
        return $this->getSingularModelName() . 's';
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
            throw new Fisma_Zend_Exception('Subclasses of the BaseController must specify the _modelName field');
        }

        $this->_helper->reportContextSwitch()
                      ->addActionContext('search', array('pdf', 'xls'))
                      ->initContext();
    }

    /**
     * Get the specified form of the subject model
     *
     * @param string|null $formName The name of the specified form
     * @return Zend_Form The specified form of the subject model
     */
    public function getForm($formName = null)
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
    protected function saveValue($form, $subject = null)
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
     * Display details for a single record.
     *
     * All of the default logic for viewing a record is performed in _viewObject, so that child classes can use the
     * default logic but still render their own views.
     *
     * @return void
     */
    public function viewAction()
    {
        $this->_viewObject();
        $this->renderScript('object/view.phtml');
    }

    /**
     * A protected method which holds all of the logic for the view action, but does not actually render a view
     */
    protected function _viewObject()
    {
        $id = $this->_request->getParam('id');
        $subject = $this->_getSubject($id);

        if ($this->_enforceAcl) {
            $this->_acl->requirePrivilegeForObject('read', $subject);
        }

        // Load the object's form
        $form = $this->getForm();
        $form->setReadOnly(true);
        $this->setForm($subject, $form);
        $this->view->form = $form;

        $this->view->id   = $id;
        $this->view->subject = $subject;
        $this->view->links = $this->getViewLinks($subject);

        $this->view->modelName = $this->getSingularModelName();
        $this->view->toolbarButtons = $this->getToolbarButtons();
    }

    /**
     * Display a create page for a single record. 
     *
     * All of the default logic for creating a record is performed in _createObject, so that child classes can use the
     * default logic but still render their own views.
     *
     * @return void
     */
    public function createAction()
    {
        $this->_createObject();

        $this->renderScript('object/create.phtml');
    }

    /**
     * A protected method which holds all of the logic for the create page but does not actually render a view
     *
     * @return void
     */
    public function _createObject()
    {
        if ($this->_enforceAcl) {
            $this->_acl->requirePrivilegeForClass('create', $this->getAclResourceName());
        }

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
                    $msg   = "{$this->_modelName} created successfully";
                    $type = 'notice';
                    $this->view->priorityMessenger($msg, $type);
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

        $this->view->modelName = $this->getSingularModelName();
        $this->view->toolbarButtons = $this->getToolbarButtons();
    }
    /**
     * Display an edit page for a single record.
     *
     * All of the default logic for editing a record is performed in _editObject, so that child classes can use the
     * default logic but still render their own views.
     *
     * @return void
     */
    public function editAction()
    {
        $this->_editObject();

        $this->renderScript('object/edit.phtml');
    }

    /**
     * A protected method which holds all of the logic for the edit page but does not actually render a view
     */
    protected function _editObject()
    {
        $id     = $this->_request->getParam('id');
        $subject = $this->_getSubject($id);

        if ($this->_enforceAcl) {
            $this->_acl->requirePrivilegeForObject('update', $subject);
        }

        $this->view->subject = $subject;

        $form   = $this->getForm();
        $form->setAction("{$this->_moduleName}/{$this->_controllerName}/edit/id/$id");

        $this->view->links = $this->getEditLinks($subject);

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
                    $this->view->priorityMessenger($msg, $type);
                    $this->_redirect("{$this->_moduleName}/{$this->_controllerName}/view/id/$id");
                } catch (Doctrine_Exception $e) {
                    //Doctrine_Manager::connection()->rollback();
                    $msg  = "Error while trying to save: ";
                        $msg .= $e->getMessage();
                    $type = 'warning';
                } catch (Fisma_Zend_Exception_User $e) {
                    $msg  = "Error while trying to save: " . $e->getMessage();
                    $type = 'warning';
                }
                $this->view->priorityMessenger($msg, $type);
            } else {
                $errorString = Fisma_Zend_Form_Manager::getErrors($form);
                $error = "Error while trying to save: {$this->_modelName}: <br>$errorString";
                $this->view->priorityMessenger($error, 'warning');
            }
        }

        $this->view->modelName = $this->getSingularModelName();
        $this->view->toolbarButtons = $this->getToolbarButtons();

        $form = $this->setForm($subject, $form);
        $this->view->form = $form;
        $this->view->id   = $id;
    }

    /**
     * Delete a subject model
     *
     * @return void
     */
    public function deleteAction()
    {
        $id = $this->_request->getParam('id');
        $subject = $this->_getSubject($id);

        if (!$this->_isDeletable()) {
            throw new Fisma_Zend_Exception("This model is marked as not deletable");
        }

        if ($this->_enforceAcl) {
            $this->_acl->requirePrivilegeForObject('delete', $subject);
        }

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
     * Delete multiple records 
     * 
     * @access public
     * @return string JSON string 
     */
    public function multiDeleteAction()
    {
        $this->_acl->requirePrivilegeForClass('delete', $this->getAclResourceName());
        $recordIds = Zend_Json::decode($this->_request->getParam('records'));

        if (!$this->_isDeletable()) {
            throw new Fisma_Zend_Exception("This model is marked as not deletable");
        }

        if (empty($recordIds)) {
            return $this->_helper->json(array('msg' => 'An error has occured.', 'status' => 'warning'));
        }

        $records = Doctrine_Query::create()
                   ->from("$this->_modelName a")
                   ->whereIn('a.id', $recordIds)
                   ->execute();

        $numRecords = $records->count();

        try {
            Doctrine_Manager::connection()->beginTransaction();

            foreach ($records as $record) {
                $this->_acl->requirePrivilegeForObject('delete', $record);

                $record->delete();
                $record->free();

                unset($record);
            }

            Doctrine_Manager::connection()->commit();

            if (1 == $numRecords) {
                $noun = $this->getSingularModelName();
                $verb = "was";
            } else {
                $noun = $this->getPluralModelName();
                $verb = "were";
            }
            
            $message = "$numRecords $noun $verb deleted.";
            $status = 'notice';
            
        } catch (Doctrine_Exception $e) {
            
            Doctrine_Manager::connection()->rollback();
            
            if (Fisma::debug()) {
                $message .= $e->getMessage();
            } else {
                $message .= 'An error has occured while deleting selected record(s)';
            }
            $status = 'warning';
            
            $logger = $this->getInvokeArg('bootstrap')->getResource('Log');
            $logger->log($e->getMessage() . "\n" . $e->getTraceAsString(), Zend_Log::ERR);
            
        } catch (Exception $e) {
            Doctrine_Manager::connection()->rollBack();
            $message = $e->getMessage();
            $status = 'warning';
        }

        return $this->_helper->json(array('msg' => $message, 'status' => $status));
    }
    
    /**
     * List the subjects
     *
     * @return void
     */
    public function listAction()
    {
        if ($this->_enforceAcl) {
            $this->_acl->requirePrivilegeForClass('read', $this->getAclResourceName());
        }

        $keywords = trim($this->_request->getParam('keywords'));

        $table = Doctrine::getTable($this->_modelName);
        
        if (!($table instanceof Fisma_Search_Searchable)) {
            throw new Fisma_Zend_Exception(get_class($table) . ' does not implement Fisma_Search_Searchable.');
        }
        
        $searchableFields = $table->getSearchableFields();

        // Create the YUI table that will display results
        $searchResultsTable = new Fisma_Yui_DataTable_Remote();

        $searchResultsTable->setResultVariable('records') // Matches searchAction()
                           ->setDataUrl($this->getBaseUrl() . '/search')
                           ->setSortAscending(true)
                           ->setRenderEventFunction('Fisma.Search.highlightSearchResultsTable')
                           ->setRequestConstructor('Fisma.Search.generateRequest')
                           ->setRowCount($this->_paging['count'])
                           ->setClickEventBaseUrl($this->getBaseUrl() . '/view/id/')
                           ->setClickEventVariableName('id');

        // The initial sort column is the first column (by convention)
        $firstColumn = each($searchableFields);
        $searchResultsTable->setInitialSortColumn($firstColumn['key']);
        reset($searchableFields);
        
        // If user can delete objects, then add a checkbox column
        if ($this->_isDeletable() && $this->_acl->hasPrivilegeForClass('delete', $this->getAclResourceName())) {
            $column = new Fisma_Yui_DataTable_Column('<input id="dt-checkbox" type="checkbox">',
                                                     false,
                                                     "Fisma.TableFormat.formatCheckbox",
                                                     null,
                                                     'deleteCheckbox',
                                                     false);

            $searchResultsTable->addColumn($column);
        }

        $visibleColumns = $this->_getColumnVisibility();

        // Look up searchable columns and add them to the table
        $searchEngine = Zend_Registry::get('search_engine');

        foreach ($searchableFields as $fieldName => $searchParams) {

            if (isset($searchParams['hidden']) && $searchParams['hidden'] === true) {
                continue;
            }

            $label = $searchParams['label'];
            $sortable = $searchEngine->isColumnSortable($this->_modelName, $fieldName);

            if (isset($visibleColumns[$fieldName])) {
                $visible = (bool)$visibleColumns[$fieldName];
            } else {
                $visible = $searchParams['initiallyVisible'];
            }

            $column = new Fisma_Yui_DataTable_Column($label,
                                                     $sortable,
                                                     "YAHOO.widget.DataTable.formatText",
                                                     null,
                                                     $fieldName,
                                                     !$visible);

            $searchResultsTable->addColumn($column);
        }

        $searchForm = $this->getSearchForm();
        $searchForm->getElement('modelName')->setValue($this->_modelName);
        $this->view->searchForm = $searchForm;

        $searchMoreOptionsForm = $this->getSearchMoreOptionsForm();
        $this->view->searchMoreOptionsForm = $searchMoreOptionsForm;

        // If there is an advanced parameter, switch the form default from simple to advanced.
        if ('advanced' == $this->getRequest()->getParam('queryType')) {
            $searchForm->getElement('searchType')->setValue('advanced');
            $searchForm->getElement('keywords')->setAttrib('style', 'visibility: hidden;');

            $searchMoreOptionsForm->getElement('advanced')->setAttrib('checked', 'true');

            $searchResultsTable->setDeferData(true);
        }

        $this->view->toolbarButtons = $this->getToolbarButtons();
        $this->view->pluralModelName = $this->getPluralModelName();
        $this->view->searchResultsTable = $searchResultsTable;
        $this->view->assign($searchResultsTable->getProperties());

        // Advanced search options is indexed by name, but for the client side it should be numerically indexed with
        // the name as an array element instead
        $advancedSearchOptions = array();

        foreach ($searchableFields as $fieldName => $fieldDefinition) {
            $advancedSearchOptions[] = array_merge(array('name' => $fieldName), $fieldDefinition);
        }

        $this->view->advancedSearchOptions = json_encode($advancedSearchOptions);
        $this->view->searchPreferences = $this->_getSearchPreferences();

        $this->renderScript('object/list.phtml');
    }

    /**
     * Get table column visibility information
     *
     * @return array Key is field name and value is boolean.
     */
    protected function _getColumnVisibility()
    {
        $userId = CurrentUser::getInstance()->id;
        $namespace = 'Fisma.Search.TablePreferences';
        $storage = Doctrine::getTable('Storage')->getUserIdAndNamespaceQuery($userId, $namespace)->fetchOne();
        $visibleColumns = (!empty($storage) && !empty($storage->data)) ? $storage->data : array();
        $visibleColumns = (!empty($visibleColumns[$this->_modelName])) ? $visibleColumns[$this->_modelName] : array();
        $visibleColumns = (!empty($visibleColumns['columnVisibility'])) ? $visibleColumns['columnVisibility'] : array();
        return $visibleColumns;
    }

    /**
     * Get the search preferences
     *
     * @return array
     */
    protected function _getSearchPreferences()
    {
        $userId = CurrentUser::getInstance()->id;
        $namespace = 'Fisma.Search.QueryState';
        $storage = Doctrine::getTable('Storage')->getUserIdAndNamespaceQuery($userId, $namespace)->fetchOne();
        $result = array('type' => 'simple');
        if (!empty($storage)) {
            $data = $storage->data;
            $data = empty($data[$this->_modelName]) ? array() : $data[$this->_modelName];
            if (!empty($data['type']) && $data['type'] === 'advanced') {
                $result['type'] = 'advanced';
                $result['fields'] = empty($data['fields']) ? array() : $data['fields'];
            }
        }
        return $result;
    }
    /**
     * Apply a user query to the search engine and return the results in JSON format
     *
     * @return string The encoded table data in json format
     */
    public function searchAction()
    {
        if ($this->_enforceAcl) {
            $this->_acl->requirePrivilegeForClass('read', $this->getAclResourceName());
        }

        $format = $this->getRequest()->getParam('format');

        //initialize the data rows
        $searchResults = array(
            'startIndex'      => $this->_paging['startIndex'],
            'pageSize'        => $this->_paging['count']
        );

        // Setup search parameters
        $sortColumn = $this->getRequest()->getParam('sort');

        $searchableFields = Doctrine::getTable($this->_modelName)->getSearchableFields();

        if (empty($sortColumn)) {
            // Pick the first searchable column as the default sort column
            $fieldNames = array_keys($searchableFields);

            $sortColumn = $fieldNames[0];
        }

        $sortDirection = $this->getRequest()->getParam('dir', 'asc');
        $sortBoolean = ('asc' == $sortDirection);
        $showDeletedRecords = ('true' == $this->getRequest()->getParam('showDeleted'));
        
        if (empty($format)) {
            // For HTML UI, add a limit/offset to query
            $start = $this->getRequest()->getParam('start', $this->_paging['startIndex']);
            $rows = $this->getRequest()->getParam('count', $this->_paging['count']);
        } else {
            // For PDF/XLS export, $rows is an arbitrarily high number (that won't DoS the system)
            $start = 0;
            $rows = self::MAX_EXPORT_RECORDS;
        }

        // Execute simple search (default) or advanced search (if explicitly requested)
        $searchEngine = Zend_Registry::get('search_engine');
        
        // For exports, disable highlighting and result length truncation
        if (!empty($format)) {
            $searchEngine->setHighlightingEnabled(false);
            $searchEngine->setMaxRowLength(null);
        }

        $queryType = $this->getRequest()->getParam('queryType');

        if ('advanced' == $queryType) {

            // Extract search criteria from URL query string
            $searchCriteria = new Fisma_Search_Criteria;

            $queryJson = $this->getRequest()->getParam('query');
            $query = Zend_Json::decode($queryJson);

            foreach ($query as $queryItem) {
                $searchCriterion = new Fisma_Search_Criterion(
                    $queryItem['field'],
                    $queryItem['operator'],
                    $queryItem['operands']
                );

                $searchCriteria->add($searchCriterion);
            }

            // Run advanced search
            $result = $searchEngine->searchByCriteria(
                $this->_modelName,
                $searchCriteria,
                $sortColumn,
                $sortBoolean,
                $start,
                $rows,
                $showDeletedRecords
            );
        } else {
            $keywords = $this->getRequest()->getParam('keywords');

            // Run simple search
            $result = $searchEngine->searchByKeyword(
                $this->_modelName,
                $keywords,
                $sortColumn,
                $sortBoolean,
                $start,
                $rows,
                $showDeletedRecords
            );
        }

        // store query options
        $queryOptions = $this->getRequest()->getParam('queryOptions');
        if (!empty($queryOptions)) {
            $userId = $this->_me->id;
            $namespace = 'Fisma.Search.QueryState';
            $storage = Doctrine::getTable('Storage')->getUserIdAndNamespaceQuery($userId, $namespace)->fetchOne();
            if (empty($storage)) {
                $storage = new Storage();
                $storage->userId = $userId;
                $storage->namespace = $namespace;
                $storage->data = array();
            }
            $data = $storage->data;
            $queryOptions = Zend_Json::decode($queryOptions);
            $data[$this->_modelName] = $queryOptions;
            $storage->data = $data;
            $storage->save();
        }
        
        // Create the appropriate output for the requested format
        if (empty($format)) {
            $searchResults['recordsReturned'] = $result->getNumberReturned();
            $searchResults['totalRecords'] = $result->getNumberFound();
            $searchResults['records'] = $result->getTableData();

            return $this->_helper->json($searchResults);
        } else {
            $report = new Fisma_Report;
            
            $visibleColumns = $this->_getColumnVisibility();

            // Columns are returned in wrong order and need to be re-arranged
            $rawSearchData = $result->getTableData();
            $reformattedSearchData = array();
            
            // Create a place holder for each table row. This gets filled in during the following loop.
            foreach ($rawSearchData as $rawDatum) {
                $reformattedSearchData[] = array();
            }

            foreach ($searchableFields as $fieldName => $searchableField) {
                
                // Visibility is determined by stored cookie, with fallback to search field definition
                if (isset($visibleColumns[$fieldName])) {
                    $visible = (bool)$visibleColumns[$fieldName];
                } else {
                    $visible = isset($searchableField['initiallyVisible']) && $searchableField['initiallyVisible'];
                }

                // For visible columns, display the column in the report and add data from the 
                // raw result for that column
                if ($visible) {
                    $report->addColumn(
                        new Fisma_Report_Column($searchableField['label']), $searchableField['sortable']
                    );

                    foreach ($rawSearchData as $index => $datum) {
                        if (isset($rawSearchData[$index][$fieldName])) {
                            $reformattedSearchData[$index][$fieldName] = $rawSearchData[$index][$fieldName];
                        } else {
                            $reformattedSearchData[$index][$fieldName] = '';
                        }
                    }
                }
            }

            $report->setTitle('Search Results for ' . $this->getPluralModelName())
                   ->setData($reformattedSearchData);

            $this->_helper->reportContextSwitch()->setReport($report);
        }
    }

    /**
     * Overridable method to execute search query
     *
     * @param Doctrine_Query $query The query to be executed
     * @return Doctrine_Collection Results of the query
     */
    public function executeSearchQuery(Doctrine_Query $query)
    {
        return $query->execute();
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

    /**
     * Get the "more search options" form and decorate it
     *
     * @return Zend_Form
     */
    public function getSearchMoreOptionsForm()
    {
        $searchForm = Fisma_Zend_Form_Manager::loadForm('search_more_options');

        // Remove the "Show Deleted" button if this model doesn't support soft-delete
        if (!Doctrine::getTable($this->_modelName)->hasColumn('deleted_at')) {
            $searchForm->removeElement('showDeleted');
        }
        
        // Remove the delete button if the user doesn't have the right to click it
        if (!$this->_isDeletable() || !$this->_acl->hasPrivilegeForClass('delete', $this->getAclResourceName())) {
            $searchForm->removeElement('deleteSelected');
        }

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

    /**
     * Return an array of links that are displayed on the object edit page
     *
     * The keys are link labels and the values are the URLs
     *
     * @param Fisma_Doctrine_Record $subject
     * @return array
     */
    public function getEditLinks(Fisma_Doctrine_Record $subject)
    {
        $links = array();

        if (!$this->_enforceAcl || $this->_acl->hasPrivilegeForObject('read', $subject)) {
            $links['View'] = "{$this->_moduleName}/{$this->_controllerName}/view/id/{$subject->id}";
        }

        if (!$this->_enforceAcl || ($this->_isDeletable() && $this->_acl->hasPrivilegeForObject('delete', $subject))) {
            $links['Delete'] = "{$this->_moduleName}/{$this->_controllerName}/delete/id/{$subject->id}";
        }

        return $links;
    }

    /**
     * Return an array of links that are displayed on the object view page
     *
     * The keys are link labels and the values are the URLs
     *
     * @param Fisma_Doctrine_Record $subject
     * @return array
     */
    public function getViewLinks(Fisma_Doctrine_Record $subject)
    {
        $links = array();

        if (!$this->_enforceAcl || $this->_acl->hasPrivilegeForObject('read', $subject)) {
            $links['Edit'] = "{$this->_moduleName}/{$this->_controllerName}/edit/id/{$subject->id}";
        }

        if (!$this->_enforceAcl || ($this->_isDeletable() && $this->_acl->hasPrivilegeForObject('delete', $subject))) {
            $links['Delete'] = "{$this->_moduleName}/{$this->_controllerName}/delete/id/{$subject->id}";
        }

        return $links;
    }
    
    /**
     * Returns true if the model is deletable, false otherwise.
     * 
     * The default implementation returns true because most models are deletable. Models which are not deletable should
     * override this method in their controller and return false.
     * 
     * @return bool
     */
    protected function _isDeletable()
    {
        return true;
    }

    /**
     * Check and get a specified record.
     *
     * @param int $id The specified record id
     * @return Doctrine_Record The found record
     * @throws Fisma_Zend_Exception_User If the specified record id is not found
     */
    protected function _getSubject($id)
    {
        $table = Doctrine::getTable($this->_modelName);
        $query = Doctrine_Query::create()->from($this->_modelName)->where('id = ?', $id);

        // If user has the delete privilege, then allow viewing of deleted record
        if ($table->hasColumn('deleted_at') && $this->_acl->hasPrivilegeForClass('delete', $this->_modelName)) {
            $query->andWhere('deleted_at = deleted_at OR deleted_at IS NULL');
        }

        $record = $query->fetchOne();

        if (false == $record) {
             $msg = '%s (%d) not found. Make sure a valid ID is specified.';
             throw new Fisma_Zend_Exception_User(sprintf($msg, $this->_modelName, $id));
        }
        
        return $record;
    }
}
