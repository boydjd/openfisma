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
 * The security controller provides convenient access to the current user instance through the
 * $_me private class member. This controller also verifies an authenticated user before
 * it executes any actions.
 * 
 * Any controllers which will be access controlled should inherit from this class, instead
 * of inheriting from Zend_Controller directly.
 *
 * @author     Mark E. Haase <mhaase@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Controller
 */
abstract class Fisma_Zend_Controller_Action_Security  extends Zend_Controller_Action
{
    /**
     * Authenticated user instance
     * 
     * @var User
     */
    protected $_me = null;

    /**
     * Authenticated user's ACL 
     * 
     * @var Fisma_Zend_Acl 
     */
    protected $_acl = null;
    
    /**
     * Non-authenticated users are not allowed to access Security Controllers. Redirect them to
     * the login page.
     * 
     * @return void
     * @throws Fisma_Zend_Exception_InvalidAuthentication if user session expired
     */
    public function preDispatch()
    {
        parent::preDispatch();
   
        // Verify that the user is authenticated, and store a reference to the authenticated user credentials
        $auth = Zend_Auth::getInstance();
        //use the consistent storage
        $auth->setStorage(new Fisma_Zend_Auth_Storage_Session());

        if ($auth->hasIdentity()) {
            $container = $this->getInvokeArg('bootstrap')->getContainer();

            // Store a reference to the authenticated user inside the controller, for convenience
            $this->_me = $container->currentUser->get();

            // getting the ACL can throw an exception that we want to trap
            try {
                // Store a reference to the authenticated user's ACL inside the controller
                $this->_acl = $container->acl->get();
            } catch(Fisma_Zend_Exception_InvalidAuthentication $e) {
                // this error condition handled by Fisma_Zend_Controller_Action_Helper_Security
            }
        }
    }
}
