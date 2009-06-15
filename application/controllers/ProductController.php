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
 * Handles CRUD for product objects.
 *
 * @package   Controller
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/mw/index.php?title=License
 */
class ProductController extends SecurityController
{
    private $_paging = array(
        'startIndex' => 0,
        'count' => 20
    );
    
    /**
     * Invoked before each Action
     */
    public function preDispatch()
    {
        $this->_paging['startIndex'] = $this->_request->getParam('startIndex', 0);
    }

    /**
     * Returns the standard form for creating, reading, and updating products.
     *
     * @return Zend_Form
     */
    private function _getProductForm()
    {
        $form = Fisma_Form_Manager::loadForm('product');
        return Fisma_Form_Manager::prepareForm($form);
    }

    /**
     *  render the searching boxes and keep the searching criteria
     */
    public function searchbox()
    {
        Fisma_Acl::requirePrivilege('products', 'read');
        $value = trim($this->_request->getParam('keywords'));
        $this->view->assign('keywords', $value);
        $this->render('searchbox');
    }

    /**
     * show the list page, not for data
     */
    public function listAction()
    {
        Fisma_Acl::requirePrivilege('products', 'read');

        $value = trim($this->_request->getParam('keywords'));
        $this->searchbox();
        
        $link = '';
        empty($value) ? $link .='' : $link .= '/keywords/' . $value;
        $this->view->link     = $link;
        $this->view->pageInfo = $this->_paging;
        $this->view->keywords = $value;
        $this->render('list');
    }
    
    /**
     * list the products from the search, 
     * if search none, it list all products
     *
     */
    public function searchAction()
    {
        Fisma_Acl::requirePrivilege('products', 'read');
        $this->_helper->layout->setLayout('ajax');
        $this->_helper->viewRenderer->setNoRender();
        
        $sortBy = $this->_request->getParam('sortby', 'name');
        $order  = $this->_request->getParam('order', 'ASC');
        $value  = $this->_request->getParam('value'); 
        
        $query  = Doctrine_Query::create()
                    ->select('*')->from('Product')
                    ->orderBy("$sortBy $order")
                    ->limit($this->_paging['count'])
                    ->offset($this->_paging['startIndex']);

        if (!empty($value)) {
            $this->_helper->searchQuery($value, 'product');
            $cache = $this->getHelper('SearchQuery')->getCacheInstance();
            // get search results in ids
            $productIds = $cache->load($this->_me->id . '_product');
            if (!empty($productIds)) {
                $productIds = implode(',', $productIds);
            } else {
                // set ids as a not exist value in database if search results is none.
                $productIds = -1;
            }
            $query->where('id IN (' . $productIds . ')');
        }
        
        $totalRecords = $query->count();
        $products     = $query->execute();
        $tableData    = array('table' => array(
                            'recordsReturned' => count($products->toArray()),
                            'totalRecords'    => $totalRecords,
                            'startIndex'      => $this->_paging['startIndex'],
                            'sort'            => $sortBy,
                            'dir'             => $order,
                            'pageSize'        => $this->_paging['count'],
                            'records'         => $products->toArray()
                        ));
        echo json_encode($tableData);
    }
    
    /**
     * Display a single product record with all details.
     */
    public function viewAction()
    {
        Fisma_Acl::requirePrivilege('products', 'read');

        $this->searchbox();
        
        $form   = $this->_getProductForm();
        $id     = $this->_request->getParam('id');
        $v      = $this->_request->getParam('v', 'view');
        $product = Doctrine::getTable('Product')->find($id);
        
        if ($v == 'edit') {
            $this->view->assign('viewLink', "/panel/product/sub/view/id/$id");
            $form->setAction("/panel/product/sub/update/id/$id");
        } else {
            // In view mode, disable all of the form controls
            $this->view->assign('editLink', "/panel/product/sub/view/id/$id/v/edit");
            $form->setReadOnly(true);            
        }
        $this->view->assign('deleteLink', "/panel/product/sub/delete/id/$id");
        $form->setDefaults($product->toArray());
        $this->view->form = $form;
        $this->view->id   = $id;
        $this->render($v);
    }

