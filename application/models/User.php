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
     * 
     * @return User
     */
    public static function currentUser() {
        $authSession = new Zend_Session_Namespace(Zend_Auth::getInstance()->getStorage()->getNamespace());
        
        return $authSession->currentUser;        
    }
    
    /**
     * Lock an account, which will prevent a user from logging in.
     * 
     * @param string $lockType manual, password, inactive, or expired
     */
    public function lockAccount($lockType)
    {
        if (empty($lockType)) {
            throw new Fisma_Exception_General("Lock type cannot be blank");
        }
        
        $this->locked = true;
        $this->lockTs = new Zend_Date();
        $this->lockType = $lockType;
        $this->save();
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
            $hashType = $this->hashType;
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

    /**
     * Validate the user's e-mail change.
     * 
     * @param string $validateCode validate code
     * @return bool
     */
    public function validateEmail($validateCode)
    {
        $email = empty($this->notifyEmail)?$this->email:$this->notifyEmail;

        $validation = Doctrine::getTable('EmailValidation')
                        ->findByDql("email = '$email' AND userId = $this->id");
        if ($validateCode == $validation[0]->validationCode) {
            $this->emailValidate = true;
            $validation->delete();
            //@todo english
            $this->log('Email validate successfully');
            return true;
        } else {
            //@todo english
            $this->log('Email validate faild');
            return false;
        }
    }

    /**
     * Close out the current user's session
     */
    public function logout()
    {
        //@todo english
        $this->log('Log out');
        Zend_Auth::getInstance()->clearIdentity();
    }

    /** 
     * Log any creation, modification, disabling and termination of account.
     *
     * @param string $message log message
     */
    private function log($message)
    {
        $accountLog = new AccountLog();
        $accountLog->ip      = $_SERVER["REMOTE_ADDR"];
        $accountLog->message = $message;
        $this->AccountLogs[] = $accountLog;
        $this->save();
    }
    
    /**
     * Get the OrgSystems which are belong to specify user
     *
     * @param int $id user id
     * @return collection $orgSystem
     */
    public function getOrgSystems($id = null)
    {
        // if the user id doesn't be assigned,
        // then use specify id
        if (is_null($id)) {
            $id = $this->id;
        }
        // get the record of this user
        $user = Doctrine::getTable('User')->find($id);
        // if the user hasn't privileged organizations,
        // return an empty organization collection
        if (0 == count($user->Organizations)) {
            return $user->Organizations;
        } else {
            // if the user has privileged organizations,
            // find all system-type children which are belong to these organizations
            $q = Doctrine_Query::create()
                 ->select()
                 ->from('Organization o');
            foreach ($user->Organizations as $organization) {
                $q->orWhere('o.lft >= ? AND o.rgt <= ? AND o.orgtype = ?',
                            array($organization->lft, $organization->rgt, 'system'));
            }
            $orgSystem = $q->execute();
            return $orgSystem;
        }
    }
    
    /**
     * Bulid the relationship between specify user and orgSystems
     * 
     * The algorithm relies on the linear sequence representative logic. 
     * <lft rgt> combination is used to determind if the two nodes are parent or child. 
     * For example, node <2, 7> is parent of <4, 5> because 2 < 4 and 7 > 5. 
     * 
     * @see http://www.sitepoint.com/article/hierarchical-data-database/2/
     * @param array $ids orgSys id
     * @return boolean
     */
    public function setOrgSystems($ids)
    {
        $q = Doctrine_Query::create()
             ->select()
             ->from('Organization o')
             ->whereIn('o.id', $ids)
             ->orderBy('o.lft ASC');
        // get the records of selected organizations 
        $orgs = $q->execute()->toArray();
        if (empty($orgs)) {
            return false;
        }
        // The useless ids will be pushed in this array 
        $discardIds = array();
        // The first reference
        $flagRecord = $orgs[0];
        // loop times
        $loopTimes = count($orgs) - 1;
        for ($i = 0; $i < $loopTimes; $i ++) {
            if ($flagRecord['rgt'] > $orgs[$i+1]['rgt']) {
                $discardIds[] = $orgs[$i+1]['id'];
            } else {
                $flagRecord = $orgs[$i+1];
            }
        }
        $keepIds = array_diff($ids, $discardIds);
        foreach ($keepIds as $keepId) {
            $userOrg = new UserOrganization();
            $userOrg->userId = $this->id;
            $userOrg->organizationId = $keepId;
            $userOrg->save();
        }
        return true;
    }
    
}
