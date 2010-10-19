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
                echo "dELETE $index\n";
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
        
        // Remove contents of index
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
        
        $index->delete($object->id);
        
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

        $document = new Zend_Search_Lucene_Document();
        
        // Always add an ID field
        if (!isset($object['id'])) {
            $message = "Cannot index objects that do not have an ID field. (Type is: $type)";

            throw new Fisma_Search_Exception($message);
        }
        
        $table = Doctrine::getTable($type);
        
        foreach ($searchableFields as $name => $field) {
            $rawValue = $this->_getRawValueForField($table, $object, $name, $field);
            
            $field = Zend_Search_Lucene_Field::Text($name, $rawValue, 'iso-8859-1');
            
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
     * @param string $type The class containing the column
     * @param string $columnName
     * @return bool
     */
    public function isColumnSortable($type, $columnName)
    {
        throw new Exception("NOT IMPLEMENTED");
    }

    /**
     * Optimize the index (degfragments the index)
     */
    public function optimizeIndex()
    {
        throw new Exception("NOT IMPLEMENTED");
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
        throw new Exception("NOT IMPLEMENTED");
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
