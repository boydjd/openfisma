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
     * Returns an object which represents the current, authenticated user
     * If the $user is current User, then return this object instead of create a new one.
     * 
     * @param Doctrine_Record $user 
     * @return User
     */
    public static function currentUser($user = null) {
        if (Fisma::RUN_MODE_COMMAND_LINE != Fisma::mode()) {
            $auth = Zend_Auth::getInstance();
            $auth->setStorage(new Fisma_Auth_Storage_Session());
            $identity = $auth->getIdentity();
            if ($user instanceof Doctrine_Record && $user->username == $identity) {
                $identity = $user;
            } elseif ($identity ) {
                $identity = Doctrine::getTable('User')->findonebyUsername($identity);
            }
            return $identity;
        } else {
            return new User();
        }
    }
    
    public function isOperator() {
        if (Fisma::RUN_MODE_COMMAND_LINE != Fisma::mode()) {
            $auth = Zend_Auth::getInstance();
            $auth->setStorage(new Fisma_Auth_Storage_Session());
            $identity = $auth->getIdentity();
            if ($identity) {
                $identity = Doctrine::getTable('User')->findonebyUsername($identity);
            }
            return $identity;
        } else {
            return true;
        }
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
        /** @todo english */
        $this->log("Account unlocked by $lockType");
        Notification::notify(Notification::USER_LOCKED, $this, self::currentUser());
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
        /** @todo english */
        $this->log("Account unlocked");

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
        if (empty($hashType)) {
            $hashType = Configuration::getConfig('hash_type');
        }

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
            //@todo english,aslo see the follow
            $this->log('Email validate successfully');
            return true;
        } else {
            $this->log('Email validate faild');
            return false;
        }
    }

    /**
     * Validate the credential
     *
     * @param string $password 
     * @return bool
     */
    public function login($password)
    {
        if (Fisma::RUN_MODE_COMMAND_LINE == Fisma::mode()) {
            throw new Fisma_Exception("Login is not allowed in command line mode");
        }
        
        $loginRet = false;
        $this->getTable()->getRecordListener()->setOption('disabled', true);
        if ($this->password == $this->hash($password)) {
            $this->lastLoginTs = Fisma::now();
            $this->lastLoginIp = $this->currentLoginIp;
            $this->currentLoginIp = $_SERVER['REMOTE_ADDR'];
            $this->oldFailureCount = $this->failureCount;
            $this->failureCount = 0;
            //@todo english, also see the follow
            $this->log("Login successfully");
            Notification::notify(Notification::USER_LOGIN_SUCCESS, $this, self::currentUser());
            $loginRet = true;
        } else {
            $this->failureCount++;
            if ($this->failureCount > Configuration::getConfig('failure_threshold')) {
                $this->lockAccount(User::LOCK_TYPE_PASSWORD);
            }
            $this->log("Login failure");
            Notification::notify(Notification::USER_LOGIN_FAILURE, $this, self::currentUser());
        }
        $this->save();
        return $loginRet;
    }

    /**
     * Close out the current user's session
     */
    public function logout()
    {
        if (Fisma::RUN_MODE_COMMAND_LINE == Fisma::mode()) {
            throw new Fisma_Exception("Logout is not allowed in command line mode");
        }
        
        $this->log('Log out');
        Zend_Auth::getInstance()->clearIdentity();
    }

    /** 
     * Log any creation, modification, disabling and termination of account.
     *
     * @param string $message log message
     */
    public function log($message)
    {
        $accountLog = new AccountLog();
        $accountLog->ip = $_SERVER["REMOTE_ADDR"];
        $accountLog->message = $message;
        // Assigning the ID instead of the user object prevents doctrine from calling the preSave hook on the 
        // User object
        $accountLog->userId = $this->id;
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
            $existEvents[$event['id']] = $event['name'];
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
            $availableEvents[$event->id] = $event->name;
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

}
