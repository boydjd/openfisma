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
 * <http://www.gnu.org/licenses/>.
 */

$script = new OptimizeIndexes();
$script->run();

/**
 * Optimizes (and creates if necessary) Lucene indexes for OpenFISMA
 * 
 * @author     Mark E. Haase <mhaase@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2009 (http://www.endeavorsystems.com)
 * @license    http://www.openfisma.org/content/license
 * @package    Scripts
 * @version    $Id$
 */
class OptimizeIndexes
{
    /**
     * A list of models which this script maintains the indexes for.
     * 
     * This list needs to be updated as new models are created.
     * 
     * @var array
     */
    private $_models = array(
        'AccountLog',
        'Asset',
        'Finding',
        'Network',
        'Organization',
        'Product',
        'Role',
        'System',
        'SystemDocument',
        'Source',
        'User'
    );
    
    /**
     * The number of records to fetch at a time when creating a new index
     * @var int
     */
    const FETCH_ROWS = 100;
    
    /**
     * The number of seconds to wait between updating the status line on STDOUT
     * @var int
     */
    const STATUS_UPDATE_INTERVAL = 1;
    
    /**
     * The number of records to process in between each index defragementation
     * @var int
     */
    const DEFRAG_RECORD_COUNT = 1000;
    
    /**
     * Create a script object and connect it to the Fisma library
     */
    public function __construct() 
    {
        require_once(realpath(dirname(__FILE__) . '/../../library/Fisma.php'));

        Fisma::initialize(Fisma::RUN_MODE_COMMAND_LINE);
        Fisma::connectDb();
        
        // Zend Search Lucene is a memory hog, esp. in php 5.2
        ini_set('memory_limit', '256M');
    }
        
    /**
     * The script's entry point
     */
    public function run()
    {
        print "This may take several minutes...\n";
        $start = time();
        
        // Process each model
        foreach ($this->_models as $model) {
            if (!is_dir(Fisma::getPath('index') . '/' . $model)) {
                $this->_createIndex($model);
            }
            
            $this->_optimizeIndex($model);
        }
        
        $stop = time();
        $elapsed = $stop - $start;
        $minutes = floor($elapsed/60);
        $seconds = $elapsed - ($minutes * 60);
        
        print "Finished in $minutes minutes and $seconds seconds\n";
    }
    
    /**
     * Create an index
     * 
     * This is a slow process because each record has to be hydrated as an object, and the index has to be
     * defragmented frequently.
     */
    private function _createIndex($model)
    {
        // Fisma_Index will create the index automatically if it doesn't exist
        $index = new Fisma_Index($model);
        
        // Create a query to get all rows from the table in multiple blocks
        $query = Doctrine_Query::create()
                 ->from($model)
                 ->limit(self::FETCH_ROWS);
        $offset = 0;
        $totalRecords = Doctrine::getTable($model)->count();
        $currentRecord = 0;
        $lastStatusUpdateTime = time();
        
        // Update status
        $status = "$model: Indexed 0 rows (0%)";
        fwrite(STDOUT, $status);        
        $statusLength = strlen($status);
        
        while ($currentRecord < $totalRecords) {
            // Set the offset and execute the query
            $query->offset($offset);
            $records = $query->execute();
            $offset += self::FETCH_ROWS;
        
            // Loop through records
            foreach ($records as $record) {
                $index->update($record);
                $currentRecord++;
                
                // Update the status on STDOUT periodically
                if (time() - $lastStatusUpdateTime >= self::STATUS_UPDATE_INTERVAL) {
                    // 0x8 is the backspace code
                    fwrite(STDOUT, str_repeat(chr(0x8), $statusLength));
                    $status = "$model: Indexed $currentRecord rows (" 
                            . sprintf('%d%%', ($currentRecord / $totalRecords) * 100) 
                            . ")" ;
                    fwrite(STDOUT, "$status");
                    $statusLength = strlen($status);
                    $lastStatusUpdateTime = time();
                }

                // Defrag the index after every 1000 records
                if (0 == $currentRecord % self::DEFRAG_RECORD_COUNT) {
                    $index->optimize();
                }
            }
        }
            
        // Defrag the index 1 last time
        $index->optimize();
        
        // Update status line
        fwrite(STDOUT, str_repeat(chr(0x8), $statusLength));
        $status = "$model: Indexed $currentRecord rows (COMPLETE)\n";
        fwrite(STDOUT, $status);
    }
    
    /**
     * Optimize an index
     */
    private function _optimizeIndex($model)
    {
        $index = new Fisma_Index($model);
        $index->optimize();
        print("$model: Optimized\n");
    }
}
