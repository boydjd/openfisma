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
     * Delete all documents of the specified type in the index
     * 
     * "Type" refers to a model, such as Asset, Finding, Incident, etc. 
     * 
     * @param string $type
     */
    public function deleteByType($type)
    {
        throw new Fisma_Zend_Exception('not implemented');
    }

    /**
     * Index a Doctrine collection of objects
     * 
     * @param Doctrine_Collection $collection
     */
    public function indexCollection(Doctrine_Collection $collection)
    {
        throw new Fisma_Zend_Exception('not implemented');
    }

    /**
     * Add the specified object to the search engine index
     * 
     * @param Fisma_Doctrine_Record $object
     */
    public function indexObject(Fisma_Doctrine_Record $object)
    {
        throw new Fisma_Zend_Exception('not implemented');
    }

    /**
     * Simple search: search all fields for the specified keyword
     * 
     * @param string $keyword
     * @return array Rectangular array of search results
     */
    public function searchByKeyword($type, $keyword)
    {
        throw new Fisma_Zend_Exception('not implemented');
    }

    /**
     * Advanced search: search based on a customized Solr query
     * 
     * @param SolrQuery $keyword
     * @return array Rectangular array of search results
     */
    public function searchByQuery(SolrQuery $query)
    {
        throw new Fisma_Zend_Exception('not implemented');
    }

    /**
     * Validate that index directory exists and has reasonable permissions
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
}
