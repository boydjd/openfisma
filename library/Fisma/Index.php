<?php
/**
 * Copyright (c) 2008 Endeavor Systems, Inc.
 *
 * This file is part of OpenFISMA.
 *
 * OpenFISMA is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * OpenFISMA is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with OpenFISMA.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author    Mark E. Haase <mhaase@endeavorsystems.com>
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://openfisma.org/content/license
 * @version   $Id$
 */

/**
 * A wrapper for Zend_Search_Lucene which adds some convenience functions to apply Lucene consistently
 * throughout OpenFISMA.
 * 
 * @package Fisma
 */
class Fisma_Index extends Zend_Search_Lucene
{
    /**
     * Zend_Search_Lucene optimization tuning
     * @see http://framework.zend.com/manual/en/zend.search.lucene.index-creation.html
     * @var int
     */
    const MAX_BUFFERED_DOCS = 10;
    
    /**
     * Zend_Search_Lucene optimization tuning
     * @see http://framework.zend.com/manual/en/zend.search.lucene.index-creation.html
     * @var int
     */
    const MAX_MERGE_DOCS = 100;

    /**
     * Zend_Search_Lucene optimization tuning
     * @see http://framework.zend.com/manual/en/zend.search.lucene.index-creation.html
     * @var int
     */
    const MERGE_FACTOR = 5;
    
    /**
     * Open the index for the specified class, creating the index first if necessary.
     * 
     * This also tunes some of the configuration parameters.
     * 
     * @param $class string The name of the class which this record should go in
     */
    public function __construct($class) 
    {
        // Set privileges on index files to be readable only by owner and group
        Zend_Search_Lucene_Storage_Directory_Filesystem::setDefaultFilePermissions(0660);
        
        // Call the parent constructor
        $indexPath = Fisma::getPath('index') . '/' . $class;
        $createIndex = !is_dir($indexPath);
        parent::__construct($indexPath, $createIndex);
        if ($createIndex) {
            // Set permissions to that only owner and group can read or list index files
            chmod($indexPath, 0770);
        }
        
        // Set optimization parameters. This is tuned for small batch, interactive indexing. It will not be very
        // efficient for large batch indexing.
        $this->setMaxBufferedDocs(self::MAX_BUFFERED_DOCS);
        $this->setMaxMergeDocs(self::MAX_MERGE_DOCS);
        $this->setMergeFactor(self::MERGE_FACTOR);
    }

    /**
     * Add or update a record in the index
     *
     * @param Doctrine_Record
     */
    public function update(Doctrine_Record $record)
    {
        $luceneDoc = new Zend_Search_Lucene_Document();
        
        // Each Lucene document must have a unique ID field. If the record does not contain an id field,
        // then throw an exception.
        if (!isset($record->id)) {
            throw new Fisma_Index_Exception('Cannot index class (' 
                                           . get_class($record) 
                                           . ') because it does not contain an "id" field');
        } else {
            $luceneDoc->addField(Zend_Search_Lucene_Field::Keyword('id', $record->id));
        }

        // The indexer will pick up columns and relations that are tagged as being indexable.
        $this->_indexRecordColumns($luceneDoc, $record);
        $this->_indexRecordRelations($luceneDoc, $record);
        
        // Check whether this record already exists in the index. If so, it must be removed first.
        $luceneTerm = new Zend_Search_Lucene_Index_Term($record->id, 'id');
        $luceneQuery = new Zend_Search_Lucene_Search_Query_Term($luceneTerm);
        $existingDocuments = $this->find($luceneQuery);
        foreach ($existingDocuments as $document) {
            $this->delete($document->id);
        }
        
        // Add the new document
        $this->addDocument($luceneDoc);
    }
    
    /**
     * Executes a Lucene query and returns an array of the matched IDs
     * 
     * @param string $query
     * @return array
     */
    public function findIds($query)
    {
        $ids = array();
        
        $results = $this->find($query);
        foreach ($results as $result) {
            $ids[] = $result->getDocument()->id;
        }
        
        return $ids;
    }
    
    /**
     * Add any record columns with a 'searchIndex' attribute to the lucene document
     * 
     * Notice that the document is passed by reference and will be modified by this method
     * 
     * @param Zend_Search_Lucene_Document $document
     * @param Doctrine_Record $record
     */
    private function _indexRecordColumns(Zend_Search_Lucene_Document &$document, Doctrine_Record $record)
    {    
        // Enumerate the columns on this record and see which ones require indexing
        $table = $record->getTable();
        $columns = $table->getColumns();
        foreach ($columns as $physicalColumnName => $columnDefinition) {
            // getColumns() returns physical column names. use getFieldName to get the properly camel-cased
            // field name instead.
            $columnName = $table->getFieldName($physicalColumnName);
            if (isset($columnDefinition['extra']['searchIndex'])) {                                
                // If this field is also marked as HTML, then strip tags before indexing it. Otherwise, index it as is.
                if ($columnDefinition['extra']['purify']) {
                    $indexData = strip_tags($record->$columnName);
                } else {
                    $indexData = $record->$columnName;
                }
                
                // Create a Lucene field with a type that corresponds to this column
                $fieldName = $this->_getIndexFieldName($columnName, $columnDefintion);
                switch ($columnDefinition['extra']['searchIndex']) {
                    case 'keyword':
                        $field = Zend_Search_Lucene_Field::Keyword($fieldName, $indexData);
                        break;
                    case 'unindexed':
                        $field = Zend_Search_Lucene_Field::UnIndexed($fieldName, $indexData);
                        break;
                    case 'binary':
                        $field = Zend_Search_Lucene_Field::Binary($fieldName, $indexData);
                        break;
                    case 'text':
                        $field = Zend_Search_Lucene_Field::Text($fieldName, $indexData);
                        break;
                    case 'unstored':
                        $field = Zend_Search_Lucene_Field::UnStored($fieldName, $indexData);
                        break;
                    default:
                        throw new Fisma_Index_Exception("Invalid index type: {$columnDefinition['extra']['searchIndex']}");
                }
                $document->addField($field);
            }
        }
    }
    
    /**
     * Add any record relations with a 'searchIndex' attribute to the lucene document
     * 
     * Notice that the document is passed by reference and will be modified by this method
     * 
     * @param Zend_Search_Lucene_Document $document
     * @param Doctrine_Record $record
     */
    private function _indexRecordRelations(Zend_Search_Lucene_Document &$document, Doctrine_Record $record)
    {
        $relations = $record->getTable()->getRelations();
        foreach ($relations as $relationName => $relationDefinition) {
            ;
        }
    }
    
    /**
     * Determine what name the index will use to represent the specified column
     * 
     * The name defaults to the column name, but it can be overridden by the attribute 'searchAlias'.
     * This can be used to make it easier for users to write Lucene query strings
     * 
     * @param string $columnName
     * @param array $columnDefinition
     */
    private function _getIndexFieldName($columnName, $columnDefinition)
    {
        $fieldName = isset($columnDefinition['extra']['searchAlias']) 
                   ? $columnDefinition['extra']['searchAlias']
                   : $columnName;
        
        return $fieldName;
    }
}
