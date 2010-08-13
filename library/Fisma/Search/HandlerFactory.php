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
 * Creates instances of ezcSearchHandler
 * 
 * @author     Mark E. Haase
 * @copyright  (c) Endeavor Systems, Inc. 2010 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Fisma
 * @subpackage Fisma_Search
 */
class Fisma_Search_HandlerFactory
{
    /**
     * Get a search handler for searching a specific class
     * 
     * @param string $class The name of the class to create a handler for
     * @return ezcSearchHandler
     */
    public function getSearchHandler($class)
    {
        $handlerType = Fisma::configuration()->getConfig('search_backend');

        // The handler types correspond to the enum values on the configuration table's search_backend column
        switch ($handlerType) {
            case 'zend_search_lucene':
                $searchHandler = $this->_getZslSearchHandler($class);
                break;
            case 'solr':
                $searchHandler = $this->_getSolrSearchHandler($class);
                break;
            default:
                throw new Fisma_Zend_Exception("Invalid search handler type: $handlerType");
        }
        
        return $searchHandler;
    }

    /**
     * Get a zend_search_lucene search handler
     * 
     * @param string $class The name of the class to create the handler for
     * @return ezcSearchZendLuceneHandler 
     */
    private function _getZslSearchHandler($class)
    {
        $indexPath = Fisma::getPath('index') . '/' . $class;

        // ezComponents does not create the directory for the index automatically, so we need to do it first
        if (!is_dir($indexPath)) {
            if (!mkdir($indexPath, 0770)) {
                throw new Fisma_Zend_Exception("Not able to create index directory: $indexPath");
            }
        }

        $searchHandler = new ezcSearchZendLuceneHandler($indexPath);

        return $searchHandler;
    }

    /**
     * Get a Solr search handler
     * 
     * @return ezcSearchSolrHandler
     */
    private function _getSolrSearchHandler()
    {
        $configuration = Fisma::configuration();
        
        $solrHost = $configuration->getConfig('search_solr_host');
        $solrPort = $configuration->getConfig('search_solr_port');
        $solrPath = $configuration->getConfig('search_solr_path');

        $searchHandler = new ezcSearchSolrHandler($solrHost, $solrPort, $solrPath);
        
        return $searchHandler;
    }
}
