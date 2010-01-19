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
    ini_set('memory_limit', '512M');
    Fisma::initialize(Fisma::RUN_MODE_TEST);
    Fisma::setConfiguration(new Fisma_Configuration_Database(), true);

    Zend_Session::start();

    if (Fisma::isInstall()) {
        Fisma::connectDb();
    }

    Fisma::setNotificationEnabled(false);
    Fisma::setListenerEnabled(false);
    $cli = new Doctrine_Cli(Zend_Registry::get('doctrine_config'));
    $cliArguments = array('doctrine-cli.php', 'build-all-reload', '--no-confirmation');
    $cli->run($cliArguments);
    Fisma::setListenerEnabled(true);

    $frontController = Zend_Controller_Front::getInstance();
    $frontController->setControllerDirectory(Fisma::getPath('controller'));
    Fisma::dispatch();
} catch (Zend_Config_Exception $zce) {
    echo 'Configuration exception during bootstrap.\n';
} catch (Exception $exception) {
    echo 'An exception occured during bootstraping.\n';
    echo get_class($exception) . '\n';
    echo $exception->getMessage() . '\n';
    echo $exception->getTraceAsString();
}
