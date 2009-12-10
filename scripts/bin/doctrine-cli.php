#!/usr/bin/env php
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
 * Doctrine cli tasks dispatcher.
 * 
 * @author     Mark E. Haase <mhaase@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Scripts
 * @version    $Id$
 */
require_once(realpath(dirname(__FILE__) . '/../../library/Fisma.php'));

try {
    $startTime = time();
    
    Fisma::initialize(Fisma::RUN_MODE_COMMAND_LINE);
    Fisma::setConfiguration(new Fisma_Configuration_Database());
    Fisma::connectDb();
    Fisma::setNotificationEnabled(false);

    /** @todo temporary hack to load large datasets */
    ini_set('memory_limit', '512M');

    $cli = new Doctrine_Cli(Zend_Registry::get('doctrine_config'));
    $cli->run($_SERVER['argv']);
    
    $stopTime = time();
    print("Elapsed time: " . ($stopTime - $startTime) . " seconds\n");
} catch (Zend_Config_Exception $zce) {
    print "The application is not installed correctly. If you have not run the installer, you should do that now.";
} catch (Exception $e) {
    print get_class($e) 
        . "\n" 
        . $e->getMessage() 
        . "\n"
        . $e->getTraceAsString()
        . "\n";
}
