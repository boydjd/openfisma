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
 * The error controller implements error-handling logic and displays user-level
 * errors to the user.
 *
 * @author     Jim Chen <xhorse@users.sourceforge.net>
 * @copyright  (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Controller
 */
class ErrorController extends Zend_Controller_Action
{
    /**
     * This action handles Application errors, Errors in the controller chain arising from missing 
     * controller classes and/or action methods
     * 
     * @return void
     */
    public function errorAction()
    {
        $errors = $this->_getParam('error_handler');

        // If exception is an authentication exception, forward to auth/login
        if ($errors->exception instanceof Fisma_Zend_Exception_InvalidAuthentication) {
            $this->view->error = $errors->exception->getMessage();
            $this->_forward('logout', 'Auth');
            return;
        }

        // If an error occurs in any context other than the default, then the view suffix will have changed; therefore,
        // we should always reset the view suffix before rendering an error message.
        $this->_helper->viewRenderer->setViewSuffix('phtml');
        $content = null;

        $this->getResponse()->clearBody();
        $content = (string)$errors->exception;
        $this->getInvokeArg('bootstrap')->getResource('Log')->log($content, Zend_Log::ERR);
        $this->view->content = $content;

        if ($errors->exception instanceof Fisma_Zend_Exception_User) {
            $this->view->message = $errors->exception->getMessage();
        } else {         
            $this->view->message = "<p>An unexpected error has occurred. This error has been logged"
                                 . " for administrator review.</p><p>You may want to try again in a"
                                 . " few minutes. If the problem persists, please contact your"
                                 . " administrator.</p>";
        }

        if ($errors->type === Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_CONTROLLER ||
            $errors->type === Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_ACTION) {
            $this->getResponse()->setHttpResponseCode(404);
            $this->render('error404');
        }

        $front = Zend_Controller_Front::getInstance();
        if ($stack = $front->getPlugin('Zend_Controller_Plugin_ActionStack')) {
            //clear the action stack to prevent additional exceptions would be throwed
            while($stack->popStack());
        }

        //Remove Fisma_Zend_Controller_Action_Helper_ReportContextSwitch to prevent 
        //additional exception being thrown
        if ($actionHelperStack = Zend_Controller_Action_HelperBroker::getStack()) {
            if ($actionHelperStack->offsetExists('ReportContextSwitch')) {
                $actionHelperStack->offsetUnset('ReportContextSwitch');
            }    
        }
    }

    /**
     * Error handler for input validation error
     * 
     * @return void
     */
    public function inputerrorAction()
    {
        $content = null;
        $errors = $this->_getParam('inputerror');
        $this->_helper->layout->setLayout('error');
        $content = "<h1>Input Error!</h1>" . PHP_EOL;
        $content.= "Error input fields:";
        foreach ($errors as $fieldname => $erroritem) {
            $content.= " $fieldname  ";
        }
        $this->getResponse()->clearBody();
        $this->view->content = $content . '<p>';
        $this->render('error');
    }
}
