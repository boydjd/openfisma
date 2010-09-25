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
 * A command-line-oriented class for rebuilding search indexes
 * 
 * @author     Mark E. Haase <mhaase@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2010 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Fisma
 * @subpackage Fisma_Cli
 */
class Fisma_Cli_RebuildIndex extends Fisma_Cli_Abstract
{
    /**
     * The number of records to fetch at a time when creating a new index
     * 
     * @var int
     */
    const INDEX_CHUNK_SIZE = 25;

    /**
     * Configure the arguments accepted for this CLI program
     * 
     * @return array An array containing getopt long syntax
     */
    public function getArgumentsDefinitions()
    {
        return array(
            'model|m=w' => "Name of model to rebuild index for. Mutually exclusive with --all option.",
            'all|a' => 'Rebuilds ALL models\' indexes. Mutually exclusive with --model option.'
        );
    }    
    
    /**
     * Drop the model indexes specified on the command line
     */
    protected function _run()
    {     
        $modelName = $this->getOption('model');
        $allModels = $this->getOption('all');

        // The two options are mutually exclusive
        if ( (is_null($modelName) && is_null($allModels)) || (!is_null($modelName) && !is_null($allModels)) ) {

            throw new Fisma_Zend_Exception_User("You must specify either a model or the all option, but not both.");
        }

        // Create a list of all model indexes which are to be rebuilt
        if ($allModels) {
            $indexEnumerator = new Fisma_Search_IndexEnumerator();
            
            $searchableClasses = $indexEnumerator->getSearchableClasses(Fisma::getPath('model'));            
        } else {
            $searchableClasses = array($modelName);
        }

        // Do the actual indexing
        $searchEngine = Fisma_Search_BackendFactory::getSearchBackend();

        foreach ($searchableClasses as $searchableClass) {
            $this->_rebuildIndex($searchEngine, $searchableClass);
        }
    }
    
    /**
     * Rebuild a specific model index
     * 
     * @param Fisma_Search_Backend_Abstract $searchEngine The engine to use for indexing
     * @param string $modelName The name of the model to index
     */
    private function _rebuildIndex(Fisma_Search_Backend_Abstract $searchEngine, $modelName)
    {
        // Delete all documents in this model's index first
        $searchEngine->deleteByType($modelName);

        // Get a total count of all records
        $allRecordsQuery = Doctrine_Query::create()
                           ->from("$modelName a")
                           ->setHydrationMode(Doctrine::HYDRATE_ARRAY);
        
        // Add relations (if any) to the query -- this results in more efficient indexing of related records
        $table = Doctrine::getTable($modelName);
        $searchableFields = $table->getSearchableFields();
        
        // Implementers can tweak the selection query to filter out undesired records
        if (method_exists($table, 'getSearchIndexQuery')) {
            $allRecordsQuery = call_user_func(array($table, 'getSearchIndexQuery'), $allRecordsQuery);
        }

        $currentAlias = 'a';
        $relationAliases = array();

        foreach ($searchableFields as $fieldName => $fieldDefinition) {
            if (isset($fieldDefinition['join'])) {
                $relation = $fieldDefinition['join']['relation'];
                
                // Create a new relation alias if needed
                if (!isset($relationAliases[$relation])) {
                    $currentAlias = chr(ord($currentAlias) + 1);

                    // Nested relations are allowed, ie. "System.Organization"
                    $relationParts = explode('.', $relation);

                    // First relation is related directly to the base table
                    $allRecordsQuery->leftJoin("a.{$relationParts[0]} $currentAlias");
                    $allRecordsQuery->addSelect("$currentAlias.id");
                    
                    // Remaining relations are recursively related to each other
                    for ($i = 1; $i < count($relationParts); $i++) {
                        $previousAlias = $currentAlias;
                        $currentAlias = chr(ord($currentAlias) + 1);
                        
                        $relationPart = $relationParts[$i];
                        
                        $allRecordsQuery->leftJoin("$previousAlias.$relationPart $currentAlias");
                        $allRecordsQuery->addSelect("$currentAlias.id");
                    }
                    
                    $relationAliases[$relation] = $currentAlias;
                }
                
                $relationAlias = $relationAliases[$relation];

                $name = $fieldDefinition['join']['field'];

                $allRecordsQuery->addSelect("$relationAlias.$name");
            } else {
                $allRecordsQuery->addSelect("a.$fieldName");
            }
        }

        // Execute query
        $totalRecords = $allRecordsQuery->count();

        // Progress bar for console progress monitoring
        $progressBar = $this->_getProgressBar($totalRecords);
        $progressBar->update(0, $modelName);

        // Loop over chunks of records from this table and index each chunk in a single request
        $currentRecord = 0;

        while ($currentRecord < $totalRecords) {

            // Get the next set of records and index them
            $allRecordsQuery->limit(self::INDEX_CHUNK_SIZE)
                            ->offset($currentRecord);

            $recordSet = $allRecordsQuery->execute();

            $searchEngine->indexCollection($modelName, $recordSet);

            $currentRecord += count($recordSet);

            $progressBar->update($currentRecord);
        }

        print "\n";
    }
}
