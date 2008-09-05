<?php
/**
 * basic.php
 *
 * System wide utility functions
 *
 * @package App
 * @author     Xhorse   xhorse at users.sourceforge.net
 * @copyright  (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license    http://www.openfisma.org/mw/index.php?title=License
 * @version $Id$
*/

    /**
         Form config file name.
     */
    define('FORMCONFIGFILE', 'form.conf');


    require_once(APPS . DS .'Exception.php');

    /** 
     * include library files
     *
     * @param ... filenames
    */
    function uses()
    {
        $args = func_get_args();
        foreach ($args as $file) {
            require_once(LIBS . DS . strtolower($file) . '.php');
        }
    }

    /**
     * add path(es) into the including path variable
     *
     * @param ... pathes
     */
    function import() 
    {
        $args = func_get_args();
        $targetPath = null;
        foreach ($args as $dir) {
            if ( is_dir($dir) ) {
                $targetPath .= $dir . PATH_SEPARATOR ;
            } else {
                throw new fisma_Exception($dir . ' is missing or not 
                                          a directory');
            }
        }
        if (! empty($targetPath) ) {
            $includePath = ini_get('include_path');
            ini_set('include_path', $targetPath . $includePath);
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
            require_once( MODELS . DS . 'config.php' );
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
