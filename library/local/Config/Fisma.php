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
    const PATH_CONFIG = 'application/config/';

    const SYS_CONFIG = 'app.ini';
    const INSTALL_CONFIG = 'install.conf';
    const ERROR_LOG = 'error.log';
    const FORM_CONFIGFILE  = 'form.conf';

    const TEST_MODE = 'test';

    /**
     * @todo english
     * set the cache lifeTime in seconds
     */
    const cacheLifeTime = 7200;

    /** 
     * The relative paths that makes the layout
     *
     * @var _path
     */
    private $_path = array(
            'library'=>'library',
            'data'=>'data',
            'application'=>'application',
            'config'=>'application/config'
        );
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
     * The root path of the installed application
     */
    protected static $_root = null;
    
    /**
     * The application wide current time stamp
     */
    protected static $_now = null;

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
        if (isset($root) && is_dir($root)) {
            self::$_root = $root;
        } else {
            self::$_root = realpath(dirname(__FILE__) . '/../../../');
        }
        // APPLICATION CONSTANTS - Set the constants to use in this application.
        // These constants are accessible throughout the application, even in ini 
        // files. 
        define('APPLICATION_ROOT', self::$_root);
        define('APPLICATION_PATH', self::$_root . '/' . $this->_path['application']);
        $this->initSetting();
        //freeze the NOW, minimize the impact of running time cost.
        self::$_now = time(); 
    }

    /**
     * Application setting initialization
     *
     * Read settings from the ini file and make them effective
     *
     * @return void
     */
    public function initSetting()
    {
        //initialize path
        // INCLUDE PATH - Several libraries and files need to be available to our application when
        // searching for their location. We need to include these directories in the include path
        // so the application automatically searches these directories looking for files. This array
        // puts together a list of directories to add to the include path
        $incPaths['lib'] = $this->getPath('library');
        $incPaths[] = "{$incPaths['lib']}/local";
        $incPaths[] = "{$incPaths['lib']}/Pear";
        $incPaths[] = $this->getPath('application') . '/models';

    
        set_include_path(implode(PATH_SEPARATOR, $incPaths) . PATH_SEPARATOR . get_include_path());

        require_once 'Zend/Loader.php';
        Zend_Loader::registerAutoload();

        $sysfile = self::$_root."/" . self::PATH_CONFIG . self::SYS_CONFIG;
        try {
            // CONFIGURATION - Setup the configuration object
            // The Zend_Config_Ini component will parse the ini file, and resolve all of
            // the values for the given section.  Here we will be using the section name
            // that corresponds to the APP's Environment
            $config = new Zend_Config_Ini($sysfile);
            // REGISTRY - setup the application registry
            // An application registry allows the application to store application
            // necessary objects into a safe and consistent (non global) place for future
            // retrieval.  This allows the application to ensure that regardless of what
            // happends in the global scope, the registry will contain the objects it
            // needs.
            $registry = Zend_Registry::getInstance();
            
            if (!isset($config->environment)) {
                $config->environment = 'production';
            }
            $configuration = $config->{$config->environment};
            self::addSysConfig($configuration);
            // Start Session Handling using Zend_Session 
            Zend_Session::start($configuration->session);

            //initialize Zend_Cache
            if (!Zend_Registry::isRegistered('cache')) {
                $frontendOptions = array(
                    'caching'  => true,
                    'lifetime' => self::cacheLifeTime,
                    'automatic_serialization' => true
                );

                $backendOptions = array(
                    'cache_dir' => APPLICATION_ROOT . '/data/cache'
                );
                if (!is_writable($backendOptions['cache_dir'])) {
                    echo $backendOptions['cache_dir'] . ' is not writable';
                }
                $cache = Zend_Cache::factory('Core',
                                             'File',
                                             $frontendOptions,
                                             $backendOptions);
                if (!empty($cache)) {
                    Zend_Registry::set('cache', $cache);
                }
            }

        } catch(Zend_Config_Exception $e) {
            //using default configuration
            $config = new Zend_Config(array());
        }

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
     * start the bootstrap
     *
     * @param string $mode to bootstrap different configurations
     */
    public function bootstrap($mode=null)
    {
        $frontController = Zend_Controller_Front::getInstance();

        if ($mode == self::TEST_MODE) {
            $initPlugin = new Plugin_Initialize_Unittest(self::$_root);
        } else {
            if (self::isInstall()) {
                $initPlugin = new Plugin_Initialize_Webapp(self::$_root);
            } else {
                $initPlugin = new Plugin_Initialize_Install(self::$_root);
            }
        }
        $frontController->registerPlugin($initPlugin);
        $flag = self::readSysConfig('throw_exception');
        $frontController->throwExceptions('1'===$flag);
    }
    
    /**
     * start the bootstrap for unit test
     *
     */
    public function unitBootstrap()
    {
        $this->bootstrap(self::TEST_MODE);
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
            $write = new Zend_Log_Writer_Stream(APPLICATION_ROOT . '/data/logs/' . self::ERROR_LOG);
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
        Read configurations of any sections.
        This function manages the storage, the cache, lazy initializing issue.
        
        @param $key string key name
        @param $is_fresh boolean to read from persisten storage or not.
        @return string configuration value.
     */
    function readSysConfig($key, $isFresh = false)
    {
        assert(!empty($key) && is_bool($isFresh));
        if (self::isInstall() && 
            (!Zend_Registry::isRegistered('FISMA_REG') 
             || !Zend_Registry::get('FISMA_REG')->isFresh)) {         
            $db = Zend_Db::factory(Zend_Registry::get('datasource'));
            $m = new Config($db);
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
        if ( !isset(Zend_Registry::get('FISMA_REG')->$key) ) {
            throw new Exception_General(
            "$key does not exist in system configuration");
        }

        return Zend_Registry::get('FISMA_REG')->$key;
    }

    /**
     * Read Ldap configurations
     *   
     * @return array ldap configurations
     */
    function readLdapConfig()
    {
        $ldap = $this->readSysConfig('ldap');
        if (empty($ldap)) {
            $db = Zend_Registry::get('db');
            $query = $db->select()->from('ldap_config', '*');
            $result = $db->fetchAll($query);
            foreach ($result as $row) {
                $multiOptions[$row['group']][$row['key']] = $row['value'];
            }
            $ldap = new Zend_Config(array('ldap'=>$multiOptions));
            $this->addSysConfig($ldap);
        }
        return $ldap->toArray();
    }


    /**
     * To determind if the application has been properly installed.
     * 
     * @return bool 
     */
    public function isInstall()
    {
        $reg = Zend_Registry::getInstance();
        if ( $reg->isRegistered('datasource') ) {
            return true;
        } 

        try {
            $config = new Zend_Config_Ini(self::$_root."/" . 
                self::PATH_CONFIG . self::INSTALL_CONFIG);
            if (!empty($config->database)) {
                Zend_Registry::set('datasource', $config->database); 
                self::addSysConfig($config->general);
                return true;
            }
        } catch (Zend_Config $e) {
            //logging
        }
        return false;
    }

        
    /**
     * use Registry SYSCONFIG to merge other config
     * @param object @config  
     * @return Zend_Registry
     */
    public function addSysConfig($config)
    {
        if (Zend_Registry::isRegistered('FISMA_REG')) {
            $sysconfig = Zend_Registry::get('FISMA_REG');
            $sysconfig = new Zend_Config($sysconfig->toArray(), $allowModifications = true);
            $sysconfig->merge($config);
            Zend_Registry::set('FISMA_REG', $sysconfig);
        } else {
            Zend_Registry::set('FISMA_REG', $config);
        } 
    }

    /**
     * Get real paths of the installed application
     *
     * @param string $part the component of the path
     * @return string the path
     */ 
    public function getPath($part='root')
    {
        $ret = self::$_root;
        if (!isset($this->_path[$part])) {
            assert(false);
        } else {
            $ret .= "/{$this->_path[$part]}";
        }
        return $ret;
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

    /**
     * @todo english
     * Update Zend_Search_Lucene index
     *
     * This function can create one, update one and update a number of Zend_Lucene indexes.
     *
     * @param string index $indexName under the "data/index/" folder
     * @param string|array $id
     *           string specific a table primary key   
     *                      if the id exists in the index, then update it, else create a index.
     *           array  specific index docuement ids
     *                      update a number of exist indexes
     * @param array $data fields need to update
     */
    public static function updateIndex($indexName, $id, $data)
    {
        if (!is_dir(APPLICATION_ROOT . '/data/index/'.$indexName)) {
            return false;
        }
        $index = new Zend_Search_Lucene(APPLICATION_ROOT . '/data/index/'.$indexName);
        if (is_array($id)) {
            //Update many indexes
            foreach ($id as $oneId) {
                $doc = $index->getDocument($oneId);
                foreach ($data as $field=>$value) {
                    $doc->addField(Zend_Search_Lucene_Field::UnStored($field, $value));
                }
                $index->addDocument($doc);
            }
        } else {
            $hits = $index->find('key:'.md5($id));
            if (!empty($hits)) {
                //Update one index
                $doc = $index->getDocument($hits[0]);
                foreach ($data as $field=>$value) {
                    $doc->addField(Zend_Search_Lucene_Field::UnStored($field, $value));
                }
                $index->addDocument($doc);
            } else {
                //Create one index
                $doc = new Zend_Search_Lucene_Document();
                $doc->addField(Zend_Search_Lucene_Field::UnIndexed('rowId', $id));
                $doc->addField(Zend_Search_Lucene_Field::UnStored('key', md5($id)));
                foreach ($data as $field=>$value) {
                    $doc->addField(Zend_Search_Lucene_Field::UnStored($field, $value));
                }
                $index->addDocument($doc);
            }
        }
        $index->commit();
    }

    /**
     * @todo english
     * Delete Zend_Search_Lucene index
     *
     * @param string index indexName under the "data/index/" folder
     * @param integer $id row id which is indexed by Zend_Lucene
     */
    public static function deleteIndex($indexName, $id)
    {
        if (!is_dir(APPLICATION_ROOT . '/data/index/'.$indexName)) {
            return false;
        }
        $index = new Zend_Search_Lucene(APPLICATION_ROOT . '/data/index/'.$indexName);
        $hits = $index->find('key:'.md5($id));
        $index->delete($hits[0]);
        $index->commit();
    }

    /**
     * Fuzzy Search by Zend_Search_Lucene
     *
     * @param string $keywords the search conditions
     *      The keywords should be as following format:
     *              a.   keyword (search keyword in all fields)
     *              b.   field:keyword (search keyword in field)
     *              c.   keyword1 field:keyword2 -keyword3 (required keyword1 in all fields,
     *                   required keyword2 in field, not required keyword3 in all fields)
     *              d.   keywor*  (to search for keywor, keyword, keywords, etc.)
     *              e.   keywo?d  (to search for keyword, keywoaed ,etc.)
     *              f.   mod_date:[20080101 TO 20080130] (search mod_date fields between 20080101 and 20080130)
     *              g.   title:{Aida To Carmen} (search whose titles would be sorted between Aida and Carmen)
     *              h.   keywor~  (fuzzy search, search like keyword, leyword, etc.)
     *              i.   keyword1 AND keyword2 (search documents that contain keyword1 and keyword2)
     *              j.   keyword1 OR keyword2 (search docuements that contain keyword1 or keyword2)
     *              k.   keyword1 AND NOT keyword2 (search documents that contain keyword1 but not keywords2)
     *              ... see Zend_Search_Lucene for more format
     * @param string $indexName index name
     * @return array table row ids
     */
    public function searchQuery($keywords, $indexName)
    {
        if (!is_dir(APPLICATION_ROOT . '/data/index/' . $indexName)) {
            return false;
        }
        $cache = Zend_Registry::get('cache');
        $index = new Zend_Search_Lucene(APPLICATION_ROOT . '/data/index/' . $indexName);
        if (!$cache->load('keywords') || $keywords != $cache->load('keywords')) {
            $hits = $index->find($keywords);
            $ids = array();
            foreach ($hits as $row) {
                $id = $row->rowId;
                if (!empty($id)) {
                    $ids[] = $id;
                }
            }
            $cache->save($ids, $indexName);
            $cache->save($keywords, 'keywords');
        }
        return $cache->load($indexName);
    }
}
