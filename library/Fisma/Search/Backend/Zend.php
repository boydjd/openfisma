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
 * Search engine backend based on the Zend Search Lucene library
 * 
 * @author     Mark E. Haase <mhaase@endeavorystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2010 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Fisma
 * @subpackage Fisma_Search
 */
class Fisma_Search_Backend_Zend extends Fisma_Search_Backend_Abstract
{
    /**
     * A cache of open indexes
     *
     * Each key is the name of an index, e.g. 'Asset', and the value is the index object
     *
     * @var array
     */
    private $_indexes = array();

    /**
     * Delete all documents in the index
     */
    public function deleteAll() 
    {
        $indexPath = Fisma::getPath('index');
        
        $indexDir = opendir($indexPath);

        while ($index = readdir($indexDir)) {
            // Skip .* files
            if ('.' == $index{0}) {
                continue;
            }

            if (is_dir($indexPath . '/' . $index)) {
                $this->deleteByType($index);
            }
        }
    }

    /**
     * Delete all documents of the specified type in the index
     *
     * "Type" refers to a model, such as Asset, Finding, Incident, etc.
     *
     * @param string $type
     */
    public function deleteByType($type) 
    {
        $indexPath = $this->_getIndexPath($type);

        if (!file_exists($indexPath)) {
            // Nothing to do
            return;
        }
        
        // Remove contents of index directory
        $indexDir = opendir($indexPath);
        
        if (!$indexDir) {
            throw new Fisma_Zend_Exception("Not able to open directory: $indexPath");
        }

        while ($indexFile = readdir($indexDir)) {
            // Skip .* files
            if ('.' == $indexFile{0}) {
                continue;
            }

            $indexFilePath = realpath($indexPath . '/' . $indexFile);

            unlink($indexFilePath);
        }
        
        // Remove the empty index directory
        rmdir($indexPath);
    }

    /**
     * Delete the specified object from the index
     *
     * $type must have a corresponding table class which implements Fisma_Search_Searchable
     *
     * @param string $type The class of the object
     * @param array $object
     */
    public function deleteObject($type, $object)
    {
        $index = $this->_openIndex($type);
        
        // Deleting an object requires us to find it first
        $term = new Zend_Search_Lucene_Index_Term($object->id, 'id');
        $query = new Zend_Search_Lucene_Search_Query_Term($term);
        $hits = $index->find($query);
        
        // We only expect one result (id is a primary key) but will delete all hits just in case
        foreach ($hits as $hit) {
            $index->delete($hit->id);
        }
        
        $index->commit();
    }

    /**
     * Index an array of objects
     *
     * @param string $type The class of the object
     * @param array $collection
     */
    public function indexCollection($type, $collection)
    {
        foreach ($collection as $object) {
            $this->indexObject($type, $object);
        }
    }

    /**
     * Add the specified object (in array format) to the search engine index
     *
     * This will overwrite any existing object with the same luceneDocumentId
     *
     * @param string $type The class of the object
     * @param array $object
     */
    public function indexObject($type, $object) 
    {
        $searchableFields = $this->_getSearchableFields($type);

        // Unlike Solr, ZSL doesn't automatically replace documents, so we need to delete explicitly
        $this->deleteObject($type, $object);
        
        $document = new Zend_Search_Lucene_Document();
        
        // Always add an ID and documentType field
        if (!isset($object['id'])) {
            $message = "Cannot index objects that do not have an ID field. (Type is: $type)";

            throw new Fisma_Search_Exception($message);
        } else {
            $document->addField(Zend_Search_Lucene_Field::Keyword('id', $object['id'], 'iso-8859-1'));
        }
        
        $table = Doctrine::getTable($type);
        
        foreach ($searchableFields as $name => $field) {
            $rawValue = $this->_getRawValueForField($table, $object, $name, $field);
            
            if (is_null($rawValue)) {
                continue;
            }

            $doctrineDefinition = $table->getColumnDefinition($table->getColumnName($name));
            
            if (isset($doctrineDefinition['extra']['purify']) && 'html' == $doctrineDefinition['extra']['purify']) {
                $purified = $this->_convertHtmlToIndexString($rawValue);

                $field = Zend_Search_Lucene_Field::Unstored($name, $purified, 'iso-8859-1');
            } elseif ('integer' == $field['type']) {
                $field = Zend_Search_Lucene_Field::Keyword($name, $rawValue, 'iso-8859-1');
            } else {
                $field = Zend_Search_Lucene_Field::Unstored($name, $rawValue, 'iso-8859-1');
            }
            
            $document->addField($field);
        }
        
        $index = $this->_openIndex($type);

        $index->addDocument($document);
    }

