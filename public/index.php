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
