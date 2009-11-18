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
 * A User is a person who has the ability to log into the system and execute its functionality, such as viewing
 * and possibly modifying or deleting data.
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
     * Account was manually locked by an administrator
     */    
    const LOCK_TYPE_MANUAL = 'manual';

    /**
     * Account was locked due to several consecutive password failures
     */
    const LOCK_TYPE_PASSWORD = 'password';

    /**
     * Account was locked due to a period of inactivity (i.e. not logging in)
     */
    const LOCK_TYPE_INACTIVE = 'inactive';

    /**
     * Account was locked due to an expired password
     */
    const LOCK_TYPE_EXPIRED = 'expired';

    /** 
     * Account logs event type
     */
    const CREATE_USER = 'create user';
    const MODIFY_USER = 'modify user';
    const DELETE_USER = 'delete user';
    const LOCK_USER   = 'lock user';
    const UNLOCK_USER   = 'unlock user';
    const LOGIN_FAILURE = 'login failure';
    const LOGIN         = 'login';
    const LOGOUT        = 'logout';
    const ACCEPT_ROB    = 'accept rob';
    const CHANGE_PASSWORD = 'change password';
    const VALIDATE_EMAIL  = 'validate email';

    /**
     * Returns an object which represents the current, authenticated user
     * 
     * @return User
     */
    public static function currentUser() {
        if (Fisma::RUN_MODE_COMMAND_LINE != Fisma::mode()) {
            $auth = Zend_Auth::getInstance();
            $auth->setStorage(new Fisma_Auth_Storage_Session());
            return $auth->getIdentity();
        } else {
            /** @todo remove this, should either throw an excp.. or just return null */
            return new User();
        }
    }

    /**
     * construct 
     * 
     * @return void
     */
    public function construct() {
        // If the user hashType is already set, leave it alone. If not set, set the user hashType to system hashType
        $this->hashType = (empty($this->hashType)) ? Configuration::getConfig('hash_type') : $this->hashType;
    }

    /**
     * Lock an account, which will prevent a user from logging in.
     * 
     * @param string $lockType manual, password, inactive, or expired
     */
    public function lockAccount($lockType)
    {
        if (empty($lockType)) {
            throw new Fisma_Exception("Lock type cannot be blank");
        }
        $this->locked = true;
        $this->lockTs = date('Y-m-d H:i:s');
        $this->lockType = $lockType;
        $this->save();
        $this->log(self::LOCK_USER, "Account locked: $lockType");
        Notification::notify('USER_LOCKED', $this, self::currentUser());
    }
    
    /**
     * Unlock this account, which will allow a user to login again.
     */
    public function unlockAccount()
    {
        $this->locked = false;
        $this->lockTs = null;
        $this->lockType = null;
        $this->failureCount = 0;
        $this->save();
        $this->log(self::UNLOCK_USER, "Account unlocked");
    }
        
    /**
     * Verifies that this account is not locked. If it is locked, then this throws an authentication exception.
     */
    public function checkAccountLock()
    {
        if ($this->locked) {
            // Check if this is a lock which should be released
            if (self::LOCK_TYPE_PASSWORD == $this->lockType && Configuration::getConfig('unlock_enabled')) {
                $lockRemainingMinutes = $this->getLockRemainingMinutes();
                // A negative or zero value indicates the lock has expired
                if ($lockRemainingMinutes <= 0) {
                    $this->unlockAccount();
                }
            }
            
            // Construct an error message based on the lock type
            $reason = $this->getLockReason();
            throw new Fisma_Exception_AccountLocked("Account is locked ($reason)");
        }
    }
    
    /**
     * Returns the number of minutes until this account is automatically unlocked. Could be negative if the lock already
     * expired but has not actually been removed yet.
     * 
     * Throws an exception if the account is not eligible for automatic unlock (due to system configuration, or the
     * lock type on the account).
     * 
     * @return int
     */
    public function getLockRemainingMinutes()
    {
        if ($this->locked 
            && self::LOCK_TYPE_PASSWORD == $this->lockType
            && Configuration::getConfig('unlock_enabled')) {

            $lockTs = new Zend_Date($this->lockTs, Zend_Date::ISO_8601);
            $lockTs->addSecond(Configuration::getConfig('unlock_duration'));
            $now = Zend_Date::now();            
            $lockTs->sub($now);
            // ceil() so that 1 second remaining is rounded up to 1 minute, rather than rounded down to 0 minute
            // (otherwise the lock would be released early)
            $lockMinutesRemaining = ceil($lockTs->getTimestamp() / 60);
        } else {
            throw new Fisma_Exception('This account is not eligible for automatic unlock');
        }

        return $lockMinutesRemaining;
    }

    /**
     * Returns a human-readable explanation of why the account was locked
     * 
     * @return string
     */
    public function getLockReason()
    {
        switch ($this->lockType) {
            case self::LOCK_TYPE_MANUAL:
                $reason = 'by administrator';
                break;
            case self::LOCK_TYPE_PASSWORD:
                $reason = Configuration::getConfig('failure_threshold')
                        . ' failed login attempts';
                if (Configuration::getConfig('unlock_enabled')) {
                    $reason .= ', will be unlocked in '
                             . $this->getLockRemainingMinutes()
                             . ' minutes';
                }
                break;
            case self::LOCK_TYPE_INACTIVE:
                $reason = 'exceeded '
                        . Configuration::getConfig('account_inactivity_period')
                        . ' days of inactivity';
                break;
            case self::LOCK_TYPE_EXPIRED:
                $reason = 'password is more than '
                        . Configuration::getConfig('pass_expire')
                        . ' days old';
                break;
            default:
                throw new Fisma_Exception("Unexpected lock type ($this->lockType)");
                break;
        }
        
        return $reason;
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
                            $systemResource = "$organization->nickname/$privilege->resource";
                            if (!$acl->has($systemResource)) {
                                $acl->add(new Zend_Acl_Resource($systemResource));
                            }
                            $acl->allow($newRole, $systemResource, $privilege->action);
                            
                            // The wildcard resources indicates whether a user has this privilege on *any* 
                            // system. This is useful for knowing when to show certain user interface elements
                            // like menu items. The resource is named "*/finding"
                            $wildcardResource = "*/$privilege->resource";
                            if (!$acl->has($wildcardResource)) {
                                $acl->add(new Zend_Acl_Resource($wildcardResource));
                            }
                            $acl->allow($newRole, $wildcardResource, $privilege->action);                            
                        }
                    } else {
                        // Create a resource and grant it to the current role
                        if (!$acl->has($privilege->resource)) {
                            $acl->add(new Zend_Acl_Resource($privilege->resource));
                        }
                        $acl->allow($newRole, $privilege->resource, $privilege->action);
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
    public function hash($password, $hashType = null) 
    {
        $hashType   = (empty($hashType)) ? $this->hashType : $hashType;
        $hashString = $this->passwordSalt . $password;
        
        if ('sha1' == $hashType) {
            return sha1($hashString);
        } elseif ('md5' == $hashType) {
            return md5($hashString);
        } elseif ('sha256' == $hashType) {
            return mhash(MHASH_SHA256, $hashString);
        } else {
            throw new Fisma_Exception("Unsupported hash type: {$hashType}");
        }
    }

    /**
     * Validate the user's e-mail change.
     * @todo an user has multiple emails(email, notifyEmail), current database can't give the correct 
     * way to show which email is validated
     * 
     * @param string $validateCode validate code
     * @return bool
     */
    public function validateEmail($validateCode)
    {
        $emailValidation = $this->EmailValidation->getLast();
        if ($validateCode == $emailValidation->validationCode) {
            $this->emailValidate = true;
            $emailValidation->delete();
            $this->save();
            $this->log(self::VALIDATE_EMAIL, 'Email validation successful');
            return true;
        } else {
            $this->log(self::VALIDATE_EMAIL, 'Email validation failed');
            return false;
        }
    }

    /**
     * Performs house keeping that needs to run at log in
     */
    public function login()
    {
        $this->getTable()->getRecordListener()->setOption('disabled', true);

        $this->lastLoginTs = Fisma::now();
        
        $this->lastLoginIp = $this->currentLoginIp;
        $this->currentLoginIp = $_SERVER['REMOTE_ADDR'];
        
        $this->oldFailureCount = $this->failureCount;
        $this->failureCount = 0;

        $this->save();
    }

    /** 
     * Log any creation, modification, disabling and termination of account.
     *
     * @param string $event log event type
     * @param string $message log message
     */
    public function log($event, $message)
    {
        $accountLog = new AccountLog();
        if (!in_array($event, $accountLog->getTable()->getEnumValues('event'))) {
            throw new Fisma_Exception("Invalid account log event type");
        }
        $accountLog->ip      = (isset($_SERVER['REMOTE_ADDR'])) ? $_SERVER['REMOTE_ADDR'] : null;
        $accountLog->event   = $event;
        $accountLog->message = $message;
        // Assigning the ID instead of the user object prevents doctrine from calling the preSave hook on the 
        // User object
        if(isset(self::currentUser()->id)) {
            $operator = self::currentUser()->id;
        } else {
            //if the currentUser has not been set yet during login
            $operator = $this->id;
        }
        $accountLog->userId = $operator;
        $accountLog->save();
    }

    /**
     * Get user's exist events
     *
     * @return array
     */
    public function getExistEvents()
    {
        $existEvents = null;
        foreach ($this->Events as $event) {
            $existEvents[$event['id']] = $event['description'];
        }
        return $existEvents;
    }

    /**
     * Get user's available events for received notifications
     *
     * @return array
     */
    public function getAvailableEvents()
    {
        $availableEvents = null;
        if ('root' == $this->username) {
            $query = Doctrine::getTable('Event')->findAll();
        } else {
            $query = Doctrine_Query::Create()
                        ->select('e.*')
                        ->from('Event e')
                        ->innerJoin('e.Privilege p')
                        ->innerJoin('p.Role r')
                        ->innerJoin('r.Users u')
                        ->where('u.id = ?', $this->id)
                        ->orderBy('e.name')
                        ->execute();
        }
        
        foreach ($query as $event) {
            $availableEvents[$event->id] = $event->description;
        }

        $existEvents = $this->getExistEvents();
        if (!empty($existEvents)) {
            $availableEvents = array_diff($availableEvents, $existEvents);
        }
        return $availableEvents;
    }
    
    /**
     * Generate a random password salt for this user
     */
    public function generateSalt() {
        /** @todo remove contstant value 10, which is the length of the salt. */
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz1234567890';
        $length = strlen($chars) - 1;
        $salt = '';
        for ($i = 1; $i <= 10; $i++) {
            $salt .= $chars{rand(0, $length)};
        }
        $this->passwordSalt = $salt;
    }

    /**
     * Get the user's organizations.
     * 
     * Unlike using $this->Organizations, this method implements the correct business logic for the root user,
     * who won't have any joins in the UserOrganization model, but should still have access to all organizations
     * anyway.
     * 
     * @return Doctrine_Collection
     */
    public function getOrganizations() 
    {
        $query = $this->getOrganizationsQuery();
        $result = $query->execute();
        
        return $result;
    }
    
    /**
     * Get a query which will select this user's organizations.
     * 
     * This could be useful if you want to do something more advanced with the user's organizations,
     * such as using aggregation functions or joining to another model. You can extend the query returned
     * by this function to do so.
     * 
     * @return Doctrine_Query
     */
    public function getOrganizationsQuery()
    {
        // The base query grabs all organizations and sorts by 'lft', which will put the records into 
        // tree order.
        $query = Doctrine_Query::create()
                 ->from('Organization o')
                 ->orderBy('o.lft');
        
        // For all users other than root, we want to join to the user table to limit the systems returned
        // to those which this user has been granted access to.
        if ('root' != $this->username) {
            $query->innerJoin('o.Users u')
                  ->where('u.id = ?', $this->id);
        } 
        
        return $query;      
    }
}
