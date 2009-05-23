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
 * @author    woody
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/mw/index.php?title=License
 * @version   $Id$
 * @package   Fisma
 *
 */

/**
 * The plugin handling all the initial configurations
 */
class Fisma_Controller_Plugin_Setting extends Zend_Controller_Plugin_Abstract
{

    const INSTALL_CONFIG = 'install.conf';
    /**
     * Default system configuration. This is a default for app.ini configuration
     *
     * @var $_defaultSysConf
     */
    static private $_defaultSysConf = array(
        'general' => array('include_path' => 'library/Pear'),
        'debug' => array('level' => 0)
    );

    /** 
     * @var _installed flag indicating that if the application is installed
     */
    protected $_installed = false;

    /** 
     * @var root path
     */
    protected $_root = null;

    /**
     * Indicates whether the application is in debug mode or not
     *
     * @var _debug
     */
    protected static $_debug = false;
    
    /**
     * The application wide current time stamp
     */
    protected static $_now = null;

    /**
     * Log instance 
     */
    protected $_log = null;

    /** 
     * The relative paths that makes the layout
     *
     * @var _path
     */
    private static $_path = null;
    
    /**
     * Constructor
     *
     * @param  string $root The root directory of the application
     * @return void
     */
    public function __construct($root = null)
    {
        $path = array(
                'library' => 'library',
                'pear' => 'library/Pear',
                'data' => 'data',
                'pub' => 'public',
                'application' => 'application',
                'config' => 'application/config',
                'models' => 'application/models',
                'yui' => 'public/yui',
                'local' => 'library/local/');

        // get the default root 
        if (empty($root)) {
            $root = $this->_getRoot();
        }
        if (is_dir($root)) {
            $this->_root = $root;
        }
        if (empty($this->_root)) {
            throw new Fisma_Config_Exception("Wrong root:$root");
        }
        foreach ($path as $k=>$d) {
            self::$_path[$k] = "$root/$d";
        }
        self::$_defaultSysConf['path'] = self::$_path;
        $this->addConfig(new Zend_Config(self::$_defaultSysConf));

        //freeze the NOW, minimize the impact of running time cost.
        self::$_now = time(); 
    }
    /**
     * get the root path of application
     *
     * @return $path
     */
    private function _getRoot()
    {
        // local current file and move up 5 levels folder to the root
        $path = __FILE__;
        for($i = 1; $i <= 5; $i ++) {
            $path = dirname($path);
        }
        return $path;
    }
    
    public function routeStartup(Zend_Controller_Request_Abstract $request)
    {
        try {
            $this->parse();
        } catch (Zend_Config_Exception $e) {
            throw new Scarab_Exception_Config($e->getMessage());
        }
    }

