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
 * The reverse indexer is used when a field on one model is stored in the denormalized schema of another model. 
 * 
 * When such a field is updated, the search engine must also update the stale documents corresponding to the related
 * model.
 * 
 * @author     Mark E. Haase <mhaase@endeavorsystems.com>
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Fisma
 * @subpackage Fisma_Search
 */
class Fisma_Search_ReverseIndexer
{
    /**
     * 
     *
     * @param int $id Primary key of the object which triggered the reindex
     * @param Doctrine_Table $table The doctrine table that contains the object
     * @param array List of modified fields on the object
     */
    public function reindexAffectedDocuments($id, Doctrine_Table $table, $modified)
    {        
        $relationsThatNeedUpdating = $this->_getRelationsNeedingUpdate($table, $modified);

        if (count($relationsThatNeedUpdating)) {
            /**
             * @todo Currently when a large number of denormalized records need to be updated (due to a modification on
             * the "1" side of a many-to-1 relationship), we do the updates inside the same process which is serving the
             * request. In the future, this operation should be forked to another process so that the request can be
             * serviced as quickly as possible.
             *
             * Until then, we need to turn off the script time limit.
             */
            set_time_limit(0);
            
            foreach ($relationsThatNeedUpdating as $relation) {
                $this->_reindexRelatedRecords($id, $relation);
            }
        }
    }

    /**
     * Given a table, the id of a record from that table, and the name of a foreign relation on that table, 
     * re-index all of the foreign records related to the local record. 
     * 
     * @param int $id The identifier of the local record
     * @param string $relation The name of the relation (in dotted form, e.g. Finding.Source is Finding's Source)
     */
    private function _reindexRelatedRecords($id, $relation)
    {
        $searchEngine = Fisma_Search_BackendFactory::getSearchBackend();
        $indexer = new Fisma_Search_Indexer($searchEngine);
        
        // $relation looks like: "SystemDocument.System.Organization". SystemDocument is the base class and 
        // System.Organization is the relation name
        $relationParts = explode('.', $relation);
        $baseClass = array_shift($relationParts);
        $relationName = implode('.', $relationParts);
        
        // Relation aliases will be passed by referenced and filled in by the called method
        $relationAliases = null;
        $fetchQuery = $indexer->getRecordFetchQuery($baseClass, $relationAliases);
        
        // Now filter query to only fetch records related to the modified record.
        // (Relation alias is safe to interpolate because it is generated from doctrine metadata without user input.)
        $relationAlias = $relationAliases[$relationName];
        $fetchQuery->andWhere("$relationAlias.id = ?", $id);

        // Chunk size can be set by the model table or else we use a default value
        $chunkSize = Fisma_Cli_RebuildIndex::INDEX_CHUNK_SIZE;
        $table = Doctrine::getTable($baseClass);

        if ($table instanceof Fisma_Search_CustomChunkSize_Interface) {
            $chunkSize = $table->getIndexChunkSize();
        }
        
        // Do the actual indexing
        $indexer->indexRecordsFromQuery($fetchQuery, $baseClass, $chunkSize);
        
        $searchEngine->commit();        
    }

    /**
     * Return an array of relations (relative to the specified table) which would need to be reindexed based on the
     * modified columns.
     *
     * @param Doctrine_Table $table
     * @param array List of modified fields on the object that triggered the reindex
     * @return array
     */
    private function _getRelationsNeedingUpdate($table, $modified)
    {
        $reverseIndex = $this->_getReverseIndex();

        // Get model name from table name by removing last 5 characters: "Table"
        $modelName = substr(get_class($table), 0, -5);

        $relationsNeedUpdating = array();

        if (isset($reverseIndex[$modelName])) {
            $tableRelatedFields = $reverseIndex[$modelName];
            
            foreach ($tableRelatedFields as $tableRelatedField => $tableRelations) {
                if (in_array($tableRelatedField, $modified)) {
                    foreach ($tableRelations as $tableRelation) {
                        if (!isset($relationsNeedUpdating[$tableRelation])) {
                            // Use array as a set (all values are set to a dummy value)
                            $relationsNeedUpdating[$tableRelation] = true;
                        }
                    }
                }
            }            
        }
        
        return array_keys($relationsNeedUpdating);
    }

    /**
     * Return an array of reverse indexes for all models.
     *
     * The reverse index is used to identify which fields
     * on each model are included in the denormalized search data for another model. For example, the Finding Source
     * model's "nickname" field is indexed by the Finding model. When the Finding Source nickname is changed, all
     * corresponding Findings need to be re-indexed.
     *
     * The return value is a nested array. The outer array contains names of the base models. In the example above, this
     * would be "Source". The middle array is a list of fields on that model which affect the search indexes of other
     * models. In the example above, that would be "nickname". The innermost array contains the name of the actual 
     * models which are affected by the first two items. In the example above, that would be Finding.
     *
     * E.g. array('Source' => array('nickname' => array('Finding'))) means that when we modify the Source.nickname field
     * we should re-index all findings where Finding.Source.nickname = Source.nickname.
     *
     * @return array
     */
    private function _getReverseIndex()
    {
        $bootstrap = Zend_Controller_Front::getInstance()->getParam('bootstrap');

        $cache = false;
        if ($bootstrap && $bootstrap->hasResource('cachemanager')) {
            $cache = $bootstrap->getResource('cachemanager')->getCache('default');
        }

        $reverseIndex = $cache ? $cache->load('searchEngineReverseIndex') : false;

        if (!$reverseIndex) {
        
            $indexEnumerator = new Fisma_Search_IndexEnumerator;
            
            $searchableModels = $indexEnumerator->getSearchableClasses(Fisma::getPath('model'));
            
            $reverseIndex = array();
            
            foreach ($searchableModels as $searchableModel) {
    
                $table = Doctrine::getTable($searchableModel);
    
                $searchableFields = $table->getSearchableFields();
                
                foreach ($searchableFields as $searchableField) {
                    if (isset($searchableField['join'])) {
                        $relatedModel = $searchableField['join']['model'];
                        $relationName = $searchableField['join']['relation'];
                        $relatedField = $searchableField['join']['field'];
                        
                        if (empty($relatedModel) ||
                            empty($relationName) ||
                            empty($relatedField)) {
                        
                            throw new Fisma_Search_Exception("Search relation is not configured correctly.");
                        }
                        
                        if (!isset($reverseIndex[$relatedModel])) {
                            $reverseIndex[$relatedModel] = array();
                        }
                        
                        if (!isset($reverseIndex[$relatedModel][$relatedField])) {
                            $reverseIndex[$relatedModel][$relatedField] = array();
                        }
                        
                        $reverseIndex[$relatedModel][$relatedField][] = "$searchableModel.$relationName";
                    }
                }
            }
            
            if ($cache) {
                $cache->save($reverseIndex, 'searchEngineReverseIndex');
            }
        }
        
        return $reverseIndex;

    }
}
