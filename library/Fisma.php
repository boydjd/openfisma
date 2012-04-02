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
     * The application configuration, stored in application/config/application.ini
     *
     * @var Zend_Config_Ini
     */
    public static $appConf;

    /**
     * The root path of the application.
     *
     * @var string
     */
    private static $_rootPath;

    /**
     * An array of paths to special parts of the application, such as the log directory, cache directory, etc.
     * These are relative to the root path.
     *
     * @see $_includePath;
     * @var array;
     */
    private static $_applicationPath = array(
        'application' => 'application',
        'cache' => 'data/cache',
        'config' => 'application/config',
        'data' => 'data',
        'fixture' => 'application/doctrine/data/fixtures',
        'form' => 'application/modules/default/forms',
        'formViews' => 'application/modules/default/views/scripts/form-element',
        'image' => 'public/images',
        'index' => 'data/index',
        'layout' => 'application/layouts/scripts',
        'listener' => 'application/models/listener',
        'log' => 'data/logs',
        'migration' => 'application/migrations',
        'migrationViews' => 'application/migration-views',
        'sampleData' => 'application/doctrine/data/sample',
        'sampleDataBuild' => 'application/doctrine/data/sample-build',
        'schema' => 'application/doctrine/schema',
        'scripts' => 'scripts',
        'systemDocument' => 'data/uploads/system-document',
        'temp' => 'data/temp',
        'test' => 'tests',
        'uploads' => 'data/uploads',
        'fileStorage' => 'data/uploads',
        'viewHelper' => 'application/modules/default/views/helpers',
        'yui' => 'public/yui'
    );

    /**
     * A zend session that OpenFISMA can use without worries about collisions to other frameworks that may
     * be running.
     *
     * @var Zend_Session_Namespace
     */
    private static $_session;

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
     * @throws Fisma_Zend_Exception if neither the environment parameter in application.ini is 'production' nor
     * 'development'
     */
    public static function initialize($mode)
    {
        self::$_mode = $mode;

        // Determine the root path of the application. This is based on knowing where this file is relative
        // to the root. So if this file moves, then this logic won't work anymore.
        self::$_rootPath = realpath(dirname(__FILE__) . '/../');

        // Enable the Zend autoloader. This depends on the Zend library being in its expected place.
        require_once(self::$_rootPath . '/library/Zend/Loader/Autoloader.php');
        $loader = Zend_Loader_Autoloader::getInstance();
        $loader->setFallbackAutoloader(true);

        // Enable autoloading for application resources
        $resourceLoader = new Zend_Loader_Autoloader_Resource(array(
            'basePath'  => self::$_rootPath,
            'namespace' => 'Application_'
        ));

        $resourceLoader->addResourceType('service', 'application/services/', 'Service_');
        $resourceLoader->addResourceType('migration', 'application/migrations/', 'Migration_');

        // Set the initialized flag
        self::$_initialized = true;

        // Configure the autoloader to suppress warnings in production mode, but enable them in development mode
        $loader->suppressNotFoundWarnings(!Fisma::debug());
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
            throw new Fisma_Zend_Exception('System has no configuration object');
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
     * Returns whether Fisma_Doctrine_Record_Listeners are enabled
     *
     * @return boolean Ture if listener is enabled, false otherwise
     */
    public static function getListenerEnabled()
    {
        return self::$_listenerEnabled;
    }

    /**
     * Enable or disable all Fisma_Doctrine_Record_Listeners
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

        // Enumerate all classes and search for ones that subclass the Fisma_Doctrine_Record_Listener marker class
        $classes = get_declared_classes();
        foreach ($classes as $class) {
            if (is_subclass_of($class, 'Fisma_Doctrine_Record_Listener')) {
                Fisma_Doctrine_Record_Listener::setEnabled($enabled);
            }
        }
    }

    /**
     * Returns true if in debug mode, false otherwise.
     *
     * @return bool Ture if in debug mode, false otherwise
     * @throws Fisma_Zend_Exception if the Fisma object has not been initialized
     */
    public static function debug()
    {
        if (!self::$_initialized) {
            throw new Fisma_Zend_Exception('The Fisma object has not been initialized.');
        }

        if (!isset(self::$appConf['debug'])) {
            return false;
        } else {
            return (self::$appConf['debug'] == 1);
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
     * @throws Fisma_Zend_Exception if no path found for the key
     * @see Fisma::initialize()
     */
    public static function getPath($key)
    {
        if (!self::$_initialized) {
            throw new Fisma_Zend_Exception('The Fisma object has not been initialized.');
        }

        if (isset(self::$appConf['includePaths'][$key])) {
            return self::$appConf['includePaths'][$key];
        } elseif (isset(self::$_applicationPath[$key])) {
            return self::$_rootPath . '/' . self::$_applicationPath[$key];
        } else {
            throw new Fisma_Zend_Exception("No path found for key: \"$key\"");
        }
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
        return Zend_Date::now()->toString(Fisma_Date::FORMAT_DATETIME);
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
            throw new Fisma_Zend_Exception('Configuration already exists');
        } else {
            self::$_configuration = $config;
        }
    }

    /**
     * Return a Zend_Session_Namespace which is unique to OpenFISMA (won't collide with any other framework
     * that may be running.)
     *
     * @return Zend_Session_Namespace
     */
    public static function getSession()
    {
        if (!self::$_session) {
            self::$_session = new Zend_Session_Namespace('OpenFISMA');
        }

        return self::$_session;
    }

    /**
     * setAppConfig
     *
     * @param array $config
     * @static
     * @access public
     * @return void
     */
    public static function setAppConfig(array $config)
    {
        self::$appConf = $config;
    }

    /**
     * Wrapper for htmlentities to turn off double encoding
     *
     * @param mixed $value
     * @static
     * @access public
     * @return void
     */
    public static function htmlentities($value)
    {
        return htmlentities($value, ENT_COMPAT, 'UTF-8', FALSE);
    }
}
