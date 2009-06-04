<?php
/**
 * Copyright (c) 2008 Endeavor Systems, Inc.
 *
 * This file is part of OpenFISMA.
 *
 * OpenFISMA is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * OpenFISMA is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with OpenFISMA.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author    Mark E. Haase <mhaase@endeavorsystems.com>
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/mw/index.php?title=License
 * @version   $Id$
 * @package   Controller
 */

/**
 * The security controller provides convenient access to the current user instance through the
 * $_me private class member. This controller also verifies an authenticated user before
 * it executes any actions.
 * 
 * Any controllers which will be access controlled should inherit from this class, instead
 * of inheriting from Zend_Controller directly.
 *
 * @package   Controller
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/mw/index.php?title=License
 */
class SecurityController extends MessageController
{
    /**
     * Authenticated user instance
     */
    protected $_me = null;
    
    /**
     * Stores the current time. This might be useful for synchronizing events in the audit logs that result
     * from a single invocation of a controller that runs for several seconds.
     */
    public static $now = null;

    /**
     * Initialize class members
     */
    public function init()
    {
        // Initialize time storage
        if (empty(self::$now)) {
            self::$now = Zend_Date::now();
        }
        
        // Verify that the user is authenticated, and store a reference to the authenticated user credentials
        if (Zend_Auth::getInstance()->hasIdentity()) {
            if (isset($redirectInfo->page)) {
                unset($redirectInfo->page);
            }

            // Store a reference to the authenticated user inside the controller, for convenience
            $this->_me = $user = User::currentUser();
            // Store a reference to the ACL inside the view object
            $this->view->assign('acl', $this->_me->acl());

            // Update the session timeout
            $authSession = new Zend_Session_Namespace(Zend_Auth::getInstance()->getStorage()->getNamespace());
            $authSession->setExpirationSeconds(Configuration::getConfig('session_inactivity_period'));
        } else {
            // User is not authenticated. The preDispatch will forward the user to the login page,
            // but we want to store their original request so that we can redirect them to their
            // original destination after they have authenticated.
            $redirectInfo = new Zend_Session_Namespace('redirect_page');
            $redirectInfo->page = $_SERVER['REQUEST_URI'];
        }
    }

    /**
     * Non-authenticated users are not allowed to access Security Controllers. Redirect them to
     * the login page.
     */
    public function preDispatch()
    {
        if (empty($this->_me)) {
            throw new Fisma_Exception_InvalidAuthentication('Your session has expired. Please log in again to begin a new session.');
            $this->_forward('login', 'User');
        }
    }
}