    /**
     * Application setting initialization
     *
     * Read settings from the ini file and make them effective
     *
     * @return void
     */
    public function parse($file = null)
    {
        if (empty($file)) {
            $file = self::$_path['config'] . "/app.ini";
        }
        $config = new Zend_Config(self::$_defaultSysConf, true);
        $ini = new Zend_Config_Ini($file, null, array('allowModifications'=>true));
        $general = $ini->{$ini->environment};
        $general = $config->merge($general);
        $path = implode(PATH_SEPARATOR, self::$_defaultSysConf['path']);
        set_include_path($path .  PATH_SEPARATOR . get_include_path());

        if (isset($general->session->save_path)) {
            Zend_Session::setOptions(array('save_path' => self::$_path['data'] . $general->session->save_path,
                                           'name' => $general->session->name,
                                           'remember_me_seconds' => 0));
        }
        $this->addConfig($ini);
        assert_options(ASSERT_ACTIVE,0);
        $this->_debug = (0 < $ini->debug->level);
        if ($this->_debug) {
            assert_options(ASSERT_BAIL,1);
            error_reporting(E_ALL);
            ini_set('display_errors', 1);
            //Initialize xdebug settings
            foreach ($ini->debug->xdebug as $k => $v) {
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


    /**
     * Returns true if the application is in debug mode, false otherwise
     *
     * @return boolean
     */
    public static function debug() {
        return self::$_debug;
    }

    /**
     * Returns true if the application has been installed 
     *
     * This application requires the install.ini file present in the config directory and 
     * its content should be at lease contains [db] section
     *
     * @return boolean
     */
    public function installed() {
        try {
            if (!$this->_installed) {
                $installFile = self::$_path['config']. '/' . self::INSTALL_CONFIG;
                $config = new Zend_Config_Ini($installFile);
                if (!empty($config->database)) {
                    Zend_Registry::set('datasource', $config->database);
                    Zend_Registry::set('doctrine_config', array(
                           'data_fixtures_path'  =>  $this->root . '/application/doctrine/data/fixtures',
                           'models_path'         =>  $this->root . '/application/models',
                           'migrations_path'     =>  $this->root . '/application/doctrine/migrations',
                           'sql_path'            =>  $this->root . '/application/doctrine/data/sql',
                           'yaml_schema_path'    =>  $this->root . '/application/doctrine/schema'
                    ));
                    foreach ($config->general as $k => $v) {
                        Zend_Registry::set($k, $v);
                    }
                    $this->_installed = true;
                } else {
                    $log = $this->getLogInstance();
                    $log->log('The install file is damaged. Reinstall it! ', Zend_Log::CRIT);
                }
            }
        } catch (Zend_Config_Exception $e) {
            $log = $this->getLogInstance();
            $log->log('The application has not been installed', Zend_Log::WARN);
        }
        return $this->_installed;
    }

    /**
     * Read configurations of any sections.
     * 
     * Example: getConfig(array('path','APP')) = $REG->path->APP
     *          getConfig('path.APP') is the same
     *          the predefined constants are 'path', 'CONFIG', 'APP', 'DATA', 'LIB'
     * 
     * @param string|array $key key names. If it is an array, the element would be 
     *                                     the section name in sequence.
     * @param boolean $isFresh  to read from persisten storage or not.
     * @return mix configuration value.
     */
    function getConfig($key = null, $isFresh = false)
    {
        if (is_null($key)) {
            throw new Fisma_Exception_Config(
                "require empty value in system configuration");
        }
        
        if (!is_array($key)) {
            $key = explode('.',$key);
        }
        
        if ($this->installed() && $isFresh) {
            $this->readLdapConfig();
            $db = Zend_Db::factory(Zend_Registry::get('datasource'));
            require_once(self::$_path['models'].'/FismaModel.php');
            require_once(self::$_path['models'].'/Config.php');
            $m = new Config($db);
            $pairs = $m->fetchAll();
            $configs = array();
            foreach ($pairs as $val) {
                $configs[$val->key] = $val->value;
                if (in_array($val->key, array('use_notification',
                    'behavior_rule', 'privacy_policy'))) {
                    $configs[$val->key] = $val->description;
                }
            }
            $configs['isFresh'] = true;
            $this->addConfig(new Zend_Config($configs));
            if (!isset($configs[$key[0]])) {
                throw new Fisma_Exception_Config(
                    "{$key[0]} does not exist in system configuration");
            }
        }
        
        if (Zend_Registry::isRegistered($key[0])) {
            return Zend_Registry::get($key[0]);
        }
        
        if (Zend_Registry::isRegistered('FISMA_REG')) {
            if (!isset(Zend_Registry::get('FISMA_REG')->$key[0])) {
                return $this->getConfig($key, true);
            } else {
                $config = Zend_Registry::get('FISMA_REG')->$key[0];
                if (isset($key[1]) && isset($config->$key[1])) {
                    return $config->$key[1];
                } else {
                    return $config;
                }
            }
        }
        
    }
        
    /**
     * Add configuration into the application
     *
     * @param object @config  
     * @return Zend_Registry
     */
    public function addConfig($config)
    {
        $sect = 'FISMA_REG';
        if (!$config instanceof Zend_Config) {
            throw new Fisma_Exception_Parameter('Zend_Config is expected while '. get_class($config));
        }
        $sysconfig = $config;
        if (Zend_Registry::isRegistered($sect)) {
            $sysconfig = Zend_Registry::get($sect);
            $sysconfig->merge($config);
        } else {
            $sysconfig = new Zend_Config($config->toArray(), true);
        }
        Zend_Registry::set($sect, $sysconfig);
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
        if (null === $this->_log) {
            $write = new Zend_Log_Writer_Stream(self::$_path['data'] . '/logs/error.log');
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
     * Get real paths of the installed application
     *
     * @param string $part the component of the path
     * @return string the path
     */ 
    public function getPath($part = null)
    {
        // return the root path when $part doesn't be assigned.
        if (is_null($part)) {
            return $this->_root;
        }
        return $this->getConfig('path.' . $part);
    }
    
    /**
     * Read Ldap configurations
     *   
     * @return array ldap configurations
     */
    private function readLdapConfig()
    {
        $db = Zend_Registry::get('db');
        $query = $db->select()->from('ldap_config', '*');
        $result = $db->fetchAll($query);
        if (!empty($result)) {
            foreach ($result as $row) {
                $multiOptions[$row['group']][$row['key']] = $row['value'];
            }
            $ldap = new Zend_Config(array('ldap'=>$multiOptions));
            $this->addConfig($ldap);
            return $ldap->toArray();
        }
        return null;
    }
    
    /**
     * Retrieve the current time
     *
     * @return unix timestamp
     */
    public static function now()
    {
        return self::$_now;
    }
    
}
