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
 
// APPLICATION_PATH is a constant pointing to our application/ subdirectory.
// We use this to add our "library" directory to the include_path, so that 
// PHP can find our Zend Framework classes.
define('APPLICATION_PATH', realpath(dirname(__FILE__) . '/../application/'));
define('APPLICATION_ROOT', realpath(dirname(__FILE__). '/..'));
set_include_path(APPLICATION_ROOT . '/library' . PATH_SEPARATOR . get_include_path());

// AUTOLOADER - Set up autoloading.
// This is a nifty trick that allows ZF to load classes automatically so
// that you don't have to litter your code with 'include' or 'require'
// statements.
require_once "Zend/Loader.php";
Zend_Loader::registerAutoload();

// REQUIRE APPLICATION BOOTSTRAP: Perform application-specific setup
// This allows you to setup the MVC environment to utilize. Later you 
// can re-use this file for testing your applications.
// The try-catch block below demonstrates how to handle bootstrap 
// exceptions. In this application, if defined a different 
// APPLICATION_ENVIRONMENT other than 'production', we will output the 
// exception and stack trace to the screen to aid in fixing the issue
try {
    require '../application/bootstrap.php';
} catch (Exception $exception) {
    echo '<html><body><center>'
       . 'An exception occured while bootstrapping the application.';
    if (defined('APPLICATION_ENVIRONMENT') && APPLICATION_ENVIRONMENT != 'production') {
        echo '<br /><br />' . $exception->getMessage() . '<br />'
           . '<div align="left">Stack Trace:' 
           . '<pre>' . $exception->getTraceAsString() . '</pre></div>';
    }
    echo '</center></body></html>';
    exit(1);
}

// DISPATCH:  Dispatch the request using the front controller.
// The front controller is a singleton, and should be setup by now. We 
// will grab an instance and dispatch it, which dispatches your 
// application.
Zend_Controller_Front::getInstance()->dispatch();

/* $front = Zend_Controller_Front::getInstance();
// It's wrapped in a try-catch to provide a high-level error facility.
try {
    $front->dispatch();
} catch (Exception $e) {
    $log = Config_Fisma::getInstance()->getLogInstance();
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
 */