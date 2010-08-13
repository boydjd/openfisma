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
        'count' => 20
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
        $this->render();
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
                    if (!empty($objectId)) {
                            $this->_redirect("{$this->_moduleName}/{$this->_controllerName}/view/id/$objectId");
                    }
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
        $link = empty($keywords) ? '' :'/keywords/' . $this->view->escape($keywords, 'url');
        $this->view->link     = $link;
        $this->view->pageInfo = $this->_paging;
        $this->view->keywords = $keywords;
        $this->render('list');
    }

    /** 
     * Search the subject
     *
     * This outputs a json object. Allowing fulltext search from each record enpowered by lucene
     * 
     * @return string The encoded table data in json format
     */
    public function searchAction()
    {
        $this->_acl->requirePrivilegeForClass('read', $this->getAclResourceName());
        $sortBy = $this->_request->getParam('sortby', 'id');
        $order  = $this->_request->getParam('order');
        $keywords  = html_entity_decode($this->_request->getParam('keywords')); 

        //filter the sortby to prevent sqlinjection
        $subjectTable = Doctrine::getTable($this->_modelName);
        if (!in_array(strtolower($sortBy), $subjectTable->getColumnNames())) {
            return $this->_helper->json('Invalid "sortBy" parameter');
        }

        $order = strtoupper($order);
        if ($order != 'DESC') {
            $order = 'ASC'; //ignore other values
        }
        
        $query  = Doctrine_Query::create()
                    ->select('*')->from($this->_modelName)
                    ->orderBy("$sortBy $order")
                    ->limit($this->_paging['count'])
                    ->offset($this->_paging['startIndex']);

        //initialize the data rows
        $tableData    = array('table' => array(
                            'recordsReturned' => 0,
                            'totalRecords'    => 0,
                            'startIndex'      => $this->_paging['startIndex'],
                            'sort'            => $sortBy,
                            'dir'             => $order,
                            'pageSize'        => $this->_paging['count'],
                            'records'         => array()
                        ));
        if (!empty($keywords)) {
            // lucene search 
            $index = new Fisma_Index($this->_modelName);
            $ids = $index->findIds($keywords);
            if (!empty($ids)) {
                $query->whereIn('id', $ids);
            } else {
                //no data
                return $this->_helper->json($tableData);
            }
        }
        
        $totalRecords = $query->count();
        $rows         = $query->execute();
        $rows         = $this->handleCollection($rows);
        $tableData['table']['recordsReturned'] = count($rows);
        $tableData['table']['totalRecords'] = $totalRecords;
        $tableData['table']['records'] = $rows;
        return $this->_helper->json($tableData);
    }

    /**
     * Return array of the collection.
     * 
     * If an collection need to change its keys to some other value, please override it
     * in the controller which is inherited from this Controller
     * 
     * @param Doctrine_Collections $rows The spepcific Doctrine_Collections object
     * @return array The array representation of the specified Doctrine_Collections object
     */
    public function handleCollection($rows)
    {
        return $rows->toArray();
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
}
