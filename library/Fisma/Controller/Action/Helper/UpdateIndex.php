<?php
require_once 'Zend/Controller/Action/Helper/Abstract.php';

class Fisma_Controller_Action_Helper_UpdateIndex extends Zend_Controller_Action_Helper_Abstract
{
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
        if (!is_dir(Fisma_Controller_Front::getPath('data') . '/index/'.$indexName)) {
            return false;
        }
        @ini_set("memory_limit", -1);
        $index = new Zend_Search_Lucene(Fisma_Controller_Front::getPath('data') . '/index/'.$indexName);
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
     * Perform helper when called as $this->_helper->updateIndex() from an action controller
     * 
     * @param  string $resource
     * @param  string $operation 
     */
    public function direct($indexName, $id, $data)
    {
        $this->updateIndex($indexName, $id, $data);
    }
    
}