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
 * @version   $Id: basic.php 940 2008-09-27 13:40:22Z ryanyang $
 *
 * @todo This class should be renamed. "Fisma" doesn't mean anything. Also this class serves multiple purposes. It
 * should be split up into separate classes that each serve a single purpose.
 */

class Config_Fisma
{
    /**
     * The section name of system wide configuration
     *
     * @todo Remove these.. no point in having constants which are the same name as the value that they represent.
     */ 
    const SYSCONFIG = 'sysconf';
    const LDAPCONFIG = 'ldapconf';
    const CONFIGFILE_NAME = 'install.conf';
    const ERROR_LOG = 'error.log';
    const FORMCONFIGFILE  = 'form.conf';

    /**
     * Singleton instance
     *
     * Marked only as protected to allow extension of the class. To extend,
     * simply override {@link getInstance()}.
     *
     * @var Config_Fisma
     */
    protected static $_instance = null;

    /**
     * Indicates whether the application is in debug mode or not
     */
    protected static $_debug = false;
    
    /**
     * Log instance to record fatal error message
     *
     */
    protected static $_log = null;

    /**
     * Constructor
     *
     * Instantiate using {@link getInstance()}; System wide config is a singleton
     * object.
     *
     * @return void
     */
    private function __construct()
    {
        //assuming not installed first unless it is
        Zend_Registry::set('installed', false);

        if (is_file(APPLICATION_CONFIGS . '/' . self::CONFIGFILE_NAME)) {
            $config = new Zend_Config_Ini(APPLICATION_CONFIGS . '/' . self::CONFIGFILE_NAME);
            if (!empty($config->database)) {
                Zend_Registry::set('datasource', $config->database);
                Zend_Registry::set('installed', true);
            }
            
            self::addSysConfig($config->general);

            // Debug setting
            if (!empty($config->debug)) {
                if ($config->debug->level > 0) {
                    self::$_debug = true;
                    error_reporting(E_ALL);
                    ini_set('display_errors', 1);
                    foreach ($config->debug->xdebug as $k => $v) {
                        if ($k == 'start_trace') {
                            if (1 == $v && function_exists('xdebug_start_trace')) {
                                xdebug_start_trace();
                            }
                        } else {
                            @ini_set('xdebug.' . $k, $v);
                        }
                    }
                }
            }
        }
    }

    /**
     * Enforce singleton; disallow cloning 
     * 
     * @return void
     */
    private function __clone()
    {
    }

