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
 * The configuration controller deals with displaying and updating system
 * configuration items through the user interface.
 *
 * @author     Ben Zheng <ben.zheng@reyosoft.com>
 * @copyright  (c) Endeavor Systems, Inc. 2011 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Controller
 */
class Finding_ConfigController extends Fisma_Zend_Controller_Action_Security
{
    /**
     * Hook into the pre-dispatch to do an ACL check
     */
    public function preDispatch()
    {
        parent::preDispatch();

        $this->_acl->requireArea('finding_admin');
    }

    /**
     * Returns the standard form for system configuration
     *
     * @param string $formName The name of the form to load
     * @return Zend_Form The loaded form
     */
    private function _getConfigForm($formName)
    {
        // Load the form and populate the dynamic pull downs
        $form = Fisma_Zend_Form_Manager::loadForm($formName);
        $form = Fisma_Zend_Form_Manager::prepareForm($form);

        return $form;
    }

    /**
     * Display and update the persistent configurations
     *
     * @GETAllowed
     * @return void
     */
    public function generalAction()
    {
        $form = $this->_getConfigForm('finding_general_config');

        $selectValues = array(
            'default_bureau_id' => Doctrine::getTable('OrganizationType')->getOrganizationTypeArray(),
            'threat_type' => array(
                'threat_level' => 'Threat Level',
                'residual_risk' => 'Residual Risk'
            )
        );

        // Populate default values for non-submit button elements
        foreach ($form->getElements() as $element) {
            if ($element instanceof Zend_Form_Element_Submit) {
                continue;
            }

            $name = $element->getName();

            if ($element instanceof Zend_Form_Element_Select) {
                $element->setMultiOptions($selectValues[$name])
                        ->setRegisterInArrayValidator(false);
            }

            $element->setValue(Fisma::configuration()->getConfig($name));
        }

        if ($this->_request->isPost()) {
            $config = $this->_request->getPost();

            if ($form->isValid($config)) {
                $values = $form->getValues();

                foreach ($values as $item => &$value) {
                    Fisma::configuration()->setConfig($item, $value);
                }

                $this->view->priorityMessenger('Configuration updated successfully', 'notice');
            } else {
                $errorString = Fisma_Zend_Form_Manager::getErrors($form);
                $this->view->priorityMessenger("Unable to save configurations:<br>$errorString", 'warning');
            }
        }

        $this->view->form = $form;
    }
}
