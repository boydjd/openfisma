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
 * This listener updates the Lucene index whenever a record is updated.
 * 
 * @author     Mark E. Haase <mhaase@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Listener
 */
class IndexListener extends Fisma_Doctrine_Record_Listener
{
    /**
     * Controls whether search index changes are auto-committed or not
     * 
     * @var bool
     */
    static private $_autoCommit = true;

    /**
     * Get whether index changes are auto-committed or not
     * 
     * @return bool
     */
    static public function getAutoCommitEnabled()
    {
        return self::$_autoCommit;
    }

    /**
     * Set whether index changes are auto-committed or not
     * 
     * @param bool $enabled
     */
    static public function setAutoCommitEnabled($enabled)
    {
        self::$_autoCommit = $enabled;
    }
    
    /**
     * New records always get indexed
     * 
     * @param Doctrine_Event $event The listened doctrine event to process
     * @return void
     */
    public function postInsert(Doctrine_Event $event)
    {
        if (!self::$_listenerEnabled) {
            return;
        }

        $record = $event->getInvoker();

        if (!($record->getTable() instanceof Fisma_Search_Searchable)) {
            $message = 'Object table does not implement the Fisma_Search_Searchable interface: ' . get_class($record);

            throw new Fisma_Search_Exception($message);
        }

        $modelName = get_class($record);
        $relationAliases = array();

        $searchEngine = Zend_Registry::get('search_engine');
        $indexer = new Fisma_Search_Indexer($searchEngine);
        $indexQuery = $indexer->getRecordFetchQuery($modelName, $relationAliases);
        
        // Relation aliases are derived from doctrine table metadata and are safe to interpolate
        $baseClassAlias = $relationAliases[$modelName];
        $indexQuery->andWhere("$baseClassAlias.id = ?", $record->id);

        $indexer->indexRecordsFromQuery($indexQuery, $modelName);

        if (self::$_autoCommit) {
            $searchEngine->commit();
        }
    }

    /**
     * Updated records are only indexed if one of its indexable fields was modified
     * 
     * @param Doctrine_Event $event The listened doctrine event to process
     * @return void
     */
    public function postUpdate(Doctrine_Event $event)
    {
        if (!self::$_listenerEnabled) {
            return;
        }

        $record = $event->getInvoker();
        $modified = $record->getLastModified();

        if (!($record->getTable() instanceof Fisma_Search_Searchable)) {
            $message = 'Object table does not implement the Fisma_Search_Searchable interface: ' . get_class($record);

            throw new Fisma_Search_Exception($message);
        }

        // A quick shortcut:
        if (0 == count($modified)) {
            return;
        }

        $table = $record->getTable();

        // If an indexed field changed, then update the index for this object
        if ($this->_needsReindex($modified, $table)) {
            $modelName = get_class($record);
            $relationAliases = array();

            $searchEngine = Zend_Registry::get('search_engine');
            $indexer = new Fisma_Search_Indexer($searchEngine);
            $indexQuery = $indexer->getRecordFetchQuery($modelName, $relationAliases);

            // Relation aliases are derived from doctrine table metadata and are safe to interpolate
            $baseClassAlias = $relationAliases[$modelName];
            $indexQuery->andWhere("$baseClassAlias.id = ?", $record->id);

            $indexer->indexRecordsFromQuery($indexQuery, $modelName);

            if (self::$_autoCommit) {
                $searchEngine->commit();
            }
        }

        // If an indexed field changed on a related model, then reindex the affected documents belonging to that 
        // related model
        $reverseIndexer = new Fisma_Search_ReverseIndexer;
        $reverseIndexer->reindexAffectedDocuments($record->id, $record->getTable(), array_keys($modified));
    }
    
    /**
     * Remove deleted records from the keyword index
     * 
     * @param Doctrine_Event $event The listened doctrine event to process
     * @return void
     */
    public function postDelete(Doctrine_Event $event)
    {
        if (!self::$_listenerEnabled) {
            return;
        }

        $record   = $event->getInvoker();
        $modified = $record->getLastModified();

        if (!($record->getTable() instanceof Fisma_Search_Searchable)) {
            $message = 'Object table does not implement the Fisma_Search_Searchable interface: ' . get_class($record);

            throw new Fisma_Search_Exception($message);
        }

        // If the record is softDeleted, then reindex it. Otherwise, delete the record from the index.
        $searchEngine = Zend_Registry::get('search_engine');

        if ($record->getTable()->hasColumn('deleted_at')) {
            $modelName = get_class($record);
            $relationAliases = array();

            $indexer = new Fisma_Search_Indexer($searchEngine);
            $indexQuery = $indexer->getRecordFetchQuery($modelName, $relationAliases);

            // Relation aliases are derived from doctrine table metadata and are safe to interpolate
            $baseClassAlias = $relationAliases[$modelName];
            $indexQuery->andWhere("$baseClassAlias.id = ?", $record->id);

            $indexer->indexRecordsFromQuery($indexQuery, $modelName);
        } else {
            $searchEngine->deleteObject(get_class($record), $record->toArray());
        }

        if (self::$_autoCommit) {
            $searchEngine->commit();
        }
    }
    
    /**
     * Determine if a record needs to be re-indexed based on changes made by a user
     * 
     * This is smart enough to check whether the user modified any indexable fields. If the user did not,
     * then a re-index is not required.
     * 
     * @param array $modified List of modified fields
     * @param Doctrine_Table $table
     */
    private function _needsReindex($modified, Doctrine_Table $table)
    {
        $searchableFields = $table->getSearchableFields();
        $relations = $table->getRelations();

        foreach (array_keys($modified) as $modifiedField) {
            foreach ($searchableFields as $searchFieldName => $searchFieldDefinition) {

                // Did the user modify an indexable field on this object model?
                if ($modifiedField == $searchFieldName) {

                    return true;
                }

                // Did the user modify an indexable foreign key?
                if (isset($searchFieldDefinition['join'])) {
                    foreach ($relations as $relation) {
                        if ($relation->getLocalFieldName() == $modifiedField && 
                            $relation->getAlias() == $searchFieldDefinition['join']['relation']) {
                        
                            return true;
                        }
                    }
                }
            }
        }
        
        return false;
    }
}
