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
 * @package   Model
 */

/**
 * Handles CRUD for "user" objects.
 *
 * @package   Model
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/license.php
 */
class User extends BaseUser
{

    /**
     * Check the password whether correctly
     * 
     * @param string $password
     * @return boolean
     */
    public function login($password) 
    {
        if ($this->password == $this->_hash($password)) {
            return true;
        } else {
            return false;
        }
    }
    
    /**
     * Lock an account, which will prevent a user from logging in.
     * 
     * @param string $lockType manual, password, inactive, or expired
     */
    public function lock($lockType)
    {
        if (empty($lockType)) {
            throw new Fisma_Exception_General("Cannot lock an account with out an account type");
        }
        
        $this->locked = true;
        $this->lockTs = new Zend_Date();
        $this->lockType = $lockType;
        $this->save();
    }
    
    /**
     * Unlock this account, which will allow a user to login again.
     */
    public function unlock()
    {
        $this->locked = false;
        $this->lockTs = null;
        $this->lockType = null;
        $this->failureCount = 0;
        $this->save();
    }

    /**
     * Returns this user's access control list (ACL) object. It will initialize the ACL first,
     * if necessary.
     * 
     * OpenFISMA authorization allows a user to possess one role across a range of information
     * systems. In order to translate this into an ACL, OpenFISMA produces the equivalent of a
     * cartesian join between the roles and systems table. In the future, roles will be assigned
     * to individual systems.
     * 
     * @todo Create separate roles for separate systems. This requires the user interface to be 
     * upgraded to make it possible to configure this.
     * 
     * Example ACL tree for a user who has ISSO access to system 1 & 2, and ADMIN access to system 1:
     * <pre>
     * username
     *   ISSO
     *     dashboard
     *       read
     *     system1
     *       finding
     *         create
     *         update
     *     system2
     *       finding
     *         create
     *         update
     *   ADMIN
     *     system1
     *       finding
     *         delete
     * </pre>
     * 
     * @return Zend_Acl
     */
    public function acl()
    {
        if (!Zend_Registry::isRegistered('acl')) {
            $acl = new Fisma_Acl();
            
            // For each role, add its privileges to the ACL
            $roleArray = array();
            foreach ($this->Roles as $role) {
                // Roles are stored by role.nickname, e.g. "ADMIN", which are guaranteed to be unique
                $newRole = new Zend_Acl_Role($role->nickname);
                $acl->addRole($newRole);
                $roleArray[] = $role->nickname;
                
                foreach ($role->Privileges as $privilege) {
                    // If a privilege is organization-specific, then grant it as a nested resource within
                    // that organization, otherwise, grant it directly to that resource.
                    // e.g. Organization->Finding->Create versus Network->Create
                    if ($privilege->orgSpecific) {
                        // This is the cartesian join between roles and systems
                        foreach ($this->Organizations as $organization) {
                            // Fake hierarhical access control by storing system-specific attributes like this:
                            // If the system nickname is "ABC" and the resource is called "finding", then the
                            // resource stored in the ACL is called "ABC/finding"
                            $newResource = new Zend_Acl_Resource("$organization->nickname/$privilege->resource");
                            $acl->add($newResource);
                            $acl->allow($newRole, $newResource, $privilege->action);
                        }
                    } else {
                        // Create a resource and grant it to the current role
                        $newResource = new Zend_Acl_Resource($privilege->resource);
                        $acl->add($newResource);
                        $acl->allow($newRole, $newResource, $privilege->action);
                    }
                }
            }

            // Create a role for this user that inherits all of the roles created above
            $userRole = new Zend_Acl_Role($this->username);
            $acl->addRole($userRole, $roleArray);

            Zend_Registry::set('acl', $acl);
        }
        
        return Zend_Registry::get('acl');
    }
    
    /**
     * hash the password by different type
     *
     * @param string $password
     * @param string $hashType
     * @return the hash password
     * @throws Fisma_Exception_General
     */
    private function _hash($password) 
    {
        if (empty($hashType)) {
            $hashType = $this->hashType;
        }
        
        $hashTypes = $this->getTable(get_class($this))->getColumnDefinition('hashtype');
        
        if (!in_array($hashType, $hashTypes['values'])) {
            throw new Fisma_Exception_General("Unknown hash type: {$hashType}");
        }
        
        if ('sha1' == $hashType) {
            return sha1($password);
        } elseif ('md5' == $hashType) {
            return md5($password);
        } elseif ('sha256' == $hashType) {
            return mhash(MHASH_SHA256, $password);
        } else {
            throw new Fisma_Exception_General("Unsupported hash type: {$hashType}");
        }
    }
    
}
