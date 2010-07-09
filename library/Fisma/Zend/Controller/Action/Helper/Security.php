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

        $controller = $this->getActionController();
        $container = $controller->getInvokeArg('bootstrap')->getContainer();

        // Verify that the user is authenticated, and store a reference to the authenticated user credentials
        $auth = Zend_Auth::getInstance();
        //use the persistant storage
        $auth->setStorage(new Fisma_Zend_Auth_Storage_Session());

        if ($auth->hasIdentity()) {
            $me = $container->currentUser->get();

            // getting the ACL can throw an exception that we want to trap
            try {
                $acl = $container->acl->get();

                // Setup the ACL view helper
                $aclHelper = $controller->view->getHelper('acl')->setAcl($acl);
            } catch(Fisma_Zend_Exception_InvalidAuthentication $e) {
                $controller->view->error = $e->getMessage();
                $this->_forward('logout', 'Auth');
            }
        } else {
            // User is not authenticated. The preDispatch will forward the user to the login page,
            // but we want to store their original request so that we can redirect them to their
            // original destination after they have authenticated.
            $session = Fisma::getSession();
            $session->redirectPage = $_SERVER['REQUEST_URI'];

            $message = 'Your session has expired. Please log in again to begin a new session.';
            $controller->view->error = $message;
            $this->_forward('logout', 'Auth');
        }
    }

    /**
     * The _forward() method from Zend_Controller_Action for use within this helper.
     *
     * @param string $action
     * @param string $controller
     * @param string $module
     * @param array $params
     * @return void
     */
    protected function _forward($action, $controller = null, $module = null, array $params = null)
    {
        $request = $this->getRequest();

        if (null !== $params) {
            $request->setParams($params);
        }

        if (null !== $controller) {
            $request->setControllerName($controller);

            // Module should only be reset if controller has been specified
            if (null !== $module) {
                $request->setModuleName($module);
            }
        }

        $request->setActionName($action)
                ->setDispatched(false);
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

        $allowedControllers = array('auth','debug','error','help','metadata','redirect');

        return !in_array($controller, $allowedControllers);
    }
}
