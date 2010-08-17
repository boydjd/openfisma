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
     * The model that this manager is associated with
     * 
     * @var string
     */
    private $_modelName;

    /**
     * Constructor
     * 
     * @param string $modelName
     */
    public function __construct($modelName)
    {
        if (empty($modelName)) {
            throw new Fisma_Zend_Exception('$modelName is a required parameter');
        }
        
        $this->_modelName = $modelName;
    }

    /**
     * Delete the index for the model associated with this manager
     */
    public function deleteIndex()
    {
        $table = Doctrine::getTable($this->_modelName);

        $session = $table->getSearchSession();

        $deleteQuery = $session->createDeleteQuery($this->_modelName);
        
        $session->delete($deleteQuery);
    }

    /**
     * Builds an index for the model associated with this manager
     * 
     * This deletes any pre-existing documents in the index then indexes all of the data in this class's table
     */
    public function rebuildIndex()
    {
        $table = Doctrine::getTable($this->_modelName);
        $session = $table->getSearchSession();
        
        $this->deleteIndex();
                
        // Get a total count of all records
        $allRecordsQuery = Doctrine_Query::create()->from($this->_modelName);

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
    
    /**
     * Search all string fields on the associated model with a Lucene-syntax query
     * 
     * This is a convenience function because EZC does not provide any query which searches all fields by default. EZC
     * only lets you search specific fields. So this method enumerates all fields for the current model and sends those
     * to EZC.
     * 
     * @param string $luceneQuery A search query in lucene syntax
     * @return array Array of IDs of matching objects
     */
    public function searchIndex($luceneQuery)
    {
        $searchSession = Doctrine::getTable($this->_modelName)->getSearchSession();
        
        // EZC expects an explicit list of all fields to search, so we want to enumerate all searchable fields
        $definitionManager = new Fisma_Search_DefinitionManager;
        $definition = $definitionManager->fetchDefinition($this->_modelName);

        unset($definition->fields['primaryKey']);
        
        $searchFields = array_keys($definition->fields);

        // EZC requies a query builder to parse Lucene syntax queries
        $searchQuery = $searchSession->createFindQuery($this->_modelName);
        
        $queryBuilder = new ezcSearchQueryBuilder();
        $queryBuilder->parseSearchQuery($searchQuery, $luceneQuery, $searchFields);
        
        // Execute query and collect results in an array
        $searchResults = $searchSession->find($searchQuery);

        $ids = array();
    
        foreach ($searchResults->documents as $result) {
            $ids[] = $result->document->id;
        }

        return $ids;
    }
}
