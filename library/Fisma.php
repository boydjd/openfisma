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
 * @version   $Id: $
 * @package   Fisma
 */

/**
 * An object which represents the application itself, and controls items such as debug mode, include paths,
 * etc.
 *
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/mw/index.php?title=License
 * @package   Fisma
 */
class Fisma
{
    /**
     * Indicates that the application is running as a web application.
     * 
     * In web application mode, the application needs a front controller, authentication, sessions, etc.
     */
    const RUN_MODE_WEB_APP = 0;
    
    /**
     * Indicates that the application is running from the command line, such as a tool or unit test
     * 
     * In command line mode, the application doesn't need any of the things the web app needs, but it
     * does need to have the path setup so it can find the classes it needs
     */
    const RUN_MODE_COMMAND_LINE = 1;
    
    /**
     * Indicates that the application is running as a web service
     * 
     * This mode isn't used as of version 2.3, but it's placed here for future expansion
     */
    const RUN_MODE_WEB_SERVICE = 2;
    
    /**
     * The run mode the application is currently using. This must be set to one of the 
     * RUN_MODE constants above.
     */
    private static $_mode;
    
    /**
     * A flag that indicates whether the Fisma class has been intialized yet
     * 
     * @var boolean
     */
    private static $_initialized = false;
    
    /**
     * True if notifications are enabled. False otherwise. 
     * 
     * Notifications should be disabled in some circumstances, such as running the doctrine-cli script. Defaults to 
     * true.
     * 
     * @var boolean
     */
    private static $_notificationEnabled = true;
    
    /**
     * True if doctrine listeners (preSave, postInsert, etc.) are enabled. False otherwise.
     * 
     * This is useful when running the doctrine CLI script to disable listeners which are not needed.
     */
    private static $_listenerEnabled = true;
    
    /**
     * The application configuration, stored in application/config/app.conf
     * 
     * @var Zend_Config_Ini
     */
    private static $_appConf;
    
    /**
     * The root path of the application.
     * 
     * @var string
     */
    private static $_rootPath;
    
    /**
     * An array of include paths for the application. This is where PHP will search for include
     * files, such as autoloaded classes.
     * 
     * @see $_includePath;
     * @var array;
     */
    private static $_includePath;
    
    /**
     * An array of paths to special parts of the application, such as the log directory, cache directory,
     * etc. This is separate from $_includePath because 
     * 
     * @see $_includePath;
     * @var array;
     */
    private static $_applicationPath;
    
    /**
     * A single instance of Zend_Log which the application components share
     */
    private static $_log;

    /**
     * A single instance of Zend_Cache which the application components share
     */
    private static $_cache;
    
