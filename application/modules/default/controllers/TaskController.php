<?php
/**
 * Copyright (c) 2012 Endeavor Systems, Inc.
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
 * Provide helper actions for the taskable behavior
 * 
 * @author     Ben Zheng <ben.zheng@reyosoft.com>
 * @copyright  (c) Endeavor Systems, Inc. 2012 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Controller
 */
class TaskController extends Fisma_Zend_Controller_Action_Security
{
    /**
     * Set JSON context for the add action
     */
    public function init()
    {
        parent::init();

        $this->_helper->contextSwitch
                      ->setActionContext('add', 'json')
                      ->setActionContext('edit', 'json')
                      ->setActionContext('delete', 'json')
                      ->setActionContext('add-comment', 'json')
                      ->initContext();
    }

    /**
     * Get the specified form of the subject model
     *
     * @return Zend_Form The specified form of the subject model
     */
    public function getForm()
    {
        $formName = 'add_task';
        $form = Fisma_Zend_Form_Manager::loadForm($formName);
        $form = Fisma_Zend_Form_Manager::prepareForm($form);

        $form->getElement('ecd')
             ->setValue(Zend_Date::now()->toString(Fisma_Date::FORMAT_DATE))
             ->addDecorator(new Fisma_Zend_Form_Decorator_Date);

        return $form;
    }

    /**
     * Display the task form
     *
     * @GETAllowed
     */
    public function formAction()
    {
        $this->_helper->layout->disableLayout();

        $form = $this->getForm();

        $this->view->form = $form;
    }

    /**
     * Add task to a particular object
     * 
     * @GETAllowed
     * @return void
     */
    public function addAction()
    {
        $response = new Fisma_AsyncResponse();

        $objectId = $this->getRequest()->getParam('objectId');
        $objectClass = ucfirst(trim($this->getRequest()->getParam('type')));

        $form   = $this->getForm();

        if ($this->_request->isPost()) {
            $post = $this->_request->getPost();

            if ($form->isValid($post)) {
                try {
                    $object = Doctrine::getTable($objectClass)->find($objectId);

                    if (!$object) {
                        throw new Fisma_Zend_Exception("No object exist in class $objectClass with id $objectId");
                    }

                    Doctrine_Manager::connection()->beginTransaction();
                    $task = $object->getTasks()->addTask(array_filter($form->getValues()));
                    Doctrine_Manager::connection()->commit();

                    $response->task = $task->toArray();
                    $response->succeed($task->id);
                } catch (Doctrine_Validator_Exception $e) {
                    Doctrine_Manager::connection()->rollback();

                    $response->fail($e->getMessage());
                }
            } else {
                $errorString = Fisma_Zend_Form_Manager::getErrors($form);

                $response->fail($errorString);
            }
        }

        $this->view->response = $response;
    }

    /**
     * Edit a task
     *
     * @GETAllowed
     * @return void
     */
    public function editAction()
    {
        $response = new Fisma_AsyncResponse();

        $taskId = $this->getRequest()->getParam('id');
        $objectId = $this->getRequest()->getParam('objectId');
        $objectClass = ucfirst(trim($this->getRequest()->getParam('type')));
        $fieldName = $this->getRequest()->getParam('field');
        $value = $this->getRequest()->getParam('value');

        try {
            Doctrine_Manager::connection()->beginTransaction();

            $object = Doctrine::getTable($objectClass)->find($objectId);

            if (!$object) {
                throw new Fisma_Zend_Exception_User("No object exist in class $objectClass with id $objectId");
            }

            $query = $object->getTasks()->query();
            $task = $query->where('o.id = ?', $taskId)
                          ->execute()
                          ->getFirst();

            if (!$task) {
                throw new Fisma_Zend_Exception_User("No task found with id ($taskId).");
            }

            if ($fieldName == 'poc') {
                if (!empty($value)) {
                    $poc = Doctrine::getTable('Poc')->findOneByUsername($value);
                    $task->Poc = $poc;
                } else {
                    $task->Poc = null;
                }
            } elseif ($fieldName == 'ecd') {
                $datetime = new Zend_Date($value, Fisma_Date::FORMAT_DATE);
                $task->$fieldName = $datetime->toString(Fisma_Date::FORMAT_DATETIME);
            } else {
                $task->$fieldName = $value;
            }

            $task->save();

            Doctrine_Manager::connection()->commit();

            $response->succeed("Task has been updated successfully.");
        } catch (Doctrine_Exception $e) {
            Doctrine_Manager::connection()->rollback();
            throw $e;
        } catch (Exception $e) {
            if (Fisma::debug()) {
                $response->fail("Failure (debug mode): " . $e->getMessage());
            } else {
                $response->fail("Internal system error. Save task failed.");
            }

            $this->getInvokeArg('bootstrap')->getResource('Log')->err($e->getMessage() . "\n" . $e->getTraceAsString());
        }

        $this->view->response = $response;
    }

