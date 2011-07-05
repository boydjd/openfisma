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
 * Development/security testing script for HTML markup injection and character encoding
 * 
 * @author     Mark E. Haase
 * @copyright  (c) Endeavor Systems, Inc. 2010 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Fisma
 * @subpackage Fisma_Cli
 */
class Fisma_Cli_FuzzDoctrine extends Fisma_Cli_Abstract
{
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
    private $_skipFields = array(
        'Event' => array('name'), 
        'Module' => array('name'),
        'Privilege' => array('action', 'resource')
    );

    /**
     * Overwrite the database with "malicious" data
     */
    protected function _run()
    {
        Fisma::setNotificationEnabled(false);
        Fisma::setListenerEnabled(true, true);

        // Script does not run in production mode, just to be safe
        if (!Fisma::debug()) {
            throw new Fisma_Zend_Exception_User("This script only runs in debug mode, not in production mode.");
            
            return;
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

        // Find all models by scanning the model directory
        $modelDirectory = opendir(Fisma::getPath('model'));

        if (!$modelDirectory) {
            fwrite(STDOUT, "Model directory ($modelDirectory) cannot be opened.\n");
            
            return;
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

            // Skip the configuration model
            if ($modelName == 'Configuration') {
                continue;
            }

            // Check for Fisma_Doctrine_Record subclasses only
            $reflection = new ReflectionClass($modelName);

            if (!$reflection->isSubclassOf('Fisma_Doctrine_Record')) {
                continue;
            }

            $table = Doctrine::getTable($modelName);
            $columns = $table->getColumns();

            // Only fuzz 25 rows to save time and memory:
            $rows = Doctrine_Query::create()->from("$modelName")->limit(25)->execute();
            $currentRow = 0;
            $rowCount = count($rows);

            fwrite(STDOUT, "\n$modelName: $rowCount records\n");

            $progressBar = new Zend_ProgressBar(new Zend_ProgressBar_Adapter_Console, 0, $rowCount);

            foreach ($rows as $row) {

                $currentRow++;

                // Insert malicious data into all string columns
                foreach ($columns as $columnName => $columnDefinition) {
                    if ('string' == $columnDefinition['type']) {

                        /* 
                         * Some fields are skipped because OpenFISMA will not work if you modify these fields (see
                         * phpdoc for $this->_skipFields)
                         */
                        if (isset($this->_skipFields[$modelName]) && 
                            in_array($columnName, $this->_skipFields[$modelName])) {

                            continue;
                        }

                        // The following validators will not succeed with the malicous data and will cause exceptions
                        if (   isset($columnDefinition['Fisma_Doctrine_Validator_Ip'])
                            || isset($columnDefinition['email'])
                            || isset($columnDefinition['Fisma_Doctrine_Validator_Email'])
                            || isset($columnDefinition['Fisma_Doctrine_Validator_Phone'])
                            || isset($columnDefinition['Fisma_Doctrine_Validator_Url'])) {

                            continue;
                        }

                        $fieldName = $table->getFieldName($columnName);

                        /* Insert malicous text and add row number as a way of making each value unique
                         * It contains:
                         * 1. Problematic characters when working with HTML encodings, like angle brackets and ampersand
                         * 2. Weird UTF-8 characters (smart quotes) that do not exist in LATIN-1
                         * 3. A javascript snippet which tries to run an alert
                         *
                         * I tried to make this compact so it would have the highest likelihood of fitting in a field.
                         */
                        $row->$fieldName = "><&“”<script>alert('$modelName - $fieldName - $currentRow');</script>" 
                                         . rand();
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

                $progressBar->update($currentRow);
            }

            fwrite(STDOUT, "\n");
        }
    }
}
