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
 * @author    Jim Chen <xhorse@users.sourceforge.net>
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/mw/index.php?title=License
 * @version   $Id$
 *
 * @todo This file seems to serve no purpose, and does not fit into the object-
 * oriented or MVC paradigms that OpenFISMA is based on. Consider this file
 * for refactoring, or else update the documentation to explain it's purpose.
 */

    /**
         Form config file name.
     */
    define('FORMCONFIGFILE', 'form.conf');
 
    /** 
        Exam the Acl of the existing logon user to decide permission or denial.

        @param $resource resources
        @param $action actions
        @return bool permit or not
    */
    function isAllow($resource, $action)
    {
        $auth = Zend_Auth::getInstance();
        $me = $auth->getIdentity();
        if ( $me->account == "root" ) {
            return true;
        }
        $roleArray = &$me->roleArray;
        $acl = Zend_Registry::get('acl');
        try{
            foreach ($roleArray as $role) {
                if ( true == $acl->isAllowed($role, $resource, $action) ) {
                    return true;
                }
            }
        } catch(Zend_Acl_Exception $e){
            /// @todo acl log information
        }
        return false;
    }

    /**
     The section name of system wide configuration
     */ 
    define('SYSCONFIG', 'sysconf');
    /** 
        Read configurations of any sections.
        This function manages the storage, the cache, lazy initializing issue.
        
        @param $key string key name
        @param $is_fresh boolean to read from persisten storage or not.
        @return string configuration value.
     */
    function readSysConfig($key, $isFresh = false)
    {
        assert(!empty($key) && is_bool($isFresh));
        if ( ! Zend_Registry::isRegistered(SYSCONFIG) || $isFresh ) {
            $m = new Config();
            $pairs = $m->fetchAll();
            $configs = array();
            foreach ($pairs as $v) {
                $configs[$v->key] = $v->value;
                if (in_array($v->key, array('use_notification',
                    'behavior_rule', 'privacy_policy'))) {
                    $configs[$v->key] = $v->description;
                }
            }
            Zend_Registry::set(SYSCONFIG, new Zend_Config($configs));
        }
        if ( !isset(Zend_Registry::get(SYSCONFIG)->$key) ) {
            throw new fisma_Exception(
            "$key does not exist in system configuration");
        }
        return Zend_Registry::get(SYSCONFIG)->$key;
    }

    define('LDAPCONFIG', 'ldapconf');
    /**
     * Read Ldap configurations
     *   
     * @return array ldap configurations
     */
    function readLdapConfig()
    {
        if ( ! Zend_Registry::isRegistered(LDAPCONFIG) ) {
            $db = Zend_Registry::get('db');
            $query = $db->select()->from('ldap_config', '*');
            $result = $db->fetchAll($query);
            foreach ($result as $row) {
                $multiOptions[$row['group']][$row['key']] = $row['value'];
            }
            Zend_Registry::set(LDAPCONFIG, $multiOptions);
        }
        return Zend_Registry::get(LDAPCONFIG);
    }
    
    /**
        To make a partial statement from an array of value where IN is used.
    */
    function makeSqlInStmt($array)
    {
        assert(is_array($array));
        return "'" . implode("','", $array). "'"; 
    }

    /**
        @deprecated 
        @link nullGet
    */
    function echoDefault(&$value, $default='')
    {
        echo nullGet($value, $default);
    }
    
    /**
        get the value of an variable. Return assigned value if it's empty. 

        The function is useful in template
        when getting some uncertain value from model.
    */
    function nullGet(&$value, $default='')
    {
        if ( !empty($value) ) {
            return $value;
        } else {
            return $default;
        }
    }

    function isInstall()
    {
        $reg = Zend_Registry::getInstance();
        $ret = false;           
        if ( $reg->isRegistered('installed') ) {
            $ret = $reg->get('installed');
        }
        return $ret;
    }

    /**
     *  mapping the key/value from different arrays
     * 
     *  @param array $array1 the key1=>val1
     *  @param array $array2 the val1=>val2
     *  @return array the key1=>val2
     */
    function directMap($array1, $array2)
    {
        $ret = array();
        foreach ($array1 as $k=>$v) {
            if (array_key_exists($v, $array2)) {
                $ret[$k] = $array2[$v];
            }
        }
        return $ret;
    }
