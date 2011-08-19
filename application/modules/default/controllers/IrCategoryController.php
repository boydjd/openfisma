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
 * CRUD behavior for incident categories
 * 
 * @author     Mark E. Haase
 * @copyright  (c) Endeavor Systems, Inc. 2010 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Controller
 */
class IrCategoryController extends Fisma_Zend_Controller_Action_Object
{
    /**
     * The main name of the model.
     * 
     * This model is the main subject which the controller operates on.
     * 
     * @var string
     */
    protected $_modelName = 'IrSubCategory';

    /**
     * Invoked before each Action
     */
    public function preDispatch()
    {
        parent::preDispatch();

        $module = Doctrine::getTable('Module')->findOneByName('Incident Reporting');

        if (!$module->enabled) {
            throw new Fisma_Zend_Exception('This module is not enabled.');
        }
    }

    /**
     * Override parent to populate selects with options
     *
     * @param string|null $formName The name of the specified form
     * @return Zend_Form The specified form of the subject model
     */
    public function getForm($formName = null)
    {
        $form = parent::getForm($formName);

        // Populate categories select
        $categoryQuery = Doctrine_Query::create()
                         ->from('IrCategory')
                         ->select('id', 'category')
                         ->orderBy('category');

        $categories = $categoryQuery->execute()->toKeyValueArray('id', 'category');
        
        $form->getElement('categoryId')->setMultiOptions($categories);
        
        // Populate workflows select
        $workflowQuery = Doctrine_Query::create()
                         ->from('IrWorkflowDef')
                         ->select('id', 'name');
        $workflows = $workflowQuery->execute()->toKeyValueArray('id', 'name');

        $form->getElement('workflowId')->setMultiOptions($workflows);

        return $form;
    }

    /**
     * Override to provide a better singular name
     */
    public function getSingularModelName()
    {
        return 'Incident Category';
    }

    /**
     * Override to provide a better plural name
     */
    public function getPluralModelName()
    {
        return 'Incident Categories';
    }
    
    protected function _isDeletable()
    {
        return false;
    }
}
