<?php
/**
 * Copyright (c) 2010 Endeavor Systems, Inc.
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
 * A base controller for the security control catalogs
 * 
 * @author     Mark E. Haase
 * @copyright  (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Controllers
 * @subpackage SUBPACKAGE
 * @version    $Id$
 */
class SecurityControlCatalogBaseController extends Fisma_Zend_Controller_Action_Security
{
    /**
     * A helper function to create the objects required to render the toolbar partial
     * 
     * @return Zend_Form
     */
    protected function _getToolbarForm()
    {
        $form = Fisma_Zend_Form_Manager::loadForm('security_control_catalog_toolbar');

        // Set up the available and default values for the form
        $form->getElement('id')
             ->addMultiOptions($this->_getCatalogs())
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
    
    /**
     * Get an array of catalogs by id and name, in a format that is compatible with 
     * Zend_Form_Element_Select#addMultiOptions()
     * 
     * @return array
     */
    protected function _getCatalogs()
    {
        // Get data for the select element. Columns are aliased as 'key' and 'value' for addMultiOptions().
        $catalogQuery = Doctrine_Query::create()
                        ->select('id AS key, name AS value')
                        ->from('SecurityControlCatalog')
                        ->orderBy('name')
                        ->setHydrationMode(Doctrine::HYDRATE_ARRAY);

        return $catalogQuery->execute();
    }
}
