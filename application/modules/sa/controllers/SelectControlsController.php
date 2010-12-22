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
 * Allow users to add/remove controls and enhancements within a security authroization.
 *
 * @author     Andrew Reeves <andrew.reeves@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2010 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    SA
 */
class Sa_SelectControlsController extends Fisma_Zend_Controller_Action_Security
{
    /**
     * Initialize internal members.
     * 
     * @return void
     */
    public function init()
    {
        parent::init();
        $this->_helper->contextSwitch()
                      ->addActionContext('control-tree-data', 'json')
                      ->addActionContext('remove-control', 'json')
                      ->addActionContext('remove-enhancement', 'json')
                      ->initContext();
        $this->_helper->ajaxContext()
                      ->addActionContext('add-control', 'html')
                      ->addActionContext('add-enhancements', 'html')
                      ->addActionContext('edit-common-control', 'html')
                      ->initContext();
    }
    
    /**
     * @return void
     */
    public function indexAction()
    {
        $this->view->id = $this->_request->getParam('id');
        $this->view->sa = Doctrine::getTable('SecurityAuthorization')->find($this->view->id);

        $this->view->goBack = new Fisma_Yui_Form_Button_Link(
            'goBack', 
            array(
                'value' => 'Go Back',
                'imageSrc' => '/images/left_arrow.png',
                'href' => '/sa/security-authorization/view/id/' . $this->view->id
            )
        );
        
        $this->view->expandAll = new Fisma_Yui_Form_Button(
            'expandAll',
            array(
                'label' => 'Expand All',
                'imageSrc' => '/images/expand.png',
                'onClickFunction' => 'expandAll'
            )
        );
        
        $this->view->collapseAll = new Fisma_Yui_Form_Button(
            'collapseAll',
            array(
                'label' => 'Collapse All',
                'imageSrc' => '/images/collapse.png',
                'onClickFunction' => 'collapseAll'
            )
        );
        
        $this->view->addControl = new Fisma_Yui_Form_Button(
            'addControl',
            array('label' => 'Add Control', 'onClickFunction' => 'addControl')
        );
        
        $this->view->completeSelection = new Fisma_Yui_Form_Button(
            'completeSelection',
             array('label' => 'Complete Selection', 'onClickFunction' => 'completeSelect')
        );

        $completeForm = new Fisma_Zend_Form();
        $completeForm->setAction('/sa/security-authorization/complete-step')
                     ->setAttrib('id', 'completeForm')
                     ->addElement(new Zend_Form_Element_Hidden('id'))
                     ->addElement(new Zend_Form_Element_Hidden('step'))
                     ->setElementDecorators(array('ViewHelper'))
                     ->setDefaults(array('id' => $this->view->id, 'step' => 'Select'));
        $this->view->completeForm = $completeForm;
    }
   
    /**
     * @return void
     */
    public function removeControlAction()
    {
        $id = $this->_request->getParam('id');
        $controlId = $this->_request->getParam('securityControlId');
        $this->view->securityAuthorizationId = $id;
        $this->view->controlId = $controlId;

        $saScCollection = Doctrine_Query::create()
            ->from('SaSecurityControl saSc')
            ->leftJoin('saSc.SaSecurityControlEnhancement saSce')
            ->where('saSc.securityAuthorizationId = ?', $id)
            ->andWhere('saSc.securityControlId = ?', $controlId)
            ->execute();
        $this->view->saSc = $saScCollection->toArray(true);

        foreach ($saScCollection as $saSc) {
            foreach ($saSc->SaSecurityControlEnhancement as $saSce) {
                $saSce->delete();
            }
            $saSc->delete();
        }
        $this->view->result = 'ok';
    }

    /**
     * @return void
     */
    public function removeEnhancementAction()
    {
        $id = $this->_request->getParam('id');
        $enhancementId = $this->_request->getParam('securityControlEnhancementId');
        $this->view->securityAuthorizationId = $id;
        $this->view->controlEnhancementId = $enhancementId;

        $saSceCollection = Doctrine_Query::create()
            ->from('SaSecurityControlEnhancement saSce')
            ->innerJoin('saSce.SaSecurityControl saSc')
            ->where('saSc.securityAuthorizationId = ?', $id)
            ->andWhere('saSce.securityControlEnhancementId = ?', $enhancementId)
            ->execute();
        $this->view->saSce = $saSceCollection->toArray(true);

        foreach ($saSceCollection as $saSce) {
            $saSce->delete();
        }
        $this->view->result = 'ok';
    }

