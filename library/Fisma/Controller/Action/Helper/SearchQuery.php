<?php
require_once 'Zend/Controller/Action/Helper/Abstract.php';

class Fisma_Controller_Action_Helper_SearchQuery extends Zend_Controller_Action_Helper_Abstract
{
    static private $_cache = null;
    
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
            return false;
        }
        $cache = self::getCacheInstance();
        $userId = Zend_Auth::getInstance()->getIdentity()->id;
        $index = new Zend_Search_Lucene(Fisma_Controller_Front::getPath('data') . '/index/' . $indexName);
        if (!$cache->load($userId . '_keywords') || $keywords != $cache->load($userId . '_keywords')) {
            $hits = $index->find($keywords);
            $ids = array();
            foreach ($hits as $row) {
                $id = $row->rowId;
                if (!empty($id)) {
                    $ids[] = $id;
                }
            }
            $cache->save($ids, $userId . '_' . $indexName);
            $cache->save($keywords, $userId . '_keywords');
        }
        return $cache->load($userId . '_' . $indexName);
    }
    
    /**
     * @todo english
     * Initialize the cache instance
     *
     * make the directory "/path/to/data/cache" writable
     *
     * @return Zend_Cache
     */
    public function getCacheInstance()
    {
        if (null == self::$_cache) {
            $frontendOptions = array(
                'caching'  => true,
                //@todo english cache life same as system expiring period
                'lifetime' => Fisma_Controller_Front::readSysConfig('expiring_seconds'), 
                'automatic_serialization' => true
            );

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
     * @param  string $resource
     * @param  string $operation 
     */
    public function direct($keywords, $indexName)
    {
        $this->searchQuery($keywords, $indexName);
    }
}