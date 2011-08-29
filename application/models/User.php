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
 * A User is a person who has the ability to log into the system and execute its functionality, such as viewing
 * and possibly modifying or deleting data.
 *
 * @author     Mark E. Haase <mhaase@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Model
 */
class User extends BaseUser
{
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
     * The mininum number of unique passwords required before an old password can be reused
     */
    const PASSWORD_HISTORY_LIMIT = 3;

    /**
     * Doctrine hook which is used to set up mutators
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();

        $this->hasMutator('lastRob', 'setLastRob');
        $this->hasMutator('password', 'setPassword');
    }

    /**
     * Hook into the constructor to map the plainTextPassword field
     */
    public function construct()
    {
        /*
         * This mapped value is not persistent, but it will be stored in memory for the duration of the request
         * whenever the password is modified.
         */
        $this->mapValue('plainTextPassword');
    }

    /**
     * Lock an account, which will prevent a user from logging in.
     *
     * @param string $lockType One specified lock type from manual, password, inactive, or expired
     * @return void
     */
    public function lockAccount($lockType)
    {
        if (empty($lockType)) {
            throw new Fisma_Zend_Exception("Lock type cannot be blank");
        }

        $this->locked = true;
        $this->lockTs = Fisma::now();
        $this->lockType = $lockType;
        $this->save();

        // Invalidating the ACL will make the lock effective on the next page refresh. Otherwise the user
        // would be able to continue using the app for the rest of his session.
        if ($this->locked) {
            $this->invalidateAcl();
        }

        // If the account is locked due to password failure, etc., then there is no current user. In that case, we log
        // with a null user and include the remote IP address in the log entry instead.
        if (CurrentUser::getInstance()) {
            $message = 'Locked: ' . $this->getLockReason();
            $this->getAuditLog()->write($message);
        } else {
            $message = 'Locked by unknown user ('
                    . $_SERVER['REMOTE_ADDR']
                    . '): '
                . $this->getLockReason();
            $this->getAuditLog()->write($message);
        }

        Notification::notify('USER_LOCKED', $this, CurrentUser::getInstance());
    }

    /**
     * Unlock this account, which will allow a user to login again.
     *
     * @return void
     */
    public function unlockAccount()
    {
        // If the account is expired, then set lastLoginTs to null so that the user will be able to log in again
        $expirationPeriod = Fisma::configuration()->getConfig('account_inactivity_period');
        $accountExpiration = new Zend_Date($this->lastLoginTs, Zend_Date::ISO_8601);
        $accountExpiration->addDay($expirationPeriod);
        $now = Zend_Date::now();

        if ($accountExpiration->isEarlier($now)) {
            $this->lastLoginTs = null;
        }

        // Update remaining lock fields
        $this->locked = false;
        $this->lockTs = null;
        $this->lockType = null;
        $this->failureCount = 0;

        $this->save();

        // If the account is unlocked automatically (such as an expired lock), then there may not be an authenticated
        // user. In that case, the user is null and the IP address is included
        if (CurrentUser::getInstance()) {
            $this->getAuditLog()->write('Unlocked');
        } else {
            $message = 'Unlocked by unknown user ('
                    . $_SERVER['REMOTE_ADDR']
                    . ')';
            $this->getAuditLog()->write($message);
        }
    }

    /**
     * Verifies that this account is not locked. If it is locked, then this throws an authentication exception.
     *
     * @return void
     * @throws Fisma_Zend_Exception_AccountLocked if the account is locked
     */
    public function checkAccountLock()
    {
        if ($this->locked) {
            // Check if this is a lock which should be released
            if (self::LOCK_TYPE_PASSWORD == $this->lockType && Fisma::configuration()->getConfig('unlock_enabled')) {
                $lockRemainingMinutes = $this->getLockRemainingMinutes();
                // A negative or zero value indicates the lock has expired
                if ($lockRemainingMinutes <= 0) {
                    $this->unlockAccount();
                }
            }

            // Construct an error message based on the lock type
            $reason = $this->getLockReason();
            throw new Fisma_Zend_Exception_AccountLocked("Account is locked ($reason)");
        }
    }