    /**
     * Returns true if the specified column is sortable
     *
     * This is defined in the search abstraction layer since ultimately the sorting capability is determined by the
     * search engine implementation.
     *
     * ZSL can sort on any column but it can be quite slow.
     *
     * @param string $type The class containing the column
     * @param string $columnName
     * @return bool
     */
    public function isColumnSortable($type, $columnName)
    {
        $searchableFields = $this->_getSearchableFields($type);
        
        $sortable = false;
        
        if (isset($searchableFields[$columnName]['sortable'])) {
            $sortable = $searchableFields[$columnName]['sortable'];
        }
        
        return $sortable;
    }

    /**
     * Optimize the index (degfragments the index)
     */
    public function optimizeIndex()
    {     
        // ZSL indexes are stored separately for each object type, so we need to list all possible
        // indexes first
        $indexEnumerator = new Fisma_Search_IndexEnumerator();
        
        $searchableClasses = $indexEnumerator->getSearchableClasses(Fisma::getPath('model'));
        
        // Loop over searchable classes and tell ZSL to optimize each index
        foreach ($searchableClasses as $searchableClass) {
            $index = $this->_openIndex($searchableClass);
            
            $index->optimize();
        }
    }

    /**
     * Simple search: search all fields for the specified keyword
     *
     * @param string $type Name of model index to search
     * @param string $keyword
     * @param string $sortColumn Name of column to sort on
     * @param boolean $sortDirection True for ascending sort, false for descending
     * @param int $start The offset within the result set to begin returning documents from
     * @param int $rows The number of documents to return
     * @param bool $deleted If true, include soft-deleted records in the results
     * @return Fisma_Search_Result
     */
    public function searchByKeyword($type, $keyword, $sortColumn, $sortDirection, $start, $rows, $deleted)
    {
        $table = Doctrine::getTable($type);
        $searchableFields = $this->_getSearchableFields($type);
        
        // Check sorting parameters
        if (!$this->isColumnSortable($type, $sortColumn)) {
            throw new Fisma_Search_Exception("Not a sortable column: $sortColumn");
        }

        // Create subquery for the keywords (or a default query if no keywords provided)
        $trimmedKeyword = trim($keyword);

        if (!empty($trimmedKeyword)) {
            $keywordTokens = explode(' ', $trimmedKeyword);
            $keywordTokens = array_filter($keywordTokens);
            $keywordTokens = array_map(array($this, 'escape'), $keywordTokens);
        }

        $zslQuery = new Zend_Search_Lucene_Search_Query_MultiTerm;

        if (count($keywordTokens)) {
            foreach ($keywordTokens as $keyword) {
                $zslQuery->addTerm(new Zend_Search_Lucene_Index_Term($keyword));
            }

            // Use lucene index to get IDs of matching documents     
            $index = $this->_openIndex($type);
            $zslResult = $index->find($zslQuery);
        }
        
        // Now use matched IDs to query Doctrine for actual document contents
        $doctrineQuery = Doctrine_Query::create()
                         ->from("$type a")
                         ->setHydrationMode(Doctrine::HYDRATE_SCALAR);

        $currentAlias = 'a';
        $relationAliases = array();

        // Add any join tables required to get related records
        foreach ($searchableFields as $fieldName => $fieldDefinition) {
            if (isset($fieldDefinition['join'])) {
                $relation = $fieldDefinition['join']['relation'];
                
                // Create a new relation alias if needed
                if (!isset($relationAliases[$relation])) {
                    $currentAlias = chr(ord($currentAlias) + 1);

                    // Nested relations are allowed, ie. "System.Organization"
                    $relationParts = explode('.', $relation);

                    // First relation is related directly to the base table
                    $doctrineQuery->leftJoin("a.{$relationParts[0]} $currentAlias");
                    $doctrineQuery->addSelect("$currentAlias.id");
                    
                    // Remaining relations are recursively related to each other
                    for ($i = 1; $i < count($relationParts); $i++) {
                        $previousAlias = $currentAlias;
                        $currentAlias = chr(ord($currentAlias) + 1);
                        
                        $relationPart = $relationParts[$i];
                        
                        $doctrineQuery->leftJoin("$previousAlias.$relationPart $currentAlias");
                        $doctrineQuery->addSelect("$currentAlias.id");
                    }
                    
                    $relationAliases[$relation] = $currentAlias;
                }
                
                $relationAlias = $relationAliases[$relation];

                $name = $fieldDefinition['join']['field'];

                $doctrineQuery->addSelect("$relationAlias.$name");
            } else {
                $doctrineQuery->addSelect("a.$fieldName");
            }
        }

        if (isset($zslResult)) {
            // Create an array of matched Ids from lucene and add to doctrine query 
            $ids = array();
    
            foreach ($zslResult as $hit) {
                $ids[] = $hit->getDocument()->getField('id')->value;
            }
            
            $doctrineQuery->whereIn('a.id', $ids);
        }

        // Handle soft delete records
        if ($deleted) {
            // Hack: its not possible to disable just the soft delete listener, so disable all dql callbacks instead
            Doctrine_Manager::getInstance()->setAttribute(Doctrine::ATTR_USE_DQL_CALLBACKS, false);
        }
        
        // Add ACL constraints
        $aclTerms = $this->_getAclTerms($table);
        $aclFields = array();

        foreach ($aclTerms as $aclTerm) {
            if (!isset($aclFields[$aclTerm['field']])) {
                $aclFields[$aclTerm['field']] = array();
            }

            $aclFields[$aclTerm['field']][] = $aclTerm['value'];
        }

        foreach ($aclFields as $aclField => $aclValues) {
            $relationTable = $searchableFields[$aclField]['join']['relation'];
            $relationAlias = $relationAliases[$relationTable];
            $relationField = $searchableFields[$aclField]['join']['field'];

            $doctrineQuery->whereIn("$relationAlias.$relationField", $aclValues);
        }

        // Add sorting and limit/offset
        $sortDefinition = $searchableFields[$sortColumn];
        $sortOrder = $sortDirection ? 'ASC' : 'DESC'; 
        
        if (isset($sortDefinition['join'])) {
            $relationTable = $sortDefinition['join']['relation'];
            $relationAlias = $relationAliases[$relationTable];
            $relationField = $sortDefinition['join']['field'];

            $doctrineQuery->orderBy("relationAlias.$relationField $sortOrder");
        } else {
            $doctrineQuery->orderBy("a.$sortColumn $sortOrder");
        }

        $doctrineCount = $doctrineQuery->count();

        $doctrineQuery->limit($rows)
                      ->offset($start);

        // Get result and convert to Fisma_Search_Result
        $doctrineResult = $doctrineQuery->execute();

        // Fix hack for soft delete records (see above)
        if ($deleted) {
            Doctrine_Manager::getInstance()->setAttribute(Doctrine::ATTR_USE_DQL_CALLBACKS, true);
        }

        // Remove table alias prefixes (first two characters) from column name
        $tableData = array();

        foreach ($doctrineResult as $row) {
            $rowData = array();

            foreach ($row as $columnName => $columnValue) {
                $newColumnName = substr($columnName, 2);
                $newColumnValue = $this->_convertHtmlToIndexString($columnValue);

                $maxRowLength = $this->getMaxRowLength();

                if ($maxRowLength && strlen($newColumnValue) > $maxRowLength) {
                    $shortValue = substr($newColumnValue, 0, $maxRowLength);

                    // Trim after the last white space (so as not to break in the middle of a word)
                    $spacePosition = strrpos($shortValue, ' ');

                    if ($spacePosition) {
                        $shortValue = substr($shortValue, 0, $spacePosition);
                    }

                    $newColumnValue = $shortValue . '...';
                } else {
                    $newColumnValue = $newColumnValue;
                }
                
                $rowData[$newColumnName] = $newColumnValue;
            }
            
            $tableData[] = $rowData;
        }

        return new Fisma_Search_Result($doctrineCount, count($doctrineResult), $tableData);    
    }

