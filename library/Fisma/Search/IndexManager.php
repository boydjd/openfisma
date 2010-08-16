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
 * Manages keyword search indexes
 * 
 * @author     Mark E. Haase
 * @copyright  (c) Endeavor Systems, Inc. 2010 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Fisma
 * @subpackage Fisma_Search
 */
class Fisma_Search_IndexManager
{
    /**
     * The number of records to fetch at a time when creating a new index
     * 
     * @var int
     */
    const FETCH_ROWS = 100;    

    /**
     * Delete the index for the specified class
     * 
     * @param string $className
     */
    public function deleteIndexForClass($className)
    {
        $table = Doctrine::getTable($className);

        $session = $table->getSearchSession();

        $deleteQuery = $session->createDeleteQuery($className);
        
        $session->delete($deleteQuery);
    }

    /**
     * Return an array of all classes in OpenFISMA which are defined as searchable
     * 
     * @param string $modelPath Path to model files
     */
    public function getSearchableClasses($modelPath)
    {
        $modelNames = array();
        
        $iterator = new DirectoryIterator($modelPath);        

        foreach ($iterator as $file) {
            
            // Skip directories
            if (!$file->isFile()) {
                continue;
            }
            
            $name = $file->getFilename();
            
            // Skip table classes
            if (strpos($name, 'Table') !== false) {
                continue;
            }
            
            require_once(realpath($modelPath . '/' . $name));

            // Strip off .php extension
            $modelName = substr($name, 0, -4);
            
            // Check for Fisma_Doctrine_Record subclasses only
            $reflection = new ReflectionClass($modelName);

            if (!$reflection->isSubclassOf('Fisma_Doctrine_Record')) {
                continue;
            }

            // Check if the model has search attributes
            $table = Doctrine::getTable($modelName);

            $columns = $table->getColumns();
            
            foreach ($columns as $column) {
                if ('string' == $column['type'] && isset($column['extra']['search'])) {
                    $modelNames[] = $modelName;
                    
                    break;
                }
            }
        }
        
        return $modelNames;
    }

    /**
     * Builds an index for the specified class
     * 
     * This deletes any pre-existing documents in the index then indexes all of the data in this class's table
     * 
     * @param string $className
     */
    public function rebuildIndexForClass($className)
    {
        $table = Doctrine::getTable($className);
        $session = $table->getSearchSession();
        
        $this->deleteIndexForClass($className);
                
        // Get a total count of all records
        $allRecordsQuery = Doctrine_Query::create()
                           ->from($className);

        $totalRecords = $allRecordsQuery->count();
        
        $progressBar = new Zend_ProgressBar(new Zend_ProgressBar_Adapter_Console, 0, $totalRecords);
        
        // Create new documents and index them
        $session->beginTransaction();
        
        $currentRecord = 0;
        while ($currentRecord < $totalRecords) {
            
            // Get the next set of records and index them
            $allRecordsQuery->limit(self::FETCH_ROWS)
                            ->offset($currentRecord);

            $recordSet = $allRecordsQuery->execute();
            
            foreach ($recordSet as $record) {
                $session->index($record);
                
                $currentRecord++;
            }

            $recordSet->free();
            
            $progressBar->update($currentRecord);
        }
        
        $session->commit();
    }
}
