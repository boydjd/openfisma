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
 * Abstract base class for search engine backends
 *
 * @author     Mark E. Haase <mhaase@endeavorystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2010 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Fisma
 * @subpackage Fisma_Search
 */
abstract class Fisma_Search_Backend_Abstract
{
    /**
     * True if highlighting should be turned on
     */
    private $_highlightingEnabled = true;

    /**
     * Search results are limited to this number of characters per field
     *
     * @var int
     */
    private $_maxRowLength = 100;

    /**
     * Delete all documents in the index
     */
    abstract public function deleteAll();

    /**
     * Commit changes to the index (if the backend supports read consistency)
     *
     * An implementation may provide a stub for commit() if the backend doesn't support it.
     */
    abstract public function commit();

    /**
     * Delete all documents of the specified type in the index
     *
     * "Type" refers to a model, such as Asset, Finding, Incident, etc.
     *
     * @param string $type
     */
    abstract public function deleteByType($type);

    /**
     * Delete the specified object from the index
     *
     * $type must have a corresponding table class which implements Fisma_Search_Searchable
     *
     * @param string $type The class of the object
     * @param array $object
     */
    abstract public function deleteObject($type, $object);

    /**
     * Index an array of objects
     *
     * @param string $type The class of the object
     * @param array $collection
     */
    abstract public function indexCollection($type, $collection);

    /**
     * Add the specified object (in array format) to the search engine index
     *
     * This will overwrite any existing object with the same luceneDocumentId
     *
     * @param string $type The class of the object
     * @param array $object
     */
    abstract public function indexObject($type, $object);

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
    abstract public function isColumnSortable($type, $columnName);

    /**
     * Optimize the index (degfragments the index)
     */
    abstract public function optimizeIndex();

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
    abstract public function searchByKeyword($type, 
                                             $keyword, 
                                             $sortColumn, 
                                             $sortDirection, 
                                             $start, 
                                             $rows, 
                                             $deleted);

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
    abstract public function searchByCriteria(
        $type,
        Fisma_Search_Criteria $criteria,
        $sortColumn,
        $sortDirection,
        $start,
        $rows,
        $deleted
    );

    /**
     * Validate the backend's configuration
     *
     * The implementing class should use this to exercise basic diagnostics
     *
     * @return mixed Return TRUE if configuration is valid, or a string error message otherwise
     */
    abstract public function validateConfiguration();

    /**
     * Escape a parameter for inclusion in a Lucene query
     *
     * @see http://lucene.apache.org/java/2_4_0/queryparsersyntax.html#Escaping%20Special%20Characters
     *
     * @param string $parameter
     * @return string Escaped parameter
     */
    public function escape($parameter)
    {
        $specialChars = '+-!(){}[]^"~*?:\&|';

        return addcslashes($parameter, $specialChars);
    }

    /**
     * Get Max Row Length
     *
     * @return int
     */
    public function getMaxRowLength()
    {
        return $this->_maxRowLength;
    }

    /**
     * Set Max Row Length
     *
     * Set to null to turn off row length limit
     *
     * @param int $length
     */
    public function setMaxRowLength($length)
    {
        $this->_maxRowLength = $length;
    }

    /**
     * Get whether highlighting is enabled or not
     *
     * @return bool
     */
    public function getHighlightingEnabled()
    {
        return $this->_highlightingEnabled;
    }

    /**
     * Control highlighting behavior
     *
     * @param bool $enabled
     */
    public function setHighlightingEnabled($enabled)
    {
        $this->_highlightingEnabled = $enabled;
    }

