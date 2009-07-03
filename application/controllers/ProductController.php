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
class ProductController extends BaseController
{
    protected $_modelName = 'Product';
    
    /**
     * Delete a product
     */
    public function deleteAction()
    {
        Fisma_Acl::requirePrivilege('product', 'delete');
        
        $id = $this->_request->getParam('id');
        $product = Doctrine::getTable('Product')->find($id);
        if (!$product) {
            /** @todo english */
            $msg   = "Invalid Product";
            $type = self::M_WARNING;
        } else {
            $assets = $product->Assets->toArray();
            if (!empty($assets)) {
                /** 
                 * @todo english
                 */
                $msg = 'This product can not be deleted because it is already associated with one or more ASSETS';
                $type = self::M_WARNING;
            } else {
                parent::deleteAction();
                // parent method will take care 
                // of the message and forword the page
                return;
            }
        }
        $this->message($msg, $type);
        $this->_forward('list');
    }
    
    /**
     * Render the form for searching the products
     */
    public function advancesearchAction()
    {
        Fisma_Acl::requirePrivilege('product', 'read');
        $this->_helper->layout->setLayout('ajax');
        $product = new Product();
        $req = $this->getRequest();
        $prodId = $req->getParam('prod_list', '');
        $prodName = $req->getParam('prod_name', '');
        $prodVendor = $req->getParam('prod_vendor', '');
        $prodVersion = $req->getParam('prod_version', '');
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
        $this->view->prod_list = $qry->execute()->toArray();
    }
}