    /**
     * Delete a task
     *
     * @GETAllowed
     * @return void
     */
    public function deleteAction()
    {
        $response = new Fisma_AsyncResponse();

        $taskId = $this->getRequest()->getParam('taskId');
        $objectId = $this->getRequest()->getParam('objectId');
        $objectClass = ucfirst(trim($this->getRequest()->getParam('type')));

        try {
            Doctrine_Manager::connection()->beginTransaction();

            $object = Doctrine::getTable($objectClass)->find($objectId);

            if (!$object) {
                throw new Fisma_Zend_Exception("No object exist in class $objectClass with id $objectId");
            }

            $query = $object->getTasks()->query();
            $task = $query->andWhere('o.id = ?', $taskId)
                          ->execute()
                          ->getFirst();

            if (!$task) {
                throw new Fisma_Zend_Exception("No task found with id ($taskId).");
            }

            $task->delete();

            Doctrine_Manager::connection()->commit();

            $response->succeed("Task has been deleted successfully.");
        } catch (Doctrine_Exception $e) {
            Doctrine_Manager::connection()->rollback();
            throw $e;
        } catch (Exception $e) {
            if (Fisma::debug()) {
                $response->fail("Failure (debug mode): " . $e->getMessage());
            } else {
                $response->fail("Internal system error. Delete task failed.");
            }

            $this->getInvokeArg('bootstrap')->getResource('Log')->err($e->getMessage() . "\n" . $e->getTraceAsString());
        }

        $this->view->response = $response;
    }

    /**
     * Add comment to a particular object
     *
     * @GETAllowed
     * @return void
     */
    public function addCommentAction()
    {
        $response = new Fisma_AsyncResponse();

        $taskId = $this->getRequest()->getParam('taskId');
        $objectId = $this->getRequest()->getParam('objectId');
        $objectClass = ucfirst(trim($this->getRequest()->getParam('type')));
        $trimmedComment = trim($this->getRequest()->getParam('comment'));

        try {
            $object = Doctrine::getTable($objectClass)->find($objectId);

            if (!$object) {
                throw new Fisma_Zend_Exception("No object exist in class $objectClass with id $objectId");
            }

            if (empty($trimmedComment)) {
                throw new Fisma_Zend_Exception_User("Comment cannot be blank");
            }

            $query = $object->getTasks()->query();
            $task = $query->andWhere('o.id = ?', $taskId)
                    ->execute()
                    ->getFirst();

            if (!$task) {
                throw new Fisma_Zend_Exception("No task found with id ($taskId).");
            }

            // Add comment and include comment details (including username) in response object
            $commentRecord = $task->getComments()->addComment($trimmedComment);
            $commentArray = $commentRecord->toArray();
            $commentArray['username'] = $this->view->userInfo(htmlspecialchars($commentRecord->User->username));
            $commentArray['comment'] = Fisma_String::textToHtml(htmlspecialchars($commentArray['comment']));

            $response->comment = $commentArray;

        } catch (Fisma_Zend_Exception_User $e) {
            $response->fail($e->getMessage());
        } catch (Exception $e) {
            if (Fisma::debug()) {
                $response->fail("Failure (debug mode): " . $e->getMessage());
            } else {
                $response->fail("Internal system error. File not uploaded.");
            }

            $this->getInvokeArg('bootstrap')->getResource('Log')->err($e->getMessage() . "\n" . $e->getTraceAsString());
        }

        $this->view->response = $response;
    }
}