    /**
     * Convert HTML string to a form that is ideal for text indexing
     *
     * This removes tags but ensures that the removal of tags does not result in separate words being concatenated
     * together.
     *
     * Notice that malformed HTML inputs may be mangled by this method.
     *
     * @param string $htmlString
     * @return string
     */
    protected function _convertHtmlToIndexString($html)
    {
        // Remove line feeds. They are replaced with spaces to prevent the next word on the next line from adjoining
        // the last word on the previous line, but consecutive spaces are culled out later.
        $html = str_replace(chr(10), ' ', $html);
        $html = str_replace(chr(13), ' ', $html);

        // Remove tags, but be careful not to concatenate together two words that were split by a tag
        $html = preg_replace('/(\w)<.*?>(\W)/', '$1$2', $html);
        $html = preg_replace('/(\W)<.*?>(\w)/', '$1$2', $html);
        $html = preg_replace('/<.*?>/', ' ', $html);

        // Decode entities (this way we don't index words like 'lt', 'rt', and 'amp')
        $html = html_entity_decode($html);

        // Remove excess whitespace
        $html = preg_replace('/[ ]*(?>\r\n|\n|\x0b|\f|\r|\x85)[ ]*/', "\n", $html);
        $html = preg_replace('/^\s+/', '', $html);
        $html = preg_replace('/\s+$/', '', $html);
        $html = preg_replace('/ +/', ' ', $html);

        // Character set encoding -- input charset is a guess
        $html = iconv('ISO-8859-1', 'UTF-8//TRANSLIT//IGNORE', $html);

        return $html;
    }
    
    /**
     * Return an array of ACL terms
     *
     * e.g. the following return value indicates a user has access to any document where the 'id' field is 1 or 2
     *
     * array(
     *     array('field' => 'id', 'value' => 1),
     *     array('field' => 'id', 'value' => 2),
     * )
     *
     * @param Doctrine_Table $table
     * @return mixed Array of acl terms or null if ACL does not apply
     */
    protected function _getAclTerms($table) 
    {
        $aclFields = $table->getAclFields();

        // If no ACL fields, then don't return any ACL terms
        if (count($aclFields) == 0) {
            return null;
        }

        $ids = array();
        
        foreach ($aclFields as $aclFieldName => $callback) {      
            $aclIds = call_user_func($callback);

            if ($aclIds === false) {
                $message = "Could not call ACL ID provider ($callback) for ACL field ($name).";

                throw new Fisma_Zend_Exception($message);
            }

            foreach ($aclIds as &$aclId) {
                $ids[] = array(
                    'field' => $aclFieldName,
                    'value' => $this->escape($aclId)
                );
            }
        }

        return $ids;
    }
    
    /**
     * Returns the raw value for a field based on the search metadata definition.
     *
     * This has the ability to load data from a related model as well.
     *
     * @param Doctrine_Table $table
     * @param array $object
     * @param string $doctrineFieldName Name of field given by Doctrine
     * @param array $searchFieldDefinition
     * return mixed The raw value of the field
     */
    protected function _getRawValueForField($table, $object, $doctrineFieldName, $searchFieldDefinition)
    {
        $rawValue = null;

        if (!isset($searchFieldDefinition['join'])) {
            $rawValue = $object[$table->getFieldName($doctrineFieldName)];
        } else {
            // Handle nested relations
            $relationParts = explode('.', $searchFieldDefinition['join']['relation']);

            $relatedObject = $object;

            foreach ($relationParts as $relationPart) {
                $relatedObject = $relatedObject[$relationPart];
            }

            $rawValue = $relatedObject[$searchFieldDefinition['join']['field']];
        }
        
        return $rawValue;
    }
    
    /**
     * Return searchable fields for a particular model
     *
     * @param string $type Name of model 
     */
    protected function _getSearchableFields($type)
    {
        $table = Doctrine::getTable($type);

        if (!($table instanceof Fisma_Search_Searchable)) {
            $message = 'Objects which are to be indexed must have a table that implements'
                     . ' the Fisma_Search_Searchable interface';

            throw new Fisma_Zend_Exception($message);
        }
        
        return $table->getSearchableFields();
    }
    
    /**
     * Tokenize a basic search query and return an array of tokens
     * 
     * @param string $basicQuery
     * @return array
     */
    public function _tokenizeBasicQuery($basicQuery)
    {
        return preg_split("/[\s,]+/", $basicQuery);
    }
}
