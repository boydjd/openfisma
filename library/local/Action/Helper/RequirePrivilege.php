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
 * @author    Ryan Yang <ryan@users.sourceforge.net>
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/mw/index.php?title=License
 * @version   $Id: RequirePrivilege.php 1174 2008-11-13 01:07:19Z woody712 $
 */

/**
 * @see Zend_Controller_Action_Helper_Abstract
 */
require_once 'Zend/Controller/Action/Helper/Abstract.php';

/**
 * Require privilege
 *
 * @uses       Controller_Action_Helper_Abstract
 * @category   local
 * @package    Controller
 * @subpackage Controller_Action_Helper
 * @copyright  Copyright (c) 2005-2008
 * @license    http://www.openfisma.org/mw/index.php?title=License
 */
class Action_Helper_RequirePrivilege extends Zend_Controller_Action_Helper_Abstract
{

    /**
     * use ACL to judge whether support the requirement
     * 
     * @param  string $resource
     * @param  string $operation
     */
    public function requirePrivilege($resource, $operation)
    {
        try {
            $auth = Zend_Auth::getInstance();
            $me = $auth->getIdentity();
            $roleArray = &$me->roleArray;
            if ( $me->account != "root" ) {
                $acl = Zend_Registry::get('acl');
                foreach ($roleArray as $role) {
                    if ( true == $acl->isAllowed($role, $resource, $operation) ) {
                        return ;
                    }
                }
                throw new Exception_PrivilegeViolation("User does not have the privilege for ($resource, $operation)");
            }
        } catch (Zend_Acl_Exception  $e) {
            $request = $this->getRequest();
    
            if ($request instanceof Zend_Controller_Request_Abstract === false){
                /**
                 * @see Zend_Controller_Action_Exception
                 */
                require_once 'Zend/Controller/Action/Exception.php';
                throw new Zend_Controller_Action_Exception('Request object not set yet');
            }
            $controller = $request->getControllerName();
            $module = $request->getModuleName();
            throw new Exception_PrivilegeViolation(
                "ACL violation in {$controller}->{$module}:".$e->getMessage());
        }
    }

    /**
     * Perform helper when called as $this->_helper->requirePrivilage() from an action controller
     * 
     * @param  string $resource
     * @param  string $operation 
     */
    public function direct($resource, $operation)
    {
        $this->requirePrivilege($resource, $operation);
    }
}