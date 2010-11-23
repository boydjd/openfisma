<?php
/**
 * Copyright (c) 2010 Endeavor Systems, Inc.
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
 * Generate random vulnerability objects (for load testing)
 *
 * @author     Andrew Reeves <andrew.reeves@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2010 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Fisma
 * @subpackage Fisma_Cli
 */
class Fisma_Cli_GenerateVulnerabilities extends Fisma_Cli_Abstract
{
    /**
     * Configure the arguments accepted for this CLI program
     *
     * @return array An array containing getopt long syntax
     */
    public function getArgumentsDefinitions()
    {
        return array(
            'number|n=i' => "Number of vulnerability objects to generate"
        );
    }

    /**
     * Drop the index specified on the command line, or if none is specified, drop and rebuild ALL indexes
     */
    protected function _run()
    {
        Fisma::setNotificationEnabled(false);
        Fisma::setListenerEnabled(false);

        $numEntries = $this->getOption('number');
        $entries = array();

        // Get Assets
        $assetIds = Doctrine_Query::create()
                        ->select('a.id')
                        ->from('Asset a')
                        ->setHydrationMode(Doctrine::HYDRATE_NONE)
                        ->execute();

        $status = array('OPEN', 'FIXED', 'WONTFIX');
        $threat = array('LOW', 'MODERATE', 'HIGH');

        $statusCount = count($status)-1;
        $threatCount = count($threat)-1;
        $assetIdsCount = count($assetIds)-1;

        // Progress bar for console progress monitoring
        $generateProgressBar = $this->_getProgressBar($numEntries);
        $generateProgressBar->update(0, "Generate Vulnerabilities");

        for ($i = 1; $i <= $numEntries; $i++) {
            $discoveredDate = rand(0, time());

            $entry = array();
            $randomstatus = $status[rand(0, $statusCount)];

            //Status defaults to OPEN and state transition does not allow from OPEN to OPEN
            if ($randomstatus != 'OPEN') {
                $entry['status'] = $randomstatus;
            }
            $entry['threatLevel'] = $threat[rand(0, $threatCount)];
            $entry['assetId'] = $assetIds[rand(0, $assetIdsCount)][0];
            $entry['description'] = Fisma_String::loremIpsum(rand(2, 1000));
            $entry['recommendation'] = Fisma_String::loremIpsum(rand(2, 1000));
            $entry['threat'] = Fisma_String::loremIpsum(rand(2, 1000));
            $zdDescDate = new Zend_Date($discoveredDate);
            $entry['discoveredDate'] = $zdDescDate->toString('yyyy-MM-dd');
            $entries[] = $entry;
            unset($entry);
            
            $generateProgressBar->update($i, "Generate Vulnerabilities");
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
    }
}
