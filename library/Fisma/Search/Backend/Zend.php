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
     * ZSL doesn't support commits, so this is a no-op.
     */
    public function commit()
    {
        // ZSL doesn't support commits, so this is a no-op.
    }

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
        $term = new Zend_Search_Lucene_Index_Term($object['id'], 'id');
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
            $keywordTokens = $this->_tokenizeBasicQuery($trimmedKeyword);
            $keywordTokens = array_filter($keywordTokens);
            $keywordTokens = array_map(array($this, 'escape'), $keywordTokens);
        }

        $zslQuery = new Zend_Search_Lucene_Search_Query_MultiTerm;

        if (isset($keywordTokens)) {
            foreach ($keywordTokens as $keyword) {
                $zslQuery->addTerm(new Zend_Search_Lucene_Index_Term(strtolower($keyword)));
            }

            // Use lucene index to get IDs of matching documents     
            $index = $this->_openIndex($type);
            $zslResult = $index->find($zslQuery);
        }

        // Now use matched IDs to query Doctrine for actual document contents
        $doctrineQuery = Doctrine_Query::create()
                         ->from("$type a")
                         ->setHydrationMode(Doctrine::HYDRATE_SCALAR);

        if (!isset($searchableFields['id'])) {
            $doctrineQuery->select('a.id');
        }

        $currentAlias = 'a';
        $relationAliases = array($type => $currentAlias);

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

                $doctrineQuery->addSelect("$relationAlias.$name $fieldName");
            } else {
                $doctrineQuery->addSelect("a.$fieldName");
            }
        }

        if (isset($zslResult)) {
            // Create an array of matched Ids from lucene and add to doctrine query.
            $ids = array();
    
            foreach ($zslResult as $hit) {
                $ids[] = $hit->getDocument()->getField('id')->value;
            }

            if (count($ids)) {
                $doctrineQuery->whereIn('a.id', $ids);   
            } else {
                // If no matches in Lucene, then we don't need to go any further.
                return new Fisma_Search_Result(0, 0, array());
            }      
        }

        // Handle soft delete records
        if ($table->hasColumn('deleted_at')) {
            $doctrineQuery->addSelect('a.deleted_at');

            if ($deleted) {
                $doctrineQuery->andWhere('(a.deleted_at = a.deleted_at OR a.deleted_at IS NULL)');
            } else {
                // The DQL listener gets confused when you join multiple models with soft-delete, so be explicit:
                $doctrineQuery->andWhere('(a.deleted_at IS NULL)');
            }
        }
        
        // Implementers can tweak the selection query to filter out undesired records
        if ($table instanceof Fisma_Search_CustomIndexBuilder_Interface) {
            $doctrineQuery = $table->getSearchIndexQuery($doctrineQuery, $relationAliases);
        }

        // Add ACL constraints
        $aclTerms = $this->_getAclTerms($table);
        $aclFields = array();

        if ($aclTerms) {
            foreach ($aclTerms as $aclTerm) {
                if (!isset($aclFields[$aclTerm['field']])) {
                    $aclFields[$aclTerm['field']] = array();
                }

                $aclFields[$aclTerm['field']][] = $aclTerm['value'];
            }
        }

        foreach ($aclFields as $aclField => $aclValues) {
            if (isset($searchableFields[$aclField]['join'])) {
                $relationTable = $searchableFields[$aclField]['join']['relation'];
                $relationAlias = $relationAliases[$relationTable];
                $relationField = $searchableFields[$aclField]['join']['field'];

                $doctrineQuery->whereIn("$relationAlias.$relationField", $aclValues);
            } else {
                $relationAlias = $relationAliases[$type];

                $doctrineQuery->whereIn("$relationAlias.$aclField", $aclValues);
            }
        }

        // Add sorting and limit/offset
        $sortDefinition = $searchableFields[$sortColumn];
        $sortOrder = $sortDirection ? 'ASC' : 'DESC'; 
        
        if (isset($sortDefinition['join'])) {
            $relationTable = $sortDefinition['join']['relation'];
            $relationAlias = $relationAliases[$relationTable];
            $relationField = $sortDefinition['join']['field'];

            $doctrineQuery->orderBy("$relationAlias.$relationField $sortOrder");
        } else {
            $doctrineQuery->orderBy("a.$sortColumn $sortOrder");
        }

        $doctrineCount = $doctrineQuery->count();

        if (isset($rows) && isset($start)) {
            $doctrineQuery->limit($rows)
                          ->offset($start);
        }

        // Get result and convert to Fisma_Search_Result
        $doctrineResult = $doctrineQuery->execute();

        // Remove table alias prefixes (first two characters) from column name
        $tableData = array();
        $rootAlias = $relationAliases[$type];

        foreach ($doctrineResult as $row) {
            $rowData = array();

            // Some models don't explicitly include "id" but that needs to be included in the search results
            if (!isset($searchableFields['id'])) {
                $rowData['id'] = $row[$rootAlias . '_id'];
            }

            foreach ($searchableFields as $columnName => $columnDefinition) {

                if (isset($columnDefinition['join'])) {
                    $tableAlias = $relationAliases[$columnDefinition['join']['relation']];
                } else {
                    $tableAlias = $relationAliases[$type];
                }

                $doctrineColumnName = $tableAlias . '_' . $columnName;

                $columnValue = $this->_convertHtmlToIndexString($row[$doctrineColumnName]);

                $maxRowLength = $this->getMaxRowLength();

                if ($maxRowLength && strlen($columnValue) > $maxRowLength) {
                    $shortValue = substr($columnValue, 0, $maxRowLength);

                    // Trim after the last white space (so as not to break in the middle of a word)
                    $spacePosition = strrpos($shortValue, ' ');

                    if ($spacePosition) {
                        $shortValue = substr($shortValue, 0, $spacePosition);
                    }

                    $columnValue = $shortValue . '...';
                }

                $rowData[$columnName] = $columnValue;
            }

            if ($deleted && $table->hasColumn('deleted_at')) {
                $rowData['deleted_at'] = $row[$rootAlias . '_deleted_at'];
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
        $table = Doctrine::getTable($type);
        $searchableFields = $this->_getSearchableFields($type);
        
        // Check sorting parameters
        if (!$this->isColumnSortable($type, $sortColumn)) {
            throw new Fisma_Search_Exception("Not a sortable column: $sortColumn");
        }

        // Create a lucene query AND a doctrine query. Each index is used to search different fields.
        $zslTermQuery = new Zend_Search_Lucene_Search_Query_MultiTerm;

        $doctrineQuery = Doctrine_Query::create()
                         ->from("$type a")
                         ->setHydrationMode(Doctrine::HYDRATE_SCALAR);

         if (!isset($searchableFields['id'])) {
             $doctrineQuery->select('a.id');
         }

        $currentAlias = 'a';
        $relationAliases = array($type => $currentAlias);

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

                $doctrineQuery->addSelect("$relationAlias.$name $fieldName");
            } else {
                $doctrineQuery->addSelect("a.$fieldName");
            }
        }

        foreach ($criteria as $criterion) {

            // Make sure that the criteria field name is valid. This makes it safe to interpolate in queries later.
            if (!isset($searchableFields[$criterion->getField()])) {
                throw new Fisma_Search_Exception("Criteria field is not a valid search field.");
            }

            $searchDefinition = $searchableFields[$criterion->getField()];

            if (isset($searchDefinition['join'])) {
                $relationTable = $searchDefinition['join']['relation'];
                $relationAlias = $relationAliases[$relationTable];
                $relationField = $searchDefinition['join']['field'];
    
                $sqlFieldName = "$relationAlias.$relationField";
                $luceneFieldName = $criterion->getField();
            } else {
                $sqlFieldName = 'a.' . $criterion->getField();
                $luceneFieldName = $criterion->getField();
            }

            $operands = array_map('addslashes', $criterion->getOperands());

            $operator = $criterion->getOperator();

            switch ($operator) {
                case 'dateAfter':
                    $doctrineQuery->andWhere("$sqlFieldName >= ?", $operands[0]);
                    break;

                case 'dateBefore':
                    $doctrineQuery->andWhere("$sqlFieldName < ?", $operands[0]);
                    break;

                case 'dateBetween':
                    $doctrineQuery->andWhere("$sqlFieldName BETWEEN ? AND ?", array($operands[0], $operands[1]));
                    break;

                case 'dateDay':
                    $doctrineQuery->andWhere("$sqlFieldName = ?", $operands[0]);
                    break;

                case 'dateThisMonth':
                    $doctrineQuery->andWhere("YEAR($sqlFieldName) = YEAR(NOW())")
                                  ->andWhere("MONTH($sqlFieldName) = MONTH(NOW())");
                    break;

                case 'dateThisYear':
                    $doctrineQuery->andWhere("YEAR($sqlFieldName) = YEAR(NOW())");
                    break;

                case 'dateToday':
                    $doctrineQuery->andWhere("$sqlFieldName = DATE(NOW())");
                    break;

                case 'floatBetween':
                    if (!is_numeric($operands[0]) || !is_numeric($operands[1])) {
                        throw new Fisma_Search_Exception("Invalid operands to floatBetween criterion.");
                    }

                    $doctrineQuery->andWhere("$sqlFieldName BETWEEN ? AND ?", array($operands[0], $operands[1]));
                    break;

                case 'floatGreaterThan':
                    if (!is_numeric($operands[0])) {
                        throw new Fisma_Search_Exception("Invalid operands to floatGreaterThan criterion.");
                    }

                    $doctrineQuery->andWhere("$sqlFieldName >= ?", $operands[0]);
                    break;

                case 'floatLessThan':
                    if (!is_numeric($operands[0])) {
                        throw new Fisma_Search_Exception("Invalid operands to floatLessThan criterion.");
                    }

                    $doctrineQuery->andWhere("$sqlFieldName <= ?", $operands[0]);
                    break;

                case 'integerBetween':
                    $doctrineQuery->andWhere("$sqlFieldName BETWEEN ? AND ?", array($operands[0], $operands[1]));
                    break;

                case 'integerDoesNotEqual':
                    $doctrineQuery->andWhere("$sqlFieldName <> ?", $operands[0]);
                    break;

                case 'integerEquals':
                    $doctrineQuery->andWhere("$sqlFieldName = ?", $operands[0]);
                    break;

                case 'integerGreaterThan':
                    $doctrineQuery->andWhere("$sqlFieldName > ?", $operands[0]);
                    break;

                case 'integerLessThan':
                    $doctrineQuery->andWhere("$sqlFieldName < ?", $operands[0]);
                    break;

                case 'textContains':
                    $text = strtolower($operands[0]);
                    $zslTermQuery->addTerm(new Zend_Search_Lucene_Index_Term($text, $luceneFieldName), true);
                    break;

                case 'enumIs':
                    $doctrineQuery->andWhere("$sqlFieldName = ?", $operands[0]);
                    break;

                case 'textDoesNotContain':
                    $text = strtolower($operands[0]);
                    $zslTermQuery->addTerm(new Zend_Search_Lucene_Index_Term($text, $luceneFieldName), false);
                    break;

                case 'enumIsNot':
                    $doctrineQuery->andWhere("$sqlFieldName <> ?", $operands[0]);
                    break;

                // ZSL doesn't really have an exact match syntax... so use Doctrine instead
                case 'textExactMatch':
                    $doctrineQuery->andWhere("$sqlFieldName LIKE ?", $operands[0]);
                    break;

                case 'textNotExactMatch':
                    $doctrineQuery->andWhere("$sqlFieldName NOT LIKE ?", $operands[0]);
                    break;

                default:
                    // Fields can define custom criteria (that wouldn't match any of the above cases)
                    if (isset($searchableFields[$luceneFieldName]['extraCriteria'][$operator])) {
                        $callback = $searchableFields[$luceneFieldName]['extraCriteria'][$operator]['idProvider'];

                        $ids = call_user_func_array($callback, $operands);

                        if ($ids === false) {
                            throw new Fisma_Zend_Exception("Not able to call callback ($callback)");
                        }

                        $fieldName = $searchableFields[$luceneFieldName]['extraCriteria'][$operator]['idField'];
                        
                        $doctrineQuery->whereIn("a.$fieldName", $ids);
                    } else {
                        throw new Fisma_Search_Exception("Undefined search operator: " . $criterion->getOperator());
                    }
            }
        }

        // If the query contains only negative terms, then ZSL won't return any results. We need to detect this 
        // condition and invert the meaning of the query (i.e. turn it from pure negative to pure positive)
        $signs = $zslTermQuery->getSigns();
        $flipSigns = true;

        if ($signs) {
            foreach ($signs as $sign) {
                if ($sign !== false) {
                    $flipSigns = false;
                    break;
                }
            }
        } else {
            $flipSigns = false;
        }

        if ($flipSigns) {
            $terms = $zslTermQuery->getTerms();

            // Recreate the multiterm query with positive terms instead of negative terms
            $zslTermQuery = new Zend_Search_Lucene_Search_Query_MultiTerm;
            
            foreach ($terms as $term) {
                $zslTermQuery->addTerm($term, null);
            }
        }

        $index = $this->_openIndex($type);
        
        if (count($zslTermQuery->getQueryTerms())) {
            $zslResult = $index->find($zslTermQuery);            
        }

        if (isset($zslResult)) {
            // Create an array of matched Ids from lucene and add to doctrine query.
            $ids = array();
    
            foreach ($zslResult as $hit) {
                $ids[] = $hit->getDocument()->getField('id')->value;
            }

            if (count($ids)) {
                if ($flipSigns) {
                    $doctrineQuery->whereNotIn('a.id', $ids);                    
                } else {
                    $doctrineQuery->whereIn('a.id', $ids);                    
                }
            } else {
                // If no matches in Lucene, then we don't need to go any further.
                return new Fisma_Search_Result(0, 0, array());
            }      
        }

        // Handle soft delete records
        if ($table->hasColumn('deleted_at')) {
            $doctrineQuery->addSelect('a.deleted_at');

            if ($deleted) {
                $doctrineQuery->andWhere('(a.deleted_at = a.deleted_at OR a.deleted_at IS NULL)');
            } else {
                // The DQL listener gets confused when you join multiple models with soft-delete, so be explicit:
                $doctrineQuery->andWhere('(a.deleted_at IS NULL)');
            }
        }
        
        // Implementers can tweak the selection query to filter out undesired records
        if ($table instanceof Fisma_Search_CustomIndexBuilder_Interface) {
            $doctrineQuery = $table->getSearchIndexQuery($doctrineQuery, $relationAliases);
        }
        
        // Add ACL constraints
        $aclTerms = $this->_getAclTerms($table);
        $aclFields = array();

        if ($aclTerms) {
            foreach ($aclTerms as $aclTerm) {
                if (!isset($aclFields[$aclTerm['field']])) {
                    $aclFields[$aclTerm['field']] = array();
                }

                $aclFields[$aclTerm['field']][] = $aclTerm['value'];
            }
        }

        foreach ($aclFields as $aclField => $aclValues) {
            $aclFieldDefinition = $searchableFields[$aclField];

            if (isset($aclFieldDefinition['join'])) {
                $relationTable = $aclFieldDefinition['join']['relation'];
                $relationAlias = $relationAliases[$relationTable];
                $relationField = $aclFieldDefinition['join']['field'];
    
                $doctrineQuery->whereIn("$relationAlias.$relationField", $aclValues);
            } else {
                $doctrineQuery->whereIn("a.$aclField", $aclValues);
            }
        }

        // Add sorting and limit/offset
        $sortDefinition = $searchableFields[$sortColumn];
        $sortOrder = $sortDirection ? 'ASC' : 'DESC'; 
        
        if (isset($sortDefinition['join'])) {
            $relationTable = $sortDefinition['join']['relation'];
            $relationAlias = $relationAliases[$relationTable];
            $relationField = $sortDefinition['join']['field'];

            $doctrineQuery->orderBy("$relationAlias.$relationField $sortOrder");
        } else {
            $doctrineQuery->orderBy("a.$sortColumn $sortOrder");
        }

        $doctrineCount = $doctrineQuery->count();

        if (isset($rows) && isset($start)) {
            $doctrineQuery->limit($rows)
                          ->offset($start);
        }

        // Get result and convert to Fisma_Search_Result
        $doctrineResult = $doctrineQuery->execute();

        // Remove table alias prefixes (first two characters) from column name
        $tableData = array();
        $rootAlias = $relationAliases[$type];

        foreach ($doctrineResult as $row) {
            $rowData = array();
            
            // Some models don't explicitly include "id" but that needs to be included in the search results
            if (!isset($searchableFields['id'])) {
                $rowData['id'] = $row[$rootAlias . '_id'];
            }

            foreach ($searchableFields as $columnName => $columnDefinition) {

                if (isset($columnDefinition['join'])) {
                    $tableAlias = $relationAliases[$columnDefinition['join']['relation']];
                } else {
                    $tableAlias = $relationAliases[$type];
                }

                $doctrineColumnName = $tableAlias . '_' . $columnName;

                $columnValue = $this->_convertHtmlToIndexString($row[$doctrineColumnName]);

                $maxRowLength = $this->getMaxRowLength();

                if ($maxRowLength && strlen($columnValue) > $maxRowLength) {
                    $shortValue = substr($columnValue, 0, $maxRowLength);

                    // Trim after the last white space (so as not to break in the middle of a word)
                    $spacePosition = strrpos($shortValue, ' ');

                    if ($spacePosition) {
                        $shortValue = substr($shortValue, 0, $spacePosition);
                    }

                    $columnValue = $shortValue . '...';
                }

                $rowData[$columnName] = $columnValue;
            }

            if ($deleted && $table->hasColumn('deleted_at')) {
                $rowData['deleted_at'] = $row[$rootAlias . '_deleted_at'];
            }

            $tableData[] = $rowData;
        }

        return new Fisma_Search_Result($doctrineCount, count($doctrineResult), $tableData);    
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
