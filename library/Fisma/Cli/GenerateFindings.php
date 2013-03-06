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
 * Generate random finding objects (for load testing)
 *
 * @author     Joshua Boyd <joshua.boyd@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2010 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Fisma
 * @subpackage Fisma_Cli
 */
class Fisma_Cli_GenerateFindings extends Fisma_Cli_AbstractGenerator
{
    /**
     * Configure the arguments accepted for this CLI program
     *
     * @return array An array containing getopt long syntax
     */
    public function getArgumentsDefinitions()
    {
        return array(
            'number|n=i' => "Number of finding objects to generate"
        );
    }

    /**
     * Drop the index specified on the command line, or if none is specified, drop and rebuild ALL indexes
     */
    protected function _run()
    {
        Fisma::setNotificationEnabled(false);
        Fisma::setListenerEnabled(false);

        $inMemoryConfig = new Fisma_Configuration_Array();
        $inMemoryConfig->setConfig('hash_type', 'sha1');
        $inMemoryConfig->setConfig('session_inactivity_period', '9999999');
        Fisma::setConfiguration($inMemoryConfig, true);

        $configuration = Zend_Registry::get('doctrine_config');

        $numFindings = $this->getOption('number');

        if (is_null($numFindings)) {
            throw new Fisma_Zend_Exception_User("Number is a required argument.");

            return;
        }

        $findings = array();

        // Get Organizations
        $organizationIds = Doctrine_Query::create()
                            ->select('o.id, o.pocId')
                            ->from('Organization o')
                            ->leftJoin('o.System s')
                            ->where("s.sdlcphase <> 'disposal' OR s.sdlcphase IS NULL")
                            ->setHydrationMode(Doctrine::HYDRATE_NONE)
                            ->execute();

        // Get sources
        $sourceIds = Doctrine_Query::create()
                        ->select('s.id')
                        ->from('Source s')
                        ->setHydrationMode(Doctrine::HYDRATE_NONE)
                        ->execute();

        // Get security controls
        $securityControlIds = Doctrine_Query::create()
                                ->select('s.id')
                                ->from('SecurityControl s')
                                ->setHydrationMode(Doctrine::HYDRATE_NONE)
                                ->execute();

        $threat = array('LOW', 'MODERATE', 'HIGH');
        $effectiveness = array('LOW', 'MODERATE', 'HIGH');

        $typeCount = count($type)-1;
        $statusCount = count($status)-1;
        $threatCount = count($threat)-1;
        $effectivenessCount = count($effectiveness)-1;
        $organizationIdsCount = count($organizationIds)-1;
        $sourceIdsCount = count($sourceIds)-1;
        $securityControlIdsCount = count($securityControlIds)-1;

        // Progress bar for console progress monitoring
        $generateProgressBar = $this->_getProgressBar($numFindings);
        $generateProgressBar->update(0, "Generate Findings");

        for ($i = 1; $i <= $numFindings; $i++) {
            $date = new Zend_Date();
            $date->setTimestamp(rand(time()-2e7, time()));
            $discoveredDate = $date->getDate()->toString(Fisma_Date::FORMAT_DATE);

            $date->addTimestamp(rand(0, 4e7));
            $currentEcd = $date->getDate()->toString(Fisma_Date::FORMAT_DATE);

            $finding = array();
            $finding['threatLevel'] = $threat[rand(0, $threatCount)];
            $finding['countermeasuresEffectiveness'] = $effectiveness[rand(0, $effectivenessCount)];

            $orgRandomIndex = $this->_randomLog(0, $organizationIdsCount);
            $finding['responsibleOrganizationId'] = $organizationIds[$orgRandomIndex][0];
            $finding['pocId'] = $organizationIds[$orgRandomIndex][1];

            $finding['sourceId'] = $sourceIds[$this->_randomLog(0, $sourceIdsCount)][0];
            $finding['securityControlId'] = $securityControlIds[$this->_randomLog(0, $securityControlIdsCount)][0];
            $finding['description'] = Fisma_String::loremIpsum(rand(90, 100));
            $finding['recommendation'] = Fisma_String::loremIpsum(rand(90, 100));
            $finding['threat'] = Fisma_String::loremIpsum(rand(0, 100));
            $finding['countermeasures'] = Fisma_String::loremIpsum(rand(90, 100));
            $finding['discoveredDate'] = $discoveredDate;
            $finding['ecdLocked'] = FALSE;
            $findings[] = $finding;
            unset($finding);

            $generateProgressBar->update($i);
        }

        print "\n";

        $saveProgressBar = $this->_getProgressBar($numFindings);
        $saveProgressBar->update(0, "Save Findings");

        $currentFinding = 0;

        try {
            Doctrine_Manager::connection()->beginTransaction();

            foreach ($findings as $finding) {
                $f = new Finding();

                $f->merge($finding);
                $f->CreatedBy = $this->_getRandomUser();
                if (empty($f->pocId)) {
                    $f->pocId = $this->_getRandomUser()->id;
                }

                //@TODO workflow simulation

                $f->save();
                $f->free();
                unset($f);

                $currentFinding++;
                $saveProgressBar->update($currentFinding);
            }

            Doctrine_Manager::connection()->commit();
        } catch (Exception $e) {
            Doctrine_Manager::connection()->rollBack();
            throw $e;
        }
        print "\n";
    }
}