    /**
     * Advanced search: search based on a list of specific field criteria
     *
     * @param string $type Name of model index to search
     * @param Fisma_Search_Criteria $criteria
     * @param string $sortColumn Name of column to sort on
     * @param boolean $sortDirection True for ascending sort, false for descending
     * @param int $start The offset within the result set to begin returning documents from
     * @param int $rows The number of documents to return
     * @param bool $deleted If true, include soft-deleted records in the results
     * @return Fisma_Search_Result Rectangular array of search results
     */
    public function searchByCriteria(
        $type,
        Fisma_Search_Criteria $criteria,
        $sortColumn,
        $sortDirection,
        $start,
        $rows,
        $deleted
        ) 
    {
        throw new Exception("NOT IMPLEMENTED");
    }

    /**
     * Validate the backend's configuration
     *
     * @return mixed Return TRUE if configuration is valid, or a string error message otherwise
     */
    public function validateConfiguration()
    {
       $indexDir = Fisma::getPath('index');

        if (!is_writeable($indexDir)) {
            return "Index directory ($indexDir) is not writeable";
        }

        return true;
    }

    /**
     * Get path for a specified index
     */
    private function _getIndexPath($type)
    {
        // Use basename on $type to prevent any potential path traversal attacks
        $indexPath = Fisma::getPath('index') . '/' .  basename($type);

        return $indexPath;
    }
    
    /**
     * Open an index with the specified name
     */
    private function _openIndex($type)
    {
        if (isset($this->_indexes[$type])) {
            // If this index is already open, reuse existing object
            $index = $this->_indexes[$type];
        } else {
            $indexPath = $this->_getIndexPath($type);

            if (file_exists($indexPath)) {
                $index = Zend_Search_Lucene::open($indexPath);
            } else {
                $index = Zend_Search_Lucene::create($indexPath);
            }
            
            // Cache a reference to the index
            $this->_indexes[$type] = $index;
        }

        return $index;
    }
}
