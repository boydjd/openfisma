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
 * Provides access control primitives
 * 
 * @author     Mark E. Haase <mhaase@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Fisma
 * @subpackage Fisma_Acl
 * @version    $Id$
 */
class Fisma_Acl extends Zend_Acl
{
    /**
     * Check whether the current user has access to the specified area in OpenFISMA
     * 
     * @param string $area
     * @return bool
     */
    static public function hasArea($area)
    {
        $user = Zend_Auth::getInstance()->getIdentity();
        
        return self::_isAllowed($user, 'area', $area);
    }
    
    /**
     * Require the current user to have access to the specified area, or else throw an exception
     * 
     * @param string $area
     * @throws Fisma_Exception_InvalidPrivilege
     */
    static public function requireArea($area)
    {
        if (!self::hasArea($area)) {
            throw new Fisma_Exception_InvalidPrivilege("User does not have access to this area: '$area'");
        }
    }
    
    /**
     * Check whether the current user has a particular privilege on a particular object
     * 
     * This method checks to see if the object has an ACL dependency on a particular organization, and adjusts the ACL
     * query accordingly.
     * 
     * @see Fisma_Acl_OrganizationDependency
     * 
     * @param string $privilege
     * @param object $object
     * @return bool
     */
    static public function hasPrivilegeForObject($privilege, $object)
    {
        // Safety check: make sure that $object is actually an object
        if (!is_object($object)) {
            throw new Fisma_Exception("\$object is not an object");
        }

        $user = Zend_Auth::getInstance()->getIdentity();
        $resourceName = Doctrine_Inflector::tableize(get_class($object));

        // Handle objects with organization ACL dependency
        if ($object instanceof Fisma_Acl_OrganizationDependency) {
            $organizationId = $object->getOrganizationDependencyId();
            $resourceName = "$organizationId/$resourceName";
        }

        return self::_isAllowed($user, $resourceName, $privilege);
    }
    
    /**
     * Require the current user to have a particular privilege on a particular object, or else throw an exception
     * 
     * @param string $privilege
     * @param object $object
     * @throws Fisma_Exception_InvalidPrivilege
     */
    static public function requirePrivilegeForObject($privilege, $object)
    {
        if (!self::hasPrivilegeForObject($privilege, $object)) {
            $message = "User does not have privilege '$privilege' for this object.";
            throw new Fisma_Exception_InvalidPrivilege($message);
        }
    }
    
    /**
     * Check whether a user has a particular privilege on a class of objects
     * 
     * @param string $privilege
     * @param string $className
     * @return bool
     */
    static public function hasPrivilegeForClass($privilege, $className)
    {
        // Safety check: make sure that $className is an actual class
        if (!class_exists($className)) {
            $message = "Privilege check failed for class '$className' because the class could not be found";
            throw new Fisma_Exception($message);
        }
        
        $user = Zend_Auth::getInstance()->getIdentity();
        $resourceName = Doctrine_Inflector::tableize($className);

        return self::_isAllowed($user, $resourceName, $privilege);
    }
    
    /**
     * Require the current user to have a particular privilege on a particular class of objects, or else throw an 
     * exception
     * 
     * @param string $privilege
     * @param string $className
     * @throws Fisma_Exception_InvalidPrivilege
     */
    static public function requirePrivilegeForClass($privilege, $className)
    {
        if (!self::hasPrivilegeForClass($privilege, $className)) {
            $message = "User does not have privilege '$privilege' for class '$className'";
            throw new Fisma_Exception_InvalidPrivilege($message);
        }
    }
    
    /**
     * A wrapper to the ACL isAllowed() method which catches Zend_Acl_Exception
     * 
     * This is an unfortunate hack, because Zend_Acl throws an exception if you query a resources that doesn't exist.
     * 
     * @todo is there a better way to handle this?
     * 
     * @param User $user
     * @param string $resourceName
     * @param string $privilege
     */
    static private function _isAllowed($user, $resourceName, $privilege)
    {
        // Root can do anything
        if ('root' == $user->username) {
            return true;
        }
        
        try {
            return User::currentUser()->acl()->isAllowed($user->username, $resourceName, $privilege);
        } catch (Zend_Acl_Exception $e) {
            return false;
        }
    }
}
