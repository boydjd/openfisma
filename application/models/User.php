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
     * The name of the cookie in which the search preference bitmask is stored.
     */
    const SEARCH_PREF_COOKIE = 'search_columns_pref';

    /**
     * Attempt to login a user with the specified credentials. If successful, then
     * this user credential will be registered in the current session.
     * 
     * @param string $username
     * @param string $password
     */
    public static function login($username, $password) 
    {
        $user = Doctrine_Query::create()
                ->from('User u')
                ->where('u.username LIKE ?', $username)
                ->limit(1)
                ->execute();
        $now = new Zend_Date();

        // If the username isn't found, throw an exception
        if ($user->count() == 0) {
            /** @doctrine fix the logging function */
            //$this->_user->log('LOGINFAILURE', '', 'Failure');
            // Notice that we don't tell the user whether the username is correct or not.
            // This is a security feature to prevent bruteforcing usernames.
            throw new Zend_Auth_Exception("Incorrect username or password");
        }
        $user = $user[0];
        
        // If the system is using database authentication and the account is locked, then check to see
        // what the reason for the lock was and whether it can be unlocked automatically.
        if ('database' == Fisma_Controller_Front::readSysConfig('auth_type') && $user->locked) {
            $result = $user->_checkAccountLock();
            if ($result !== true) {
                // The account is still locked
                throw new Zend_Auth_Exception($result);
            }
        }
        
        // Proceed through authentication based on the configured mechanism (LDAP, Database, etc.)
        $result = $user->_authenticate($username, $password);

        // If authentication succeeds, then store the authenticated user in the registry. Otherwise,
        // throw an Exception.
        if ($result->isValid()) {
            Zend_Registry::set('authenticatedUser', $user);
        } else {
            $this->failureCount++;
            $this->save();
            if ($this->failureCount > Fisma_Controller_Front::readSysConfig('auth_type')) {
                $this->_lockAccount('password');
            }
            /** @doctrine fix logging */
            //$this->_user->log('LOGINFAILURE',$whologin['id'],'Failure');
            throw new Zend_Auth_Exception("Incorrect username or password");
        }
        
        // At this point, the user is authenticated.
        // Now check if the account has expired.
        $user->_checkAccountExpiration();
        
        // Set cookie for 'column manager' to control the columns whether visible
        // Persistent cookies are prohibited on U.S. government web servers by federal law. 
        // This cookie will expire at the end of the session.
        setcookie(self::SEARCH_PREF_COOKIE, $user->searchColumnsPref, false, '/');

        // Set up the session timeout for the authentication token
        $store = Zend_Auth::getInstance()->getStorage();
        $sessionExpiration = new Zend_Session_Namespace($store->getNamespace());
        $sessionExpiration->setExpirationSeconds(Fisma_Controller_Front::readSysConfig('expiring_seconds'));
        
        // Check password expiration
        $user->_checkPasswordExpiration();
        
        /** @doctrine write to log and create notification */
        //$this->_user->log('LOGIN', $_me->id, "Success");
        
        return $user;
    }
    
    /**
     * Lock an account, which will prevent a user from logging in.
     * 
     * @param string $lockType manual, password, inactive, or expired
     */
    private function _lockAccount($lockType)
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
    private function _unlockAccount()
    {
        $this->locked = false;
        $this->lockTs = null;
        $this->lockType = null;
        $this->failureCount = 0;
        $this->save();
    }
    
    /**
     * Check to see if the current account lock can be removed, or if not return a message
     * indicating why the account is locked.
     * 
     * @return string|boolean Returns true if account was unlocked, returns a message otherwise
     */
    private function _checkAccountLock()
    {
        if ($this->lockType == 'manual') {
            return 'Your account has been locked by an Administrator. '
                 . 'Please contact the'
                 . ' <a href="mailto:'
                 . Fisma_Controller_Front::readSysConfig('contact_email')
                 . '">Administrator</a>.';
        } elseif ($this->lockType == 'password') {
            // If this system is configured to let accounts unlock automatically,
            // then check whether it can be unlocked now
            if (Fisma_Controller_Front::readSysConfig('unlock_enabled') == 1) {
                $unlockTs = new Zend_Date($this->lockTs);
                $unlockTs->add(Fisma_Controller_Front::readSysConfig('unlock_duration'), Zend_Date::SECOND);
                $now = new Zend_Date();
                if ($unlockTs->isLater($now)) {
                    $unlockTs->sub($now);
                    return 'Your user account has been locked due to '
                          . Fisma_Controller_Front::readSysConfig('failure_threshold')
                          . ' or more unsuccessful login attempts. Your account will be unlocked in '
                          . ceil($unlockTs->getTimestamp()/60)
                          . ' minutes. Please try again at that time.<br>'
                          . ' You may also contact the Administrator for further assistance.';
                } else {
                    $this->unlockAccount();
                    return true;
                }
            } else {
                return 'Your user account has been locked due to '
                     . Fisma_Controller_Front::readSysConfig('failure_threshold')
                     . ' or more unsuccessful login attempts. Please contact the <a href="mailto:'
                     . Fisma_Controller_Front::readSysConfig('contact_email')
                     . '">Administrator</a>.';
            }
        } elseif ($this->lockType == 'inactive') {
            return 'Your account has been locked automatically because you have not '
                 . 'not logged in over '
                 . Fisma_Controller_Front::readSysConfig('max_absent_time')
                 . ' days.';
        } elseif ($this->lockType == 'expired') {
            return 'Your account has been locked automatically because you have not '
                 . 'changed your password in over '
                 . Fisma_Controller_Front::readSysConfig('pass_expire')
                 . ' days.';
        } else {
            throw new Exception("Undefined account lock type");
        }
    }

    /**
     * Check to see if this account has expired. Accounts expire when a user has not logged within a 
     * certain number of days (configurable by the Admin)
     */
    private function _checkAccountExpiration() 
    {
        $expirationPeriod = Fisma_Controller_Front::readSysConfig('max_absent_time');
        $expirationDate = new Zend_Date();
        $expirationDate->sub($expirationPeriod, Zend_Date::DAY);
        $lastLogin = new Zend_Date($this->lastLoginTs, 'YYYY-MM-DD HH-MI-SS');

        if ( !$lastLogin->equals(new Zend_Date('0000-00-00 00:00:00')) && $lastLogin->isEarlier($expirationDate) ) {
            $this->_lockAccount('inactive');
            /** @doctrine fix logging */
            //$this->_user->log('ACCOUNT_LOCKOUT', $_me->id, "User Account $_me->account Successfully Locked");
            throw new Zend_Auth_Exception('Your account has been locked because you have not logged in for '
                . $expirationPeriod
                . ' or more days. Please contact the <a href=\"mailto:'
                . Fisma_Controller_Front::readSysConfig('contact_email')
                . '">Administrator</a>.');
        } 
    }
    
    /**
     * Check to see if the user's password has expired.
     */
    private function _checkPasswordExpiration()
    {
        $passExpirePeriod = Fisma_Controller_Front::readSysConfig('pass_expire');
        $passExpireTs = new Zend_Date($this->passwordTs);
        $passExpireTs->add($passExpirePeriod, Zend_Date::DAY);
        
        if ($passExpireTs->isEarlier(new Zend_Date())) {
            $this->_lockAccount('expired');
            /** @doctrine fix logging */
            //$this->_user->log('ACCOUNT_LOCKOUT',$_me->id,"User Account $_me->account Successfully Locked");
            throw new Zend_Auth_Exception('Your user account has been locked because you have not'
                . " changed your password for $passExpirePeriod or more days."
                . ' Please contact the '
                . ' <a href="mailto:'. Fisma_Controller_Front::readSysConfig('contact_email')
                . '">Administrator</a>.');

        }
    }

    /**
     * authenticate() - Authenticate the user against LDAP or backend database.
     *
     * @param string $username
     * @param string $password
     * @return Zend_Auth_Result
     */
    private function _authenticate($username, $password) {
        $db = Zend_Registry::get('db');
        $authType = Fisma_Controller_Front::readSysConfig('auth_type');

        // The root user is always authenticated against the database.
        if ($username == 'root') {
            $authType = 'database';
        }

        // Handle LDAP or database authentication for non-root users.
        if ($authType == 'ldap') {
            $config = new Config();
            $data = $config->getLdap();
            $authAdapter = new Zend_Auth_Adapter_Ldap($data, $username, $password);
        } else if ($authType == 'database') {
            $authAdapter = new Zend_Auth_Adapter_DbTable($db, 'user', 'username', 'password');
            $digestPass = $this->_hash($password);
            $authAdapter->setIdentity($username)->setCredential($digestPass);
        }

        $auth = Zend_Auth::getInstance();
        return $auth->authenticate($authAdapter);
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
     * Generate the hash of a password
     *
     * @param string $password
     * @param string $hashType The hash type to use. If null, then use the user's existing password hash type.
     * @return string
     */
     private function _hash($password, $hashType = null) 
     {
         if (empty($hashType)) {
             $hashType = $this->hashType;
         }
        
         if ('sha1' == $hashType) {
             return sha1($password);
         } else {
             throw new Fisma_Exception_General("Unknown hash type: {$hashType}");
         }
     }
}
