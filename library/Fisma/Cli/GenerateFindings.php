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
class Fisma_Cli_GenerateFindings extends Fisma_Cli_Abstract
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

        // The CLI needs an in-memory configuration object, since it might drop and/or reload the configuration table
        $inMemoryConfig = new Fisma_Configuration_Array();
        $inMemoryConfig->setConfig('hash_type', 'sha1');
        $inMemoryConfig->setConfig('session_inactivity_period', '9999999');
        Fisma::setConfiguration($inMemoryConfig, true);    

        $configuration = Zend_Registry::get('doctrine_config');

        $numFindings = $this->getOption('number');
        
        if (is_null($numFindings)) {
            fwrite(STDOUT, "Number is a required argument.\n");
            
            return;
        }

        $findings = array();

        // Get Organizations
        $organizationIds = Doctrine_Query::create()
                            ->select('o.id')
                            ->from('Organization o')
                            ->setHydrationMode(Doctrine::HYDRATE_NONE)
                            ->execute();

        // Get Assets
        $assetIds = Doctrine_Query::create()
                        ->select('a.id')
                        ->from('Asset a')
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

        $type = array('NONE', 'CAP', 'AR', 'FP');
        $status = array('PEND', 'NEW', 'DRAFT', 'MSA', 'EN', 'EA', 'CLOSED');
        $threat = array('LOW', 'MODERATE', 'HIGH');
        $effectiveness = array('LOW', 'MODERATE', 'HIGH');

        $typeCount = count($type)-1;
        $statusCount = count($status)-1;
        $threatCount = count($threat)-1;
        $effectivenessCount = count($effectiveness)-1;
        $organizationIdsCount = count($organizationIds)-1;
        $assetIdsCount = count($assetIds)-1;
        $sourceIdsCount = count($sourceIds)-1;
        $securityControlIdsCount = count($securityControlIds)-1;

        for ($numFindings; $numFindings > 0; $numFindings--) {
            $discoveredDate = rand(0, time());

            $finding = array();
            $finding['currentEcd'] = date("Y-m-d", $discoveredDate+rand());
            $finding['type'] = $type[rand(0, $typeCount)];
            $finding['status'] = $status[rand(0, $statusCount)];
            $finding['threatLevel'] = $threat[rand(0, $threatCount)];
            $finding['countermeasuresEffectiveness'] = $effectiveness[rand(0, $effectivenessCount)];
            $finding['responsibleOrganizationId'] = $organizationIds[rand(0, $organizationIdsCount)][0];
            $finding['assetId'] = $assetIds[rand(0, $assetIdsCount)][0];
            $finding['sourceId'] = $sourceIds[rand(0, $sourceIdsCount)][0];
            $finding['securityControlId'] = $securityControlIds[rand(0, $securityControlIdsCount)][0];
            $finding['description'] = Fisma_String::loremIpsum(rand(2, 1000));
            $finding['recommendation'] = Fisma_String::loremIpsum(rand(2, 1000));
            $finding['mitigationStrategy'] = Fisma_String::loremIpsum(rand(2, 1000));
            $finding['resourcesRequired'] = Fisma_String::loremIpsum(rand(2, 1000));
            $finding['threat'] = Fisma_String::loremIpsum(rand(2, 1000));
            $finding['countermeasures'] = Fisma_String::loremIpsum(rand(2, 1000));
            $finding['discoveredDate'] = date("Y-m-d", $discoveredDate);
            $finding['ecdLocked'] = FALSE;
            $findings[] = $finding;
            unset($finding);
        }

        try {
            Doctrine_Manager::connection()->beginTransaction();

            foreach ($findings as $finding) {
                $f = new Finding();
                $f->merge($finding);
                $f->save();
                $f->free();
                unset($f);
            }

            Doctrine_Manager::connection()->commit();
        } catch (Exception $e) {
            Doctrine_Manager::connection()->rollBack();
            throw $e;
        }    
    }
}
