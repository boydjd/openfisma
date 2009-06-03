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
 * @author    woody
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/mw/index.php?title=License
 * @version   $Id$
 * @package   Helper
 *
 */
require_once 'Zend/Controller/Action/Helper/Abstract.php';

/**
 * Execute the query in Zend_Search_Lucene
 * 
 * Find the keywords you specified by Zend_Search_Lucene
 */
class Fisma_Controller_Action_Helper_SearchQuery extends Zend_Controller_Action_Helper_Abstract
{
    /**
     * store the cache for Zend_Search_Lucene
     *
     * @var object
     */
    private $_cache = null;
    
    /**
     * Fuzzy Search by Zend_Search_Lucene
     *
     * @param string $keywords the search conditions
     *      The keywords should be as following format:
     *              a.   keyword (search keyword in all fields)
     *              b.   field:keyword (search keyword in field)
     *              c.   keyword1 field:keyword2 -keyword3 (required keyword1 in all fields,
     *                   required keyword2 in field, not required keyword3 in all fields)
     *              d.   keywor*  (to search for keywor, keyword, keywords, etc.)
     *              e.   keywo?d  (to search for keyword, keywoaed ,etc.)
     *              f.   mod_date:[20080101 TO 20080130] (search mod_date fields between 20080101 and 20080130)
     *              g.   title:{Aida To Carmen} (search whose titles would be sorted between Aida and Carmen)
     *              h.   keywor~  (fuzzy search, search like keyword, leyword, etc.)
     *              i.   keyword1 AND keyword2 (search documents that contain keyword1 and keyword2)
     *              j.   keyword1 OR keyword2 (search docuements that contain keyword1 or keyword2)
     *              k.   keyword1 AND NOT keyword2 (search documents that contain keyword1 but not keywords2)
     *              ... see Zend_Search_Lucene for more format
     * @param string $indexName index name
     * @return array table row ids
     */
    public function searchQuery($keywords, $indexName)
    {
        if (!is_dir(Fisma_Controller_Front::getPath('data') . '/index/' . $indexName)) {
            /** 
             * @todo english 
             */
            throw new Fisma_Exception_General('The path of creating indexes is not existed');
        }
        // get the variable of cache
        $cache = $this->getCacheInstance();
        // get the identity of the user
        $userId = Zend_Auth::getInstance()->getIdentity()->id;
        // build the object of LUCENE
        $index = new Zend_Search_Lucene(Fisma_Controller_Front::getPath('data') . '/index/' . $indexName);
        // if the keywords didn't in cache or current keywords is different from the keywords in cache,
        // then do the LUCENE searching
        if (!$cache->load($userId . '_keywords') || $keywords != $cache->load($userId . '_keywords')) {
            $hits = $index->find($keywords);
            $ids = array();
            foreach ($hits as $row) {
                $id = $row->rowId;
                if (!empty($id)) {
                    $ids[] = $id;
                }
            }
            // Cache current searching result, and identify it from user id.
            $cache->save($ids, $userId . '_' . $indexName);
            // Cache current keywords, and identify it from user id.
            $cache->save($keywords, $userId . '_keywords');
        }
        //get the last result
        return $cache->load($userId . '_' . $indexName);
    }
    
    /**
     * Initialize the cache instance
     *
     * make the directory "/path/to/data/cache" writable
     *
     * @return Zend_Cache
     */
    public function getCacheInstance()
    {
        if (null == $this->_cache) {
            $frontendOptions = array(
                'caching'  => true,
                // cache life same as system expiring period
                'lifetime' => Configuration::getConfig('expiring_seconds'), 
                'automatic_serialization' => true);

            $backendOptions = array(
                'cache_dir' => Fisma_Controller_Front::getPath('data') . '/cache'
            );
            $this->_cache = Zend_Cache::factory('Core',
                                                'File',
                                                $frontendOptions,
                                                $backendOptions);

        }
        return $this->_cache;
    }
    
    /**
     * Perform helper when called as $this->_helper->searchQuery() from an action controller
     * 
     * @param  string $keywords
     * @param  string $indexName 
     */
    public function direct($keywords, $indexName)
    {
        return $this->searchQuery($keywords, $indexName);
    }
}