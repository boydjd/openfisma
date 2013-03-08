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
class Fisma_Cli_GenerateVulnerabilities extends Fisma_Cli_AbstractGenerator
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

        $threat = array('LOW', 'MODERATE', 'HIGH');

        $threatCount = count($threat)-1;
        $assetIdsCount = count($assetIds)-1;

        // Progress bar for console progress monitoring
        $generateProgressBar = $this->_getProgressBar($numEntries);
        $generateProgressBar->update(0, "Generate Vulnerabilities");

        for ($i = 1; $i <= $numEntries; $i++) {
            $discoveredDate = rand(0, time());

            $entry = array();

            $entry['threatLevel'] = $threat[$this->_randomLog(0, $threatCount)];
            $entry['assetId'] = $assetIds[$this->_randomLog(0, $assetIdsCount)][0];
            $entry['description'] = Fisma_String::loremIpsum(rand(90, 100));
            $entry['recommendation'] = Fisma_String::loremIpsum(rand(90, 100));
            $entry['threat'] = Fisma_String::loremIpsum(rand(90, 100));
            $zdDescDate = new Zend_Date($discoveredDate);
            $entry['discoveredDate'] = $zdDescDate->toString('yyyy-MM-dd');
            $entries[] = $entry;
            unset($entry);

            $generateProgressBar->update($i, "Generate Vulnerabilities");
        }

        print "\n";
        $saveProgressBar = $this->_getProgressBar($numEntries);
        $saveProgressBar->update(0, "Save Vulnerabilities");

        $currentVulnerability = 0;

        try {
            Doctrine_Manager::connection()->beginTransaction();

            foreach ($entries as $entry) {
                $e = new Vulnerability();
                $e->merge($entry);
                $e->CreatedBy = $this->_getRandomUser();
                if (empty($e->pocId)) {
                    $e->pocId = $this->_getRandomUser()->id;
                }
                $e->save();

                //workflow simulation
                $rand = rand(0, 100);
                while ($rand >= 25) {
                    $rand = rand(0, $rand);
                    $transitions = $e->CurrentStep->transitions;
                    $randTransition = rand(0, count($transitions) -1);
                    $transition = $transitions[$randTransition]['name'];
                    $userId = $this->_getRandomUser()->id;
                    try {
                        $nextStep = $e->CurrentStep->getNextStep($transition); //Use destionationId to bypass ACL checking
                        WorkflowStep::completeOnObject($e, $transition, 'Completed by generation script', $userId, rand(7, 30), $nextStep->id);
                    } catch (Exception $e) {
                    }
                }

                $e->save();
                $e->free();
                unset($e);

                $currentVulnerability++;
                $saveProgressBar->update($currentVulnerability);
            }

            Doctrine_Manager::connection()->commit();
        } catch (Exception $e) {
            Doctrine_Manager::connection()->rollBack();
            throw $e;
        }
        print "\n";
    }
}
