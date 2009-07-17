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
 * @author    Ryan Yang <ryan@users.sourceforge.net>
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/mw/index.php?title=License
 * @version   $Id$
 */

/**
 * Lucene index URD
 *
 * @package    Fisma
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license    http://www.openfisma.org/mw/index.php?title=License
 */
class Fisma_Lucene
{
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
    public static function search($keywords, $indexName)
    {
        if (!is_dir(Fisma::getPath('index') . '/' . $indexName)) {
            throw new Fisma_Exception("Cannot search '$indexName' because the index does not exist.");
        }
        $cache = Fisma::getCacheInstance('LuceneSearch');
        $userId = User::currentUser()->id;
        $index = new Zend_Search_Lucene(Fisma::getPath('index') . '/' . $indexName);
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
     * Update Zend_Search_Lucene index
     *
     * This function can create one, update one Zend_Lucene index.
     *
     * @param string $name index name
     * @param int $id
     * @param array $data the data that need to insert/udpate into index
     */
    public static function updateIndex($name, $id, $data)
    {
        if (!is_dir(Fisma::getPath('data') . '/index/' . $name)) {
            return;
        }
        set_time_limit(0);
        $index = new Zend_Search_Lucene(Fisma::getPath('data') . '/index/' . $name);
        $hits = $index->find('key:' . md5($id));
        if (!empty($hits)) {
            //Update one index
            $doc = $index->getDocument($hits[0]);
            foreach ($data as $field=>$value) {
                if ('string' == self::getColumnType($name, $field)) {
                   $doc->addField(Zend_Search_Lucene_Field::UnStored($field, $value));
                }
            }
            $index->addDocument($doc);
        } else {
            //Create one index
            $doc = new Zend_Search_Lucene_Document();
            $doc->addField(Zend_Search_Lucene_Field::UnIndexed('rowId', $id));
            $doc->addField(Zend_Search_Lucene_Field::UnStored('key', md5($id)));
            foreach ($data as $field=>$value) {
                if ('string' == self::getColumnType($name, $field)) {
                    $doc->addField(Zend_Search_Lucene_Field::UnStored($field, $value));
                }
            }
            $index->addDocument($doc);
        }
        $index->commit();
    }

    /**
     * Delete Zend_Search_Lucene index
     *
     * @param string $name actually, it is the folder name in the this path "data/index/" 
     * @param integer $id row id which is indexed by Zend_Lucene
     */
    public static function deleteIndex($name, $id)
    {
        if (!is_dir(Fisma::getPath('data') . '/index/' . $name)) {
            return;
        }
        $index = new Zend_Search_Lucene(Fisma::getPath('data') . '/index/' . $name);
        $hits = $index->find('key:' . md5($id));
        $index->delete($hits[0]);
        $index->commit();
    }

    /**
     * Get the column type of a specific field (the index name is same to the table name)
     *
     * @param string $indexName the index name
     * @param string $field  a specific field in a talbe
     * @return string 
     */
    public static function getColumnType($indexName, $field)
    {
        $indexTable       =  Doctrine::getTable(ucfirst($indexName));
        $columnDefinition = $indexTable->getColumnDefinition(strtolower($field));
        return $columnDefinition['type'];
    }
}
