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
 * Search engine backend based on the PECL solr extension
 * 
 * @author     Mark E. Haase <mhaase@endeavorystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2010 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Fisma
 * @subpackage Fisma_Search
 */
class Fisma_Search_Backend_Solr extends Fisma_Search_Backend_Abstract
{
    /**
     * Client object is used for communicating with Solr server
     * 
     * @var SolrClient
     */
    private $_client;
    
    /**
     * Constructor
     * 
     * @param string $hostname Hostname or IP address where Solr's servlet container is running
     * @param int $port The port that Solr's servlet container is listening on
     * @param string $path The path within the servlet container that Solr is running on
     */
    public function __construct($hostname, $port, $path)
    {
        $clientConfig = array(
            'hostname' => $hostname,
            'port' => $port,
            'path' => $path
        );
        
        $this->_client = new SolrClient($clientConfig);
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
        $this->_client->deleteByQuery('documentType:' . $type);

        $this->_client->commit();
    }
    
    /**
     * Delete the specified object from the index
     * 
     * The $object needs to belong to a table which implements Fisma_Search_Searchable
     * 
     * @param Fisma_Doctrine_Record $object
     */
    public function deleteObject($object)
    {
        $documentId = get_class($object) . $object->id;
        
        $this->_client->deleteById($documentId);

        $this->_client->commit();
        
    }

    /**
     * Index a Doctrine collection of objects
     * 
     * @param Doctrine_Collection $collection
     */
    public function indexCollection(Doctrine_Collection $collection)
    {
        $documents = $this->_convertCollectionToDocumentArray($collection);
        
        $this->_client->addDocuments($documents);

        $this->_client->commit();
    }
    
    /**
     * Add the specified object to the search engine index
     * 
     * The client library will overwrite any document with a matching documentId automatically
     * 
     * @param Fisma_Doctrine_Record $object
     */
    public function indexObject(Fisma_Doctrine_Record $object)
    {
        $document = $this->_convertObjectToDocument($object);
        
        $this->_client->addDocument($document);
        
        $this->_client->commit();
    }

    /**
     * Returns true if the specified column is sortable
     * 
     * This is defined in the search abstraction layer since ultimately the sorting capability is determined by the
     * search engine implementation.
     * 
     * In Solr, sorting is only available for stored, un-analyzed, single-valued fields.
     * 
     * @param array $columnDefinition A doctrine column definition
     * @return bool
     */
    public function isColumnSortable($columnDefinition)
    {
        $suffix = $this->_getSuffixForColumn($columnDefinition);
        
        switch ($suffix) {
            case 'text':
                $sortable = false;
                break;
            
            // Following conditions all fall through
            case 'str':
            case 'enum':
            case 'date':
            case 'int':
                $sortable = true;
                break;
            
            default:
                throw new Fisma_Search_Exception("Sortability not defined for suffix ($suffix)");
                break;
        }

        return $sortable;
    }

    /**
     * Optimize the index (degfragments the index)
     */
    public function optimizeIndex()
    {
        $this->_client->optimize();
    }

