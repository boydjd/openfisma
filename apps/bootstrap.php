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

if (!defined('ROOT')) {
    define('ROOT', dirname(dirname(__FILE__)));
}

/**
 * The paths.php file needs to be included before defining the loadClass
 * function, in order for that function to know which paths to search.
 */
require_once (ROOT . '/paths.php');

/**
 * loadClass() - Define a standard handler for loading classes without having
 * to include the class file. This is automatically called by PHP to resolve
 * undeclared class references. Do not invoke this method directly.
 *
 * This assumes that classes are named using ZF standards or else are directly
 * in the search path. See paths.php for search path directories.
 *
 * @param string $className
 */
function loadClass($className) {
    // Get the include path and break it into parts
    $includePath = get_include_path();
    $includePathParts = explode(':', $includePath);
    
    // Reformat the class name into a class path by replacing underscores with
    // forward slashes.
    $classPath = str_replace('_', '/', $className);
    
    // Iterate through each includes directory to see if it contains the class
    // file. If it does, then load that file and return.
    foreach ($includePathParts as $includeDirectory) {
        $classFile = "$includeDirectory/$classPath.php";
        if (file_exists($classFile)) {
            require_once($classFile);
            return;
        }
    }
    
    // If the file isn't found in any includes directory, then throw an
    // exception.
    die("Unable to autoload class $className\nThe include path contains: "
      . print_r($includePathParts,true));
    throw new Exception("Unable to autoload class \"$className\"");
}
// Register our custom class loader:
spl_autoload_register("loadClass");

// Include basic utility functions and system-wide configuration
require_once (APPS . '/basic.php');
require_once (CONFIGS . '/setting.php');

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
    'layoutPath' => VIEWS . DS . 'layouts',
    'contentKey' => 'CONTENT'
);

// Use ".tpl" as the default view suffix
Zend_Layout::startMvc($options)->setViewSuffix('tpl');
$viewRender = Zend_Controller_Action_HelperBroker::
              getStaticHelper('viewRenderer');
$viewRender->setViewSuffix('tpl')
           ->setNeverRender(true);
Zend_Controller_Action_HelperBroker::addHelper($viewRender);

// Get the front controller instance and kick it off
$front = Zend_Controller_Front::getInstance();
$router = $front->getRouter();
if (!isInstall()) {
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
    $front->registerPlugin(
        new Zend_Controller_Plugin_ErrorHandler(
            array(
                'model' => null,
                'controller' => 'Install',
                'action' => 'error'
            )
        )
    );
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
    $front->registerPlugin(
        new Zend_Controller_Plugin_ErrorHandler(
            array(
                'model' => null,
                'controller' => 'Error',
                'action' => 'error'
            )
        )
    );
}

// This is the actual application bootstrap. It's wrapped in a try-catch to
// provide a high-level error facility.
try {
    $front->throwExceptions(true);
    Zend_Controller_Front::run(APPS . '/controllers');
} catch (Exception $e) {
    $write = new Zend_Log_Writer_Stream(LOG . '/' . ERROR_LOG);
    $log = new Zend_Log($write);
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

    // Get the stack trace and indent it by 4 spaces
    $stackTrace = $e->getTraceAsString();
    $stackTrace = preg_replace("/^/", "    ", $stackTrace);
    $stackTrace = preg_replace("/\n/", "\n    ", $stackTrace);

    // Log the error message and stack trace.
    $log->log($e->getMessage() . "\n$stackTrace",
              Zend_Log::ERR);
              
    // @todo This needs to be improved. Ideally we'd show a real page that has
    // administrator contact info.
    echo "An unrecoverable error has occured. The error has been logged and"
       . " an administrator will review the issue shortly";
}
