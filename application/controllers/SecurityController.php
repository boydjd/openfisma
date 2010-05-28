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
 * @version    $Id$
 */
class SecurityController extends Zend_Controller_Action
{
    /**
     * Authenticated user instance
     * 
     * @var User
     */
    protected $_me = null;
    
    /**
     * Stores the current time. This might be useful for synchronizing events in the audit logs that result
     * from a single invocation of a controller that runs for several seconds.
     * 
     * @var Zend_Date
     */
    public static $now = null;

    /**
     * Initialize class members
     * 
     * @return void
     */
    public function init()
    {
        parent::init();
        // Initialize time storage
        if (empty(self::$now)) {
            self::$now = Zend_Date::now();
        }
        
        // Verify that the user is authenticated, and store a reference to the authenticated user credentials
        $auth = Zend_Auth::getInstance();
        //use the consistent storage
        $auth->setStorage(new Fisma_Zend_Auth_Storage_Session());

        if ($auth->hasIdentity()) {
            // Store a reference to the authenticated user inside the controller, for convenience
            $this->_me = User::currentUser();
             
            // Store a reference to the ACL inside the view object
            $this->view->assign('acl', $this->_me->acl());
        } else {
            // User is not authenticated. The preDispatch will forward the user to the login page,
            // but we want to store their original request so that we can redirect them to their
            // original destination after they have authenticated.
            $session = Fisma::getSession();
            $session->redirectPage = $_SERVER['REQUEST_URI'];
        }
    }

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
   
        $cont = $this->_request->controller; 
        $act  = $this->_request->action; 

        if (!(($cont == 'incident') && (in_array($act, array('anonreport','anoncreate','anonsuccess'))))) {
            if (empty($this->_me)) {
                $message = 'Your session has expired. Please log in again to begin a new session.';
                throw new Fisma_Zend_Exception_InvalidAuthentication($message);
            }
        }
    }
}
