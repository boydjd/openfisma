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
 * Generate random findings, number to create specified as argument on command line 
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
    Fisma::connectDb();
    Fisma::setNotificationEnabled(false);
    Fisma::setListenerEnabled(false);

    /** @todo temporary hack to load large datasets */
    ini_set('memory_limit', '-1');

    // The CLI needs an in-memory configuration object, since it might drop and/or reload the configuration table
    $inMemoryConfig = new Fisma_Configuration_Array();
    $inMemoryConfig->setConfig('hash_type', 'sha1');
    $inMemoryConfig->setConfig('session_inactivity_period', '9999999');
    Fisma::setConfiguration($inMemoryConfig, true);    

    $configuration = Zend_Registry::get('doctrine_config');

    $numEntries = $argv[1];
    $entries = array();

    // Get Assets
    $assetIds = Doctrine_Query::create()
                    ->select('a.id')
                    ->from('Asset a')
                    ->setHydrationMode(Doctrine::HYDRATE_NONE)
                    ->execute();

    $status = array('OPEN', 'WONTFIX', 'FIXED', 'CLOSED');
    $threat = array('LOW', 'MODERATE', 'HIGH');

    $statusCount = count($status)-1;
    $threatCount = count($threat)-1;
    $assetIdsCount = count($assetIds)-1;

    for ($numEntries; $numEntries > 0; $numEntries--) {
        $discoveredDate = rand(0, time());

        $entry = array();
        $entry['status'] = $status[rand(0, $statusCount)];
        $entry['threatLevel'] = $threat[rand(0, $threatCount)];
        $entry['assetId'] = $assetIds[rand(0, $assetIdsCount)][0];
        $entry['description'] = Fisma_String::loremIpsum(rand(2, 1000));
        $entry['recommendation'] = Fisma_String::loremIpsum(rand(2, 1000));
        $entry['threat'] = Fisma_String::loremIpsum(rand(2, 1000));
        $entry['discoveredDate'] = date("Y-m-d", $discoveredDate);
        $entries[] = $entry;
        unset($entry);
    }

    try {
        Doctrine_Manager::connection()->beginTransaction();

        foreach ($entries as $entry) {
            $e = new Vulnerability();
            $e->merge($entry);
            $e->save();
            $e->free();
            unset($e);
        }
        
        Doctrine_Manager::connection()->commit();
    } catch (Exception $e) {
        Doctrine_Manager::connection()->rollBack();
        throw $e;
    }

    $stopTime = time();

    print("Elapsed time: " . ($stopTime - $startTime) . " seconds\n");
} catch (Exception $e) {
    echo (string)$e;
}
