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
 * @package   Controller
 */

/**
 * The product controller handles CRUD for product objects.
 *
 * @package   Controller
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/mw/index.php?title=License
 */
class ProductController extends SecurityController
{
    private $_paging = array(
        'mode' => 'Sliding',
        'append' => false,
        'urlVar' => 'p',
        'path' => '',
        'currentPage' => 1,
        'perPage' => 20
    );

    /**
     * @todo english
     * Initilize Class
     */
    public function init()
    {
        parent::init();
        $this->_product = new Product();
    }

    /**
     * @todo english
     * Invoked before each Action
     */
    public function preDispatch()
    {
        $req = $this->getRequest();
        $this->_pagingBasePath = $req->getBaseUrl() . '/panel/product/sub/list';
        $this->_paging['currentPage'] = $req->getParam('p', 1);
        $ajaxContext = $this->_helper->getHelper('AjaxContext');
        $ajaxContext->addActionContext('search', 'html')
                    ->initContext();
    }

    /**
     * Returns the standard form for creating, reading, and updating products.
     *
     * @return Zend_Form
     */
    public function getProductForm()
    {
        $form = Form_Manager::loadForm('product');
        return Form_Manager::prepareForm($form);
    }

    /**
     * Render the form for searching the products
     */
    public function searchAction()
    {
        $this->_acl->requirePrivilege('admin_products', 'read');
        
        $product = new Product();
        $req = $this->getRequest();
        $prodId = $req->getParam('prod_list', '');
        $prodName = $req->getParam('prod_name', '');
        $prodVendor = $req->getParam('prod_vendor', '');
        $prodVersion = $req->getParam('prod_version', '');
        $qry = $product->select()->setIntegrityCheck(false)->from(array(),
            array());
        if (!empty($prodName)) {
            $qry->where("name = '$prodName'");
            $this->view->prodName = $prodName;
        }
        if (!empty($prodVendor)) {
            $qry->where("vendor='$prodVendor'");
            $this->view->prodVendor = $prodVendor;
        }
        if (!empty($prodVersion)) {
            $qry->where("version='$prodVersion'");
            $this->view->prodVersion = $prodVersion;
        }
        $qry->limit(100, 0);
        $this->view->prod_list = $product->fetchAll($qry)->toArray();
    }

    /**
     * List the products from the search, if search none, it list all products
     */
    public function searchbox()
    {
        $this->_acl->requirePrivilege('admin_products', 'read');
        
        $qv = trim($this->_request->getParam('qv'));
        if (!empty($qv)) {
            //@todo english  if product index dosen't exist, then create it.
            if (!is_dir(Config_Fisma::getPath('data') . '/index/product/')) {
                $this->createIndex();
            }
            $ret = Config_Fisma::searchQuery($qv, 'product');
            $count = count($ret);
        } else {
            $count = $this->_product->count();
        }

        $this->_paging['totalItems'] = $count;
        $this->_paging['fileName'] = "{$this->_pagingBasePath}/p/%d";
        $pager = & Pager::factory($this->_paging);
        $this->view->assign('qv', $qv);
        $this->view->assign('total', $count);
        $this->view->assign('links', $pager->getLinks());
        $this->render('searchbox');
    }
    
    /**
     * List the products according to search criterias.
     */
    public function listAction()
    {
        $this->_acl->requirePrivilege('admin_products', 'read');
        //Display searchbox template
        $this->searchbox();
        
        $value = trim($this->_request->getParam('qv'));

        $query = $this->_product->select()->from('products', '*')
                                         ->order('name ASC')
                                         ->limitPage($this->_paging['currentPage'],
                                                     $this->_paging['perPage']);

        if (!empty($value)) {
            $cache = Config_Fisma::getCacheInstance();
            //@todo english  get search results in ids
            $productIds = $cache->load($this->_me->id . '_product');
            if (!empty($productIds)) {
                $ids = implode(',', $productIds);
            } else {
                //@todo english  set ids as a not exist value in database if search results is none.
                $ids = -1;
            }
            $query->where('id IN (' . $ids . ')');
        }
        $productList = $this->_product->fetchAll($query)->toArray();
        $this->view->assign('product_list', $productList);
        $this->render('sublist');
    }

    /**
     * Display a single product record with all details.
     */
    public function viewAction()
    {
        $this->_acl->requirePrivilege('admin_products', 'read');

        //Display searchbox template
        $this->searchbox();
        
        $form = $this->getProductForm();
        $id = $this->_request->getParam('id');
        $v = $this->_request->getParam('v', 'view');

        $res = $this->_product->find($id)->toArray();
        $product = $res[0];
        if ($v == 'edit') {
            $this->view->assign('viewLink', "/panel/product/sub/view/id/$id");
            $form->setAction("/panel/product/sub/update/id/$id");
        } else {
            // In view mode, disable all of the form controls
            $this->view->assign('editLink', "/panel/product/sub/view/id/$id/v/edit");
            $form->setReadOnly(true);            
        }
        $form->setDefaults($product);
        $this->view->form = $form;
        $this->view->assign('id', $id);
        $this->render($v);
    }

