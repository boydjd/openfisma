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
 * Action helper to handle the authentication check to ensure users are logged in before allowing
 * access to site resources.
 *
 * This helper MUST be explicitly registered in the Bootstrap or somewhere similar in order for its
 * preDispatch() hook to be triggered.
 * 
 * @uses Zend_Controller_Action_Helper_Abstract
 * @package Fisma_Zend_Controller_Action_Helper 
 * @version $Id: $
 * @copyright (c) Endeavor Systems, Inc. 2010 {@link http://www.endeavorsystems.com}
 * @author Andrew Reeves <andrew.reeves@endeavorsystems.com> 
 * @license http://www.openfisma.org/content/license GPLv3
 */
class Fisma_Zend_Controller_Action_Helper_Security extends Zend_Controller_Action_Helper_Abstract
{
    /**
     * Overridden preDispatch hook to do security checking
     *
     * @return void
     */
    public function preDispatch()
    {
        if (!$this->_authenticationRequired()) {
            return;
        }

        $currentUser = CurrentUser::getInstance();

        $controller = $this->getActionController();

        if ($currentUser != null) {
            // Setup the ACL view helper
            $controller->view->getHelper('acl')->setAcl($currentUser->acl());
        } else {
            // store original requested URL in session for the login script to redirect to
            $session = Fisma::getSession();
            $session->redirectPage = $_SERVER['REQUEST_URI'];

            $message = 'Your session has expired. Please log in again to begin a new session.';
            throw new Fisma_Zend_Exception_InvalidAuthentication($message);
        }
    }

    /**
     * Method to return whether authentication is required for the current controller/action pair
     *
     * @return bool True if authentication is required, false otherwise.
     */
    protected function _authenticationRequired()
    {
        $request = $this->getRequest();
        $controller = strtolower($request->getControllerName());
        $action  = strtolower($request->getActionName());

        // controller-wide security exceptions
        $allowedControllers = array('auth','debug','error','help','metadata','redirect');

        // action-specific security exceptions
        $allowedActions = array('incident' => array('report', 'review-report', 'cancel-report', 'save-report'));

        $isAllowedController = in_array($controller, $allowedControllers);
        $isAllowedAction = isset($allowedActions[$controller]) && in_array($action, $allowedActions[$controller]);

        return !($isAllowedController || $isAllowedAction);
    }
}
