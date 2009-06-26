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
 * @author    Ryan yang <ryan.yang@reyosoft.com>
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
                if (is_string($value)) {
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
                if (is_string($value)) {
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
}
