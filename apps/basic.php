<?php
/**
 * @file basic.php
 *
 * @description System wide utility functions
 *
 * @author     Xhorse   xhorse at users.sourceforge.net
 * @copyright  (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license    http://www.openfisma.org/mw/index.php?title=License
 * @version $Id$
*/
    define( 'CONFIGFILE_NAME', 'install.conf');
    require_once(APPS . DS .'Exception.php');

    /** 
     * include library files
     *
     * @param ... filenames
    */
    function uses() {
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
    function import() {
        $args = func_get_args();
        $target_path = null;
        foreach ($args as $dir) {
            if( is_dir($dir) ) {
                $target_path .= $dir . PATH_SEPARATOR ;
            }else{
                throw new fisma_Exception($dir . ' is missing or not a directory');
            }
        }
        if(! empty($target_path) ){
            $include_path = ini_get('include_path');
            ini_set('include_path',  $target_path . $include_path);
        }
    }
 
    /** 
        Exam the Acl of the existing logon user to decide permission or denial.

        @param $resource resources
        @param $action actions
        @return bool permit or not
    */
    function isAllow($resource, $action) {
        $auth = Zend_Auth::getInstance();
        $me = $auth->getIdentity();
        if($me->account == "root"){
            return true;
        }
        $role_array = &$me->role_array;
        $acl = Zend_Registry::get('acl');
        try{
            foreach ($role_array as $role){
                if(true == $acl->isAllowed($role,$resource,$action)){
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
    define('SYSCONFIG','sysconf');
    /** 
        Read configurations of any sections.
        This function manages the storage, the cache, lazy initializing issue.
        
        @param $key string key name
        @param $is_fresh boolean to read from persisten storage or not.
        @return string configuration value.
     */
    function readSysConfig($key, $is_fresh = false)
    {
        assert( !empty($key) && is_bool($is_fresh) );
        if( ! Zend_Registry::isRegistered(SYSCONFIG) || $is_fresh ){
            require_once( MODELS . DS . 'config.php' );
            $m = new Config();
            $pairs = $m->fetchAll();
            $configs = array();
            foreach( $pairs as $v ) {
                $configs[$v->key] = $v->value;
            }
            Zend_Registry::set(SYSCONFIG, new Zend_Config($configs) );
        }
        if( !isset(Zend_Registry::get(SYSCONFIG)->$key) ){
            throw new fisma_Exception("$key does not exist in system configuration");
        }
        return Zend_Registry::get(SYSCONFIG)->$key;
    }
    
    /**
        To make a partial statement from an array of value where IN is used.
    */
    function makeSqlInStmt($array)
    {
        assert( is_array($array) );
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

        The function is useful in template when getting some uncertain value from model.
    */
    function nullGet(&$value, $default='')
    {
        if( !empty($value) ) {
            return $value;
        }else{
            return $default;
        }
    }

    function isInstall()
    {
        $reg = Zend_Registry::getInstance();
        $ret = false;           
        if( $reg->isRegistered('installed') ) {
	        $ret = $reg->get('installed');
        }
        return $ret;
    }
 
