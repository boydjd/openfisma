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

try {
    defined('APPLICATION_PATH')
        || define(
            'APPLICATION_PATH',
            realpath(dirname(__FILE__) . '/../application')
        );

    set_include_path(
        APPLICATION_PATH . '/../library/Symfony/Components' . PATH_SEPARATOR . 
        APPLICATION_PATH . '/../library' .  PATH_SEPARATOR . 
        get_include_path()
    );

    require_once 'Fisma.php';
    require_once 'Zend/Application.php';

    Fisma::initialize(Fisma::RUN_MODE_WEB_APP);
    
    if (Fisma::isInstall()) {
        $application = new Zend_Application(
            'production',
            APPLICATION_PATH . '/config/application.ini'
        );
    } else {
        // If the application isn't installed, we need to create a basic configuration to allow the application to
        // bootstrap and install.
        $config = array(
            'bootstrap' => array(
                'path' => APPLICATION_PATH . "/Bootstrap.php",
                'class' => 'Bootstrap',
                'container' => array(
                    'type' => 'symfony'
                )
             ),
             'resources' =>
                array(
                   'frontcontroller' => array(
                       'controllerdirectory' => APPLICATION_PATH . '/controllers'
                   )
                ),
             'includePaths' =>
                array(
                    'library' => APPLICATION_PATH . '/../library'
                ),
        );

        $application = new Zend_Application(
            'production',
            new Zend_Config($config)
        );
    }

    $application->bootstrap()->run();
} catch (Zend_Config_Exception $zce) {
    // A zend config exception indicates that the application may not be installed properly
    echo '<h1>The application is not installed correctly</h1>';
    echo '<p>If you have not run the installer, you should do that now.</p>';
} catch (Exception $exception) {
    // If a bootstrap exception occurs, that indicates a serious problem, such as a syntax error.
    // We won't be able to do anything except display an error.
    echo '<h1>An exception occurred while bootstrapping the application.</h1>';
    if (Fisma::debug()) {
        echo '<p>' 
             . get_class($exception) 
             . '</p><p>' 
             . $exception->getMessage() 
             . '</p><p>'
             . "<p><pre>Stack Trace:\n" 
             . $exception->getTraceAsString() 
             . '</pre></p>';
    }
}
