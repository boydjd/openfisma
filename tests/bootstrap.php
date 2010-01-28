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
    
    /** 
     * The bootstrap recreates all tables, including the configuration table. So to bootstrap the bootstrap, we need
     * a temporary, in-memory configuration object. This gets replaced with a database-backed configuration object
     * after the configuration table has been re-built.
     */
    $configuration = new Fisma_Configuration_Array();
    $configuration->setConfig('hash_type', 'sha1');
    $configuration->setConfig('session_inactivity_period', '3600');
    Fisma::setConfiguration($configuration, true);

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

    // Now that the configuration table has been re-built, reset the global configuration object to use that table
    Fisma::setConfiguration(new Fisma_Configuration_Database(), true);

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
