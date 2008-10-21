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
// files. We optionally set APPLICATION_PATH here in case our entry point 
// isn't index.php (e.g., if required from our test suite or a script).
defined('APPLICATION_ROOT')
    or define('APPLICATION_ROOT', realpath(dirname(__FILE__) . '/..'));

defined('APPLICATION_PATH')
    or define('APPLICATION_PATH', dirname(__FILE__));

// The paths.php file contains constants representing commonly accessed paths in the application.
require_once "config/paths.php";

// Register the class loader:
require_once "Zend/Loader.php";
Zend_Loader::registerAutoload();

// Initialize the global setting object
$fisma = Config_Fisma::getInstance();

// If we are in command line mode, then drop out of the bootstrap before we
// render any views.
if (defined('COMMAND_LINE')) {
    return;
}

// Load config.ini file into config object, then set options for Zend_Session
$config = new Zend_Config_Ini('config.ini', 'development');
Zend_Session::start($config);

// Initialize the view renderer
// @todo what is the difference between $viewRenderer and $viewRender?
$viewRenderer = Zend_Controller_Action_HelperBroker::
                getStaticHelper('viewRenderer');
$viewRenderer->initView();
$viewRenderer->view->doctype('HTML4_STRICT');

// This configuration option tells Zend_Date to use the standard PHP date format
// instead of standard ISO format. This is convenient for interfacing Zend_Date
// with legacy PHP code.
Zend_Date::setOptions(array('format_type' => 'php'));

// Set layout options
$options = array(
    'layout' => 'default',
    'layoutPath' => VIEWS . '/layouts',
    'contentKey' => 'CONTENT'
);

// Use ".tpl" as the default view suffix
Zend_Layout::startMvc($options);
$viewRender = Zend_Controller_Action_HelperBroker::
              getStaticHelper('viewRenderer');
$viewRender->setNeverRender(true);
Zend_Controller_Action_HelperBroker::addHelper($viewRender);

$front = Zend_Controller_Front::getInstance();
$front->setControllerDirectory(APPS . '/controllers');

$router = $front->getRouter();
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
    $front->registerPlugin(new Zend_Controller_Plugin_ErrorHandler(
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
    $front->registerPlugin(new Zend_Controller_Plugin_ErrorHandler(
        array(
            'model' => null,
            'controller' => 'Error',
            'action' => 'error'
        )
    ));
}



