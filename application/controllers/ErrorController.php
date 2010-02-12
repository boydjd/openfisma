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
 * @version    $Id$
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
        // If an error occurs in any context other than the default, then the view suffix will have changed; therefore,
        // we should always reset the view suffix before rendering an error message.
        $auth = Zend_Auth::getInstance();
        $auth->setStorage(new Fisma_Auth_Storage_Session());

        $this->_helper->viewRenderer->setViewSuffix('phtml');
        $content = null;
        $errors = $this->_getParam('error_handler');

        // if the user hasn't login, or the session expired.
        if ($errors->exception instanceof Fisma_Exception_InvalidAuthentication) {
            $this->view->assign('error', $errors->exception->getMessage());
            //remind the user to login
            $this->_forward('logout', 'Auth');
        // if the user want to access an empty path.  
        } elseif (!$auth->hasIdentity()) {
            $this->view->assign('error', 'Access denied. Please login first.');
            $this->_forward('logout', 'Auth');
        // if the user has login and meeted an exception.
        } else {
            $this->getResponse()->clearBody();
            $content = get_class($errors->exception)
                     . ": "
                     . $errors->exception->getMessage()
                     . '<br>'
                     . $errors->exception->getTraceAsString()
                     . '<br>';
            $logger = Fisma::getLogInstance();
            $logger->log(Fisma_String::htmlToPlainText($content), Zend_Log::ERR);
            $this->view->content = $content;

            if ($errors->exception instanceof Fisma_Exception_InvalidPrivilege) {
                $this->view->message = $errors->exception->getMessage();
            } else {         
                $this->view->message = "<p>An unexpected error has occurred. This error has been logged"
                                     . " for administrator review.</p><p>You may want to try again in a"
                                     . " few minutes. If the problem persists, please contact your"
                                     . " administrator.</p>";
            }

            $front = Zend_Controller_Front::getInstance();
            if ($stack = $front->getPlugin('Zend_Controller_Plugin_ActionStack')) {
                //clear the action stack to prevent additional exceptions would be throwed
                while($stack->popStack());
            }
            
            // Add headers and footers for logged in users
            if ($auth->hasIdentity()) {
                $this->_helper->actionStack('header', 'panel');
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
        $this->_helper->actionStack('header', 'panel');
        $this->render('error');
    }
}
