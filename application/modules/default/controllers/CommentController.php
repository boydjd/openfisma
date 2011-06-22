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
 * Provide helper actions for the Commentable behavior
 * 
 * @author     Mark E. Haase
 * @copyright  (c) Endeavor Systems, Inc. 2010 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Controller
 */
class CommentController extends Fisma_Zend_Controller_Action_Security
{
    /**
     * Set JSON context for the add action
     */
    public function init()
    {
        parent::init();
        
        $this->_helper->contextSwitch
                      ->setActionContext('add', 'json')
                      ->initContext();
    }

    /**
     * Display the comment form
     */
    public function formAction()
    {
        // The view is rendered into a panel, so it doesn't need a layout
        $this->_helper->layout->disableLayout();
        
        $form = Fisma_Zend_Form_Manager::loadForm('add_comment');
                                
        $this->view->form = $form;
    }
        
    /**
     * Add comment to a particular object
     */
    public function addAction()
    {
        $response = new Fisma_AsyncResponse();
        
        $objectId = $this->getRequest()->getParam('id');
        $objectClass = $this->getRequest()->getParam('type');
        $trimmedComment = trim($this->getRequest()->getParam('comment'));

        try {
            $object = Doctrine::getTable($objectClass)->find($objectId);
            
            if (!$object) {
                throw new Fisma_Zend_Exception("No object exist in class $objectClass with id $id");
            }
            
            if (empty($trimmedComment)) {
                throw new Fisma_Zend_Exception_User("Comment cannot be blank");
            }
            
            // Add comment and include comment details (including username) in response object
            $commentRecord = $object->getComments()->addComment($trimmedComment);
            $commentArray = $commentRecord->toArray();
            $commentArray['username'] = htmlspecialchars($commentRecord->User->username);
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
