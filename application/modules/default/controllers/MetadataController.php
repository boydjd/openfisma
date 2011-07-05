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
 * A controller which provides metadata about models
 * 
 * These actions are all called asynchronously
 * 
 * @author     Mark E. Haase
 * @copyright  (c) Endeavor Systems, Inc. 2010 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Controller
 */
class MetadataController extends Zend_Controller_Action
{
    /**
     * Disable layouts in the preDispatch
     * 
     * None of these actions use layouts, since they 
     */
    public function preDispatch()
    {
        $this->_helper->layout->disableLayout();
    }
    
    /**
     * Return the enum values for a specified field in a specified model
     */
    public function enumAction()
    {
        $model = $this->getRequest()->getParam('model');
        $field = $this->getRequest()->getParam('field');
        
        $table = Doctrine::getTable($model);
        if (!$table) {
            throw new Fisma_Zend_Exception("Invalid model name ($model)");
        }
        
        $enumValues = $table->getEnumValues($field);
        if (!$enumValues) {
            throw new Fisma_Zend_Exception("Invalid field name ($field)");
        }

        $this->view->selectedValue = $this->getRequest()->getParam('value');        
        $this->view->enumValues = array_values($enumValues);
    }
}
