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
     * Initialize internal members.
     * 
     * @return void
     */
    public function init()
    {
        parent::init();
        $this->_helper->contextSwitch()
                      ->addActionContext('control-table-master', 'json')
                      ->addActionContext('control-table-nested', 'json')
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
     * The main name of the model.
     * 
     * This model is the main subject which the controller operates on.
     * 
     * @var string
     */
    protected $_modelName = 'SecurityAuthorization';

    /**
     * @var array
     */
    public $_status = array('Implement' => 0,
                            'Assessment' => 0,
                            'Authorization' => 0,
                            );

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
            $org = Doctrine::getTable('Organization')->find($form->getValue('sysOrgId'));
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
            $catalogId = Fisma::configuration()->getConfig('default_security_control_catalog_id');

            // associate suggested controls
            $controls = Doctrine::getTable('SecurityControl')
                ->getCatalogIdAndImpactQuery($catalogId, $sa->impact)
                ->execute();
            foreach ($controls as $control) {
                $sacontrol = new SaSecurityControl();
                $sacontrol->securityAuthorizationId = $sa->id;
                $sacontrol->securityControlId = $control->id;
                $sacontrol->save();
                $sacontrol->free();
            }
            $controls->free();
            unset($controls);

            // associate suggested enhancements
            $sacontrols = Doctrine::getTable('SaSecurityControl')
                ->getEnhancementsForSaAndImpactQuery($sa->id, $sa->impact)
                ->execute();
            foreach ($sacontrols as $sacontrol) {
                $control = $sacontrol->SecurityControl;
                foreach ($control->Enhancements as $ce) {
                    $sace = new SaSecurityControlEnhancement();
                    $sace->securityControlEnhancementId = $ce->id;
                    $sace->saSecurityControlId = $sacontrol->id;
                    $sace->save();
                    $sace->free();
                }
                $sacontrol->free();
            }
            $sacontrols->free();
            unset($sacontrols);
        }

        return $saId;
    }

    public function implementationAction()
    {
        $this->_helper->layout()->disableLayout();
        $this->_acl->requirePrivilegeForClass('read', 'AssessmentPlanEntry');
        $this->view->id = $this->_request->getParam('id');
        $dataTable = new Fisma_Yui_DataTable_Remote();
        $dataTable->addColumn(new Fisma_Yui_DataTable_Column('ID', true, null, null, 'id', true))
                  ->addColumn(new Fisma_Yui_DataTable_Column('Control', true, null, null, 'code'))
                  ->addColumn(new Fisma_Yui_DataTable_Column('Enhancement', true, null, null, 'enhancement'))
                  ->addColumn(new Fisma_Yui_DataTable_Column('Status', true, null, null, 'status'))
                  ->setDataUrl('/sa/implementation/search/said/' . $this->view->id)
                  ->setResultVariable('table.records')
                  ->setRowCount(20)
                  ->setInitialSortColumn('id')
                  ->setSortAscending(true)
                  ->setClickEventBaseUrl('/sa/implementation/view/id/')
                  ->setClickEventVariableName('id');
        $this->view->dataTable = $dataTable;
 
        $sa = Doctrine::getTable('SecurityAuthorization')->find($this->view->id);
        $this->view->sa = $sa;
        $this->view->progress = $this->_implementationProgress($sa);
        $buttonbar = array();

        $buttonbar[] = new Fisma_Yui_Form_Button(
            'completeImplementation',
             array('label' => 'Complete Implementation', 'onClickFunction' => 'submitCompleteForm')
        );

        $buttonbar[] = $this->_createCompleteStepForm($sa);

        $this->view->buttonbar = $buttonbar;
    }

    public function assessmentPlanAction()
    {
        $this->_helper->layout()->disableLayout();
        $this->_acl->requirePrivilegeForClass('read', 'AssessmentPlanEntry');
        $this->view->id = $this->_request->getParam('id');
        $this->view->dataTable = $this->_baseAssessmentPlanDataTable();

        $sa = Doctrine::getTable('SecurityAuthorization')->find($this->view->id);
        $this->view->sa = $sa;
        $buttonbar = array();

        if ($sa->status == 'Assessment Plan') {
            $buttonbar[] = new Fisma_Yui_Form_Button(
                'completeAssessmentPlan',
                 array('label' => 'Complete Assessment Plan', 'onClickFunction' => 'submitCompleteForm')
            );
        } else if ($sa->status == 'Assessment') {
            $buttonbar[] = new Fisma_Yui_Form_Button(
                'completeAssessment',
                 array('label' => 'Complete Assessment', 'onClickFunction' => 'submitCompleteForm')
            );
        }

        $buttonbar[] = $this->_createCompleteStepForm($sa);

        $this->view->buttonbar = $buttonbar;
    }

    /**
     * @return void
     */
    public function authorizationAction()
    {
        $this->_helper->layout()->disableLayout();
        $this->view->id = $this->_request->getParam('id');
        $dataTable = $this->_baseAssessmentPlanDataTable();
        $dataTable->setDataUrl('/sa/assessment-plan-entry/search/said/' . $this->view->id . '/otherThanSatisfied/true')
                  ->addColumn(new Fisma_Yui_DataTable_Column('Finding', false, null, null, 'findingId'));
        $this->view->dataTable = $dataTable;

        $sa = Doctrine::getTable('SecurityAuthorization')->find($this->view->id);
        $this->view->sa = $sa;
        $buttonbar = array();
        $buttonbar[] = new Fisma_Yui_Form_Button(
            'completeAuthorization',
             array('label' => 'Complete Authorization', 'onClickFunction' => 'submitCompleteForm')
        );
        $buttonbar[] = $this->_createCompleteStepForm($sa);

        $this->view->buttonbar = $buttonbar;
    }

    /**
     * @return Fisma_Yui_DataTable_Abstract
     */
    protected function _baseAssessmentPlanDataTable()
    {
        $id = $this->_request->getParam('id');
        $dataTable = new Fisma_Yui_DataTable_Remote();
        $dataTable->addColumn(new Fisma_Yui_DataTable_Column('ID', true, null, null, 'id', true))
                  ->addColumn(new Fisma_Yui_DataTable_Column('Control', true, null, null, 'code'))
                  ->addColumn(new Fisma_Yui_DataTable_Column('Enhancement', true, null, null, 'enhancement'))
                  ->addColumn(new Fisma_Yui_DataTable_Column('Procedure', true, null, null, 'number'))
                  ->addColumn(new Fisma_Yui_DataTable_Column('Status', true, null, null, 'status'))
                  ->addColumn(new Fisma_Yui_DataTable_Column('Result', true, null, null, 'result'))
                  ->setDataUrl('/sa/assessment-plan-entry/search/said/' . $id)
                  ->setResultVariable('table.records')
                  ->setRowCount(20)
                  ->setInitialSortColumn('id')
                  ->setSortAscending(true)
                  ->setClickEventBaseUrl('/sa/assessment-plan-entry/view/id/')
                  ->setClickEventVariableName('id');
        return $dataTable;
    }

    /**
     * Action to complete a SA step and move to the next step.
     *
     * @return void
     */
    public function completeStepAction()
    {
        $id = $this->_getParam('id');
        $step  = $this->_getParam('step');

        if ($this->getRequest()->isPost()) {
            // update the SA to the next step
            $sa = Doctrine::getTable('SecurityAuthorization')->find($id);
            if ($sa != null) {
                $sa->completeStep($step);
                $sa->save();
            }
        }

        // redirect map
        $map = array(
            'Implement' => 'implementation',
            'Assessment Plan' => 'assessment-plan',
            'Assessment' => 'assessment-plan',
            'Authorization' => 'authorization',
            'Active' => 'view',
            'Retired' => 'view'
        );
        $url = '/sa/security-authorization/' . $map[$sa->status] . '/id/' . $sa->id;
        $this->_redirect($url);
    }

    protected function _createCompleteStepForm(SecurityAuthorization $sa)
    {
        $completeForm = new Fisma_Zend_Form();
        $completeForm->setAction('/sa/security-authorization/complete-step')
                     ->setAttrib('id', 'completeForm')
                     ->addElement(new Zend_Form_Element_Hidden('id'))
                     ->addElement(new Zend_Form_Element_Hidden('step'))
                     ->setElementDecorators(array('ViewHelper'))
                     ->setDefaults(array('id' => $sa->id, 'step' => $sa->status));
        return $completeForm;
    }
    /**
     * View the specified system
     *
     * @return void
     */
    public function viewAction()
    {
        //$this->_acl->requirePrivilegeForClass('read', 'AssessmentPlanEntry');
        $id = $this->_request->getParam('id');
        $sa = Doctrine::getTable('SecurityAuthorization')->find($id);

        $tabView = new Fisma_Yui_TabView('SecurityAuthorizationView', $id);
        $tabView->addTab($sa->Organization->nickname, "/sa/security-authorization/overview/id/$id");
        $tabView->addTab("1. Categorize", "/sa/security-authorization/fips/id/$id");
        $tabView->addTab("2. Select", "/sa/security-authorization/select-controls/id/$id");
        $tabView->addTab("3. Implementation", "/sa/security-authorization/implementation/id/$id");
        $tabView->addTab("4. Assessment", "/sa/security-authorization/assessment-plan/id/$id");
        $tabView->addTab("5. Authorization", "/sa/security-authorization/authorization/id/$id");

        $this->view->tabView = $tabView;
    }

    public function overviewAction()
    {
        $id = $this->_request->getParam('id');
        $this->_helper->layout()->disableLayout();
        $sa = Doctrine::getTable('SecurityAuthorization')->find($id);
        $this->view->sa = $sa;
    }

    /**
     * Display details for a single record.
     *
     * Override default implementation to use custom view script.
     *
     * @return void
     */

    /*
    public function viewAction()
    {
        $this->_viewObject();
    }
    */

    protected function _implementationProgress(SecurityAuthorization $sa)
    {
        $sasc = Doctrine::getTable('SaSecurityControl')->getSecurityAuthorizationQuery($sa->id)->execute();
        $sasce = Doctrine::getTable('SaSecurityControlEnhancement')->getSecurityAuthorizationQuery($sa->id)->execute();
        $sasca = new Doctrine_Collection('SaSecurityControlAggregate');
        $sasca->merge($sasc);
        $sasca->merge($sasce);
        $ids = $sasca->toKeyValueArray('id', 'id');
        $saiTable = Doctrine::getTable('SaImplementation');
        $allCount = $saiTable->getSaSecurityControlAggregateQuery($ids)->count();
        if ($allCount == 0) {
            return '(No implementations)';
        }
        $completeCount = $saiTable->getSaSecurityControlAggregateAndStatusQuery($ids, 'Complete')->count();
        if ($completeCount == 0) {
            return '0% Complete';
        }
        $ratio = $completeCount / $allCount;
        $percent = $ratio * 100;
        $startDate = new Zend_Date($sa->implementStartTs, Fisma_Date::FORMAT_DATETIME);
        $nowTs = (int)Zend_Date::now()->toString(Zend_Date::TIMESTAMP);
        $startTs = (int)$startDate->toString(Zend_Date::TIMESTAMP);
        $duration = $nowTs - $startTs;
        $duration = round($duration / $ratio);
        $completionDate = Zend_Date::now()->add($duration);
        return sprintf(
            '%s%% Complete, Estimating Complection on %s',
            round($percent, 1),
            $completionDate->toString(Zend_Date::DATE_FULL)
        );
    }

    public function selectControlsAction()
    {
        $this->_helper->layout()->disableLayout();
        $this->_viewObject();
        $this->view->buttons = array(
            new Fisma_Yui_Form_Button(
                'addControl',
                array(
                    'label' => 'Add Control',
                    'onClickFunction' => 'Fisma.SecurityControlTable.addControl',
                    'onClickArgument' => $this->view->id
                )
            ),
            new Fisma_Yui_Form_Button(
                'completeSelection',
                 array('label' => 'Complete Selection', 'onClickFunction' => 'completeSelect')
            )
        );
        $completeForm = new Fisma_Zend_Form();
        $completeForm->setAction('/sa/security-authorization/complete-step')
                     ->setAttrib('id', 'completeForm')
                     ->addElement(new Zend_Form_Element_Hidden('id'))
                     ->addElement(new Zend_Form_Element_Hidden('step'))
                     ->setElementDecorators(array('ViewHelper'))
                     ->setDefaults(array('id' => $this->view->id, 'step' => 'Select'));
        $this->view->buttons[] = $completeForm;

    }

    public function controlTableMasterAction()
    {
        $id = $this->_getParam('id');
        $sa = Doctrine::getTable('SecurityAuthorization')->find($id);
        $records = array();
        foreach ($sa->SaSecurityControls as $sasc) {
            $sceCount = $sasc->SecurityControl->Enhancements->count();
            $sasceCount = $sasc->SaSecurityControlEnhancements->count();
            $records[] = array(
                'id' => $sasc->id,
                'securityControlId' => $sasc->SecurityControl->id,
                'code' => $sasc->SecurityControl->code,
                'name' => $sasc->SecurityControl->name,
                'class' => $sasc->SecurityControl->class,
                'family' => $sasc->SecurityControl->family,
                'hasEnhancements' => $sasceCount > 0,
                'hasMoreEnhancements' => $sceCount > $sasceCount
            );
        }
        $this->view->records = $records;
        $this->view->totalRecords = count($records);
    }

    public function controlTableNestedAction()
    {
        $id = $this->_getParam('id');
        $sasc = Doctrine::getTable('SaSecurityControl')->find($id);
        $records = array();
        foreach ($sasc->SaSecurityControlEnhancements as $sasce) {
            $records[] = array(
                'id' => $sasce->id,
                'securityControlEnhancementId' => $sasce->SecurityControlEnhancement->id,
                'number' => $sasce->SecurityControlEnhancement->number
            );
        }
        $this->view->records = $records;
        $this->view->totalRecords = count($records);
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

        $saScCollection = Doctrine::getTable('SaSecurityControl')->getSaAndControlQuery($id, $controlId)->execute();
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

        $saSceCollection = Doctrine::getTable('SaSecurityControlEnhancement')
            ->getSaAndEnhancementQuery($id, $enhancementId)
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
    public function addControlAction()
    {
        $id = $this->_request->getParam('id');
        if ($this->_request->isPost()) {
            $post = $this->_request->getPost();
            $saSc = new SaSecurityControl();
            $saSc->merge($post);
            $saSc->save();
            $this->_redirect('/sa/security-authorization/select-controls/id/'.$id);
            return;
        }

        // get list of controls for the form
        $currentControls = Doctrine::getTable('SecurityControl')
            ->getSaQuery($id)
            ->execute()
            ->toKeyValueArray('id', 'id');
        $this->view->currentControls = $currentControls;
        $catalogId = Fisma::configuration()->getConfig('default_security_control_catalog_id');
        $controls = Doctrine::getTable('SecurityControl')
            ->getCatalogExcludeControlsQuery($catalogId, $currentControls)
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

        $saSecurityControl = Doctrine::getTable('SaSecurityControl')
            ->getSaAndControlQuery($id, $securityControlId)
            ->fetchOne();

        if ($this->_request->isPost()) {
            $post = $this->_request->getPost();
            foreach ($post['securityControlEnhancementIds'] as $securityControlEnhancementId) {
                $saSce = new SaSecurityControlEnhancement();
                $saSce->saSecurityControlId = $saSecurityControl->id;
                $saSce->securityControlEnhancementId = $securityControlEnhancementId;
                $saSce->save();
                $saSce->free();
            }
            $this->_redirect('/sa/security-authorization/select-controls/id/'.$id);
            return;
        }

        $this->view->id = $id;
        $this->view->securityControlId = $securityControlId;

        $currentControlEnhancements = Doctrine::getTable('SecurityControlEnhancement')->getSaAndControlQuery($id, $securityControlId)
            ->execute()
            ->toKeyValueArray('id', 'id');
        $this->view->currentControlEnhancementss = $currentControlEnhancements;

        $enhancementObjects = Doctrine::getTable('SecurityControlEnhancement')
            ->getControlExcludeEnhancementsQuery($securityControlId, $currentControlEnhancements)
            ->execute();
        $enhancements = array();
        foreach ($enhancementObjects as $enh) {
            $enhancements[$enh->id] = $enh->Control->code . " (" . $enh->number . ")";
        }
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

        $saSecurityControl = Doctrine::getTable('SaSecurityControl')
            ->getSaAndControlQuery($id, $securityControlId)
            ->fetchOne();

        if ($this->_request->isPost()) {
            $post = $this->_request->getPost();
            $common = $post['common'];
            $sysOrgId = $post['sysOrgId'];
            $saSecurityControl->common = $common == 'common';
            $saSecurityControl->inheritsId = $common == 'inherits' ? $sysOrgId : null;
            $saSecurityControl->save();
            $saSecurityControl->free();
            $this->_redirect('/sa/security-authorization/select-controls/id/'.$id);
            return;
        }

        $this->view->id = $id;
        $this->view->securityControlId = $securityControlId;

        // get a list of systems from which to inherit
        $sysOrgId = $saSecurityControl->SecurityAuthorization->sysOrgId;
        $commonSysOrgs = Doctrine::getTable('Organization')->getCommonControlExcludeOrgQuery($sysOrgId)->execute();
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

    /**
     * Override to properly return a two-word model name
     *
     * @return string Model name split into two-words
     */
    public function getSingularModelName()
    {
        return 'Security Authorization';
    }

    /**
     * Display CIA criteria and FIPS-199 categorization
     *
     * @return void
     */
    public function fipsAction()
    {
        $id = $this->getRequest()->getParam('id');
        $organization = Doctrine::getTable('Organization')->findOneBySystemId($id);
        $this->_acl->requirePrivilegeForObject('read', $organization);
        $this->_helper->layout()->disableLayout();

        $this->view->organization = $organization;
        $this->view->system = $this->view->organization->System;
        // BEGIN: Build the data table of information types associated with the system

        $informationTypesTable = new Fisma_Yui_DataTable_Remote();

        $informationTypesTable->addColumn(new Fisma_Yui_DataTable_Column('Category', true, null, null, 'category'))
                              ->addColumn(new Fisma_Yui_DataTable_Column('Name', true, null, null, 'name'))
                              ->addColumn(
                                  new Fisma_Yui_DataTable_Column('Description', false, null, null, 'description')
                              )
                              ->addColumn(
                                  new Fisma_Yui_DataTable_Column('Confidentiality', true, null, null, 'confidentiality')
                              )
                              ->addColumn(new Fisma_Yui_DataTable_Column('Integrity', true, null, null, 'integrity'))
                              ->addColumn(
                                  new Fisma_Yui_DataTable_Column('Availability', true, null, null, 'availability')
                              )
                              ->setResultVariable('informationTypes')
                              ->setInitialSortColumn('category')
                              ->setSortAscending(true)
                              ->setRowCount(10)
                              ->setDataUrl("/system/information-types/id/{$id}/format/json");

        $this->view->informationTypesTable = $informationTypesTable;
        // END: Building of data table

        // BEGIN: Build the data table of available information types to assign to the system

        if ($this->_acl->hasPrivilegeForObject('update', $organization)) {
            $availableInformationTypesTable = clone $informationTypesTable;

            $availableInformationTypesTable->addColumn(
                new Fisma_Yui_DataTable_Column('Add', 'false', 'Fisma.System.addInformationType', null, 'id')
            );

            $availableInformationTypesTable->setDataUrl(
                "/sa/information-type/active-types/systemId/{$id}/format/json"
            );

            $this->view->availableInformationTypesTable = $availableInformationTypesTable;
            // END: Building of the data table

            $this->view->informationTypesTable->addColumn(
                new Fisma_Yui_DataTable_Column('Remove', 'false', 'Fisma.System.removeInformationType', null, 'id')
            );

            $addInformationTypeButton = new Fisma_Yui_Form_Button(
                'addInformationTypeButton',
                array(
                    'label' => 'Add Information Types',
                    'onClickFunction' => 'Fisma.System.showInformationTypes',
                )
            );
            $this->view->addInformationTypeButton = $addInformationTypeButton;
        }

        $this->render();
    }
}
