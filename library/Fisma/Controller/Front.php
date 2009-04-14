<?php
class Fisma_Controller_Front extends Zend_Controller_Front
{
    public static function getInstance()
    {
        if (null === self::$_instance) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }
    
    /**
     * Read configurations of any sections.
     * This function manages the storage, the cache, lazy initializing issue.
     * 
     * @param $key string key name
     * @param $is_fresh boolean to read from persisten storage or not.
     * @return string configuration value.
     */ 
    public static function readSysConfig($key)
    {
        $setting = self::getInstance();
        return $setting->getPlugin('Fisma_Controller_Plugin_Setting')->getConfig($key);
    }
    
    public static function getLogInstance()
    {
        $setting = self::getInstance();
        return $setting->getPlugin('Fisma_Controller_Plugin_Setting')->getLogInstance();
    }
    
    public static function getPath($part)
    {
        $setting = self::getInstance();
        return $setting->getPlugin('Fisma_Controller_Plugin_Setting')->getPath($part);
    }
    
    public static function debug()
    {
        $setting = self::getInstance();
        return $setting->getPlugin('Fisma_Controller_Plugin_Setting')->debug();
    }
    
    /**
     * Retrieve the current time
     *
     * @return unix timestamp
     */
    public static function now()
    {
        $setting = self::getInstance();
        return $setting->getPlugin('Fisma_Controller_Plugin_Setting')->now();
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
                'lifetime' => self::readSysConfig('expiring_seconds'), 
                'automatic_serialization' => true
            );

            $backendOptions = array(
                'cache_dir' => self::getPath('data') . '/cache'
            );
            $this->_cache = Zend_Cache::factory('Core',
                                                'File',
                                                $frontendOptions,
                                                $backendOptions);

        }
        return $this->_cache;
    }
    
    /**
     * @todo english
     * Update Zend_Search_Lucene index
     *
     * This function can create one, update one and update a number of Zend_Lucene indexes.
     *
     * @param string index $indexName under the "data/index/" folder
     * @param string|array $id
     *           string specific a table primary key   
     *                      if the id exists in the index, then update it, else create a index.
     *           array  specific index docuement ids
     *                      update a number of exist indexes
     * @param array $data fields need to update
     */
    public static function updateIndex($indexName, $id, $data)
    {
        if (!is_dir(self::getPath('data') . '/index/'.$indexName)) {
            return false;
        }
        @ini_set("memory_limit", -1);
        $index = new Zend_Search_Lucene(self::getPath('data') . '/index/'.$indexName);
        if (is_array($id)) {
            //Update a number of indexes
            foreach ($id as $oneId) {
                $doc = $index->getDocument($oneId);
                foreach ($data as $field=>$value) {
                    $doc->addField(Zend_Search_Lucene_Field::UnStored($field, $value));
                }
                $index->addDocument($doc);
            }
        } else {
            $hits = $index->find('key:'.md5($id));
            if (!empty($hits)) {
                //Update one index
                $doc = $index->getDocument($hits[0]);
                foreach ($data as $field=>$value) {
                    $doc->addField(Zend_Search_Lucene_Field::UnStored($field, $value));
                }
                $index->addDocument($doc);
            } else {
                //Create one index
                $doc = new Zend_Search_Lucene_Document();
                $doc->addField(Zend_Search_Lucene_Field::UnIndexed('rowId', $id));
                $doc->addField(Zend_Search_Lucene_Field::UnStored('key', md5($id)));
                foreach ($data as $field=>$value) {
                    $doc->addField(Zend_Search_Lucene_Field::UnStored($field, $value));
                }
                $index->addDocument($doc);
            }
        }
        $index->commit();
    }
    
    /**
     * @todo english
     * Delete Zend_Search_Lucene index
     *
     * @param string index indexName under the "data/index/" folder
     * @param integer $id row id which is indexed by Zend_Lucene
     */
    public static function deleteIndex($indexName, $id)
    {
        if (!is_dir(self::getPath('data') . '/index/'.$indexName)) {
            return false;
        }
        $index = new Zend_Search_Lucene(self::getPath('data') . '/index/'.$indexName);
        $hits = $index->find('key:'.md5($id));
        $index->delete($hits[0]);
        $index->commit();
    }

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
        if (!is_dir(self::getPath('data') . '/index/' . $indexName)) {
            return false;
        }
        $cache = self::getCacheInstance();
        $userId = Zend_Auth::getInstance()->getIdentity()->id;
        $index = new Zend_Search_Lucene(self::getPath('data') . '/index/' . $indexName);
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
    
}
