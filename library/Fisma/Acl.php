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
 * @author    Mark E. Haase <mhaase@endeavorsystems.com
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/mw/index.php?title=License
 * @version   $Id: Fismacl.php -1M 2009-04-15 18:40:38Z (local) $
 */

/**
 * Extends Zend_Acl to tweak behavior needed for OpenFISMA.
 * 
 * 1) The role that is searched is always the current user's role.
 * 2) Ensure that the system only accesses objects within their assigned systems
 * 3) Add a requirePrivilege method, which is a convenient way to assert that a user is allowed to do something
 * 
 * @category   local
 * @package    Local
 * @copyright  Copyright (c) 2005-2008
 * @license    http://www.openfisma.org/mw/index.php?title=License
 */
class Fisma_Acl extends Zend_Acl
{
    /** 
     * Determine whether the current user has permission to perform $privilege
     * on $resource (if $organization is not null, then $resource belongs to $organization)
     * 
     * @see User::acl()
     * 
     * @param $resource
     * @param $privilege
     * @param $organization 
     * @return bool
     */
    static function hasPrivilege($resource, $privilege, $organization = null)
    {
        $identity = Zend_Auth::getInstance()->getIdentity();
        
        // Root can do anything
        if ('root' == $identity) {
            return true;
        }
        
        // Otherwise, check the ACL
        $acl = Zend_Registry::get('acl');
        if (isset($organization)) {
            // See User::acl() for explanation of how $organization is used
            return $acl->isAllowed($identity, "$organization/$resource", $privilege);
        } else {
            return $acl->isAllowed($identity, $resource, $privilege);
        }
    }
    
    /**
     * A convenience method to ensure a user has a required privilege. This would only fail due to program
     * bugs or malicious users. 
     *  
     * @see Fisma_Acl::hasPrivilege()
     * 
     * @param $resource
     * @param $privilege
     * @param $organization
     */
    static function requirePrivilege($resource, $privilege, $organization = null)
    {
        if (!self::hasPrivilege($resource, $privilege, $organization)) {
            if (isset($organization)) {
                throw new Fisma_Exception_InvalidPrivilege("User does not have the privilege for "
                    . "($organization/$resource, $privilege)");
            } else {
                throw new Fisma_Exception_InvalidPrivilege("User does not have the privilege for "
                    . "($resource, $privilege)");
            }
        }
    }
}
