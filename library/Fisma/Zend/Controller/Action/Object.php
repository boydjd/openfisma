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
     * The name of the associated objects if this model is a metadata model.
     *
     * This should match the other controller's $_modelName;
     */
    protected $_associatedModel;

    /**
     * The plural form the of the associated model, only for displaying
     */
    protected $_associatedPlural;

    /**
     * The nickname column of the associated model to display in the select menu. Default to "nickname"
     */
    protected $_associatedNickname = 'nickname';

    /**
     * Subclasses should override this if they want to use different buttons
     *
     * Default buttons are (subject to ACL):
     *
     * 1) List All <model name>s
     * 2) Create New <model name>
     * 3) Migrate Associated <associated model name> (if defined, view-page only)
     *
     * @param Fisma_Doctrine_Record $record The object for which this toolbar applies, or null if not
     * @param array $fromSearchParams The array for "Previous" and "Next" button null if not
     * @return array Array of Fisma_Yui_Form_Button
     */
    public function getToolbarButtons(Fisma_Doctrine_Record $record = null, $fromSearchParams = null)
    {
        $buttons = array();
        $isList = $this->getRequest()->getActionName() === 'list';
        $isView = $this->getRequest()->getActionName() === 'view';
        $isCreate = $this->getRequest()->getActionName() === 'create';
        $view = Zend_Layout::getMvcInstance()->getView();

        if ((!$this->_enforceAcl || $this->_acl->hasPrivilegeForClass('create', $this->getAclResourceName()))) {
            if ($isCreate) {
                $buttons['submitButton'] = new Fisma_Yui_Form_Button(
                    'saveChanges',
                    array(
                        'label' => 'Save',
                        'onClickFunction' => 'Fisma.Util.submitFirstForm',
                        'imageSrc' => '/images/ok.png'
                    )
                );

                $buttons['discardButton'] = new Fisma_Yui_Form_Button_Link(
                    'discardChanges',
                    array(
                        'value' => 'Discard',
                        'imageSrc' => '/images/no_entry.png',
                        'href' => $this->getBaseUrl() . '/create'
                    )
                );
            } else {
                $buttons['create'] = new Fisma_Yui_Form_Button_Link(
                    'toolbarCreateButton',
                    array(
                        'value' => 'Create ' . $this->getSingularModelName(),
                        'href' => $this->getBaseUrl() . '/create',
                        'imageSrc' => '/images/create.png'
                    )
                );
            }
        }

        if (
            (!$this->_enforceAcl || $this->_acl->hasPrivilegeForClass('update', $this->getAclResourceName())) &&
            $isView && $id = $this->getRequest()->getParam('id')
        ) {
            $fromSearchUrl = '';
            $fromSearchParams = $this->_getFromSearchParams($this->getRequest());
            if (!empty($fromSearchParams)) {
                $fromSearchUrl = $this->_helper->makeUrlParams($fromSearchParams);
            }

            $buttons['submitButton'] = new Fisma_Yui_Form_Button(
                'saveChanges',
                array(
                    'label' => 'Save',
                    'onClickFunction' => 'Fisma.Util.submitFirstForm',
                    'imageSrc' => '/images/ok.png'
                )
            );

            $buttons['discardButton'] = new Fisma_Yui_Form_Button_Link(
                'discardChanges',
                array(
                    'value' => 'Discard',
                    'imageSrc' => '/images/no_entry.png',
                    'href' => $this->getBaseUrl() . '/view/id/' . $id . $fromSearchUrl
                )
            );

            if (!empty($this->_associatedModel)) {
                $buttons['reassociate'] = new Fisma_Yui_Form_Button(
                    'toolbarReassociateButton',
                    array(
                        'label' => 'Migrate Associated ' . $this->_associatedPlural,
                        'onClickFunction' => 'Fisma.Util.showReassociatePanel',
                        'onClickArgument' => array(
                            'title' => 'Migrate Associated ' . $this->_associatedPlural,
                            'url'   => $this->getBaseUrl() . '/reassociate/id/' . $id . $fromSearchUrl
                        ),
                        'imageSrc' => '/images/move.png'
                    )
                );
            }
        }

        if ($isList) {
            $buttons['exportXls'] = new Fisma_Yui_Form_Button(
                'toolbarExportXlsButton',
                array(
                    'label' => 'XLS',
                    'onClickFunction' => 'Fisma.Search.exportToFile',
                    'imageSrc' => $view->serverUrl('/images/xls.gif'),
                    'onClickArgument' => 'xls'
                )
            );

            $buttons['exportPdf'] = new Fisma_Yui_Form_Button(
                'toolbarExportPdfButton',
                array(
                    'label' => 'PDF',
                    'imageSrc' => $view->serverUrl('/images/pdf.gif'),
                    'onClickFunction' => 'Fisma.Search.exportToFile',
                    'onClickArgument' => 'pdf'
                )
            );

            $buttons['configureColumn'] = new Fisma_Yui_Form_Button(
                'toolbarConfigureColumnButton',
                array(
                    'label' => 'Columns',
                    'imageSrc' => $view->serverUrl('/images/application_view_columns.png'),
                    'onClickFunction' => 'Fisma.Search.toggleSearchColumnsPanel'
                )
            );

            // Show the "More" button only when there is element in the form.
            $elements = $this->getSearchMoreOptionsForm()->getElements();
            if (!empty($elements)) {
                $buttons['moreAction'] = new Fisma_Yui_Form_Button(
                    'toolbarMoreActionButton',
                    array(
                        'label' => 'More',
                        'onClickFunction' => 'Fisma.Search.toggleMoreButton'
                    )
                );
            }
        }
        return $buttons;
    }

    /**
     * A right-aligned group of buttons which include "Return to Search Results", "Previous" and "Next" buttons.
     *
     * @param Fisma_Doctrine_Record $record The object for which this toolbar applies, or null if not
     * @param array $fromSearchParams The array for "Previous" and "Next" button null if not
     * @return array Array of Fisma_Yui_Form_Button
     */
    public function getSearchButtons(Fisma_Doctrine_Record $record = null, $fromSearchParams = null)
    {
        $buttons = array();
        $isList = $this->getRequest()->getActionName() === 'list';
        $isView = $this->getRequest()->getActionName() === 'view';
        $resourceName = $this->getAclResourceName();
        $modelName = $this->_modelName;
        $view = Zend_Layout::getMvcInstance()->getView();

        if (!$isList && (!$this->_enforceAcl || $this->_acl->hasPrivilegeForClass('read', $resourceName))) {
            $buttons['list'] = new Fisma_Yui_Form_Button_Link(
                'toolbarListButton',
                array(
                    'value' => 'Return',
                    'href' => $this->getBaseUrl() . '/list',
                    'imageSrc' => '/images/arrow_return_down_left.png'
                )
            );
        }

        if ($isView && !empty($fromSearchParams)) {
            $buttons['previous'] = new Fisma_Yui_Form_Button(
                'PreviousButton',
                 array(
                       'label' => 'Previous',
                       'onClickFunction' => 'Fisma.Util.getNextPrevious',
                       'imageSrc' => $view->serverUrl('/images/control_stop_left.png'),
                       'onClickArgument' => array(
                           'url' => $this->getBaseUrl() . '/view/id/',
                           'id' => $record->id,
                           'action' => 'previous',
                           'modelName' => $modelName
                    )
                )

            );

            if (isset($fromSearchParams['first'])) {
                $buttons['previous']->readOnly = true;
            }

            $buttons['next'] = new Fisma_Yui_Form_Button(
                'NextButton',
                 array(
                       'label' => 'Next',
                       'imageSrc' => $view->serverUrl('/images/control_stop_right.png'),
                       'onClickFunction' => 'Fisma.Util.getNextPrevious',
                       'onClickArgument' => array(
                           'url' => $this->getBaseUrl() . '/view/id/',
                           'id' => $record->id,
                           'action' => 'next',
                           'modelName' => $modelName
                    )
                )
            );

            if (isset($fromSearchParams['last'])) {
                $buttons['next']->readOnly = true;
            }
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

        $this->_helper->fismaContextSwitch()
                      ->addActionContext('create', 'json')
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
     * @return Fisma_Doctrine_Record The saved object.
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

        return $subject;
    }

    /**
     * Display a create page for a single record.
     *
     * All of the default logic for creating a record is performed in _createObject, so that child classes can use the
     * default logic but still render their own views.
     *
     * @GETAllowed
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
     * @param boolean $ignorePost Optional. If set to true, will not process posted data.
     * @return void
     */
    public function _createObject($ignorePost = false)
    {
        if ($this->_enforceAcl) {
            $this->_acl->requirePrivilegeForClass('create', $this->getAclResourceName());
        }

        $format = $this->getRequest()->getParam('format');

        if ($format == 'json') {
            $jsonResponse = new Fisma_AsyncResponse;
        }

        // Get the subject form
        $form = $this->getForm();
        $form->setAction($this->getRequest()->getRequestUri());
        $form->setDefaults($this->getRequest()->getParams());

        if ($this->_request->isPost() && !$ignorePost) {
            $post = $this->_request->getPost();

            if ($form->isValid($post)) {
                try {
                    Doctrine_Manager::connection()->beginTransaction();
                    $object = $this->saveValue($form);
                    Doctrine_Manager::connection()->commit();

                    if ($format == 'json') {
                        $jsonResponse->succeed($object->id, $object->toArray());
                    } else {
                        $msg   = $this->getSingularModelName() . ' created successfully';
                        $type = 'notice';
                        $this->view->priorityMessenger($msg, $type);
                        $this->_redirect("{$this->_moduleName}/{$this->_controllerName}/view/id/{$object->id}");
                    }
                } catch (Doctrine_Validator_Exception $e) {
                    Doctrine_Manager::connection()->rollback();

                    if ($format == 'json') {
                        $jsonResponse->fail($e->getMessage());
                    } else {
                        $msg   = $e->getMessage();
                        $model = 'warning';
                        $this->view->priorityMessenger($msg, $model);
                    }
                }
            } else {
                $errorString = Fisma_Zend_Form_Manager::getErrors($form);

                if ($format == 'json') {
                    $jsonResponse->fail($errorString);
                } else {
                    $message = 'Unable to create a ' . $this->getSingularModelName();
                    $this->view->priorityMessenger("$message:<br>$errorString", 'warning');
                }
            }
        }

        if ($format == 'json') {
            $this->view->result = $jsonResponse;
        }

        $this->view->form = $form;

        $this->view->modelName = $this->getSingularModelName();
        $this->view->toolbarButtons = $this->getToolbarButtons();
        $this->view->searchButtons = $this->getSearchButtons();
    }

    /**
     * Display/edit details for a single record.
     *
     * All of the default logic for viewing a record is performed in _viewObject, so that child classes can use the
     * default logic but still render their own views.
     *
     * @GETAllowed
     * @return void
     */
    public function viewAction()
    {
        $this->_viewObject();
        $this->renderScript('object/view.phtml');
    }

    /**
     * A protected method which holds all of the logic for the view/edit page but does not actually render a view
     *
     * @return boolean
     */
    protected function _viewObject()
    {
        $id = $this->_request->getParam('id');

        $fromSearchParams = $this->_getFromSearchParams($this->_request);
        $fromSearchUrl = '';
        if (!empty($fromSearchParams)) {
            $fromSearchUrl = $this->_helper->makeUrlParams($fromSearchParams);
        }

        $subject = $this->_getSubject($id);

        // Since combine view and edit in one action, it needs read and/or update privilege to access view page
        if ($this->_enforceAcl &&
            (!$this->_acl->hasPrivilegeForObject('read', $subject) &&
             !$this->_acl->hasPrivilegeForObject('update', $subject))) {

               $this->view->priorityMessenger("Don't have permission to read/update this object.", 'warning');
               $this->_redirect("{$this->_moduleName}/{$this->_controllerName}/list");
        }

        $this->view->subject = $subject;

        $form = $this->getForm();
        if ($this->_acl->hasPrivilegeForObject('update', $subject)) {
            $form->setAction($this->getRequest()->getRequestUri());
        } else {
            $form->setReadOnly(true);
        }

       $this->setForm($subject, $form);

        // Update the model
        if ($this->_request->isPost()) {
            if ($this->_enforceAcl) {
                $this->_acl->requirePrivilegeForObject('update', $subject);
            }

            $post = $this->_request->getPost();
            if ($form->isValid($post)) {
                try {
                    $this->saveValue($form, $subject);
                    $msg  = $this->getSingularModelName() . ' updated successfully';
                    $type = 'notice';

                    // Refresh the form, in case the changes to the model affect the form
                    $form = $this->getForm();
                    $this->view->priorityMessenger($msg, $type);
                    $this->_redirect("{$this->_moduleName}/{$this->_controllerName}/view/id/$id$fromSearchUrl");
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
                $message = 'Error while trying to save the ' . $this->getSingularModelName();
                $error = "$message:<br>$errorString";
                $this->view->priorityMessenger($error, 'warning');
            }
        }

        $viewButtons = $this->getViewButtons($subject);
        $toolbarButtons = $this->getToolbarButtons($subject, $fromSearchParams);
        $searchButtons = $this->getSearchButtons($subject, $fromSearchParams);
        $buttons = array_merge($toolbarButtons, $viewButtons);
        $this->view->modelName = $this->getSingularModelName();
        $this->view->toolbarButtons = $toolbarButtons;
        $this->view->searchButtons = $searchButtons;

        $form = $this->setForm($subject, $form);
        $this->view->form = $form;
        $this->view->id   = $id;

        return (!isset($error));
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
            $msg   = 'Invalid ' . $this->getSingularModelName() . ' ID';
            $type = 'warning';
        } else {
            try {
                Doctrine_Manager::connection()->beginTransaction();
                $subject->delete();
                Doctrine_Manager::connection()->commit();
                $msg   = $this->getSingularModelName() . ' deleted successfully';
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
     * @GETAllowed
     * @return void
     */
    public function listAction()
    {
        if ($this->_enforceAcl && $this->getAclResourceName() !== 'Finding') {
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

        $rowsPerPage = $this->_getRowsPerPage();

        $searchResultsTable->setResultVariable('records') // Matches searchAction()
                           ->setDataUrl($this->getBaseUrl() . '/search')
                           ->setSortAscending(true)
                           ->setRenderEventFunction('Fisma.Search.highlightSearchResultsTable')
                           ->setRequestConstructor('Fisma.Search.generateRequest')
                           ->setRowCount($rowsPerPage)
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

            $formatter = 'YAHOO.widget.DataTable.formatText';
            if ($searchParams['type'] === 'boolean') {
                $formatter = 'Fisma.TableFormat.formatBoolean';
            }

            if (isset($searchParams['formatter']) && $searchParams['formatter'] === 'date') {
                $formatter = 'Fisma.TableFormat.formatDate';
            }

            if (isset($searchParams['formatter']) && $searchParams['formatter'] === 'datetime') {
                $formatter = 'Fisma.TableFormat.formatDateTime';
            }

            $column = new Fisma_Yui_DataTable_Column($label,
                                                     $sortable,
                                                     $formatter,
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

        $this->view->toolbarButtons = $this->getToolbarButtons(null);
        $this->view->pluralModelName = $this->getPluralModelName();
        $this->view->assign($searchResultsTable->getProperties());

        // Advanced search options is indexed by name, but for the client side it should be numerically indexed with
        // the name as an array element instead
        $advancedSearchOptions = array();

        foreach ($searchableFields as $fieldName => $fieldDefinition) {
            $advancedSearchOptions[] = array_merge(array('name' => $fieldName), $fieldDefinition);
        }

        $this->view->advancedSearchOptions = json_encode($advancedSearchOptions);
        $this->view->searchPreferences = $this->_getSearchPreferences();

        if ($table instanceof Fisma_Search_Facetable) {
            $this->view->facet = $table->getFacetedFields();
            $searchForm->removeElement('advanced');
        }

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
     * @GETAllowed
     * @return string The encoded table data in json format
     */
    public function searchAction()
    {
        if ($this->_enforceAcl) {
            $this->_acl->requirePrivilegeForClass('read', $this->getAclResourceName());
        }

        $format = $this->getRequest()->getParam('format');
        $rowsPerPage = $this->_getRowsPerPage();

        //initialize the data rows
        $searchResults = array(
            'startIndex'      => $this->_paging['startIndex'],
            'pageSize'        => $rowsPerPage
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
            $rows = $this->getRequest()->getParam('count', $rowsPerPage);
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
        } else if ('faceted' == $queryType) {
            // Extract keyword
            $keywords = $this->getRequest()->getParam('keywords');

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

            // Run facet search
            $result = $searchEngine->search(
                $this->_modelName,
                $keywords,
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
                            if (isset($searchableField['type']) && $searchableField['type'] === 'boolean') {
                                $value = $rawSearchData[$index][$fieldName] ? 'Yes' : 'No';
                                $reformattedSearchData[$index][$fieldName] = $value;
                            } else {
                                $reformattedSearchData[$index][$fieldName] = $rawSearchData[$index][$fieldName];
                            }
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
     * Render the re-association form (should probably be loaded in a panel)
     *
     * @GETAllowed
     */
    public function reassociateAction()
    {
        $this->_helper->layout->disableLayout();

        // Sanity checking
        $this->_acl->requirePrivilegeForClass('update', $this->_modelName);
        $id = $this->getRequest()->getParam('id');

        // Fetch associated objects
        $relations = Doctrine::getTable($this->_associatedModel)->getRelations();
        $relationColumn = null;
        $relationAlias = null;
        foreach ($relations as $relation) {
            if ($relation->getClass() === $this->_modelName) {
                $relationColumn = $relation->getLocal();
                $relationAlias = $relation->getAlias();
                break;
            }
        }
        if (empty($relationColumn)) {
            throw new Fisma_Zend_Exception("No relation between {$this->_associatedModel} and {$this->_modelName}");
        }
        $associatedObjects = Doctrine_Query::create()
            ->from($this->_associatedModel . ' m')
            ->where('m.' . $relationColumn . ' = ?', $id)
            ->execute();
        $this->view->objects = $associatedObjects;

        // Handle posted data (if any)
        $values = $this->getRequest()->getPost();
        if ($values) {
            try {
                Doctrine_Manager::connection()->beginTransaction();
                $destinationObject = Doctrine::getTable($this->_modelName)->find($values['destinationObjectId']);
                foreach ($associatedObjects as $object) {
                    if ($this->_associatedModel === 'Finding') { //Finding editable only during specific stages
                        if (!in_array($object->status, array('NEW', 'DRAFT'))) {
                            continue;
                        }
                    }
                    if ($this->_associatedModel === 'Incident') { //Incident has a mutator on categoryId
                        $object->merge(array($relationColumn => $values['destinationObjectId']));
                    } else {
                        $object->$relationAlias = $destinationObject;
                    }
                    $object->save();
                }

                // Commit
                Doctrine_Manager::connection()->commit();

                $fromSearchParams = $this->_getFromSearchParams($this->getRequest());
                $fromSearchUrl = '';
                if (!empty($fromSearchParams)) {
                    $fromSearchUrl = $this->_helper->makeUrlParams($fromSearchParams);
                }
                $this->view->priorityMessenger($associatedObjects->count() . " " . $this->_associatedPlural
                                                . " reassigned successfully.");
                $this->_redirect($this->getBaseUrl() . '/view/id/' . $id . $fromSearchUrl);
            } catch (Doctrine_Exception $e) {
                // We cannot access the view script from here (for priority messenger), so rethrow after roll-back
                Doctrine_Manager::connection()->rollback();
                throw $e;
            }
        }

        // Render the displayed form
        $nickname = $this->_associatedNickname;
        $query = Doctrine_Query::create()
            ->select('id, ' . $nickname)
            ->from($this->_modelName . ' m');$associatedTable = Doctrine::getTable($this->_associatedModel);
        if ($associatedTable instanceof Fisma_Search_CustomIndexBuilder_Interface) {
            $query = $associatedTable->getSearchIndexQuery($query, array($this->_modelName => 'm'));
        }
        $query->andWhere('m.id <> ?', $id);
        $destinations = $query->execute();

        if ($destinations->count() > 0) {
            $form = $this->getForm('reassociate_objects');
            $form->getElement('sourceObjectId')->setValue($id);

            $destinationArray = array();
            foreach ($destinations as $destination) {
                $destinationArray[$destination->id] = $destination->$nickname;
            }
            $form->getElement('destinationObjectId')->setMultiOptions($destinationArray);

            $this->view->form = $form;
        } else {
            $this->view->error = "There are no other " . $this->getPluralModelName() . " to migrate associated "
                               . $this->_associatedPlural . " to.";
        }
        $this->view->associatedPlural = $this->_associatedPlural;
        $this->renderScript('object/reassociate.phtml');
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

        // Remove the delete button if the user doesn't have the right to click it
        if (!$this->_isDeletable() || !$this->_acl->hasPrivilegeForClass('delete', $this->getAclResourceName())) {
            $searchForm->removeElement('deleteSelected');
        }

        // Remove the "Show Deleted" button if this model doesn't support soft-delete
        if (!Doctrine::getTable($this->_modelName)->hasColumn('deleted_at')) {
            $searchForm->removeElement('showDeleted');
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
     * Return an array of buttons that are displayed on the object view page
     *
     * @param Fisma_Doctrine_Record $subject
     * @return array
     */
    public function getViewButtons(Fisma_Doctrine_Record $subject)
    {
        $buttons = array();
        $view = Zend_Layout::getMvcInstance()->getView();

        if ($this->_isDeletable() && (!$this->_enforceAcl || $this->_acl->hasPrivilegeForObject('delete', $subject))) {
            $postAction = "{$this->_moduleName}/{$this->_controllerName}/delete/";

            $buttons['delete'] = new Fisma_Yui_Form_Button(
                'delete' . $this->_modelName,
                 array(
                       'label' => 'Delete '. $this->_modelName,
                       'imageSrc' => $view->serverUrl('/images/trash_recyclebin_empty_closed.png'),
                       'onClickFunction' => 'Fisma.Util.showConfirmDialog',
                       'onClickArgument' => array(
                           'args' => array(null, $postAction, $subject->id),
                           'text' => 'WARNING: You are about to delete the '
                                    . strtolower($this->_modelName)
                                    . 'record. This action cannot be undone. Do you want to continue?',
                           'func' => 'Fisma.Util.formPostAction'
                    )
                )
            );

        }

        return $buttons;
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

    /**
     * Get number of rows per page .
     *
     * @return number of rows per page on the list page.
     */
    protected function _getRowsPerPage()
    {
        $storage = Doctrine::getTable('Storage')->getUserIdAndNamespaceQuery($this->_me->id, 'Fisma.RowsPerPage')
                                                ->fetchOne();
        $data = empty($storage) ? '' : $storage->data;
        return empty($storage) ? $this->_paging['count'] : $data['row'];
    }

    /**
     * Contruct fromSearchParams
     *
     * @param object $request The http request.
     * @return array The fromSearchParams
     */
    protected function _getFromSearchParams($request)
    {

        $first = $request->getParam('first');
        $last = $request->getParam('last');
        $fromSearch = $request->getParam('fromSearch');

        $urlParams = array();
        if ($first) {
            $urlParams['first'] = 1;
        }

        if ($last) {
            $urlParams['last'] = 1;
        }

        if ($fromSearch) {
            $urlParams['fromSearch'] = 1;
        }

        return $urlParams;
    }
}