    /**
     * Initialize the FISMA object
     * 
     * This sets up the root path, include paths, application paths, and then loads the application configuration.
     * This can be considered a bootrap of sorts.
     * 
     * @param int $mode One of the run modes specified as constants in this class
     */
    public static function initialize($mode) {
        self::$_mode = $mode;
        
        // Determine the root path of the application. This is based on knowing where this file is relative
        // to the root. So if this file moves, then this logic won't work anymore.
        self::$_rootPath = realpath(dirname(__FILE__) . '/../');

        // Set up include paths. These are relative to the root path. The most used paths should be at the top.
        self::$_includePath = array(
            'doctrine-models' => 'application/models/generated',
            'model' => 'application/models',
            'listener' => 'application/models/listener',
            'library' => 'library',
            'pear' => 'library/Pear'
        );
        
        // Prepend the include paths to PHP's path.
        // I discovered that PEAR has a class called "System". If the user has this class, then PEAR's System
        // may override OpenFISMA's. For this reason, OpenFISMA's include path is prepended to the user's default
        // path, instead of appended... to prevent any user libraries from clashing with our own.
        $currentPath = '';
        foreach (self::$_includePath as $path) {
            $currentPath .= PATH_SEPARATOR . realpath(self::$_rootPath . '/' . $path);
        }
        set_include_path($currentPath . PATH_SEPARATOR . get_include_path());

        // Enable the Zend autoloader. This depends on the Zend library being in its expected place.
        require_once(self::$_rootPath . '/library/Zend/Loader.php');
        Zend_Loader::registerAutoload();

        // Set up application paths. These are relative to the root path.
        self::$_applicationPath = array(
            'application' => 'application',
            'cache' => 'data/cache',
            'config' => 'application/config',
            'controller' => 'application/controllers',
            'data' => 'data',
            'fixture' => 'application/doctrine/data/fixtures',
            'form' => 'application/config/form',
            'image' => 'public/images',
            'index' => 'data/index',
            'layout' => 'application/layouts/scripts',
            'listener' => 'application/models/listener',
            'log' => 'data/logs',
            'migration' => 'application/doctrine/migrations',
            'sampleData' => 'application/doctrine/data/sample',
            'schema' => 'application/doctrine/schema',
            'systemDocument' => 'data/uploads/system-document',
            'test' => 'tests',
            'viewHelper' => 'application/views/helpers',
            'yui' => 'public/yui'
        );

        // Load the system configuration
        $appConfFile = self::$_rootPath . '/' . self::$_applicationPath['config'] . '/app.conf';
        $conf = new Zend_Config_Ini($appConfFile);
        if ('production' == $conf->environment) {
            self::$_appConf = $conf->production;
        } elseif ('development' == $conf->environment) {
            self::$_appConf = $conf->development;
        } else {
            throw new Fisma_Exception("The environment parameter in app.conf must be either \"production\" or "
                                    . "\"development\" but it's actually \"$conf->environment\"");
        }

        // PHP configuration
        $phpOptions = self::$_appConf->php->toArray();
        foreach ($phpOptions as $param => $value) {
            ini_set($param, $value);
        }

        // Xdebug configuration
        if (isset(self::$_appConf->xdebug)) {
            foreach (self::$_appConf->xdebug as $param => $value) {
                ini_set("xdebug.$param", $value);
            }
        }
        
        // Session configuration
        $sessionOptions = self::$_appConf->session->toArray();
        $sessionOptions['save_path'] = self::$_rootPath . '/' . $sessionOptions['save_path'];
        Zend_Session::setOptions($sessionOptions);

        // Set the initialized flag
        self::$_initialized = true;
    }
    
    /**
     * Connect to the database
     */
    public static function connectDb() {
        // Connect to the database
        $db = self::$_appConf->db;
        $connectString = "mysql://{$db->username}:{$db->password}@{$db->host}/{$db->schema}";
        Doctrine_Manager::connection($connectString);
        $manager = Doctrine_Manager::getInstance();
        $manager->setAttribute(Doctrine::ATTR_USE_DQL_CALLBACKS, true);
        $manager->setAttribute(Doctrine::ATTR_USE_NATIVE_ENUM, true);
        Zend_Registry::set('doctrine_config', array(
               'data_fixtures_path'  =>  self::getPath('fixture'),
               'models_path'         =>  self::getPath('model'),
               'migrations_path'     =>  self::getPath('migration'),
               'yaml_schema_path'    =>  self::getPath('schema')
        ));
    }
    
    /**
     * Configure the front controller and then dispatch it
     * 
     * @todo this is a bit ugly, it's got some unrelated stuff in it
     */
    public static function dispatch() {
        // This is a hack to accomodate the flash file uploader. Flash can't send cookies, so it posts the session
        // ID instead.
        /** @todo review this -- is it any kind of a security risk? */
        if (isset($_POST['sessionId'])) {
            Zend_Session::setId($_POST['sessionId']);
        }
        
        $frontController = Zend_Controller_Front::getInstance();
        $frontController->setControllerDirectory(Fisma::getPath('controller'));

        Zend_Date::setOptions(array('format_type' => 'php'));
        Zend_Layout::startMvc(self::getPath('layout'));
        
        Zend_Controller_Action_HelperBroker::addPrefix('Fisma_Controller_Action_Helper');

        // Configure the views
        $view = Zend_Layout::getMvcInstance()->getView();
        $view->addHelperPath(self::getPath('viewHelper'), 'View_Helper_');
        $view->doctype('HTML4_STRICT');
        $viewRenderer = Zend_Controller_Action_HelperBroker::getStaticHelper('viewRenderer');
        $viewRenderer->setView($view);
        $viewRenderer->setViewSuffix('phtml');

        $frontController->dispatch();
    }
    
    /**
     * Returns the current execution mode.
     */
    public static function mode() {
        return self::$_mode;
    }
    
    /**
     * Returns whether notifications are enabled
     * 
     * @return boolean
     */
    public static function getNotificationEnabled() {
        return self::$_notificationEnabled;
    }
    
