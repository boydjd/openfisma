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
 * Doctrine cli tasks dispatcher
 * 
 * @author     Mark E. Haase
 * @copyright  (c) Endeavor Systems, Inc. 2011 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Fisma
 * @subpackage Fisma_Cli
 */
class Fisma_Cli_Doctrine extends Fisma_Cli_Abstract
{
    /**
     * Configure the arguments accepted for this CLI program
     * 
     * @return array An array containing getopt long syntax
     */
    public function getArgumentsDefinitions()
    {
        return array(
            'auto-yes|y' => "Automatically pick 'yes' for yes/no questions",
            'auto-no|n' => "Automatically pick 'no' for yes/no questions"
        );
    }

    /**
     * Run the task with the passed arguments
     */
    protected function _run()
    {
        Fisma::setNotificationEnabled(false);
        Fisma::setListenerEnabled(false);

        // Make sure the mysql supports InnoDB
        if (!Fisma_Cli_Abstract::checkInnoDb()) {
            throw new Fisma_Zend_Exception_User(
                'The current Mysql server does not support InnoDB engine. InnoDB is required for OpenFisma!'
            );
        }

        /** @todo temporary hack to load large datasets */
        ini_set('memory_limit', '512M');

        // The CLI needs an in-memory configuration object, since it might drop and/or reload the configuration table
        $inMemoryConfig = new Fisma_Configuration_Array();
        $inMemoryConfig->setConfig('hash_type', 'sha1');
        Fisma::setConfiguration($inMemoryConfig, true);

        $configuration = Zend_Registry::get('doctrine_config');

        // Block the 'migrate' action from running because migrate approach is changed by OFJ-1617.
        $arguments = array_map('strtolower', $_SERVER['argv']);
        $migrateOffset = array_search('migrate', $arguments);
        if ($migrateOffset) {
            unset($arguments[$migrateOffset]);
        }

        // Make sure that user does not use both arguments auto-yes and auto-no at the same time
        $autoYes = $this->getOption('auto-yes');
        $autoNo = $this->getOption('auto-no');
        if (!is_null($autoYes) && !is_null($autoNo)) {
            throw new Fisma_Zend_Exception_User("Cannot use auto-yes and auto-no at the same time!");
        }

        // Check to see if sample data was requested, e.g. `doctrine-cli.php build-all-reload sample-data`
        $sampleDataParameter = array_search('sample-data', $arguments);
        if ($sampleDataParameter) {
            $sampleDataBuildPath = Fisma::getPath('sampleDataBuild');

            $this->loadFixtureFilesWithSampleData($sampleDataBuildPath);

            // Point Doctrine data loader at the new directory
            $configuration['data_fixtures_path'] = $sampleDataBuildPath;

            // Remove the request parameter before passing it to Doctrine since Doctrine won't understand it
            unset($arguments[$sampleDataParameter]);
        }

        // Kick off the CLI
        $cli = new Fisma_Doctrine_Cli($configuration);
        $cli->run($arguments);

        // Remove sample data build directory if it exists
        if (isset($sampleDataBuildPath) && is_dir($sampleDataBuildPath)) {
            print "Removing Sample Data build directory\n";
            Fisma_FileSystem::recursiveDelete($sampleDataBuildPath);
        }
    }

    /**
     * Copy fixture YAML files to sample build directory, combine fixture files with sample data
     * 
     * @param string $sampleDataBuildPath
     * @return void
     */
    protected function loadFixtureFilesWithSampleData($sampleDataBuildPath)
    {
        print "Using Sample Data\n";
    
        // If build directory already exists (e.g. from a failed prior run), then try removing it
        $sampleDataBuildPath = Fisma::getPath('sampleDataBuild');
        if (is_dir($sampleDataBuildPath)) {
            $result = Fisma_FileSystem::recursiveDelete($sampleDataBuildPath);
            
            if (!$result) {
                throw new Fisma_Zend_Exception_User("Could not remove directory for sample data build. Maybe it has"
                                             . " bad permissions? ($sampleDataBuildPath)");
            }
        }

        // Create a build directory
        if (!mkdir($sampleDataBuildPath)) {
            throw new Fisma_Zend_Exception_User('Could not create directory for sample data build. Maybe it has bad' 
                                         . " permissions? ($sampleDataBuildPath)");
        }

        // Copy files from fixtures into build directory
        $fixturePath = Fisma::getPath('fixture');
        $fixtureDir = opendir($fixturePath);

        while ($fixtureFile = readdir($fixtureDir)) {
            // Skip hidden files
            if ('.' == $fixtureFile{0}) {
                continue;
            }

            $source = "$fixturePath/$fixtureFile";
            $target = "$sampleDataBuildPath/$fixtureFile";
            if (!copy($source, $target)) {
                throw new Fisma_Zend_Exception_User("Could not copy '$source' to '$target'");
            }
        }

        $this->combineFixtureFilesWithSampleData($sampleDataBuildPath);
    }

    /**
     * Combine fixture files with sample data or merge them
     * 
     * @param string $sampleDataBuildPath
     * @return void
     */
    protected function combineFixtureFilesWithSampleData($sampleDataBuildPath)
    {
        // Copy files from sample data into build directory. If a fixture already exists, then we need to merge the 
        // YAML files together.
        $samplePath = Fisma::getPath('sampleData');
        $sampleDir = opendir($samplePath);
        
        while ($sampleFile = readdir($sampleDir)) {
            // Skip hidden files
            if ('.' == $sampleFile{0}) {
                continue;
            }
            
            $source = "$samplePath/$sampleFile";
            $target = "$sampleDataBuildPath/$sampleFile";

            // When combining fixture files with sample data, we need to strip the YAML 
            // header off of the sample data file
            $stripYamlHeader = file_exists($target);

            // If the target file does already exist, then we need to merge the YAML files.
            $sourceHandle = fopen($source, 'r');
            $targetHandle = fopen($target, 'a');

            while ($buffer = fgets($sourceHandle)) {
                if (!$stripYamlHeader) {
                    if (strpos($buffer, '##') !== FALSE) {
                        $matches = array();
                        if (preg_match("/##\s*CURDATE(-|\+)(\d+)\s*##/", $buffer, $matches)) {
                            $today = Zend_Date::now();
                            if ('+' == $matches[1]) {
                                $today->addDay($matches[2]);
                            } elseif ('-' == $matches[1]) {
                                $today->subDay($matches[2]);
                            }
                            $dateString = "'" . $today->toString('YYYY-MM-dd HH:mm:ss') . "'";
                            $buffer = preg_replace('/##\s*CURDATE.*##/', $dateString, $buffer);
                        }
                    }

                    fwrite($targetHandle, $buffer);
                } else {
                    // Look for the first YAML tag in the document and remove it. Then set the $write flag to true
                    // so that we can stop looking for the tag.
                    if (preg_match('/[^#]\w+:.*\R/', $buffer, $a)) {
                        $buffer = preg_replace('/[^#]\w+:.*(?>\r\n|\n|\x0b|\f|\r|\x85)/', '', $buffer, 1);
                        fwrite($targetHandle, $buffer);
                        $stripYamlHeader = false;
                    }
                }
            }
        }
    }
}
