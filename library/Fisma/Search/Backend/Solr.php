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
        $deleteTypeQuery = new SolrQuery('documentType\:test');

        $this->_client->deleteByQuery($deleteTypeQuery);

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
    }
    
    /**
     * Add the specified object to the search engine index
     * 
     * @param Fisma_Doctrine_Record $object
     */
    public function indexObject(Fisma_Doctrine_Record $object)
    {
        $document = $this->_convertObjectToDocument($object);
        
        $this->_client->addDocument($document);
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
     * @param Fisma_Doctrine_Record $object
     * @return SolrInputDocument
     */
    private function _convertObjectToDocument(Fisma_Doctrine_Record $object)
    {
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
        $columns = $table->getColumns();

        foreach ($columns as $columnName => $columnDefinition) {
            if (isset($columnDefinition['extra']['search'])) {
                $typeSuffix = $this->_getSuffixForColumn($columnDefinition);

                $documentFieldName = $columnName . '_' . $typeSuffix;

                $rawValue = $object[$table->getFieldName($columnName)];
                $documentFieldValue = $this->_getValueForColumn($rawValue, $columnDefinition);
                
                $document->addField($documentFieldName, $documentFieldValue);
            }
        }

        return $document;
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
        switch ($columnDefinition['type']) {
            case 'date': // falls through
            case 'datetime': // falls through
            case 'timestamp':
                $suffix = 'date';
                break;

            case 'integer':
                $suffix = 'int';
                break;

            case 'enum':
                $suffix = 'enum';
                break;

            case 'string':
                $suffix = 'text';
                break;

            default:
                throw new Fisma_Search_Exception("No suffix defined for column type ({$columnDefinition['type']})");
        }
        
        return $suffix;
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
            $value = strip_tags($rawValue);
        } elseif ('date' == $columnDefinition['type'] || 
                  'datetime' == $columnDefinition['type'] || 
                  'timestamp' == $columnDefinition['type']) {

            // Date fields need to be converted to UTC
            $tempDate = new Zend_Date($value, 'YYYY-MM-dd HH:mm:ss');
                        
            $value = $rawValue;
        } else {
            // By default, just index the raw value
            $value = $rawValue;
        }        
        
        return $value;
    }
}