    /**
     * Sets whether notifications are enabled or not.
     * 
     * @param boolean $enabled
     */
    public static function setNotificationEnabled($enabled) {
        self::$_notificationEnabled = $enabled;
    }

    /**
     * Returns whether listeners are enabled
     * 
     * @return boolean
     */
    public static function getListenerEnabled() {
        return self::$_listenerEnabled;
    }
    
    /**
     * Sets whether listeners are enabled or not.
     * 
     * @param boolean $enabled
     */
    public static function setListenerEnabled($enabled) {
        self::$_listenerEnabled = $enabled;
        
        // Enumerate the models and enable/disable the listeners for each one
        $modelDir = opendir(Fisma::getPath('model'));
        while ($file = readdir($modelDir)) {
            if ($match = strpos($file, '.php')) {
                $modelName = substr($file, 0, $match);
                require_once(Fisma::getPath('model') . '/' . $file);
                Doctrine::getTable($modelName)->getRecordListener()->setOption('disabled', !self::$_listenerEnabled);
            }
        }
    }
    
    /**
     * Returns true if in debug mode, false otherwise.
     * 
     * @return bool
     */
    public static function debug() {
        if (!self::$_initialized) {
            throw new Fisma_Exception('The Fisma object has not been initialized.');
        }
        
        return (self::$_appConf->debug == 1);
    }
    
    /**
     * Returns the path to a special part of the application, based on the provided key. 
     * 
     * This is just a shortcut to find common paths, and allows us to move things around without needing
     * to rewrite a bunch of classes. To see what keys are valid, look at the initialize function.
     * 
     * @see Fisma::initialize()
     * 
     * @param string $key
     * @return string
     */
    public static function getPath($key) {
        if (!self::$_initialized) {
            throw new Fisma_Exception('The Fisma object has not been initialized.');
        }
        
        if (isset(self::$_includePath[$key])) {
            return self::$_rootPath . '/' . self::$_includePath[$key];
        } elseif (isset(self::$_applicationPath[$key])) {
            return self::$_rootPath . '/' . self::$_applicationPath[$key];
        } else {
            throw new Fisma_Exception("No path found for key: \"$key\"");
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
    public static function getLogInstance()
    {
        if (null === self::$_log) {
            $write = new Zend_Log_Writer_Stream(self::getPath('log') . '/error.log');
            $auth = Zend_Auth::getInstance();
            if ($auth->hasIdentity()) {
                $user = User::currentUser();
                $format = '%timestamp% %priorityName% (%priority%): %message% by ' .
                    "$user->username ($user->id) from {$_SERVER['REMOTE_ADDR']}" . PHP_EOL;
            } else {
                $format = '%timestamp% %priorityName% (%priority%): %message% by ' .
                    "{$_SERVER['REMOTE_ADDR']}" . PHP_EOL;
            }
            $formatter = new Zend_Log_Formatter_Simple($format);
            $write->setFormatter($formatter);
            self::$_log = new Zend_Log($write);
        }
        return self::$_log;
    }

    /**
     * Initialize the cache instance
     *
     * make the directory "/path/to/data/cache" writable
     * 
     * @param string $identify
     * @return Zend_Cache
     */
    public static function getCacheInstance($identify = null)
    {
        if (null === self::$_cache) {
            $frontendOptions = array(
                'caching'                 => true,
                // cache life same as system expiring period
                'lifetime'                => Configuration::getConfig('session_inactivity_period'), 
                'automatic_serialization' => true
            );

            $backendOptions = array(
                'cache_dir' => Fisma::getPath('cache'),
                'file_name_prefix' => $identify
            );
            self::$_cache = Zend_Cache::factory('Core',
                                                'File',
                                                $frontendOptions,
                                                $backendOptions);
        }
        return self::$_cache;
    }

    /**
     * Returns the current timestamp in DB friendly format
     * 
     * This function is provided as a convenience for getting a timestamp which can be inserted
     * into the database without needing to know the database's format for datetime strings. The
     * timestamp is captured during initialization and frozen throughout execution of the script.
     * 
     * @todo this is designed to work with Mysql... would it work with Oracle? Db2? Dunno...
     * @return string A database friendly representation of the current time
     */
    public static function now() {
        return date('Y-m-d H:i:s');
    }
}
