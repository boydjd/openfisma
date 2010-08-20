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
     * Delete all documents of the specified type in the index
     * 
     * "Type" refers to a model, such as Asset, Finding, Incident, etc. 
     * 
     * @param string $type
     */
    abstract public function deleteByType($type);

    /**
     * Index a Doctrine collection of objects
     * 
     * @param Doctrine_Collection $collection
     */
    abstract public function indexCollection(Doctrine_Collection $collection);

    /**
     * Add the specified object to the search engine index
     * 
     * @param Fisma_Doctrine_Record $object
     */
    abstract public function indexObject(Fisma_Doctrine_Record $object);

    /**
     * Simple search: search all fields for the specified keyword
     * 
     * @param string $type Name of model index to search
     * @param string $keyword
     * @return array Rectangular array of search results
     */
    abstract public function searchByKeyword($type, $keyword);

    /**
     * Advanced search: search based on a customized Solr query
     * 
     * @param SolrQuery $keyword
     * @return array Rectangular array of search results
     */
    abstract public function searchByQuery(SolrQuery $query);

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
}
