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
                      ->setActionContext('remove', 'json')
                      ->initContext();
    }

    /**
     * Display the comment form
     *
     * @GETAllowed
     */
    public function formAction()
    {
        // The view is rendered into a panel, so it doesn't need a layout
        $this->_helper->layout->disableLayout();

        $form = Fisma_Zend_Form_Manager::loadForm('add_comment');
        //$form->setElementDecorators(array(new Fisma_Zend_Form_Decorator()));

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
            $commentArray['username'] = $this->view->userInfo(
                $commentRecord->User->displayName,
                $commentRecord->User->id
            );
            $commentArray['comment'] = Fisma_String::textToHtml(htmlspecialchars($commentArray['comment']));
            $commentTs = new Zend_Date($commentArray['createdTs'], Fisma_Date::FORMAT_DATETIME);
            $commentTs->setTimezone('UTC');
            $commentDateTime = $commentTs->toString(Fisma_Date::FORMAT_MONTH_DAY_YEAR)
                                  . ' at '
                                  . $commentTs->toString(Fisma_Date::FORMAT_AM_PM_TIME);
            $commentTs->setTimezone(CurrentUser::getAttribute('timezone'));
            $commentDateTimeLocal = $commentTs->toString(Fisma_Date::FORMAT_MONTH_DAY_YEAR)
                                  . ' at '
                                  . $commentTs->toString(Fisma_Date::FORMAT_AM_PM_TIME);
            $commentArray['createdTs'] =
                Zend_Json::encode(array("local" => $commentDateTimeLocal, "utc" => $commentDateTime));

            $response->comment = $commentArray;

        } catch (Fisma_Zend_Exception_User $e) {
            $response->fail($e->getMessage(), $e);
        } catch (Exception $e) {
            if (Fisma::debug()) {
                $response->fail("Failure (debug mode): " . $e->getMessage(), $e);
            } else {
                $response->fail("Internal system error.");
            }

            $this->getInvokeArg('bootstrap')->getResource('Log')->err($e->getMessage() . "\n" . $e->getTraceAsString());
        }

        $this->view->response = $response;
    }

    /**
     * Remove a comment from a particular object who has an audit log
     */
    public function removeAction()
    {
        $response = new Fisma_AsyncResponse();

        $objectId = $this->getRequest()->getParam('id');
        $objectClass = $this->getRequest()->getParam('type');
        $commentId = trim($this->getRequest()->getParam('commentId'));

        try {
            $object = Doctrine::getTable($objectClass)->find($objectId);

            if (!Doctrine::getTable($objectClass)->hasTemplate('Fisma_Doctrine_Behavior_AuditLoggable')) {
                throw new Fisma_Zend_Exception("Comment can only be deleted from AuditLoggable objects");
            }

            if (!$object) {
                throw new Fisma_Zend_Exception("No object exist in class $objectClass with id $id");
            }

            if (empty($commentId)) {
                throw new Fisma_Zend_Exception_User("Comment cannot be blank");
            }

            // Add comment and include comment details (including username) in response object
            $object->getAuditLog()->write(
                "Comment deleted:\n\n" . $object->getComments()->fetchOneById($commentId)->comment
            );
            $object->getComments()->removeComment($commentId);
            $object->updateJsonComments();

        } catch (Fisma_Zend_Exception_User $e) {
            $response->fail($e->getMessage(), $e);
        } catch (Exception $e) {
            if (Fisma::debug()) {
                $response->fail("Failure (debug mode): " . $e->getMessage(), $e);
            } else {
                $response->fail("Internal system error.");
            }

            $this->getInvokeArg('bootstrap')->getResource('Log')->err($e->getMessage() . "\n" . $e->getTraceAsString());
        }

        $this->view->response = $response;
        if ($returnUrl = $this->getRequest()->getParam('returnUrl')) {
            $this->_redirect($returnUrl);
        }
    }
}
