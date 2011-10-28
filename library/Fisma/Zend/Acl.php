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
 * @subpackage Fisma_Zend_Acl
 */
class Fisma_Zend_Acl extends Zend_Acl
{
    /**
     * to hold the username of the user creating $this
     * 
     * @var string
     */
    private $_username;

    /**
     * should not be called directly (except for testing purposes)
     * as the logic is for each User object to create its own Acl object
     * 
     * @param string $username
     * 
     */
    public function __construct($username)
    {
        $this->_username = $username;
    }
    /**
     * Check whether the current user has access to the specified area in OpenFISMA
     * 
     * @param string $area
     * @return bool
     */
    public function hasArea($area)
    {
        return $this->isAllowed(null, 'area', $area);
    }
    
    /**
     * Require the current user to have access to the specified area, or else throw an exception
     * 
     * @param string $area
     * @throws Fisma_Zend_Exception_InvalidPrivilege
     */
    public function requireArea($area)
    {
        if (!$this->hasArea($area)) {
            throw new Fisma_Zend_Exception_InvalidPrivilege("User does not have access to this area: '$area'");
        }
    }
    
    /**
     * Check whether the current user has a particular privilege on a particular object
     * 
     * This method checks to see if the object has an ACL dependency on a particular organization, and adjusts the ACL
     * query accordingly.
     * 
     * Privilege is allowed to contain a wildcard character, '*', which indicates that it could match ANY one of 
     * multiple, similar privileges.
     * 
     * @see Fisma_Zend_Acl_OrganizationDependency
     * 
     * @param string $privilege
     * @param object $object
     * @return bool
     */
    public function hasPrivilegeForObject($privilege, $object)
    {
        // Safety check: make sure that $object is actually an object
        if (!is_object($object)) {
            throw new Fisma_Zend_Exception("\$object is not an object");
        }
        $resourceName = Doctrine_Inflector::tableize(get_class($object));               
        $hasPrivilege = false;
        
        if (!$this->_privilegeContainsWildcard($privilege)) {
            // Handle objects with organization ACL dependency
            if ($object instanceof Fisma_Zend_Acl_OrganizationDependency) {
                $orgId = $object->getOrganizationDependencyId();
                if (empty($orgId)) {
                    $privilege = "unaffiliated";
                    $resourceName = "asset";
                } else {
                    $resourceName = self::getResourceNameForOrganizationDependency($resourceName, $orgId);
                }
            }
            $hasPrivilege = $this->hasPrivilegeForClass($privilege, $resourceName, true);
        } else {

            // Loop over all matching privileges and check them one-by-one to see if the user has any of them
            $matchedPrivileges = $this->_getPrivilegesForWildcard($resourceName, $privilege);
            foreach ($matchedPrivileges as $matchedPrivilege) {
                if ($this->hasPrivilegeForObject($matchedPrivilege, $object)) {
                    $hasPrivilege = true;
                    break;
                }
            }
        }
        return $hasPrivilege;
    }

    /**
     * Method to format correct $resourceName for objects with an organizational dependency
     */
    public static function getResourceNameForOrganizationDependency($resourceName, $organizationId)
    {
        return $organizationId.'/'.$resourceName;
    }
   
    /**
     * Require the current user to have a particular privilege on a particular object, or else throw an exception
     * 
     * @param string $privilege
     * @param object $object
     * @throws Fisma_Zend_Exception_InvalidPrivilege
     */
    public function requirePrivilegeForObject($privilege, $object, $user = null)
    {
        if (!$this->hasPrivilegeForObject($privilege, $object, $user)) {
            $message = "User does not have privilege '$privilege' for this object.";
            throw new Fisma_Zend_Exception_InvalidPrivilege($message);
        }
    }
    
    /**
     * Check whether a user has a particular privilege on a class of objects
     * 
     * Privilege is allowed to contain a wildcard character, '*', which indicates that it could match any one of 
     * multiple privileges.
     * 
     * @param string $privilege
     * @param string $className
     * @return bool
     */
    public function hasPrivilegeForClass($privilege, $className, $useClassNameAsResourceName = false)
    {
        if ($useClassNameAsResourceName) {
            $resourceName = $className;
        } else {
            // Safety check: make sure that $className is an actual class
            if (!class_exists($className)) {
                $message = "Privilege check failed for class '$className' because the class could not be found";
                throw new Fisma_Zend_Exception($message);
            }
            $resourceName = Doctrine_Inflector::tableize($className);
        }
        if (!$this->_privilegeContainsWildcard($privilege)) {
            $hasPrivilege = $this->isAllowed(null, $resourceName, $privilege);
        } else {
            // Loop over all matching privileges and check them one-by-one to see if the user has any of them
            $matchedPrivileges = $this->_getPrivilegesForWildcard($resourceName, $privilege);

            foreach ($matchedPrivileges as $matchedPrivilege) {
                if ($this->hasPrivilegeForClass($matchedPrivilege, $className, $useClassNameAsResourceName)) {
                    $hasPrivilege = true;
                    break;
                }
            }
        }
        return $hasPrivilege;
    }
    
    /**
     * Require the current user to have a particular privilege on a particular class of objects, or else throw an exception
     * 
     * @param string $privilege
     * @param string $className
     * @throws Fisma_Zend_Exception_InvalidPrivilege
     */
    public function requirePrivilegeForClass($privilege, $className)
    {
        if (!$this->hasPrivilegeForClass($privilege, $className)) {
            $message = "User does not have privilege '$privilege' for class '$className'";
            throw new Fisma_Zend_Exception_InvalidPrivilege($message);
        }
    }
    
    /**
     * A wrapper to the ACL isAllowed() method which catches Zend_Acl_Exception
     * 
     * This is an unfortunate hack, because Zend_Acl throws an exception if you query a resources that doesn't exist.
     * 
     * @param User $role    should not be provided, but kept for overridding parent::isAllowed()
     * @param string $resource
     * @param string $privilege
     */
    public function isAllowed($role = null, $resource = null, $privilege = null)
    {
        $username = $this->_username;
        if (isset($role)) { // backward compatible
            if (!is_object($role)) {
                return false;
            }
            $username = $role->username;
        }
        
        // Root can do anything
        if ('root' == $username) {
            return true;
        }
        
        try {
            return parent::isAllowed($username, $resource, $privilege);
        } catch (Zend_Acl_Exception $e) {
            return false;
        }
    }
    
    /**
     * Search for privileges matching a given resource and a privilege name which contains a wildcard '*' character
     * 
     * @param string $resource
     * @param string $privilege
     * @return array Array of matched privilege names
     */
    private function _getPrivilegesForWildcard($resource, $privilege)
    {
        // Convert the * wildcard into a SQL % wildcard
        $privilegeMatchString = str_replace('*', '%', $privilege);
        
        $privilegeQuery = Doctrine_Query::create()
                          ->select('action')
                          ->from('Privilege INDEXBY action')
                          ->where('resource LIKE ?', $resource)
                          ->andWhere('action LIKE ?', $privilegeMatchString)
                          ->setHydrationMode(Doctrine::HYDRATE_ARRAY);
        
        $resultSet = $privilegeQuery->execute();

        return array_keys($resultSet);
    }
    
    /**
     * Returns true if the specified privilege name contains a wildcard character (*)
     * 
     * @param string $privilege
     * @return bool
     */
    private function _privilegeContainsWildcard($privilege)
    {
        return strpos($privilege, '*') !== false;
    }
    
}
