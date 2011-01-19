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
    defined('APPLICATION_ENV')
        || define(
            'APPLICATION_ENV',
            (getenv('APPLICATION_ENV') ? getenv('APPLICATION_ENV') : 'development')
        );
    defined('APPLICATION_PATH') || define(
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

    $application = new Zend_Application(
        APPLICATION_ENV,
        APPLICATION_PATH . '/config/application.ini'
    );
    Fisma::setAppConfig($application->getOptions());
    Fisma::initialize(Fisma::RUN_MODE_TEST);

    Doctrine_Manager::connection(new PDO('sqlite::memory:'));

    $frontController = Zend_Controller_Front::getInstance();
    $frontController->setControllerDirectory(Fisma::getPath('controller'));
//    $application->bootstrap('fisma');
    error_reporting(E_ALL & ~E_NOTICE);
} catch (Exception $exception) {
    echo get_class($exception) . ": " . $exception->getMessage() . "\n";
    echo $exception->getTraceAsString();
}