    /**
     * Returns the number of minutes until this account is automatically unlocked. Could be negative if the lock already
     * expired but has not actually been removed yet.
     *
     * Throws an exception if the account is not eligible for automatic unlock (due to system configuration, or the
     * lock type on the account).
     *
     * @return int The remaining minutes to be unlocked automatically
     * @throws Fisma_Zend_Exception if the account is not eligible for automatic unlock
     */
    public function getLockRemainingMinutes()
    {
        if ($this->locked
                && self::LOCK_TYPE_PASSWORD == $this->lockType
                && Fisma::configuration()->getConfig('unlock_enabled')) {

            $lockTs = new Zend_Date($this->lockTs, Zend_Date::ISO_8601);
            $lockTs->addMinute(Fisma::configuration()->getConfig('unlock_duration'));
            $now = Zend_Date::now();
            $lockTs->sub($now);
            // ceil() so that 1 second remaining is rounded up to 1 minute, rather than rounded down to 0 minute
            // (otherwise the lock would be released early)
            $lockMinutesRemaining = ceil($lockTs->getTimestamp() / 60);
        } else {
            throw new Fisma_Zend_Exception('This account is not eligible for automatic unlock');
        }

        return $lockMinutesRemaining;
    }

    /**
     * Returns a human-readable explanation of why the account was locked
     *
     * @return string The human-readable explanation of why the account was locked
     * @throws Fisma_Zend_Exception if the lock type is unexcepted
     */
    public function getLockReason()
    {
        switch ($this->lockType) {
            case self::LOCK_TYPE_MANUAL:
                $reason = 'by administrator';
                break;
            case self::LOCK_TYPE_PASSWORD:
                $reason = Fisma::configuration()->getConfig('failure_threshold')
                    . ' failed login attempts';
                if (Fisma::configuration()->getConfig('unlock_enabled')) {
                    $reason .= ', will be unlocked in '
                        . $this->getLockRemainingMinutes()
                        . ' minutes';
                }
                break;
            case self::LOCK_TYPE_INACTIVE:
                $reason = 'exceeded '
                    . Fisma::configuration()->getConfig('account_inactivity_period')
                    . ' days of inactivity';
                break;
            case self::LOCK_TYPE_EXPIRED:
                $reason = 'password is more than '
                    . Fisma::configuration()->getConfig('pass_expire')
                    . ' days old';
                break;
            default:
                throw new Fisma_Zend_Exception("Unexpected lock type ($this->lockType)");
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
     * @return Zend_Acl The Fisma ACL
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
     */
    public function acl()
    {
        $cache = $this->_getCache();

        if (!$acl = $cache->load(md5($this->username) . '_acl')) {
            // Refresh the user object to ensure latest user's data is reloaded from database since there are probably
            // differences between the user object in session and database after the user is updated.
            $this->refresh();
            if ($this->locked) {
                $reason = $this->getLockReason();
                throw new Fisma_Zend_Exception_InvalidAuthentication("Account is locked ($reason)");
            }

            // Temporarily disable class loading warnings
            $classLoader = Zend_Loader_Autoloader::getInstance();
            $suppressWarningsOriginalValue = $classLoader->suppressNotFoundWarnings();
            $classLoader->suppressNotFoundWarnings(true);

            $acl = new Fisma_Zend_Acl();

            // For each role, add its privileges to the ACL
            $roleArray = array();
            foreach ($this->Roles as $role) {
                // Roles are stored by role.nickname, e.g. "ADMIN", which are guaranteed to be unique
                $newRole = new Zend_Acl_Role($role->nickname);
                $acl->addRole($newRole);
                $roleArray[] = $role->nickname;

                foreach ($role->Privileges as $privilege) {
                    /**
                     * Check whether this privilege corresponds to a class, and if it does, then check whether that
                     * class has an organization dependency
                     */
                    $organizationDependency = false;
                    $className = Doctrine_Inflector::classify($privilege->resource);

                    if (class_exists($className)) {
                        $reflection = new ReflectionClass($className);
                        $organizationDependency = $reflection->implementsInterface(
                            'Fisma_Zend_Acl_OrganizationDependency'
                        );
                    }

                    // If a privilege is organization-specific, then grant it as a nested resource within
                    // that organization, otherwise, grant it directly to that resource.
                    // e.g. Organization->Finding->Create versus Network->Create
                    if ($organizationDependency) {
                        // This is the cartesian join between roles and systems
                        foreach ($this->getOrganizationsByRole($role->id) as $organization) {
                            // Fake hierarhical access control by storing system-specific attributes like this:
                            // If the system nickname is "ABC" and the resource is called "finding", then the
                            // resource stored in the ACL is called "ABC/finding"
                            $systemResource = "$organization->id/$privilege->resource";
                            if (!$acl->has($systemResource)) {
                                $acl->add(new Zend_Acl_Resource($systemResource));
                            }
                            $acl->allow($newRole, $systemResource, $privilege->action);

                            // The wildcard resources indicates whether a user has this privilege on *any*
                            // system. This is useful for knowing when to show certain user interface elements
                            // like menu items.
                            $wildcardResource = "$privilege->resource";
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

            $cache->save($acl, md5($this->username) . '_acl');

            // Reset class loader warning suppression to original setting
            $classLoader->suppressNotFoundWarnings($suppressWarningsOriginalValue);
        }

        return $acl;
    }

    /**
     * Mark's the user's current ACL object as "dirty", indicating that it needs to be re-generated.
     *
     * This should be called if a user's privileges are changed during a request and the effects of that change need to
     * be visible within the current response.
     */
    public function invalidateAcl()
    {
        $cache = $this->_getCache();

        if ($cache) {
            $cache->remove(md5($this->username) . '_acl');
        }
    }

    /**
     * Performs house keeping that needs to run at log in
     *
     * @return void
     */
    public function login()
    {
        $this->getTable()->getRecordListener()->setOption('disabled', true);

        $this->lastLoginTs = Fisma::now();
        $this->lastLoginIp = $_SERVER['REMOTE_ADDR'];
        $this->failureCount = 0;

        $this->save();
    }

    /**
     * Get user's exist events
     *
     * @return array User's exist events in array
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
     * @return array User's available events in array
     */
    public function getAvailableEvents()
    {
        $availableEvents = array();
        
        if ('root' == $this->username) {
            $query = Doctrine::getTable('Event')->findAll();
        } else {
            $query = Doctrine_Query::Create()
                ->select('e.*')
                ->from('Event e')
                ->innerJoin('e.Privilege p')
                ->innerJoin('p.Roles r')
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
     * Get the user's organizations.
     *
     * Unlike using $this->Organizations, this method implements the correct business logic for the root user,
     * who won't have any joins in the UserOrganization model, but should still have access to all organizations
     * anyway.
     *
     * @return Doctrine_Collection The collection of user`s organizations
     */
    public function getOrganizations()
    {
        $query = $this->getOrganizationsQuery();
        $result = $query->execute();

        return $result;
    }

    /**
     * Get the user's organizations for a specific role
     * If the user is root, then we ignore the roleId
     *
     * @param int $roleId
     * @return Doctrine_Collection THe collection of user's organizations
     */
    public function getOrganizationsByRole($roleId)
    {
        $query = $this->getOrganizationsQuery();

        if ($this->username != 'root') {
            $query->where("ur.roleid = $roleId");
        }

        return $query->execute();
    }

    /**
     * Get the user's organizations for a specific privilege resource and action
     * If the user is root, then this ends up returning all of the organizations
     *
     * @param string $resource
     * @param string $action
     * @param boolean $includeDisposal Flag to indicate whether to include systems in the disposal phase, default false
     * @return Doctrine_Collection The collection of organizations
     */
    public function getOrganizationsByPrivilege($resource, $action, $includeDisposal = false)
    {
        $query = $this->getOrganizationsByPrivilegeQuery($resource, $action, $includeDisposal);
        return $query->execute();
    }

    /**
     * Get the user's systems for a specific privilege resource and action
     * If the user is root, then this ends up returning all of the systems
     *
     * @param string $resource
     * @param string $action
     * @param boolean $includeDisposal Indicates whether to include systems in the disposal phase, default is false
     * @return Doctrine_Collection The collection of systems
     */
    public function getSystemsByPrivilege($resource, $action, $includeDisposal = false)
    {
        return $this->getSystemsByPrivilegeQuery($resource, $action, $includeDisposal)
                    ->orderBy('o.nickname')
                    ->execute();
    }

    /**
     * Get the user's systems
     *
     * @return Doctrine_Collection The collection of systems
     */
    public function getSystems()
    {
        return $this->getSystemsQuery()->orderBy('o.nickname')->execute();
    }

    /**
     * Get the roles associated with a user for a specific privilege resource and action.
     *
     * @param string $resource
     * @param string $action
     * @return Doctrine_Collection The collection of user roles
     */
    public function getRolesByPrivilege($resource, $action)
    {
        $roles = new Doctrine_Collection('UserRole');

        foreach ($this->UserRole as $userRole) {
            foreach ($userRole->Role->Privileges as $privilege) {
                if ($privilege->resource == $resource && $privilege->action == $action) {
                    $roles->add($userRole);
                    break;
                }
            }
        }

        return $roles;
    }

    /**
     * Get a query which will select this user's organizations.
     *
     * This could be useful if you want to do something more advanced with the user's organizations,
     * such as using aggregation functions or joining to another model. You can extend the query returned
     * by this function to do so.
     *
     * @return Doctrine_Query The doctrine query object which selects this user's organizations
     */
    public function getOrganizationsQuery()
    {
        // The base query grabs all organizations and sorts by 'lft', which will put the records into
        // tree order.
        if ($this->username == 'root') {
            $query = Doctrine_Query::create()
                ->from('Organization o')
                ->orderBy('o.lft');
        } else {
            // For all users other than root, we want to join to the user table to limit the systems returned
            // to those which this user has been granted access to.
            $query = Doctrine_Query::create()
                ->select('o.*')
                ->from("Organization o, o.UserRole ur WITH ur.userid = $this->id")
                ->orderBy('o.lft');
        }

        return $query;
    }

    /**
     * Get a query which will select this user's organizations by privilege resource and action
     *
     * @param string $resource
     * @param string $action
     * @param boolean $includeDisposal Flag to indicate whether to include systems in the disposal phase, default false
     * @return Doctrine_Query
     */
    public function getOrganizationsByPrivilegeQuery($resource, $action, $includeDisposal = false)
    {
        $query = $this->getOrganizationsQuery();

        if ($this->username != 'root') {
            $query->leftJoin('ur.Role r')
                ->leftJoin('r.Privileges p')
                ->where('p.resource = ?', $resource)
                ->andWhere('p.action = ?', $action)
                ->groupBy('o.id')
                ->orderBy('o.nickname');

            if (!$includeDisposal) {
                $query->leftJoin('o.System s2')
                      ->andWhere("s2.sdlcphase <> 'disposal' or s2.sdlcphase is NULL"); 
            }
        }

        return $query;
    }

    /**
     * Get a query which will select this user's systems by privilege resource and action
     *
     * @param string $resource
     * @param string $action
     * @param boolean $includeDisposal Flag to indicate whether to include systems in the disposal phase, default false
     * @return Doctrine_Query
     */
    public function getSystemsByPrivilegeQuery($resource, $action, $includeDisposal = false)
    {
        $query = $this->getOrganizationsByPrivilegeQuery($resource, $action)
                      ->innerJoin('o.System s');
        if (!$includeDisposal) {
            $query->andWhere('s.sdlcPhase <> ?', 'disposal');
        }
        return $query;
    }

     /**
     * Get a query which will select this user's systems
     *
     * @return Doctrine_Query
     */
    public function getSystemsQuery()
    {
        return $this->getOrganizationsQuery()
                    ->innerJoin('o.System s');
    }

    /**
     * Doctrine hook for post-update
     *
     * @param Doctrine_Event $event The triggered doctrine event
     * @return void
     * @todo this needs to go into some sort of observer class
     */
    public function postUpdate($event)
    {
        $modified = $this->getModified(true, true);

        // Ensure that any user can not change root username
        if (isset($modified['username']) && 'root' == $modified['username']) {
            throw new Fisma_Zend_Exception_User('The root user\'s username cannot be modified.');
        }
    }

    /**
     * Prevent the root user from being deleted
     *
     * @param Doctrine_Event $event
     * @return void
     */
    public function preDelete($event)
    {
        if ('root' == $this->username) {
            throw new Fisma_Zend_Exception_User('The root user cannot be deleted.');
        }

        // Make sure the user can not be deleted when it is already associated with other objects.
        $relations = $this->getTable()->getRelations();
        foreach ($relations as $name => $relation) {
            if (count($this->$name) > 0) {
                throw new Fisma_Zend_Exception_User(
                    "This user can not be deleted because it is already associated with one or more "
                    . strtolower($name)
                );
            }
        }
    }

    /**
     * Set the last rules of behavior acceptance timestamp
     *
     * @params string $value
     */
    public function setLastRob($value)
    {
        $this->_set('lastRob', $value);

        $this->getAuditLog()->write('Accepted Rules of Behavior');
    }

    /**
     * Password mutator to handle password management
     *
     * @param string $value The value of password to encrypt and set
     * @return void
     * @throws Doctrine_Exception if your password is in password history
     */
    public function setPassword($value)
    {
        // Generate a salt if one does not exist
        if (!$this->passwordSalt) {
            $saltColumn = Doctrine::getTable('User')->getColumnDefinition('passwordsalt');
            $this->passwordSalt = Fisma_String::random($saltColumn['length']);
        }

        // Set the user's hash type if it is not set already
        if (!$this->hashType) {
            $this->hashType = Fisma::configuration()->getConfig('hash_type');
        }

        // Update password timestamp
        $this->passwordTs = Fisma::now();

        // Password is hashed with salt to make rainbow table attacks less feasible
        $password = Fisma_Hash::hash($value . $this->passwordSalt, $this->hashType);

        // Check password history
        if (strpos($this->passwordHistory, $password) !== false) {
            /**
             * @todo Throw a doctrine exception... not enough time to fix the exception handlers right now
             */
            throw new Doctrine_Exception('Your password cannot be the same as any of your previous'
                    . ' 3 passwords.');
        }
        $this->_set('password', $password);

        // Generate password history. Colons are used to delimit passwords and can be used to count how many old
        // passwords are currently stored.
        $oldPasswords = explode(':', $this->passwordHistory);
        array_unshift($oldPasswords, $this->password);
        $oldPasswords = array_slice($oldPasswords, 0, self::PASSWORD_HISTORY_LIMIT);
        $this->passwordHistory = implode(':', $oldPasswords);

        /*
         * Store the plain-text password into a mapped value, which means it will remain resident in memory for the
         * duration of this request.
         */
        $this->plainTextPassword = $value;
    }

    /**
     * Retrieve user assigned roles.
     *
     * The doctrine hydration constant can indicate what data type this method returns. By default, the
     * hydrator is assigned as Doctrine::HYDRATE_SCALAR data type.
     *
     * @param int $hydrationMode A doctrine hydrator
     * @return mixed The roles of user
     */
    public function getRoles($hydrationMode = Doctrine::HYDRATE_SCALAR)
    {
        $userRolesQuery = Doctrine_Query::create()
            ->select('u.id, r.*')
            ->from('User u')
            ->innerJoin('u.Roles r')
            ->where('u.id = ?', $this->id)
            ->setHydrationMode($hydrationMode);
        $userRolesResult = $userRolesQuery->execute();

        return $userRolesResult;
    }
}
