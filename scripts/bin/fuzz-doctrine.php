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
 * Overwrite database with malicious data wherever possible
 * 
 * This script iterates over every record and every field, and anywhere it finds a string field, it tries to insert
 * malicious or problematic characters into that field and save it.
 * 
 * @author     Mark E. Haase <mhaase@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Scripts
 * @version    $Id: generate-findings.php 3075 2010-03-05 18:01:20Z jboyd $
 */
require_once(realpath(dirname(__FILE__) . '/../../library/Fisma.php'));

/**
 * This constant contains the string which we would like to inject into each field.
 * 
 * It contains:
 * 1. Problematic characters when working with HTML encodings, like angle brackets and ampersand
 * 2. Weird UTF-8 characters (smart quotes) that do not exist in LATIN-1
 * 3. A javascript snippet which tries to run an alert
 * 
 * I tried to make this compact so it would have the highest likelihood of fitting in a field.
 * 
 * @var string
 */
const MALICIOUS_TEXT = "><&“”<script>alert('!');</script>";

/**
 * The backspace constant is used on ANSI terminals to overwrite the status line with current status information
 * 
 * This is the ASCII code for backspace.
 * 
 * @var int
 */
const BACKSPACE = 0x08;

/**
 * These are fields which should be skipped.
 * 
 * These are all read-only, so should be safe to skip.
 * 
 * @var array
 */
$skipFields = array(
    'Configuration' => array('name', 'value'), 
    'Event' => array('name'), 
    'Module' => array('name'),
    'Privilege' => array('action', 'resource')
);

try {
    // The rest of this script is timed
    $startTime = time();
    
    defined('APPLICATION_ENV')
        || define(
            'APPLICATION_ENV',
            (getenv('APPLICATION_ENV') ? getenv('APPLICATION_ENV') : 'production')
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
    Fisma::initialize(Fisma::RUN_MODE_COMMAND_LINE);
    Fisma::setAppConfig($application->getOptions());
    $application->bootstrap();

    // Script does not run in production mode, just to be safe
    if (!Fisma::debug()) {
        throw new Fisma_Zend_Exception("This script only runs in debug mode, not in production mode.");
    }
    
    // Require user to explicitly confirm what they are about to do.
    print str_repeat('#', 80)
        . "\nThis script will overwrite all data in the MAIN database with garbage data."
        . "\n\nThis script should only be used for development and research purposes."
        . "\n\nAre you sure that you want to continue? [type yes to continue]"
        . "\n"
        . str_repeat('#', 80)
        . "\n\n> ";

    $stdin = fopen("php://stdin", "r");
    $response = fgets($stdin);
    
    if ('yes' != strtolower(trim($response))) {
        return;
    }
    
    Fisma::connectDb();
    Fisma::setNotificationEnabled(false);

    // This is a quick & dirty script which uses massive amounts of memory
    ini_set('memory_limit', '-1');

    // Find all models by scanning the model directory
    $modelDirectory = opendir(Fisma::getPath('model'));
    
    if (!$modelDirectory) {
        throw new Fisma_Zend_Exception("Model directory cannot be opened");
    }
    
    while ($modelFileName = readdir($modelDirectory)) {
        // Skip hidden files
        if ('.' == $modelFileName{0}) {
            continue;
        }
        
        // Skip directories
        if (is_dir(Fisma::getPath('model') . '/' . $modelFileName)) {
            continue;
        }
                
        $modelName = substr($modelFileName, 0, -4);

        // Skip Table classes
        if ( substr($modelName, -5) == 'Table' ) {
            continue;
        }
        
        // CurrentUser is not a model, skip it
        if ($modelName == 'CurrentUser') {
            continue;
        }

        fwrite(STDOUT, "Model: $modelName ");
        
        $table = Doctrine::getTable($modelName);
        $columns = $table->getColumns();
        $rows = Doctrine_Query::create()->from("$modelName")->limit(25)->execute(); // Only fuzz 25 rows to save time
        $currentRow = 0;
        $rowCount = count($rows);
        
        $status = "($currentRow of $rowCount)";        
        fwrite(STDOUT, $status);
        
        foreach ($rows as $row) {

            // Update status line
            fwrite(STDOUT, str_repeat(chr(BACKSPACE), strlen($status)));
            $currentRow++;
            $status = "($currentRow of $rowCount)";        
            fwrite(STDOUT, $status);
            
            // Insert malicious data into all string columns
            foreach ($columns as $columnName => $columnDefinition) {
                if ('string' == $columnDefinition['type']) {

                    /* 
                     * Some fields are skipped because OpenFISMA will not work if you modify these fields (see
                     * phpdoc for $skipFields)
                     */
                    if (isset($skipFields[$modelName]) && in_array($columnName, $skipFields[$modelName])) {
                        continue;
                    }
                    
                    // The following validators will not succeed with the malicous data and will cause exceptions
                    if (   isset($columnDefinition['Fisma_Doctrine_Validator_Ip'])
                        || isset($columnDefinition['email'])
                        || isset($columnDefinition['Fisma_Doctrine_Validator_Email'])) {
                        continue;
                    }
                    
                    $fieldName = $table->getFieldName($columnName);
                    
                    // Insert malicous text and add row number as a way of making each value unique
                    $row->$fieldName = MALICIOUS_TEXT . $currentRow;
                }
            }

            try {
                $row->save();
            } catch (Exception $e) {
                fwrite(STDOUT, "\n");
                $errorMessage = "Exception on row $currentRow:: " 
                              . get_class($e) 
                              . " -- "
                              . $e->getMessage()
                              . "\n";
                fwrite(STDOUT, $errorMessage);
                
            }
        }
        
        fwrite(STDOUT, "\n");
    }
    
    $stopTime = time();

    print("Elapsed time: " . ($stopTime - $startTime) . " seconds\n");
} catch (Exception $e) {
    print get_class($e) 
        . "\n" 
        . $e->getMessage() 
        . "\n"
        . $e->getTraceAsString()
        . "\n";
}
