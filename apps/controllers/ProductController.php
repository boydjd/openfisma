<?php
/**
 * @file ProductController.php
 *
 * Product Controller
 *
 * @author     Ryan <ryan.yang@reyosoft.com>
 * @copyright  (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license    http://www.openfisma.org/mw/index.php?title=License
 * @version $Id$
*/

require_once CONTROLLERS . DS . 'SecurityController.php';
require_once MODELS . DS . 'product.php';
require_once 'Pager.php';

class ProductController extends SecurityController
{
    private $_paging = array(
            'mode'        =>'Sliding',
            'append'      =>false,
            'urlVar'      =>'p',
            'path'        =>'',
            'currentPage' => 1,
            'perPage'=>20);
    
    public function init()
    {
        parent::init();
        $this->_product = new Product();
    }

    public function preDispatch()
    {
        $req = $this->getRequest();
        $this->_paging_base_path = $req->getBaseUrl() .'/panel/product/sub/list';
        $this->_paging['currentPage'] = $req->getParam('p',1);
        if(!in_array($req->getActionName(),array('login','logout') )){
            // by pass the authentication when login
            parent::preDispatch();
        }
    }   
    
    /**
      Get Product List
    */
    public function searchAction(){
        $product = new Product();
        $req = $this->getRequest();
        $prod_id = $req->getParam('prod_list','');
        $prod_name = $req->getParam('prod_name','');
        $prod_vendor = $req->getParam('prod_vendor','');
        $prod_version = $req->getParam('prod_version','' );
        $tpl_name = $req->getParam('view','search');
        $this->_helper->layout->setLayout( 'ajax' );
        $qry = $product->select()->setIntegrityCheck(false)
                                 ->from(array(),array());

        if(!empty($prod_name)){
            $qry->where("name = '$prod_name'");
            $this->view->prod_name=$prod_name;
        }

        if(!empty($prod_vendor)){
            $qry->where("vendor='$prod_vendor'");
            $this->view->prod_vendor=$prod_vendor;
        }

        if(!empty($prod_version)){
            $qry->where("version='$prod_version'");
            $this->view->prod_version=$prod_version;
        }
        $qry->limit(100,0);

        $this->view->prod_list = $product->fetchAll($qry)->toArray();
        $this->render($tpl_name);
    }
     
    public function searchboxAction()
    {
        $req = $this->getRequest();
        $fid = $req->getParam('fid');
        $qv = $req->getParam('qv');
        $query = $this->_product->select()->from(array('p'=>'products'),array('count'=>'COUNT(p.id)'));
        $res = $this->_product->fetchRow($query)->toArray();
        $count = $res['count'];
        $this->_paging['totalItems'] = $count;
        $this->_paging['fileName'] = "{$this->_paging_base_path}/p/%d";
        $pager = &Pager::factory($this->_paging);
        $this->view->assign('fid',$fid);
        $this->view->assign('qv',$qv);
        $this->view->assign('total',$count);
        $this->view->assign('links',$pager->getLinks());
        $this->render();
    }

    public function listAction()
    {
        $req = $this->getRequest();
        $field = $req->getParam('fid');
        $value = trim($req->getParam('qv'));
        $query = $this->_product->select()->from('products','*');
        if(!empty($value)){
            $query->where("$field = ?",$value);
        }
        $query->order('name ASC')
              ->limitPage($this->_paging['currentPage'],$this->_paging['perPage']);
        $product_list = $this->_product->fetchAll($query)->toArray();                                    
        $this->view->assign('product_list',$product_list);
        $this->render('sublist');
    }
   
    public function createAction()
    {
        $req = $this->getRequest();
        if('save' == $req->getParam('s')){
            $post = $req->getPost();
            foreach($post as $k=>$v){
                if('prod_' == substr($k,0,5)){
                    $k = substr($k,5);
                    $data[$k] = $v;
                }
            }
            $data['meta'] = $data['vendor'].' '.$data['name'].' '.$data['version'];
            $res = $this->_product->insert($data);
            if(!$res){
                $msg = "Failed to create the product";
            } else {
                $msg = "Product successfully created";
            }
            $this->message($msg,self::M_NOTICE);
        }
        $this->render();
    }

    public function deleteAction()
    {
        $req = $this->getRequest();
        $id  = $req->getParam('id');
        $db = $this->_product->getAdapter();
        $qry = $db->select()->from('vuln_products')->where('prod_id = '.$id);
        $result = $db->fetchCol($qry);
        if(!empty($result)){
            $msg = 'This product cannot be deleted because it is already associated with one or more vulnerabilities.';
        }else{
            $res = $this->_product->delete('id = '.$id);
            if(!$res){
                $msg = "Failed to delete the product";
                $model = self::M_WARNING;
            }else {
                $msg = "Product created successfully";
                $model = self::M_NOTICE;
            }
        }
        $this->message($msg,$model);
        $this->_forward('list');
    }

    public function viewAction()
    {
        $req = $this->getRequest();
        $id  = $req->getParam('id');
        $result = $this->_product->find($id)->toArray();
        foreach($result as $v){
            $product_list = $v;
        }
        $this->view->assign('id',$id);
        $this->view->assign('product',$product_list);
        if('edit' == $req->getParam('v')){
            $this->render('edit');
        }else{
            $this->render();
        }
    }

    public function updateAction()
    {
        $req = $this->getRequest();
        $id  = $req->getParam('id');
        $post = $req->getPost();
        foreach($post as $k=>$v){
            if('prod_' == substr($k,0,5)){
                $k = substr($k,5);
                $data[$k] = $v;
            }
        }
        $data['meta'] = $data['vendor'].' '.$data['name'].' '.$data['version'];
        $res = $this->_product->update($data,'id = '.$id);
        if(!$res){
            $msg = "Failed to edit the product";
            $model = self::M_WARNING;
        } else {
            $msg = "Product edited successfully";
            $model = self::M_NOTICE;
        }
        $this->message($msg,$model);
        $this->_forward('view',null,'id = '.$id);
    }

}