    /**
     * Singleton instance
     *
     * @return Config_Fisma
     */
    public static function getInstance()
    {
        if (null === self::$_instance) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    /**
     * debug() - Returns true if the application is in debug mode, false otherwise
     *
     * @return boolean
     */
    static function debug() {
        return self::$_debug;
    }


    /**
     * Returns the encrypted password
     * @param $password string password
     * @return string encrypted password
     */
    public function encrypt($password) {
        $encryptType = self::readSysConfig('encrypt');
        if ('sha1' == $encryptType) {
            return sha1($password);
        }
        if ('sha256' == $encryptType) {
            $key = self::readSysConfig('encryptKey');
            $cipher_alg = MCRYPT_TWOFISH;
            $iv=mcrypt_create_iv(mcrypt_get_iv_size($cipher_alg,MCRYPT_MODE_ECB), MCRYPT_RAND);
            $encryptedPassword = mcrypt_encrypt($cipher_alg, $key, $password, MCRYPT_MODE_CBC, $iv);
            return $encryptedPassword;
        }
    }

    /**
     * Initialize the log instance
     *
     * As the log requires the authente information, the log should be only initialized 
     * after the successfully login.
     *
     * @return Zend_Log
     */
    public function getLogInstance()
    {
        if ( null === self::$_log ) {
            $write = new Zend_Log_Writer_Stream(APPLICATION_LOGS . '/' . self::ERROR_LOG);
            $auth = Zend_Auth::getInstance();
            if ($auth->hasIdentity()) {
                $me = $auth->getIdentity();
                $format = '%timestamp% %priorityName% (%priority%): %message% by ' .
                    "$me->account($me->id) from {$_SERVER['REMOTE_ADDR']}" . PHP_EOL;
            } else {
                $format = '%timestamp% %priorityName% (%priority%): %message% by ' .
                    "{$_SERVER['REMOTE_ADDR']}" . PHP_EOL;
            }
            $formatter = new Zend_Log_Formatter_Simple($format);
            $write->setFormatter($formatter);
            $this->_log = new Zend_Log($write);
        }
        return $this->_log;
    }

    /**
     * hasPrivilege() - Returns true if the current user has the specified privilege. 
     * This static function deprecates the isAllow function().
     *
     * @todo Move into new Auth class
     */
    public static function hasPrivilege($screen, $action) 
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
                if ( true == $acl->isAllowed($role, $screen, $action) ) {
                    return true;
                }
            }
        } catch(Zend_Acl_Exception $e){
            /// @todo acl log information
        }
        return false;        
    }

    /**
     * requirePrivilege() - Determine if the current user has the specified privilege. If the user does not have
     * the specified privilege, then the user is redirected to the dashboard.
     * 
     * @todo Move into new Auth class
     * @todo What is the ZF way to do this?
     */
    public static function requirePrivilege($screen, $action) 
    {
        if (!self::hasPrivilege($screen, $action)) {
            throw new Exception_PrivilegeViolation("User does not have the privilege for ($screen, $action)");
        }
    }


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
        Read configurations of any sections.
        This function manages the storage, the cache, lazy initializing issue.
        
        @param $key string key name
        @param $is_fresh boolean to read from persisten storage or not.
        @return string configuration value.
     */
    function readSysConfig($key, $isFresh = false)
    {
        assert(!empty($key) && is_bool($isFresh));
        if (!Zend_Registry::isRegistered(self::SYSCONFIG) || 
                !Zend_Registry::get(self::SYSCONFIG)->isFresh) {
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
            $configs['isFresh'] = true;
            self::addSysConfig(new Zend_Config($configs));
        }
        if ( !isset(Zend_Registry::get(self::SYSCONFIG)->$key) ) {
            throw new Exception_General(
            "$key does not exist in system configuration");
        }

        return Zend_Registry::get(self::SYSCONFIG)->$key;
    }

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
            Zend_Registry::set(self::LDAPCONFIG, $multiOptions);
        }
        return Zend_Registry::get(self::LDAPCONFIG);
    }


    /**
     * To determind if the application has been properly installed.
     * 
     * @return bool 
     */
    public function isInstall()
    {
        $reg = Zend_Registry::getInstance();
        $ret = false;           
        if ( $reg->isRegistered('installed') ) {
            $ret = $reg->get('installed');
        }
        return $ret;
    }

    /*
     * Get form object from form config file section 
     * @param string $formConfigSection the forms name namely section of
            the configuration
     * 
     * @return  Zend_Form
     */
    public function getForm ($formConfigSection)
    {
        $formIni = new Zend_Config_Ini(APPLICATION_CONFIGS . '/' . FORMCONFIGFILE,
            $formConfigSection);
        $form = new Zend_Form($formIni);
        return $form;
    }
    
    /**
     * use Registry SYSCONFIG to merge other config
     * @param object @config  
     * @return Zend_Registry
     */
    public function addSysConfig($config)
    {
        if (Zend_Registry::isRegistered(self::SYSCONFIG)) {
            $sysconfig = Zend_Registry::get(self::SYSCONFIG);
            $sysconfig = new Zend_Config($sysconfig->toArray(), $allowModifications = true);
            $sysconfig->merge($config);
            Zend_Registry::set(self::SYSCONFIG, $sysconfig);
        } else {
            Zend_Registry::set(self::SYSCONFIG, $config);
        } 
    }
}
