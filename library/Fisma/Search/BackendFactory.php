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
 * Constructs instances of search backends
 * 
 * @author     Mark E. Haase <mhaase@endeavorystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2010 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Fisma
 * @subpackage Fisma_Search
 */
class Fisma_Search_BackendFactory
{
    /**
     * Construct a backend based on a configuration
     * 
     * @param array $configuration
     * @return Fisma_Search_Backend_Abstract
     */
    public function getSearchBackend($configuration)
    {
        switch ($configuration['search_backend']) {
            case 'solr':
                $backend = new Fisma_Search_Backend_Solr(
                    $configuration['search_solr_host'],
                    $configuration['search_solr_port'],
                    $configuration['search_solr_path']
                );
                break;

            case 'zend_search_lucene':
                $backend = new Fisma_Search_Backend_Zend;
                break;

            default:
                throw new Fisma_Search_Exception("Invalid search backend type ({$configuration['search_backend']})");
        }
        
        return $backend;
    }
}
