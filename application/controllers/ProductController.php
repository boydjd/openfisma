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
    public function init()
    {
        parent::init();
        $this->_product = new Product();
    }
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
     Get Product List
     */
    public function searchAction()
    {
        $this->_helper->requirePrivilege('admin_products', 'read');
        
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
    public function searchboxAction()
    {
        $this->_helper->requirePrivilege('admin_products', 'read');
        
        $req = $this->getRequest();
        $fid = $req->getParam('fid');
        $qv = $req->getParam('qv');
        $query = $this->_product->select()->from(array(
            'p' => 'products'
        ), array(
            'count' => 'COUNT(p.id)'
        ));
        if (!empty($qv)) {
            $query->where("$fid = ?", $qv);
            $this->_pagingBasePath .= '/fid/'.$fid.'/qv/'.$qv;
        }
        $res = $this->_product->fetchRow($query)->toArray();
        $count = $res['count'];
        $this->_paging['totalItems'] = $count;
        $this->_paging['fileName'] = "{$this->_pagingBasePath}/p/%d";
        $pager = & Pager::factory($this->_paging);
        $this->view->assign('fid', $fid);
        $this->view->assign('qv', $qv);
        $this->view->assign('total', $count);
        $this->view->assign('links', $pager->getLinks());
    }
    public function listAction()
    {
        $this->_helper->requirePrivilege('admin_products', 'read');
        
        $req = $this->getRequest();
        $field = $req->getParam('fid');
        $value = trim($req->getParam('qv'));
        $query = $this->_product->select()->from('products', '*');
        if (!empty($value)) {
            $query->where("$field = ?", $value);
        }
        $query->order('name ASC')->limitPage($this->_paging['currentPage'],
            $this->_paging['perPage']);
        $productList = $this->_product->fetchAll($query)->toArray();
        $this->view->assign('product_list', $productList);
        $this->render('sublist');
    }
    public function createAction()
    {
        $this->_helper->requirePrivilege('admin_products', 'create');
        
        $req = $this->getRequest();
        if ('save' == $req->getParam('s')) {
            $post = $req->getPost();
            foreach ($post as $k => $v) {
                if ('prod_' == substr($k, 0, 5)) {
                    $k = substr($k, 5);
                    $data[$k] = $v;
                }
            }
            $data['meta'] = $data['vendor'] . ' ' . $data['name'] . ' '
                . $data['version'];
            $productId = $this->_product->insert($data);
            if (!$productId) {
                $msg = "Failed to create the product";
            } else {
                $this->_notification
                     ->add(Notification::PRODUCT_CREATED,
                         $this->_me->account, $productId);

                $msg = "Product successfully created";
            }
            $this->message($msg, self::M_NOTICE);
            $this->render('create');
        }
    }
    public function deleteAction()
    {
        $this->_helper->requirePrivilege('admin_products', 'delete');
        
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
                $msg = "Product deleted successfully";
                $model = self::M_NOTICE;
            }
        }
        $this->message($msg, $model);
        $this->_forward('list');
    }
    public function viewAction()
    {
        $this->_helper->requirePrivilege('admin_products', 'read');
        
        $req = $this->getRequest();
        $id = $req->getParam('id');
        $result = $this->_product->find($id)->toArray();
        foreach ($result as $v) {
            $productList = $v;
        }
        $this->view->assign('id', $id);
        $this->view->assign('product', $productList);
        if ('edit' == $req->getParam('v')) {
            $this->render('edit');
        }
    }
    public function updateAction()
    {
        $this->_helper->requirePrivilege('admin_products', 'update');
        
        $req = $this->getRequest();
        $id = $req->getParam('id');
        $post = $req->getPost();
        foreach ($post as $k => $v) {
            if ('prod_' == substr($k, 0, 5)) {
                $k = substr($k, 5);
                $data[$k] = $v;
            }
        }
        $data['meta'] = $data['vendor'] . ' ' . $data['name'] . ' '
            . $data['version'];
        $res = $this->_product->update($data, 'id = ' . $id);
        if (!$res) {
            $msg = "Failed to edit the product";
            $model = self::M_WARNING;
        } else {
             $this->_notification
                  ->add(Notification::PRODUCT_MODIFIED,
                      $this->_me->account, $id);

            $msg = "Product edited successfully";
            $model = self::M_NOTICE;
        }
        $this->message($msg, $model);
        $this->_forward('view', null, 'id = ' . $id);
    }
}
