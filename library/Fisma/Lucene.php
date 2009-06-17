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
     * This function can create one, update one and update a number of Zend_Lucene indexes.
     *
     * @param string index $name under the "data/index/" folder
     * @param string|array $id
     *           string specific a table primary key   
     *                      if the id exists in the index, then update it, else create a index.
     *           array  specific index docuement ids
     *                      update a number of exist indexes
     * @param array $data fields need to update
     */
    public static function updateIndex($name, $object)
    {
        if (!is_dir(Fisma_Controller_Front::getPath('data') . '/index/'.$name)) {
            return false;
        }
        $id   = $object->id;
        $data = $object->toArray();
        @ini_set("memory_limit", -1);
        $index = new Zend_Search_Lucene(Fisma_Controller_Front::getPath('data') . '/index/'.$name);
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
        if (!is_dir(Fisma_Controller_Front::getPath('data') . '/index/'.$name)) {
            return false;
        }
        $index = new Zend_Search_Lucene(Fisma_Controller_Front::getPath('data') . '/index/'.$name);
        $hits = $index->find('key:'.md5($id));
        $index->delete($hits[0]);
        $index->commit();
    }
}