    /**
     * Simple search: search all fields for the specified keyword
     * 
     * If keyword is null, then this is just a listing of all documents of a specific type
     * 
     * @param string $type Name of model index to search
     * @param string $keyword
     * @param string $sortColumn Name of column to sort on
     * @param boolean $sortDirection True for ascending sort, false for descending
     * @param int $start The offset within the result set to begin returning documents from
     * @param int $rows The number of documents to return
     * @return Fisma_Search_Result
     */
    public function searchByKeyword($type, $keyword, $sortColumn, $sortDirection, $start, $rows)
    {
        $query = new SolrQuery;

        // @todo fix this awkwardness
        // Getting the column name is a little awkward:
        $table = Doctrine::getTable($type);
        $sortColumnDefinition = $table->getColumnDefinition($table->getColumnName($sortColumn));
        $sortColumnParam = $this->escape($sortColumn) . '_' . $this->_getSuffixForColumn($sortColumnDefinition);

        $sortDirectionParam = $sortDirection ? SolrQuery::ORDER_ASC : SolrQuery::ORDER_DESC;

        // Add required fields to query. The rest of the fields are added below.
        $query->addField('id')
              ->addField('documentId')
              ->addSortField($sortColumnParam, $sortDirectionParam)
              ->setStart($start)
              ->setRows($rows);

        if (is_null($keyword)) {
            // Without keywords, this is just a listing of all documents of a specific type
            $query->setQuery('documentType:' . $type);
        } else {
            // For keyword searches, use the filter query (for efficient caching) and enable highlighting
            $query->setHighlight(true)
                  ->setHighlightSimplePre('***')
                  ->setHighlightSimplePost('***')
                  ->addFilterQuery('documentType:' . $type);
                  
            // Tokenize keyword on spaces and escape all tokens
            $keywordTokens = split(' ', $keyword);
            $keywordTokens = array_filter($keywordTokens);
            $keywordTokens = array_map(array($this, 'escape'), $keywordTokens);
        }

        // Enumerate all fields so they can be included in search results
        $searchableFields = Doctrine::getTable($type)->getSearchableFields();

        $searchTerms = array();

        $table = Doctrine::getTable($type);

        foreach (array_keys($searchableFields) as $searchableField) {
            
            // Some twiddling to convert Doctrine's field names to Solr's field names
            $columnDefinition = $table->getColumnDefinition($table->getColumnName($searchableField));
            $documentFieldName = $searchableField . '_' . $this->_getSuffixForColumn($columnDefinition);

            $query->addField($documentFieldName);
            
            if (!is_null($keyword)) {
                // Add highlighting only if there is a search term.
                $query->addHighlightField($documentFieldName);

                // Add query parts for each search term
                foreach ($keywordTokens as $keywordToken) {
                    $searchTerms[] = $documentFieldName . ':' . $keywordToken;                
                }
            }            
        }

        if (!is_null($keyword)) {
            // If there are search terms, then combine them with the logical OR operator
            $query->setQuery(implode(' OR ', $searchTerms));
        }
    
        $response = $this->_client->query($query)->getResponse(); 
        
        return $this->_convertSolrResultToStandardResult($response);
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
     * @return Fisma_Search_Result Rectangular array of search results
     */
    public function searchByCriteria($type, Fisma_Search_Criteria $criteria, $sortColumn, $sortDirection, $start, $rows)
    {
        $query = new SolrQuery;

        // @todo fix this awkwardness
        // Getting the column name is a little awkward:
        $table = Doctrine::getTable($type);
        $sortColumnDefinition = $table->getColumnDefinition($table->getColumnName($sortColumn));
        $sortColumnParam = $this->escape($sortColumn) . '_' . $this->_getSuffixForColumn($sortColumnDefinition);

        $sortDirectionParam = $sortDirection ? SolrQuery::ORDER_ASC : SolrQuery::ORDER_DESC;

        // Add required fields to query. The rest of the fields are added below.
        $query->addField('id')
              ->addField('documentId')
              ->addSortField($sortColumnParam, $sortDirectionParam)
              ->setStart($start)
              ->setRows($rows);

        // Use the filter query (for efficient caching) and enable highlighting
        $query->setHighlight(true)
              ->setHighlightSimplePre('***')
              ->setHighlightSimplePost('***')
              ->setHighlightRequireFieldMatch(true)
              ->addFilterQuery('documentType:' . $type);

        // Enumerate all fields so they can be included in search results
        $searchableFields = Doctrine::getTable($type)->getSearchableFields();

        $table = Doctrine::getTable($type);

        foreach (array_keys($searchableFields) as $searchableField) {
            
            // Some twiddling to convert Doctrine's field names to Solr's field names
            $columnDefinition = $table->getColumnDefinition($table->getColumnName($searchableField));
            $documentFieldName = $searchableField . '_' . $this->_getSuffixForColumn($columnDefinition);

            $query->addField($documentFieldName);
            $query->addHighlightField($documentFieldName);
        }

        $searchTerms = array();

        foreach ($criteria as $criterion) {
            // Some twiddling to convert Doctrine's field names to Solr's field names
            $columnDefinition = $table->getColumnDefinition($table->getColumnName($criterion->fieldName));
            $rawFieldName = $criterion->fieldName . '_' . $this->_getSuffixForColumn($columnDefinition);

            $fieldName = $this->escape($rawFieldName);
            $operand = $this->escape($criterion->operand);

            switch ($criterion->operator) {
                case 'contains':
                    $searchTerms[] = $fieldName . ':' . $operand;
                    break;
                
                default:
                    throw new Fisma_Search_Exception("Undefined search operator: " . $criterion->operator);
            }
        }

        $query->setQuery(implode($searchTerms, ' OR '));

        $response = $this->_client->query($query)->getResponse(); 

        return $this->_convertSolrResultToStandardResult($response);
    }

    /**
     * Validate that PECL extension is installed and SOLR server responds to a Solr ping request (not an ICMP)
     * 
     * @return mixed Return TRUE if configuration is valid, or a string error message otherwise
     */
    public function validateConfiguration()
    {
        if (!function_exists('solr_get_version')) {
            return "PECL Solr extension is not installed";
        }

        try {
            $this->_client->ping();
        } catch (SolrClientException $e) {
            return 'Not able to reach Solr server: ' . $e->getMessage();
        }
        
        return true;
    }

    /**
     * Convert a Doctrine_Collection object into an array of indexable Solr documents
     * 
     * @param Doctrine_Collection $collection
     * @return array Array of SolrInputDocument
     */
    private function _convertCollectionToDocumentArray(Doctrine_Collection $collection)
    {
        $documents = array();

        foreach ($collection as $object) {
            $documents[] = $this->_convertObjectToDocument($object);
        }

        return $documents;
    }
    
    /**
     * Convert a Fisma_Doctrine_Record object into an indexable Solr document
     * 
     * The object's table must also implement Fisma_Search_Searchable so that this method can get its search metadata.
     * 
     * @param Fisma_Doctrine_Record $object
     * @return SolrInputDocument
     */
    private function _convertObjectToDocument(Fisma_Doctrine_Record $object)
    {
        if (!($object->getTable() instanceof Fisma_Search_Searchable)) {
            $message = 'Objects which are to be indexed must have a table that implements'
                     . ' the Fisma_Search_Searchable interface';

            throw new Fisma_Zend_Exception($message);
        }
        
        $type = get_class($object);

        $document = new SolrInputDocument;

        // All documents have the following three fields
        if (isset($object->id)) {
            $document->addField('documentId', $type . $object->id);
            $document->addField('documentType', $type);
            $document->addField('id', $object->id);
        } else {
            throw new Fisma_Search_Exception("Cannot index object type ($type) because it does not have an id field.");
        }

        // Iterate over the model's columns and see which ones need to be indexed
        $table = Doctrine::getTable($type);
        $searchableFields = $object->getTable()->getSearchableFields();

        foreach (array_keys($searchableFields) as $doctrineFieldName) {

            if ('documentId' == $doctrineFieldName) {
                throw new Fisma_Search_Exception("Model columns cannot be named documentId");
            }
            
            $doctrineColumnDefinition = $table->getColumnDefinition($table->getColumnName($doctrineFieldName));

            $typeSuffix = $this->_getSuffixForColumn($doctrineColumnDefinition);

            $documentFieldName = $doctrineFieldName . '_' . $typeSuffix;

            $rawValue = $object[$table->getFieldName($doctrineFieldName)];
            $documentFieldValue = $this->_getValueForColumn($rawValue, $doctrineColumnDefinition);
                
            $document->addField($documentFieldName, $documentFieldValue);
        }

        return $document;
    }
    
    /**
     * Converts a Solr query result into the system's standard result format
     * 
     * Solr does some weird stuff with object storage, so this method is a little hard to understand. var_dump'ing
     * each variable will help to sort through the structure for debugging purposes.
     * 
     * @param SolrResult $solrResult
     * @return Fisma_Search_Result
     */
    public function _convertSolrResultToStandardResult($solrResult)
    {
        $numberFound = count($solrResult->response->docs);
        $numberReturned = $solrResult->response->numFound;
        $highlighting = (array)$solrResult->highlighting;
        
        $tableData = array();

        // Construct initial table data from documents part of the response
        foreach ($solrResult->response->docs as $document) {

            $row = array();

            // Solr has a weird format. Each field is an array with length 1, so we take index 0
            foreach ($document as $columnName => $columnValue) {
                $newColumnName = $this->_removeSuffixFromColumnName($columnName);
                
                $row[$newColumnName] = $columnValue[0];
            }

            $documentId = $row['documentId'];

            $tableData[$documentId] = $row;
        }
        
        // Now merge any highlighted fields into the table data
        foreach ($highlighting as $documentId => $row) {
            foreach ($row as $fieldName => $fieldValue) {
                $newFieldName = $this->_removeSuffixFromColumnName($fieldName);

                // Solr stores each field as an array with length 1, so we take index 0
                $tableData[$documentId][$newFieldName] = $fieldValue[0];
            }
        }
        
        // Remove the documentId from each field
        foreach ($tableData as &$document) {
            unset($document['documentId']);
        }
        
        // Discard the row IDs
        $tableData = array_values($tableData);

        return new Fisma_Search_Result($numberReturned, $numberFound, $tableData);
    }

    /**
     * Return the suffix for the field name based on the column's definition
     * 
     * Solr distinguishes field types based on the field names' suffixes
     * 
     * @param array $columnDefinition
     */
    private function _getSuffixForColumn($columnDefinition)
    {
        if ('string' == $columnDefinition['type']) {

            /* 
             * The 'str' suffix results in an untokenized value that is sortable. By default, this is applied to all
             * length-restricted string fields. Length-restricted fields will get a 'text' suffix which is tokenized 
             * but NOT SORTABLE. Solr cannot sort a tokenized value.
             */
            if (is_null($columnDefinition['length'])) {
                $suffix = 'text';
            } else {
                $suffix = 'str';
            }

        } elseif ('enum' == $columnDefinition['type']) {
            
            $suffix = 'enum';
            
        } elseif ('date' == $columnDefinition['type'] ||
                  'datetime' == $columnDefinition['type'] ||
                  'timestamp' == $columnDefinition['type']) {

            $suffix = 'date';

        } elseif ('integer' == $columnDefinition['type']) {
            
            $suffix = 'int';
            
        } else {
            throw new Fisma_Search_Exception("No suffix defined for column type ({$columnDefinition['type']})");
        }
        
        return $suffix;
    }
    
    /**
     * Remove the type suffix (e.g. _text, _date, etc.) from a columnName
     * 
     * @param string $columnName
     * @return string
     */
    private function _removeSuffixFromColumnName($columnName)
    {
        $suffixPosition = strpos($columnName, '_');

        if ($suffixPosition) {
            return substr($columnName, 0, strpos($columnName, '_'));
        } else {
            return $columnName;
        }
    }
    
    /**
     * Create the field value for an object based on its type and other metadata
     * 
     * @param mixed $value
     * @param array $columnDefinition
     * @return mixed
     */
    private function _getValueForColumn($rawValue, $columnDefinition)
    {

        if (isset($columnDefinition['extra']['purify']) && 'html' == $columnDefinition['extra']['purify']) {
            // HTML fields need HTML stripped            
            $value = $this->_convertHtmlToIndexString($rawValue);
        } elseif ('date' == $columnDefinition['type'] || 
                  'datetime' == $columnDefinition['type'] || 
                  'timestamp' == $columnDefinition['type']) {

            // Date fields need to be converted to UTC
            $tempDate = new Zend_Date($rawValue, 'YYYY-MM-dd HH:mm:ss');
                        
            $value = $tempDate->toString('YYYY-MM-ddTHH:mm:ss') . 'Z';
        } else {
            // By default, just index the raw value
            $value = $rawValue;
        }        
        
        return $value;
    }
}
