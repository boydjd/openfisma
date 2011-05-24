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
 */
try {
    $startTime = time();
    
    defined('APPLICATION_ENV')
        || define(
            'APPLICATION_ENV',
            (getenv('APPLICATION_ENV') ? getenv('APPLICATION_ENV') : 'development')
        );
    defined('APPLICATION_PATH') || define(
        'APPLICATION_PATH',
        realpath(dirname(__FILE__) . '/../../application')
    );

    set_include_path(
        APPLICATION_PATH . '/../library/Symfony/Components' . PATH_SEPARATOR .
        APPLICATION_PATH . '/../library' .  PATH_SEPARATOR .
        get_include_path()
    );

    require_once 'Fisma.php';
    require_once 'Zend/Application.php';

    $application = new Zend_Application(
        APPLICATION_ENV,
        APPLICATION_PATH . '/config/application.ini'
    );
    Fisma::setAppConfig($application->getOptions());
    Fisma::initialize(Fisma::RUN_MODE_COMMAND_LINE);
    $application->bootstrap(array('configuration', 'db', 'registerLogger'));
    Fisma::setNotificationEnabled(false);
    Fisma::setListenerEnabled(false);

    // Make sure the mysql supports InnoDB
    if (!Fisma_Cli_Abstract::checkInnoDb()) {
        throw new Doctrine_Exception(
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

    // Check to see if sample data was requested, e.g. `doctrine-cli.php build-all-reload sample-data`
    $sampleDataParameter = array_search('sample-data', $_SERVER['argv']);
    if ($sampleDataParameter) {
        print "Using Sample Data\n";
        
        // Create a build directory
        $sampleDataBuildPath = Fisma::getPath('sampleDataBuild');
        if (!mkdir($sampleDataBuildPath, 0700)) {
            throw new Fisma_Zend_Exception('Could not create directory for sample data build. Maybe it already exists'
                                    . " or it has the wrong permissions? ($sampleDataBuildPath)");
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
                throw new Fisma_Zend_Exception("Could not copy '$source' to '$target'");
            }
        }
        
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
        
        // Point Doctrine data loader at the new directory
        $configuration['data_fixtures_path'] = $sampleDataBuildPath;
        
        // Remove the request parameter before passing it to Doctrine since Doctrine won't understand it
        unset($_SERVER['argv'][$sampleDataParameter]);        
    }

    // Kick off the CLI
    $cli = new Doctrine_Cli($configuration);
    $cli->run($_SERVER['argv']);
    
    // Remove sample data build directory if it exists
    if (isset($sampleDataBuildPath) && is_dir($sampleDataBuildPath)) {
        print "Removing Sample Data build directory\n";
        Fisma_FileSystem::recursiveDelete($sampleDataBuildPath);
    }

    // If running migrations, then generate models also
    if (in_array('migrate', $_SERVER['argv'])) {
        print "Regenerating models because you ran migration.\n";
        $modelOptions = $configuration['generate_models_options'];
        Doctrine::generateModelsFromYaml(Fisma::getPath('schema'), Fisma::getPath('model'), $modelOptions);
    }
    
    $stopTime = time();
    print("Elapsed time: " . ($stopTime - $startTime) . " seconds\n");

} catch (Zend_Config_Exception $zce) {
    // A zend config exception indicates that the application may not be installed properly
    echo 'The application is not installed correctly.' . PHP_EOL;
    
    echo 'Exception ' . get_class($zce) . ' occured: ' . $zce->getMessage() . PHP_EOL;    

} catch (Exception $e) {
    print get_class($e) 
        . "\n" 
        . $e->getMessage() 
        . "\n"
        . $e->getTraceAsString()
        . "\n";
}
