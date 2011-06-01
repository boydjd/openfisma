<?php
/**
 * Copyright (c) 2009 Endeavor Systems, Inc.
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
 */
class Vm_ProductController extends Fisma_Zend_Controller_Action_Object
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
     * Set up context switch
     */
    public function init()
    {
        parent::init();

        $this->_helper->fismaContextSwitch()
                      ->addActionContext('autocomplete', 'json')
                      ->initContext();
    }

    /**
     * Handle autocomplete requests
     */
    public function autocompleteAction()
    {
        $keyword = $this->getRequest()->getParam('keyword');
        
        if (empty($keyword)) {
            $products = array();
        } else {
            $productQuery = Doctrine_Query::create()
                            ->from('Product p')
                            ->select('p.id, p.name')
                            ->where('p.name LIKE ?', "$keyword%")
                            ->orderBy('p.name')
                            ->limit(50)
                            ->setHydrationMode(Doctrine::HYDRATE_ARRAY);

            $products = $productQuery->execute();
        }

        $this->view->products = $products;
    }
    
    protected function _isDeletable()
    {
        return false;
    }
}
