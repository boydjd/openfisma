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
 */

// APPLICATION CONSTANTS - Set the constants to use in this application.
// These constants are accessible throughout the application, even in ini 
// files. 
defined('APPLICATION_ROOT')
    or define('APPLICATION_ROOT', realpath(dirname(__FILE__) . '/..'));

defined('APPLICATION_PATH')
    or define('APPLICATION_PATH', dirname(__FILE__));

// APPLICATION ENVIRONMENT - This sets the operating environment for OpenFISMA.
// This should be set to either development, test, or production. The application will use the 
// config settings for the respective environment setting. For example, if development is
// currently set, OpenFISMA will utilize the development settings in the app.ini file under
// the config directory. 
defined('APPLICATION_ENVIRONMENT')
    or define('APPLICATION_ENVIRONMENT', 'development');

// INCLUDE PATH - Several libraries and files need to be available to our application when
// searching for their location. We need to include these directories in the include path
// so the application automatically searches these directories looking for files. This array
// puts together a list of directories to add to the include path
$includeDirectories = array(
    APPLICATION_ROOT . '/library',
    APPLICATION_ROOT . '/library/local',
    APPLICATION_ROOT . '/library/Pear',
    APPLICATION_ROOT . '/application/models',
);

// APPLICATION_PATH is a constant pointing to our application/ subdirectory.
// We use this to add our "library" directory to the include_path, so that 
// PHP can find our Zend Framework classes.
set_include_path(implode(PATH_SEPARATOR, $includeDirectories) . PATH_SEPARATOR . get_include_path());

// AUTOLOADER - Set up autoloading.
// This is a nifty trick that allows ZF to load classes automatically so
// that you don't have to litter your code with 'include' or 'require'
// statements.
require_once "Zend/Loader.php";
Zend_Loader::registerAutoload();

// FRONT CONTROLLER - Get the front controller.
// The Zend_Front_Controller class implements the Singleton pattern, which is a
// design pattern used to ensure there is only one instance of
// Zend_Front_Controller created on each request.
$frontController = Zend_Controller_Front::getInstance();

// CONTROLLER DIRECTORY SETUP - Point the front controller to your action
// controller directory.
$frontController->setControllerDirectory(APPLICATION_PATH . '/controllers');

// APPLICATION ENVIRONMENT - Set the current environment
// Set a variable in the front controller indicating the current environment --
// commonly one of development, staging, testing, production, but wholly
// dependent on your organization and site's needs.
$frontController->setParam('env', APPLICATION_ENVIRONMENT);

// LAYOUT SETUP - Setup the layout component
// The Zend_Layout component implements a composite (or two-step-view) pattern
// In this call we are telling the component where to find the layouts scripts.
Zend_Layout::startMvc(APPLICATION_PATH . '/layouts/scripts');

// CONFIGURATION - Setup the configuration object
// The Zend_Config_Ini component will parse the ini file, and resolve all of
// the values for the given section.  Here we will be using the section name
// that corresponds to the APP's Environment
$configuration = new Zend_Config_Ini(APPLICATION_ROOT . '/application/config/app.ini', APPLICATION_ENVIRONMENT);

// REGISTRY - setup the application registry
// An application registry allows the application to store application
// necessary objects into a safe and consistent (non global) place for future
// retrieval.  This allows the application to ensure that regardless of what
// happends in the global scope, the registry will contain the objects it
// needs.
$registry = Zend_Registry::getInstance();
$registry->configuration = $configuration;

/**
 * @todo Is this necessary to do? This variable isn't used anywhere.
 */
 // Initialize the global setting object
$fisma = Config_Fisma::getInstance();

// If we are in command line mode, then drop out of the bootstrap before we
// render any views.
if (defined('COMMAND_LINE')) {
    return;
}

// VIEW SETUP - Initialize properties of the view object
// The Zend_View component is used for rendering views. Here, we grab a "global"
// view instance from the layout object, and specify the doctype we wish to
// use -- in this case, HTML4 Strict.
$view = Zend_Layout::getMvcInstance()->getView();
$view->doctype('HTML4_STRICT');

// Start Session Handling using Zend_Session 
Zend_Session::start($configuration);

// This configuration option tells Zend_Date to use the standard PHP date format
// instead of standard ISO format. This is convenient for interfacing Zend_Date
// with legacy PHP code.
Zend_Date::setOptions(array('format_type' => 'php'));

$viewRender = Zend_Controller_Action_HelperBroker::
              getStaticHelper('viewRenderer');
//$viewRender->setNeverRender(true);
Zend_Controller_Action_HelperBroker::addHelper($viewRender);

$router = $frontController->getRouter();
if (!Config_Fisma::isInstall()) {
    // If the application has not been installed yet, then define the route so
    // that only the installController can be invoked. This forces the user to
    // complete installation before using the application.
    $route['install'] = new Zend_Controller_Router_Route_Regex (
                                '([^/]*)/?(.*)$',
                                array('controller' => 'install'),
                                array('action' => 2),
                                'install/%2$s'
                            );
    $router->addRoute('default', $route['install']);

    // The installer has its own error handler which is registered here:
    $frontController->registerPlugin(new Zend_Controller_Plugin_ErrorHandler(
        array(
            'model' => null,
            'controller' => 'Install',
            'action' => 'error'
            )
        ));
} else {

    // If the application has been installed, then set up the data source,
    // default routes, and default error handler.
    $db = Zend_Db::factory(Zend_Registry::get('datasource'));
    Zend_Db_Table::setDefaultAdapter($db);
    Zend_Registry::set('db', $db);
    
    // Define an additional route to handle the final page of the installer:
    $route['install_end'] = new 
        Zend_Controller_Router_Route_Static(
            'install/complete',
            array(
                'controller' => 'install',
                'action' => 'complete'
            )
        );
    $router->addRoute('install_end', $route['install_end']);
    
    // Disallow any route which invokes the installation controller. This
    // prevents accidental or malicious execution of the installer over an
    // already installed application.
    $route['no_install'] = new
        Zend_Controller_Router_Route_Regex(
        'install/.*',
        array(
            'controller' => 'user',
            'action' => 'logout'
        )
    );
    $router->addRoute('no_install', $route['no_install']);
    
    // Register the default error controller
    $frontController->registerPlugin(new Zend_Controller_Plugin_ErrorHandler(
        array(
            'model' => null,
            'controller' => 'Error',
            'action' => 'error'
        )
    ));
}

// CLEANUP - remove items from global scope
// This will clear all our local boostrap variables from the global scope of 
// this script (and any scripts that called bootstrap).  This will enforce 
// object retrieval through the Applications's Registry
unset($frontController, $view, $configuration, $dbAdapter, $registry);
