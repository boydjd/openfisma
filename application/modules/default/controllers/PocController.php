<?php
/**
 * Copyright (c) 2011 Endeavor Systems, Inc.
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
 * PocController
 *
 * @author     Andrew Reeves <andrew.reeves@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2011 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Controller
 */
class PocController extends Fisma_Zend_Controller_Action_Object
{
    /**
     * The main name of the model.
     * 
     * @var string
     */
    protected $_modelName = 'Poc';

    /**
     * Override to provide a better singular name
     * 
     * @return string
     */
    public function getSingularModelName()
    {
        return 'Point of Contact';
    }
   
    /**
     * Override base class to prevent deletion of POC objects
     *
     * @return boolean
     */
    protected function _isDeletable()
    {
        return false;
    }

    /**
     * Set up context switch
     */
    public function init()
    {
        parent::init();

        $this->_helper->fismaContextSwitch()
                      ->addActionContext('create', 'json')
                      ->addActionContext('autocomplete', 'json')
                      ->initContext();
    }

    /**
     * Override to fill in option values for the select elements, etc.
     *
     * @param string|null $formName The name of the specified form
     * @return Zend_Form The specified form of the subject model
     */
    public function getForm($formName = null)
    {
        $form = parent::getForm($formName);

        $authType = Fisma::configuration()->getConfig('auth_type');
        if ($authType == 'database') {
            // Remove the "Check Account" button if we're not using external authentication
            $form->removeElement('checkAccount');
        } elseif ($authType == 'ldap') {
            $form->getElement('nameFirst')->setAttrib("readonly", "readonly");
            $form->getElement('nameLast')->setAttrib("readonly", "readonly");
            $form->getElement('email')->setAttrib("readonly", "readonly");
        }
        
        // Populate <select> for responsible organization
        $organizations = Doctrine::getTable('Organization')->getOrganizationSelectQuery()->execute();
        $selectArray = $this->view->systemSelect($organizations);
        $form->getElement('reportingOrganizationId')->addMultiOptions($selectArray);

        return $form;
    }

    /**
     * A helper action for autocomplete text boxes
     */
    public function autocompleteAction()
    {
        $keyword = $this->getRequest()->getParam('keyword');

        $pocQuery = Doctrine_Query::create()
                    ->from('Poc p')
                    ->select("p.id")
                    ->addSelect("CONCAT(p.username, ' [', p.nameFirst, ' ', p.nameLast, ']') AS name")
                    ->where('p.nameLast LIKE ?', "$keyword%")
                    ->orWhere('p.nameFirst LIKE ?', "$keyword%")
                    ->orWhere('p.username LIKE ?', "$keyword%")
                    ->orderBy("p.nameFirst")
                    ->setHydrationMode(Doctrine::HYDRATE_ARRAY);

        $this->view->pointsOfContact = $pocQuery->execute();
    }

    /**
     * Display the POC form without any layout
     */
    public function formAction()
    {
        $this->_helper->layout()->disableLayout();
        
        // The standard form needs to be modified to work inside a modal yui dialog
        $form = $this->getForm();
        $submit = $form->getElement('save');
        $submit->onClickFunction = 'Fisma.Finding.createPoc';

        $this->view->form = $form;
    }

    /**
     * Override _viewObject to work around the permission wonkiness.
     * 
     * A POC can also be a User. If a person has the read/poc privilege but not read/user, then the person won't be
     * able to view a User object. So we work around that right here.
     */
    protected function _viewObject()
    {
        $this->_acl->requirePrivilegeForClass('read', 'Poc');

        $this->_enforceAcl = false;
        parent::_viewObject();
        $this->_enforceAcl = true;
    }

    /**
     * Override _editObject to work around the permission wonkiness.
     * 
     * A POC can also be a User. If a person has the update/poc privilege but not update/user, then the person won't be
     * able to modify a User object. So we work around that right here.
     */
    protected function _editObject()
    {
        $this->_acl->requirePrivilegeForClass('update', 'Poc');

        $this->_enforceAcl = false;
        parent::_editObject();
        $this->_enforceAcl = true;
    }
}
