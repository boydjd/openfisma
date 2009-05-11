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
 * Delete the index
 * 
 * Delete the index specified which had been created by Zend_Search_Lucene
 */
class Fisma_Controller_Action_Helper_DeleteIndex extends Zend_Controller_Action_Helper_Abstract
{
    /**
     * Delete Zend_Search_Lucene index
     *
     * @param string $indexName actually, it is the folder name in the this path "data/index/" 
     * @param integer $id row id which is indexed by Zend_Lucene
     */
    public static function deleteIndex($indexName, $id)
    {
        if (!is_dir(Fisma_Controller_Front::getPath('data') . '/index/'.$indexName)) {
            return false;
        }
        $index = new Zend_Search_Lucene(Fisma_Controller_Front::getPath('data') . '/index/'.$indexName);
        $hits = $index->find('key:'.md5($id));
        $index->delete($hits[0]);
        $index->commit();
    }
    
    /**
     * Perform helper when called as $this->_helper->deleteIndex() from an action controller
     * 
     * @param  string $indexName
     * @param  string $id 
     */
    public function direct($indexName, $id)
    {
        $this->deleteIndex($indexName, $id);
    }
}