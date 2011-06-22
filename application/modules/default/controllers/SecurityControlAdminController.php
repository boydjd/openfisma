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
 * Administration for the security control catalog controller
 * 
 * @author     Mark E. Haase
 * @copyright  (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Controllers
 * @subpackage SUBPACKAGE
 */
class SecurityControlAdminController extends Fisma_Zend_Controller_Action_Security
{
    /**
     * Check ACL and setup header/footer
     */
    public function preDispatch()
    {
        parent::preDispatch();
        
        $this->_acl->requireArea('system_inventory_admin');
    }

    /**
     * Display administrative configuration options
     */
    public function indexAction()
    {
        $form = Fisma_Zend_Form_Manager::loadForm('security_control_catalog_admin');
        
        Fisma_Zend_Form_Manager::prepareForm($form);
        
        $currentSetting = Fisma::configuration()->getConfig('default_security_control_catalog_id');
        
        $form->getElement('defaultCatalog')
             ->addMultiOptions(Doctrine::getTable('SecurityControlCatalog')->getCatalogs())
             ->setValue($currentSetting);
        
        $this->view->form = $form;
    }
    
    /**
     * Persist any changes to the administration section and redirect back to the admin action
     */
    public function saveAction()
    {
        $newDefaultCatalogId = (int)$this->getRequest()->getParam('defaultCatalog');
        
        // Validate that the supplied ID actually exists
        $newDefaultCatalog = Doctrine::getTable('SecurityControlCatalog')->find($newDefaultCatalogId);
        
        if ($newDefaultCatalog) {
            Fisma::configuration()->setConfig('default_security_control_catalog_id', $newDefaultCatalogId);
            
            $this->view->priorityMessenger('Configuration saved.', 'notice');
        } else {
            $this->view->priorityMessenger("Invalid catalog id ($newDefaultCatalogId)", 'warning');
        }
        
        $this->_redirect('/security-control-admin/index');
    }
}
