<?php
/**
 * Copyright (c) 2008 Endeavor Systems, Inc.
 *
 * This file is part of OpenFISMA.
 *
 * OpenFISMA is free software: you can redistribute it and/or modify it under the terms of the GNU General Public 
 * License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later
 * version.
 *
 * OpenFISMA is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied 
 * warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for more 
 * details.
 *
 * You should have received a copy of the GNU General Public License along with OpenFISMA.  If not, see 
 * {@link http://www.gnu.org/licenses/}.
 */

/**
 * Handles CRUD for product objects.
 *
 * @author     Ryan Yang <ryan@users.sourceforge.net>
 * @copyright  (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Controller
 * @version    $Id$
 */
class ProductController extends BaseController
{
    /**
     * The main name of the model.
     * 
     * This model is the main subject which the controller operates on.
     * 
     * @var string
     */
    protected $_modelName = 'Product';
    
    /**
     * Delete a product
     * 
     * @return void
     */
    public function deleteAction()
    {        
        $id = $this->_request->getParam('id');
        $product = Doctrine::getTable('Product')->find($id);
        if (!$product) {
            $msg   = "Invalid Product ID";
            $type = 'warning';
        } else {
            Fisma_Acl::requirePrivilegeForObject('delete', $product);
            
            $assets = $product->Assets->toArray();
            if (!empty($assets)) {
                $msg = 'This product can not be deleted because it is already associated with one or more assets';
                $type = 'warning';
            } else {
                parent::deleteAction();
                // parent method will take care 
                // of the message and forword the page
                return;
            }
        }
        $this->view->priorityMessenger($msg, $type);
        $this->_forward('list');
    }
    
    /**
     * Render the form for searching the products
     * 
     * @return void
     */
    public function advancesearchAction()
    {
        Fisma_Acl::requirePrivilegeForObject('read', 'Product');

        $this->_helper->layout->setLayout('ajax');
        $product = new Product();
        $req = $this->getRequest();
        $prodId = $req->getParam('prodList', '');
        $prodName = $req->getParam('prodName', '');
        $prodVendor = $req->getParam('prodVendor', '');
        $prodVersion = $req->getParam('prodVersion', '');
        $qry = Doctrine_Query::create()
               ->select()
               ->from('Product');
        if (!empty($prodName)) {
            $qry->andWhere("name like ?", "%$prodName%");
            $this->view->prodName = $prodName;
        }
        if (!empty($prodVendor)) {
            $qry->andWhere("vendor like ?", "%$prodVendor%");
            $this->view->prodVendor = $prodVendor;
        }
        if (!empty($prodVersion)) {
            $qry->andWhere("version like ?", "%$prodVersion%");
            $this->view->prodVersion = $prodVersion;
        }
        $qry->limit(100)
            ->offset(0);
        $this->view->prodList = $qry->execute()->toArray();
    }
}
