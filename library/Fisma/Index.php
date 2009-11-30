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
 * <http://www.gnu.org/licenses/>.
 */

/**
 * A wrapper for Zend_Search_Lucene which adds some convenience functions to apply Lucene consistently
 * throughout OpenFISMA.
 * 
 * @author     Mark E. Haase <mhaase@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2009 (http://www.endeavorsystems.com)
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Fisma
 * @subpackage Fisma_Index
 * @version    $Id$
 */
class Fisma_Index
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
     * The maximum number of terms which can be highlighted.
     */
    const MAX_HIGHLIGHT_WORDS = 25;
    
    /**
     * The Zend_Search_Lucene instance that is being wrapped
     * 
     * @var Zend_Search_Lucene
     */
    private $_lucene;
    
    /**
     * A reference to the most recently executed query
     * 
     * @var Zend_Search_Lucene_Search_Query
     */
    private $_lastQuery;

    /**
     * This stores the path to the index. 
     * 
     * This seems to be necessary due to a bug in Zend Search Lucene where the directory sometimes gets closed 
     * prematurely when deleting a document. In that case, we need to reopen it.
     */
    private $_indexPath;

    /**
     * Open the index for the specified class, creating the index first if necessary.
     * 
     * This also tunes some of the configuration parameters.
     * 
     * @param $class string The name of the class which this record should go in
     */
    public function __construct($class) 
    {        
        try {
            // Set privileges on index files to be readable only by owner and group
            Zend_Search_Lucene_Storage_Directory_Filesystem::setDefaultFilePermissions(0660);

            // Create a new lucene object
            $this->_indexPath = Fisma::getPath('index') . '/' . $class;
            $createIndex = !is_dir($this->_indexPath);
            $this->_lucene = new Zend_Search_Lucene($this->_indexPath, $createIndex);
            if ($createIndex) {
                // Set permissions to that only owner and group can read or list index files
                chmod($this->_indexPath, 0770);
            }

            // Set optimization parameters. This is tuned for small batch, interactive indexing. It will not be very
            // efficient for large batch indexing.
            $this->_lucene->setMaxBufferedDocs(self::MAX_BUFFERED_DOCS);
            $this->_lucene->setMaxMergeDocs(self::MAX_MERGE_DOCS);
            $this->_lucene->setMergeFactor(self::MERGE_FACTOR);
        } catch (Zend_Search_Lucene_Exception $e) {
            /**
             * @see http://jira.openfisma.org/browse/OFJ-93
             */
            if (Fisma::debug()) {
                throw $e;
            }
        }
    }

    /**
     * Add or update a record in the index
     *
     * @param Doctrine_Record
     */
    public function update(Doctrine_Record $record)
    {
        try {
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

            // Delete this record if it already exists in the database
            $this->delete($record);    
        
            // Add the new document to the index. Sometimes Lucene will lose its reference to the directory (bug?).
            // If it does, then we need to recreate it.
            $currentDir = $this->_lucene->getDirectory();
            if (is_null($currentDir)) {
                $this->_lucene = new Zend_Search_Lucene($this->_indexPath);
            }
            $this->_lucene->addDocument($luceneDoc);
        } catch (Zend_Search_Lucene_Exception $e) {
            /**
             * @see http://jira.openfisma.org/browse/OFJ-93
             */
            if (Fisma::debug()) {
                throw $e;
            }
        }
    }
    
    /**
     * Executes a Lucene query and returns an array of the matched IDs
     * 
     * @param string $queryString
     * @return array
     */
    public function findIds($queryString)
    {
        $ids = array();
    
        $this->_lastQuery = Zend_Search_Lucene_Search_QueryParser::parse($queryString);
        // Call time pass by reference is deprecated...but it appears to be the only way to get 
        // ZSL to return the rewritten query
        $results = $this->_lucene->find(&$this->_lastQuery);
        foreach ($results as $result) {
            $ids[] = $result->getDocument()->id;
        }

        return $ids;
    }
    
    /**
     * Delete a record from the index
     * 
     * If the record hasn't been indexed yet, then nothing happens
     */
    public function delete(Doctrine_Record $record)
    {
        try {
            $luceneTerm = new Zend_Search_Lucene_Index_Term($record->id, 'id');
            $luceneQuery = new Zend_Search_Lucene_Search_Query_Term($luceneTerm);
            $existingDocuments = $this->_lucene->find($luceneQuery);        
            foreach ($existingDocuments as $document) {
                $this->_lucene->delete($document->id);
            }
        } catch (Zend_Search_Lucene_Exception $e) {
            /**
             * @see http://jira.openfisma.org/browse/OFJ-93
             */
            if (Fisma::debug()) {
                throw $e;
            }
        }
    }

    /**
     * Get an array of the words which should be highlighted (Based on the last execute query)
     */
    public function getHighlightWords()
    {
        try {
            if (!isset($this->_lastQuery)) {
                throw new Fisma_Index_Exception("Cannot call 'getHighlightWords()' until after a query"
                                              . " is executed.");
            }
            
            return $this->_extractHighlightWordsFromQuery($this->_lastQuery);
        } catch (Zend_Search_Lucene_Exception $e) {
            /**
             * @see http://jira.openfisma.org/browse/OFJ-93
             */
            if (Fisma::debug()) {
                throw $e;
            }
        }
    }

    /**
     * Defragment the index
     */
    public function optimize()
    {
        $this->_lucene->optimize();
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
                if (@$columnDefinition['extra']['purify'] == 'html') {
                    $indexData = strip_tags($record->$columnName);
                } else {
                    $indexData = $record->$columnName;
                }
                
                // Create a Lucene field with a type that corresponds to this column
                $fieldName = $this->_getIndexFieldName($columnName, $columnDefinition);
                $field = $this->_getIndexField($columnDefinition['extra']['searchIndex'], $fieldName, $indexData);
                $document->addField($field);
            }
        }
    }
    
    /**
     * Index specified fields in related records.
     * 
     * Notice that the document is passed by reference and will be modified by this method
     * 
     * This method works by requiring a somewhat ugly hack. The class being indexed has to declare a public
     * associative array called $relationIndex (see the Asset.php model for an example) if the author desires
     * any fields in the related record to be indexed.
     * 
     * If this array is not declared, then record relation indexing will be silently skipped
     * 
     * This method might be slow if it has to fetch many relations. One way to speed this up would be to pre-fetch
     * a bunch of objects with their relations before you index them.
     * 
     * @todo This is limited because if a related record changes, then the index does not get recreated automatically
     * 
     * @param Zend_Search_Lucene_Document $document
     * @param Doctrine_Record $record
     */
    private function _indexRecordRelations(Zend_Search_Lucene_Document &$document, Doctrine_Record $record)
    {
        if (isset($record->relationIndex)) {
            // Loop through each field of each model
            foreach ($record->relationIndex as $foreignModel => $foreignFields) {
                foreach ($foreignFields as $foreignFieldName => $foreignFieldIndex) {
                    if (!is_array($foreignFieldIndex)) {
                        throw new Fisma_Index_Exception("The relation index is malformed");
                    }
                    
                    // Get data from this foreign field
                    $foreignTable = $record->$foreignModel->getTable();
                    $foreignColumnDef = $foreignTable->getColumnDefinition($foreignFieldName);

                    // If the foreign field is set in the record, then use the value defined in the record.
                    // If the field isn't set in the record, then we pull the value from the foreign model
                    if (isset($record->$foreignFieldName)) {
                        $indexData = $record->$foreignFieldName;
                    } else {
                        $indexData = $record->$foreignModel->$foreignFieldName;
                    }

                    // If the field is marked as HTML, then strip the HTML from the data (presumably, this
                    // has already been filtered by HtmlPurifier)
                    if (@$foreignColumnDef['extra']['purify'] == 'html') {
                        $indexData = strip_tags($indexData);
                    }

                    // The naming convention for the foreign field in lucene: for example, asset will have
                    // an indexed field called 'product_version'. This can be overridden by defining the 'alias'
                    // attribute
                    if (isset($foreignFieldIndex['alias'])) {
                        $fieldName = $foreignFieldIndex['alias'];
                    } else {
                        $fieldName = strtolower("{$foreignModel}_{$foreignFieldName}");
                    }

                    // Add this field to the document
                    $field = $this->_getIndexField($foreignFieldIndex['type'], $fieldName, $indexData);                
                    $document->addField($field);
                }
            }
        }
    }
    
    /**
     * Returns a Lucene field object based on the specified type, field name, and data
     * 
     * @param 
     */
    private function _getIndexField($type, $fieldName, $indexData) 
    {
        switch ($type) {
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
                throw new Fisma_Index_Exception("Invalid index type: $type");
        }
        
        return $field;
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
    
    /**
     * Given a Lucene query, return an array of the search terms which should be highlighted
     * 
     * Some queries, such as '*', would match so many terms that highlighting would become absurd.
     * Therefore, a maximum limit is imposed.
     * 
     * @param Zend_Search_Lucene_Search_Query $query
     * @return array
     */
    private function _extractHighlightWordsFromQuery($query)
    {
        $highlightWords = array();
        
        // Iterate over the query terms and extract the part we are interested in
        $terms = $query->getQueryTerms();

        foreach ($terms as $term) {
            $highlightWords[] = $term->text;
            if (count($highlightWords) > self::MAX_HIGHLIGHT_WORDS) {
                break;
            }
        }
        
        return $highlightWords;
    }
}
