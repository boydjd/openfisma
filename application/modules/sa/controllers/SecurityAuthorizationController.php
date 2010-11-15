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
 * The index controller implements the default action when no specific request
 * is made.
 *
 * @author     Andrew Reeves <andrew.reeves@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2010 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    SA
 */
class Sa_SecurityAuthorizationController extends Fisma_Zend_Controller_Action_Object
{
    /**
     * The main name of the model.
     * 
     * This model is the main subject which the controller operates on.
     * 
     * @var string
     */
    protected $_modelName = 'SecurityAuthorization';
    
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
        $this->_forward('list');
    }

    /**
     * Return array of the collection.
     * 
     * @param Doctrine_Collections $rows The spepcific Doctrine_Collections object
     * @return array The array representation of the specified Doctrine_Collections object
     */
    public function handleCollection($rows)
    {
        $result = $rows->toArray();
        foreach ($rows as $k => $v) {
            $result[$k]['system'] = $v->Organization->name;
        }
        return $result;
    }

    /**
     * Override parent to add in extra relations
     *
     * @param Doctrine_Query $query Query to be modified
     * @return Doctrine_Collection Results of search query
     */
    public function executeSearchQuery(Doctrine_Query $query)
    {
        // join in System relation
        $alias = $query->getRootAlias();
        $query->leftJoin($alias . '.Organization org');
        $query->addSelect('org.id, org.name');
        return parent::executeSearchQuery($query);
    }

    /**
     * Hooks for manipulating and saving the values retrieved by Forms
     *
     * @param Zend_Form $form The specified form
     * @param Doctrine_Record|null $subject The specified subject model
     * @return integer ID of the object saved. 
     * @throws Fisma_Zend_Exception if the subject is not instance of Doctrine_Record
     */
    protected function saveValue($form, $subject=null)
    {
        $sa = $subject;

        /** 
         * if subject is null we need to add in the impact from the system before passing the form onto the save
         * method.
         */
        if (is_null($subject)) {
            // fetch the system and use its impact values to set the impact of this SA
            $org = Doctrine::getTable('Organization')->find($form->getValue(sysOrgId));
            $system = $org->System;
            if (empty($system)) {
                throw new Fisma_Exception('A non-system was set to the Security Authorization');
            }

            $sa = new SecurityAuthorization();
    
            $impacts = array(
                $system->confidentiality,
                $system->integrity,
                $system->availability
            );
            if (in_array('HIGH', $impacts)) {
                $sa->impact = 'HIGH';
            } else if (in_array('MODERATE', $impacts)) {
                $sa->impact = 'MODERATE';
            } else {
                $sa->impact = 'LOW';
            }
        }
        
        // call default implementation and save the ID
        $saId = parent::saveValue($form, $sa);

        // if subject null, we're creating a new object and we need to populate relations
        if (is_null($subject)) {
            $impact = $sa->impact;
            $controlLevels = array();
            switch($impact) {
                case 'HIGH':
                    $controlLevels[] = 'HIGH';
                case 'MODERATE':
                    $controlLevels[] = 'MODERATE';
                default:
                    $controlLevels[] = 'LOW';
            }
            $catalogId = Fisma::configuration()->getConfig('default_security_control_catalog_id');

            // associate suggested controls
            $controls = Doctrine_Query::create()
                ->from('SecurityControlCatalog scc')
                ->leftJoin('scc.Controls sc')
                ->leftJoin('sc.Enhancements sce')
                ->where('scc.id = ?', array($catalogId))
                ->andWhereIn('sc.controlLevel', $controlLevels)
                ->andWhere('sce.id IS NULL')
                ->orWhereIn('sce.level', $controlLevels)
                ->execute();
            $controls = $controls[0]->Controls;
            foreach ($controls as $control) {
                $sacontrol = new SaSecurityControl();
                $sacontrol->securityAuthorizationId = $sa->id;
                $sacontrol->securityControlId = $control->id;
                $sacontrol->save();
                foreach ($control->Enhancements as $ce) {
                    $sace = new SaSecurityControlEnhancement();
                    $sace->securityControlEnhancementId = $ce->id;
                    $sace->saSecurityControlId = $sacontrol->id;
                    $sace->save();
                    $sace->free();
                }
                $sacontrol->free();
            }
            $controls->free();
            unset($controls);
        }

        return $saId;
    }

    /**
     * @return void
     */
    public function controlTreeAction()
    {
        $this->view->id = $this->_request->getParam('id');
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
                    'description' => $enhancement->description
                );
            }
            $data[$control->family][] = array(
                'id' => $control->id,
                'code' => $control->code,
                'name' => $control->name,
                'enhancements' => $enhancements,
                'common' => $saControl->common ? true : false,
                'inherits' => $saControl->Inherits->nickname
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
            $this->_redirect('/sa/security-authorization/control-tree/id/'.$id);
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
        $form->setAction('/sa/security-authorization/add-control/id/'.$id);
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
            $this->_redirect('/sa/security-authorization/control-tree/id/'.$id);
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

        $enhancements = Doctrine_Query::create()
            ->from('SecurityControlEnhancement sce')
            ->whereNotIn('sce.id', $currentControlEnhancements)
            ->andWhere('sce.securityControlId = ?', $securityControlId)
            ->execute()
            ->toKeyValueArray('id', 'description');
        $this->view->availableEnhancements = $enhancements;

        // build form
        $form = $this->getForm('securityauthorizationaddenhancements');
        $form->setAction(
            '/sa/security-authorization/add-enhancements/id/'.$id . '/securityControlId/' . $securityControlId
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
            $this->_redirect('/sa/security-authorization/control-tree/id/'.$id);
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
            '/sa/security-authorization/edit-common-control/id/'.$id . '/securityControlId/' . $securityControlId
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
}