     /**
     * Display the form for creating a new product.
     */
    public function createAction()
    {
        Fisma_Acl::requirePrivilege('products', 'create');

        $this->searchbox();

        // Get the product form
        $form = $this->_getProductForm();
        $form->setAction('/panel/product/sub/save');

        // If there is data in the _POST variable, then use that to
        // pre-populate the form.
        $post = $this->_request->getPost();
        $form->setDefaults($post);

        // Assign view outputs.
        $this->view->form = $form;
        $this->render('create');
    }


    /**
     * Saves information for a newly created product.
     */
    public function saveAction()
    {
        Fisma_Acl::requirePrivilege('products', 'update');
        
        $form = $this->_getProductForm();
        $post = $this->_request->getPost();
        
        if ($form->isValid($post)) {
            $product = new Product();
            $product->merge($form->getValues());

            if (!$product->trySave()) {
                $msg   = "Failure in creation";
                $model = self::M_WARNING;
            } else {
                $this->_helper->addNotification(Notification::PRODUCT_CREATED, $this->_me->username, $product->id);
                //Create a product index
                if (is_dir(Fisma_Controller_Front::getPath('data') . '/index/product/')) {
                    $this->_helper->updateIndex('product', $product->id, $product->toArray());
                }
                $msg   = "The product is created";
                $model = self::M_NOTICE;
            }
            $this->message($msg, $model);
            $this->_forward('view', null, null, array('id' => $product->id));
        } else {
            $errorString = Fisma_Form_Manager::getErrors($form);
            // Error message
            $this->message("Unable to create product:<br>$errorString", self::M_WARNING);
            $this->_forward('create');
        }
    }

    /**
     * Delete a product
     */
    public function deleteAction()
    {
        Fisma_Acl::requirePrivilege('products', 'delete');
        
        $id = $this->_request->getParam('id', 0);
        $product = Doctrine::getTable('Product')->find($id);
        if (!$product) {
            //@todo english
            $msg   = 'Invalid product';
            $model = self::M_WARNING;
        } elseif ($product->Assets->toArray()) {
            //@todo english
            $msg = 'This network can not be deleted because it is already associated with one or more ASSETS';
            $model = self::M_WARNING;
        } else {
            if (!$product->delete()) {
                //@todo english
                $msg = "Failed to delete the product";
                $model = self::M_WARNING;
            } else {
                //Delete product index
                if (is_dir(Fisma_Controller_Front::getPath('data') . '/index/product/')) {
                    $this->_helper->deleteIndex('product', $product->id);
                }

                $this->_helper->addNotification(Notification::PRODUCT_DELETED, $this->_me->username, $product->id);
                // @todo english
                $msg   = "product deleted successfully";
                $model = self::M_NOTICE;
            }
        }
        $this->message($msg, $model);
        $this->_forward('list');
    }

    /**
     * Updates product information after submitting an edit form.
     */
    public function updateAction()
    {
        Fisma_Acl::requirePrivilege('products', 'update');
        
        $form = $this->_getProductForm();
        $id   = $this->_request->getParam('id');
        $post = $this->_request->getPost();
        $formValid = $form->isValid($post);
        
        if ($form->isValid($post)) {
            $product = new Product();
            $product = $product->getTable()->find($id);
            $product->merge($form->getValues());
            if ($product->trySave()) {
                $this->_helper->addNotification(Notification::PRODUCT_MODIFIED, $this->_me->username, $product->id);
                //Update product index
                if (is_dir(Fisma_Controller_Front::getPath('data') . '/index/product/')) {
                    $this->_helper->updateIndex('product', $product->id, $product->toArray());
                }
                //@todo english
                $msg   = "The product is saved";
                $model = self::M_NOTICE;
            } else {
                //@todo english
                $msg   = "Nothing changes";
                $model = self::M_WARNING;
            }
            $this->message($msg, $model);
            $this->_forward('view', null, null, array('id' => $id));
        } else {
            $errorString = Fisma_Form_Manager::getErrors($form);
            // Error message
            $this->message("Unable to update product<br>$errorString", self::M_WARNING);
            // On error, redirect back to the edit action.
            $this->_forward('view', null, null, array('id' => $id, 'v' => 'edit'));
        }
    }
}