     /**
     * Display the form for creating a new product.
     */
    public function createAction()
    {   
        $this->_acl->requirePrivilege('admin_products', 'create');

        //Display searchbox template
        $this->searchbox();

        // Get the product form
        $form = $this->getProductForm();
        $form->setAction('/panel/product/sub/save');

        // If there is data in the _POST variable, then use that to
        // pre-populate the form.
        $post = $this->_request->getPost();
        $form->setDefaults($post);

        // Assign view outputs.
        $this->view->form = Form_Manager::prepareForm($form);
        $this->render('create');
    }


    /**
     * Saves information for a newly created product.
     */
    public function saveAction()
    {
        $this->_acl->requirePrivilege('admin_products', 'update');
        
        $form = $this->getProductForm();
        $post = $this->_request->getPost();
        $formValid = $form->isValid($post);
        if ($form->isValid($post)) {
            $product = $form->getValues();
            unset($product['save']);
            unset($product['reset']);
            $productId = $this->_product->insert($product);
            if (! $productId) {
                $msg = "Failure in creation";
                $model = self::M_WARNING;
            } else {
                $this->_notification
                     ->add(Notification::PRODUCT_CREATED, $this->_me->account, $productId);

                //Create a product index
                if (is_dir(Config_Fisma::getPath('data') . '/index/product/')) {
                    Config_Fisma::updateIndex('product', $productId, $product);
                }

                $msg = "The product is created";
                $model = self::M_NOTICE;
            }
            $this->message($msg, $model);
            $this->_forward('view', null, null, array('id' => $productId));
        } else {
            $errorString = Form_Manager::getErrors($form);
            // Error message
            $this->message("Unable to create product:<br>$errorString", self::M_WARNING);
            $this->_forward('create');
        }
    }

    /**
     *  Delete a specified product.
     */
    public function deleteAction()
    {
        $this->_acl->requirePrivilege('admin_products', 'delete');
        
        $req = $this->getRequest();
        $id = $req->getParam('id');
        $db = $this->_product->getAdapter();
        $qry = $db->select()->from('vuln_products')->where('prod_id = ' . $id);
        $result = $db->fetchCol($qry);
        if (!empty($result)) {
            $msg = 'This product cannot be deleted because it is already'
                   .' associated with one or more vulnerabilities.';
        } else {
            $res = $this->_product->delete('id = ' . $id);
            if (!$res) {
                $msg = "Failed to delete the product";
                $model = self::M_WARNING;
            } else {
                $this->_notification
                     ->add(Notification::PRODUCT_DELETED,
                         $this->_me->account, $id);

                //Delete this product index
                if (is_dir(Config_Fisma::getPath('data') . '/index/product/')) {
                    Config_Fisma::deleteIndex('product', $id);
                }

                $msg = "Product deleted successfully";
                $model = self::M_NOTICE;
            }
        }
        $this->message($msg, $model);
        $this->_forward('list');
    }

    /**
     * Updates product information after submitting an edit form.
     */
    public function updateAction ()
    {
        $this->_acl->requirePrivilege('admin_products', 'update');
        
        $form = $this->getProductForm();
        $post = $this->_request->getPost();
        $formValid = $form->isValid($post);
        $product = $form->getValues();

        $id = $this->_request->getParam('id');
        if ($formValid) {
            unset($product['save']);
            unset($product['reset']);
            $res = $this->_product->update($product, 'id = ' . $id);
            if ($res) {
                $this->_notification
                     ->add(Notification::PRODUCT_MODIFIED, $this->_me->account, $id);

                //Update this product index
                if (is_dir(Config_Fisma::getPath('data') . '/index/product/')) {
                    Config_Fisma::updateIndex('product', $id, $product);
                }

                $msg = "The product is saved";
                $model = self::M_NOTICE;
            } else {
                $msg = "Nothing changes";
                $model = self::M_WARNING;
            }
            $this->message($msg, $model);
            $this->_forward('view', null, null, array('id' => $id));
        } else {
            $errorString = Form_Manager::getErrors($form);
            // Error message
            $this->message("Unable to update product<br>$errorString", self::M_WARNING);
            // On error, redirect back to the edit action.
            $this->_forward('view', null, null, array('id' => $id, 'v' => 'edit'));
        }
    }

    /**
     * Create products Lucene Index
     */
    protected function createIndex()
    {
        $index = new Zend_Search_Lucene(Config_Fisma::getPath('data') . '/index/product', true);
        $list = $this->_product->getList(array('meta', 'vendor', 'name', 'version', 'desc'));
        set_time_limit(0);
        if (!empty($list)) {
            foreach ($list as $id=>$row) {
                $doc = new Zend_Search_Lucene_Document();
                $doc->addField(Zend_Search_Lucene_Field::UnStored('key', md5($id)));
                $doc->addField(Zend_Search_Lucene_Field::UnIndexed('rowId', $id));
                $doc->addField(Zend_Search_Lucene_Field::UnStored('vendor', $row['vendor']));
                $doc->addField(Zend_Search_Lucene_Field::UnStored('name', $row['name']));
                $doc->addField(Zend_Search_Lucene_Field::UnStored('version', $row['version']));
                $doc->addField(Zend_Search_Lucene_Field::UnStored('desc', $row['desc']));
                $index->addDocument($doc);
            }
            $index->optimize();
        }
    }
}
