<?php
/**
 * Copyright (c) 2011 Endeavor Systems, Inc.
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
 * Fisma_Cli_GenerateSystems 
 * 
 * @uses Fisma_Cli_Abstract
 * @package Fisma_Cli 
 * @copyright (c) Endeavor Systems, Inc. 2011 {@link http://www.endeavorsystems.com}
 * @author Josh Boyd <joshua.boyd@endeavorsystems.com> 
 * @license http://www.openfisma.org/content/license GPLv3
 */
class Fisma_Cli_GenerateSystems extends Fisma_Cli_Abstract
{
    /**
     * _sampleOrganizations 
     * 
     * @var Doctrine_Collection 
     * @access private
     */
    private $_sampleOrganizations;

    /**
     * Configure the arguments accepted for this CLI program
     *
     * @return array An array containing getopt long syntax
     */
    public function getArgumentsDefinitions()
    {
        return array(
            'number|n=i' => "Number of system objects to generate"
        );
    }

    /**
     * Create systems 
     * 
     * @access protected
     * @return void
     */
    protected function _run()
    {
        Fisma::setNotificationEnabled(false);
        Fisma::setListenerEnabled(false);

        $inMemoryConfig = new Fisma_Configuration_Array();
        Fisma::setConfiguration($inMemoryConfig, true);

        $configuration = Zend_Registry::get('doctrine_config');

        $numSystems = $this->getOption('number');

        if (is_null($numSystems)) {
            throw new Fisma_Zend_Exception_User("Number is a required argument.");

            return;
        }

        $systems = array();

        // Get some organizations
        $this->_sampleOrganizations = Doctrine_Query::create()
                                      ->select('o.id')
                                      ->from('Organization o')
                                      ->leftJoin('o.System s')
                                      ->limit(50)
                                      ->execute();

        if (0 == count($this->_sampleOrganizations)) {
            throw new Fisma_Exception("Cannot generate sample data because the application has no organizations.");
        }

        // Some enumerations to randomly pick values from
        $type = array('gss', 'major', 'minor');
        $phase = array('initiation', 'development', 'implementation', 'operations', 'disposal');
        $confidentiality = array('NA', 'LOW', 'MODERATE', 'HIGH');
        $integrity = array('LOW', 'MODERATE', 'HIGH');
        $availability = array('LOW', 'MODERATE', 'HIGH');

        // Progress bar for console progress monitoring
        $generateProgressBar = $this->_getProgressBar($numSystems);
        $generateProgressBar->update(0, "Generate Systems");

        for ($i = 1; $i <= $numSystems; $i++) {
            $system = array();
            $system['name'] = Fisma_String::loremIpsum(1);
            $system['nickname'] = strtoupper(Fisma_String::loremIpsum(1)) . $i;
            $system['type'] = $type[array_rand($type)];
            $system['sdlcphase'] = $phase[array_rand($phase)];
            $system['description'] = Fisma_String::loremIpsum(rand(100, 500));
            $system['confidentiality'] = $confidentiality[array_rand($confidentiality)];
            $system['integrity'] = $integrity[array_rand($integrity)];
            $system['availability'] = $availability[array_rand($availability)];

            $systems[] = $system;
            unset($system);

            $generateProgressBar->update($i);
        }

        print "\n";

        $saveProgressBar = $this->_getProgressBar($numSystems);
        $saveProgressBar->update(0, "Save Systems");

        $currentSystem = 0;

        try {
            Doctrine_Manager::connection()->beginTransaction();

            foreach ($systems as $system) {
                $s = new System();
                $s->merge($system);

                $s->Organization = new Organization();
                $s->Organization->orgType = 'system';
                $s->Organization->merge($system);
                $s->merge($system);
                $s->save();

                $s->Organization->getNode()->insertAsLastChildOf($this->_getRandomOrganization());
                $s->Organization->save();

                $s->free();
                unset($s);

                $currentSystem++;
                $saveProgressBar->update($currentSystem);
            }

            Doctrine_Manager::connection()->commit();
        } catch (Exception $e) {
            Doctrine_Manager::connection()->rollBack();
            throw $e;
        }
    }
    
    /**
     * Return a random organization object
     * 
     * @return Fisma_Record
     */
    private function _getRandomOrganization()
    {
        return $this->_sampleOrganizations[rand(0, count($this->_sampleOrganizations))];
    }
}