    /**
     * @return void
     */
    public function controlTreeDataAction() 
    {
        $id = $this->_request->getParam('id');
        $controls = Doctrine_Query::create()
            ->from('SaSecurityControl saSC')
            ->leftJoin('saSC.SecurityControl control')
            ->leftJoin('saSC.SecurityControlEnhancements enhancements')
            ->leftJoin('saSC.Inherits inSys')
            ->where('saSC.securityAuthorizationId = ?', $id)
            ->orderBy('control.code')
            ->execute();

        $data = array();
        foreach ($controls as $saControl) {
            $enhancements = array();
            $control = $saControl->SecurityControl;
            foreach ($saControl->SecurityControlEnhancements as $enhancement) {
                $enhancements[] = array(
                    'id' => $enhancement->id,
                    'number' => $enhancement->number,
                    'description' => $enhancement->description
                );
            }
            $data[$control->family][] = array(
                'id' => $control->id,
                'code' => $control->code,
                'name' => $control->name,
                'enhancements' => $enhancements,
                'common' => $saControl->common ? true : false,
                'inherits' => is_null($saControl->Inherits) ? null : $saControl->Inherits->nickname
            );
        }
        $this->view->treeData = $data;
    }

    /**
     * @return void
     */
    public function addControlAction()
    {
        $id = $this->_request->getParam('id');
        if ($this->_request->isPost()) {
            $post = $this->_request->getPost();
            $saSc = new SaSecurityControl();
            $saSc->merge($post);
            $saSc->save();
            $this->_redirect('/sa/select-controls/index/id/'.$id);
            return;
        }

        // get list of controls for the form
        $currentControls = Doctrine_Query::create()
            ->from('SecurityControl sc')
            ->innerJoin('sc.SaSecurityControls saSc')
            ->innerJoin('saSc.SecurityAuthorization sa')
            ->where('sa.id = ?', array($id))
            ->execute()
            ->toKeyValueArray('id', 'id');
        $this->view->currentControls = $currentControls;
        $catalogId = Fisma::configuration()->getConfig('default_security_control_catalog_id');
        $controls = Doctrine_Query::create()
            ->from('SecurityControl sc')
            ->whereNotIn('sc.id', $currentControls)
            ->andWhere('sc.securityControlCatalogId = ?', $catalogId)
            ->execute();
        $controlArray = array();
        foreach ($controls as $control) {
            $controlArray[$control->id] = $control->code . ' ' . $control->name;
        }

        // build form
        $form = $this->getForm('securityauthorizationaddcontrol');
        $form->setAction('/sa/select-controls/add-control/id/'.$id);
        $form->setDefault('securityAuthorizationId', $id);
        $form->getElement('securityControlId')->addMultiOptions($controlArray);
        $this->view->id = $id;
        $this->view->addControlForm = $form;
    }

