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
 * An object which represents the application itself, and controls items such as debug mode, include paths, etc.
 * 
 * @author     Mark E. Haase <mhaase@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Fisma
 * @version    $Id$
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
     * Indicates that the application is being tested
     */
     const RUN_MODE_TEST = 3;
    
    /**
     * The run mode the application is currently using. This must be set to one of the 
     * RUN_MODE constants above.
     * 
     * @var int
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
     * 
     * @var boolean
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
     * 
     * @var Zend_Log
     */
    private static $_log;

    /**
     * A single instance of Zend_Cache which the application components share
     *
     * @todo Move into the cache manager 
     * @var Zend_Cache
     */
    private static $_cache;

    /**
     * A single instance of Zend_Cache_Manager which the application components share 
     */
    private static $_cacheManager;
    
    /**
     * A flag that indicates whether the Fisma system has been installed yet
     * 
     * @var boolean
     */
    private static $_isInstall = false;
    
    /**
     * A system-wide configuration object
     * 
     * @var Fisma_Configuration_Interface
     */
    private static $_configuration;
    
    /**
     * Initialize the FISMA object
     * 
     * This sets up the root path, include paths, application paths, and then loads the application configuration.
     * This can be considered a bootrap of sorts.
     * 
     * @param int $mode One of the run modes specified as constants in this class
     * @return void
     * @throws Fisma_Exception if neither the environment parameter in app.conf is 'production' nor 'development'
     */
    public static function initialize($mode) 
    {
        self::$_mode = $mode;
        
        // Determine the root path of the application. This is based on knowing where this file is relative
        // to the root. So if this file moves, then this logic won't work anymore.
        self::$_rootPath = realpath(dirname(__FILE__) . '/../');

        // Set up include paths. These are relative to the root path. The most used paths should be at the top.
        self::$_includePath = array(
            'doctrine-models' => 'application/models/generated',
            'model' => 'application/models',
            'controller' => 'application/controllers',
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
        require_once(self::$_rootPath . '/library/Zend/Loader/Autoloader.php');
        $loader = Zend_Loader_Autoloader::getInstance();
        $loader->registerNamespace('Fisma_');
        $loader->setFallbackAutoloader(true);

        // Set the initialized flag
        self::$_initialized = true;
        
        // Set up application paths. These are relative to the root path.
        self::$_applicationPath = array(
            'application' => 'application',
            'cache' => 'data/cache',
            'config' => 'application/config',
            'data' => 'data',
            'fixture' => 'application/doctrine/data/fixtures',
            'form' => 'application/form',
            'image' => 'public/images',
            'index' => 'data/index',
            'layout' => 'application/layouts/scripts',
            'listener' => 'application/models/listener',
            'log' => 'data/logs',
            'migration' => 'application/doctrine/migrations',
            'sampleData' => 'application/doctrine/data/sample',
            'sampleDataBuild' => 'application/doctrine/data/sample-build',
            'schema' => 'application/doctrine/schema',
            'systemDocument' => 'data/uploads/system-document',
            'test' => 'tests',
            'scripts' => 'scripts',
            'viewHelper' => 'application/views/helpers',
            'yui' => 'public/yui'
        );

        // Load the system configuration
        $appConfFile = self::$_rootPath . '/' . self::$_applicationPath['config'] . '/app.conf';
        if (file_exists($appConfFile)) {
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

            // Timezone configuration
            if (isset(self::$_appConf->timezone)) {
                ini_set("date.timezone", self::$_appConf->timezone);
            } else {
                ini_set("date.timezone", "America/New_York");
            }

            // Log all PHP errors
            ini_set('error_reporting', E_ALL | E_STRICT);
            ini_set('log_errors', TRUE);
            ini_set('error_log', self::$_rootPath . '/data/logs/php.log');

            // Session configuration
            $sessionOptions = self::$_appConf->session->toArray();
            $sessionOptions['save_path'] = self::$_rootPath . '/' . $sessionOptions['save_path'];
            Zend_Session::setOptions($sessionOptions);
            self::$_isInstall = true;
        } else {
            self::$_isInstall = false;
        }
        
        // Configure the autoloader to suppress warnings in production mode, but enable them in development mode
        $loader->suppressNotFoundWarnings(!Fisma::debug());
    }
    
    /**
     * To determine whether the Openfisma is installed
     *
     * @return boolean Ture if Openfisma is installed, false otherwise
     */
    public static function isInstall()
    {
        return self::$_isInstall;
    }
    
    /**
     * Return the system configuration object
     * 
     * @return Fisma_Configuration_Interface
     */
    public static function configuration()
    {
        if (self::$_configuration) {
            return self::$_configuration;
        } else {
            throw new Fisma_Exception('System has no configuration object');
        }
    }

    /**
     * Connect to the database
     * 
     * @return void
     */
    public static function connectDb()
    {
        // Connect to the database
        if (self::mode() != self::RUN_MODE_TEST) {
            $db = self::$_appConf->db;
        } else {
            $db = self::$_appConf->testdb;
        }
        $connectString = $db->adapter . '://' . $db->username . ':' 
                         . $db->password . '@' . $db->host 
                         . ($db->port ? ':' . $db->port : '') . '/' . $db->schema;

        Doctrine_Manager::connection($connectString);
        $manager = Doctrine_Manager::getInstance();
        $manager->setAttribute(Doctrine::ATTR_USE_DQL_CALLBACKS, true);
        $manager->setAttribute(Doctrine::ATTR_USE_NATIVE_ENUM, true);
        $manager->registerValidators(array('Fisma_Validator_Ip', 'Fisma_Validator_Url'));
        /**
         * @todo We want to enable VALIDATE_ALL in release 2.6
         */
        $manager->setAttribute(Doctrine::ATTR_VALIDATE, Doctrine::VALIDATE_CONSTRAINTS);

        /**
         * Set up the cache driver and connect to the manager.
         * Make sure that we only cache in web app mode, and that the application is installed.
         **/
        if (function_exists('apc_fetch') && self::isInstall() && self::mode() == self::RUN_MODE_WEB_APP) {
            $cacheDriver = new Doctrine_Cache_Apc();
            $manager->setAttribute(Doctrine::ATTR_QUERY_CACHE, $cacheDriver);
        }

        Zend_Registry::set(
            'doctrine_config', 
            array(
                'data_fixtures_path'  =>  self::getPath('fixture'),
                'models_path'         =>  self::getPath('model'),
                'migrations_path'     =>  self::getPath('migration'),
                'yaml_schema_path'    =>  self::getPath('schema'),
                'generate_models_options' => array(
                    'baseClassName' => 'Fisma_Record'
                )
            )
        );
    }
    
    /**
     * Configure the front controller and then dispatch it
     * 
     * @return void
     * @todo this is a bit ugly, it's got some unrelated stuff in it
     */
    public static function dispatch() 
    {
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

        if (!self::isInstall()) {
            set_time_limit(0);

            // set the fixed controller when Openfisma has been installed 
            $router = $frontController->getRouter();
            $route['install'] = new Zend_Controller_Router_Route_Regex (
                                        '([^/]*)/?(.*)$',
                                        array('controller' => 'install'),
                                        array('action' => 2),
                                        'install/%2$s'
                                    );
            $router->addRoute('default', $route['install']);
            // set the error handler when Openfisma has been installed
            $eHandler = new Zend_Controller_Plugin_ErrorHandler( array(
                        'model' => null,
                        'controller' => 'Install',
                        'action' => 'error'));
            $frontController->registerPlugin($eHandler);
        }
        // Configure the views
        $view = Zend_Layout::getMvcInstance()->getView();
        $view->addHelperPath(self::getPath('viewHelper'), 'View_Helper_');
        $view->doctype('HTML4_STRICT');
        // Make sure that we don't double encode
        $view->setEscape(array('Fisma', 'htmlentities'));
        $viewRenderer = Zend_Controller_Action_HelperBroker::getStaticHelper('viewRenderer');
        $viewRenderer->setView($view);
        $viewRenderer->setViewSuffix('phtml');

        if (self::mode() != self::RUN_MODE_TEST) {
            $frontController->dispatch();
        }
    }
    
    /**
     * Returns the current execution mode.
     * 
     * @return int The current execution mode
     */
    public static function mode() 
    {
        return self::$_mode;
    }
    
    /**
     * Retrieve the property notificationEnabled value
     * 
     * @return boolean Ture if notifications are enabled, false otherwise
     */
    public static function getNotificationEnabled() 
    {
        return self::$_notificationEnabled;
    }
    
    /**
     * Sets whether notifications are enabled or not.
     * 
     * @param boolean $enabled The specified boolean value which indicates if enable notifications
     * @return void
     */
    public static function setNotificationEnabled($enabled) 
    {
        self::$_notificationEnabled = $enabled;
    }

    /**
     * Returns whether Fisma_Record_Listeners are enabled
     * 
     * @return boolean Ture if listener is enabled, false otherwise
     */
    public static function getListenerEnabled() 
    {
        return self::$_listenerEnabled;
    }
    
    /**
     * Enable or disable all Fisma_Record_Listeners
     * 
     * @param boolean $enabled
     * @param boolean $loadClasses If true, attempt to load all listeners in the listeners directory first
     * @return void
     */
    public static function setListenerEnabled($enabled, $loadClasses = true) 
    {
        self::$_listenerEnabled = $enabled;
        
        if ($loadClasses) {
            // Load all of the listeners first
            $listenerDir = opendir(Fisma::getPath('listener'));

            while ($file = readdir($listenerDir)) {
                if ('.php' == substr($file, -4)) {
                    $className = substr($file, 0, -4);
                    if (!class_exists($className)) {
                        require_once(Fisma::getPath('listener') . '/' . $file);
                    }
                }
            }
        }
        
        // Enumerate all classes and search for ones that subclass the Fisma_Record_Listener marker class
        $classes = get_declared_classes();
        foreach ($classes as $class) {
            if (is_subclass_of($class, 'Fisma_Record_Listener')) {
                Fisma_Record_Listener::setEnabled($enabled);
            }
        }
    }
    
    /**
     * Returns true if in debug mode, false otherwise.
     * 
     * @return bool Ture if in debug mode, false otherwise
     * @throws Fisma_Exception if the Fisma object has not been initialized
     */
    public static function debug() 
    {
        if (!self::$_initialized) {
            throw new Fisma_Exception('The Fisma object has not been initialized.');
        }

        if (!isset(self::$_appConf->debug)) {
            return false;
        } else {
            return (self::$_appConf->debug == 1);
        }
    }
   
    /**
     * Returns the path to a special part of the application, based on the provided key. 
     * 
     * This is just a shortcut to find common paths, and allows us to move things around without needing
     * to rewrite a bunch of classes. To see what keys are valid, look at the initialize function.
     * 
     * @param string $key The specified key to obtain
     * @return string The application path of the key
     * @throws Fisma_Exception if no path found for the key
     * @see Fisma::initialize()
     */
    public static function getPath($key) 
    {
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
     * @return Zend_Log The instance of Zend_Log
     */
    public static function getLogInstance()
    {
        if (null === self::$_log) {
            $write = new Zend_Log_Writer_Stream(self::getPath('log') . '/error.log');
            $auth = Zend_Auth::getInstance();
            $remote = (isset($_SERVER['REMOTE_ADDR'])) ? $_SERVER['REMOTE_ADDR'] : null;

            if ($auth->hasIdentity()) {
                $user = User::currentUser();
                $format = '%timestamp% %priorityName% (%priority%): %message% by ' .
                    "$user->username ($user->id) from {$remote}" . PHP_EOL;
            } else {
                $format = '%timestamp% %priorityName% (%priority%): %message% by ' .
                    "{$remote}" . PHP_EOL;
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
     * @param string $identify The specified file name prefix
     * @return Zend_Cache The instance of Zend_Cache
     */
    public static function getCacheInstance($identify = null)
    {
        if (null === self::$_cache) {
            $frontendOptions = array(
                'caching'                 => true,
                // cache life same as system expiring period
                'lifetime'                => Fisma::configuration()->getConfig('session_inactivity_period'), 
                'automatic_serialization' => true
            );

            $backendOptions = array(
                'cache_dir' => Fisma::getPath('cache'),
                'file_name_prefix' => $identify
            );
            self::$_cache = Zend_Cache::factory(
                'Core',
                'File',
                $frontendOptions,
                $backendOptions
            );
        }
        return self::$_cache;
    }

    /**
     * Initialize the cache manager 
     * 
     * If APC is available, create an APC cache, if it's not, use a file cache.
     *
     * @return Zend_Cache_Manager 
     */
    public static function getCacheManager()
    {
        if (null === self::$_cacheManager) {
            $manager = new Zend_Cache_Manager();

            $frontendOptions = array(
                'caching' => true,
                'lifetime' => 0,
                'automatic_serialization' => true
            );

            if (function_exists('apc_fetch')) {
                $cache = Zend_Cache::factory(
                    'Core',
                    'Apc',
                    $frontendOptions
                );
            } else {
                $backendOptions = array(
                    'cache_dir' => Fisma::getPath('cache'),
                );
                $cache = Zend_Cache::factory(
                    'Core',
                    'File',
                    $frontendOptions,
                    $backendOptions
                );
            }

            $manager->setCache('default', $cache);
            self::$_cacheManager = $manager;
        }

        return self::$_cacheManager;
    }

    /**
     * Returns the current timestamp in DB friendly format
     * 
     * This function is provided as a convenience for getting a timestamp which can be inserted
     * into the database without needing to know the database's format for datetime strings. The
     * timestamp is captured during initialization and frozen throughout execution of the script.
     * 
     * @return string A database friendly representation of the current time
     * @todo this is designed to work with Mysql... would it work with Oracle? Db2? Dunno...
     */
    public static function now() 
    {
        return date('Y-m-d H:i:s');
    }
    
    /**
     * Set the system configuration object
     * 
     * @param Fisma_Configuration_Interface $config
     * @param bool $replace Whether to replace any existing configuration
     */
    public static function setConfiguration(Fisma_Configuration_Interface $config, $replace = false)
    {
        if (self::$_configuration && !$replace) {
            throw new Fisma_Exception('Configuration already exists');
        } else {
            self::$_configuration = $config;
        }
    }

    /**
     * Wrapper for htmlentities to turn off double encoding 
     * 
     * @param mixed $value 
     * @return string 
     */
    public static function htmlentities($value)
    {
        return htmlentities($value, ENT_COMPAT, 'UTF-8', FALSE);
    }
}
