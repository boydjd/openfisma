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
    const INDEX_CHUNK_SIZE = 100;

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

        sort($searchableClasses);
        
        // Do the actual indexing
        $searchEngine = Zend_Registry::get('search_engine');

        foreach ($searchableClasses as $searchableClass) {
            $this->_rebuildIndex($searchEngine, $searchableClass);
        }
    }
    
    /**
     * Rebuild a specific model index
     * 
     * @param Fisma_Search_Engine $searchEngine The engine to use for indexing
     * @param string $modelName The name of the model to index
     */
    private function _rebuildIndex(Fisma_Search_Engine $searchEngine, $modelName)
    {
        $searchEngine->deleteByType($modelName);

        $indexer = new Fisma_Search_Indexer($searchEngine);
        $allRecordsQuery = $indexer->getRecordFetchQuery($modelName);

        // Progress bar for console progress monitoring
        $totalRecords = $allRecordsQuery->count();

        $progressBar = $this->_getProgressBar($totalRecords);
        $progressBar->update(0, $modelName);
        $progressCallback = array($progressBar, 'update');

        // Chunk size can be set by the model table or else we use a default value
        $chunkSize = self::INDEX_CHUNK_SIZE;
        $table = Doctrine::getTable($modelName);

        if ($table instanceof Fisma_Search_CustomChunkSize_Interface) {
            $chunkSize = $table->getIndexChunkSize();
        }
        
        // Do the actual indexing
        $indexer->indexRecordsFromQuery($allRecordsQuery, $modelName, $chunkSize, $progressCallback);
        
        $searchEngine->commit();
        
        print "\n";
    }
}