    /**
     * @return void
     */
    public function addEnhancementsAction()
    {
        $id = $this->_request->getParam('id');
        $securityControlId = $this->_request->getParam('securityControlId');

        $saSecurityControl = Doctrine_Query::create()
            ->from('SaSecurityControl saSc')
            ->where('saSc.securityAuthorizationId = ?', $id)
            ->andWhere('saSc.securityControlId = ?', $securityControlId)
            ->execute();
        $saSecurityControl = $saSecurityControl[0];

        if ($this->_request->isPost()) {
            $post = $this->_request->getPost();
            foreach ($post['securityControlEnhancementIds'] as $securityControlEnhancementId) {
                $saSce = new SaSecurityControlEnhancement();
                $saSce->saSecurityControlId = $saSecurityControl->id;
                $saSce->securityControlEnhancementId = $securityControlEnhancementId;
                $saSce->save();
                $saSce->free();
            }
            $this->_redirect('/sa/select-controls/index/id/'.$id);
            return;
        }

        $this->view->id = $id;
        $this->view->securityControlId = $securityControlId;

        $currentControlEnhancements = Doctrine_Query::create()
            ->from('SecurityControlEnhancement sce')
            ->innerJoin('sce.SaSecurityControl saSc')
            ->where('saSc.securityAuthorizationId = ?', $id)
            ->andWhere('saSc.securityControlId = ?', $securityControlId)
            ->execute()
            ->toKeyValueArray('id', 'id');
        $this->view->currentControlEnhancementss = $currentControlEnhancements;

        $enhancementObjects = Doctrine_Query::create()
            ->from('SecurityControlEnhancement sce')
            ->whereNotIn('sce.id', $currentControlEnhancements)
            ->andWhere('sce.securityControlId = ?', $securityControlId)
            ->execute();
        $enhancements = array();
        foreach ($enhancementObjects as $enh) {
            $enhancements[$enh->id] = $enh->Control->code . " (" . $enh->number . ")";
        }
        $this->view->availableEnhancements = $enhancements;

        // build form
        $form = $this->getForm('securityauthorizationaddenhancements');
        $form->setAction(
            '/sa/select-controls/add-enhancements/id/'.$id . '/securityControlId/' . $securityControlId
        );
        $form->getElement('securityControlEnhancementIds')->addMultiOptions($enhancements);
        $this->view->form = $form;
    }

    /**
     * @return void
     */
    public function editCommonControlAction()
    {
        $id = $this->_request->getParam('id');
        $securityControlId = $this->_request->getParam('securityControlId');

        $saSecurityControl = Doctrine_Query::create()
            ->from('SaSecurityControl saSc')
            ->innerJoin('saSc.SecurityAuthorization sa')
            ->where('saSc.securityAuthorizationId = ?', $id)
            ->andWhere('saSc.securityControlId = ?', $securityControlId)
            ->fetchOne();

        if ($this->_request->isPost()) {
            $post = $this->_request->getPost();
            $common = $post['common'];
            $sysOrgId = $post['sysOrgId'];
            $saSecurityControl->common = $common == 'common';
            $saSecurityControl->inheritsId = $common == 'inherits' ? $sysOrgId : null;
            $saSecurityControl->save();
            $saSecurityControl->free();
            $this->_redirect('/sa/select-controls/index/id/'.$id);
            return;
        }

        $this->view->id = $id;
        $this->view->securityControlId = $securityControlId;

        // get a list of systems from which to inherit
        $commonSysOrgs = Doctrine_Query::create()
            ->from('Organization org')
            ->leftJoin('org.SecurityAuthorizations sa')
            ->leftJoin('sa.SaSecurityControls saSc')
            ->where('org.id != ?', $saSecurityControl->SecurityAuthorization->sysOrgId)
            ->andWhere('saSc.common = ?', true)
            ->orderBy('org.nickname')
            ->execute();
        $commonSysOrgs = $this->view->systemSelect($commonSysOrgs);

        // build form
        $form = $this->getForm('securityauthorization_editcommoncontrol');
        $form->setAction(
            '/sa/select-controls/edit-common-control/id/'.$id . '/securityControlId/' . $securityControlId
        );
        $inheritsId = $form->getElement('sysOrgId');
        $common = $form->getElement('common');
        $inheritsId->addMultiOptions($commonSysOrgs);
        if ($saSecurityControl->common) {
            $common->setValue('common');
        } else if (!empty($saSecurityControl->inheritsId)) {
            $common->setValue('inherits');
            $inheritsId->setValue($saSecurityControl->inheritsId);
        } else {
            $common->setValue('none');
        }
        $this->view->form = $form;
    }

    /**
     * Get the specified form
     * Adapted from Fisma_Zend_Controller_Action_Object
     *
     * @param string $formName The name of the specified form
     * @return Zend_Form The specified form of the subject model
     */
    public function getForm($formName)
    {
        $form = Fisma_Zend_Form_Manager::loadForm($formName);
        $form = Fisma_Zend_Form_Manager::prepareForm(
            $form, 
            array(
                'formName' => ucfirst($formName), 
                'view' => $this->view, 
                'request' => $this->_request, 
                'acl' => $this->_acl, 
                'user' => $this->_me
            )
        );
        return $form;
    }

}
