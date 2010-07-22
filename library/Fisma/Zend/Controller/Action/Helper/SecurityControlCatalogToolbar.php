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
 * Fisma_Zend_Controller_Action_Helper_SecurityControlCatalogToolbar 
 * 
 * @uses Zend_Controller_Action_Helper_Abstract
 * @package Fisma_Zend_Controller_Action_Helper 
 * @copyright (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @author Josh Boyd <joshua.boyd@endeavorsystems.com> 
 * @license http://www.openfisma.org/content/license GPLv3
 */
class Fisma_Zend_Controller_Action_Helper_SecurityControlCatalogToolbar extends Zend_Controller_Action_Helper_Abstract
{
    /**
     * A helper function to create the objects required to render the toolbar partial
     * 
     * @return Zend_Form
     */
    public function direct()
    {
        $form = Fisma_Zend_Form_Manager::loadForm('security_control_catalog_toolbar');

        // Set up the available and default values for the form
        $form->getElement('id')
             ->addMultiOptions(Doctrine::getTable('SecurityControlCatalog')->getCatalogs())
             ->setValue(Fisma::configuration()->getConfig('default_security_control_catalog_id'));
        
        $form->setDefaults($this->getRequest()->getParams());

        // These are standard decorators
        $form->setDecorators(
            array(
                'FormElements',
                array('HtmlTag', array('tag' => 'span')),
                'Form'
            )
        );
            
        // View helper and label decorators on all elements except the search button
        $form->setElementDecorators(array('ViewHelper', 'Label'));
        $form->getElement('search')->removeDecorator('Label');
        
        return $form;
    }
}
