<?php
require_once 'Zend/Controller/Action/Helper/Abstract.php';

class Fisma_Controller_Action_Helper_DeleteIndex extends Zend_Controller_Action_Helper_Abstract
{
    /**
     * @todo english
     * Delete Zend_Search_Lucene index
     *
     * @param string index indexName under the "data/index/" folder
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
     * @param  string $resource
     * @param  string $operation 
     */
    public function direct($indexName, $id)
    {
        $this->deleteIndex($indexName, $id);
    }
}