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
                ->from('SecurityControl sc')
                ->where('sc.securityControlCatalogId = ?', array($catalogId))
                ->andWhereIn('sc.controlLevel', $controlLevels)
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
            $sacontrols = Doctrine_Query::create()
                ->from('SaSecurityControl sasc')
                ->leftJoin('sasc.SecurityControl sc')
                ->leftJoin('sc.Enhancements sce')
                ->where('sasc.securityAuthorizationId = ?', $sa->id)
                ->andWhereIn('sce.level', $controlLevels)
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
            'Active' => 'view'
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
     * Display details for a single record.
     *
     * Override default implementation to use custom view script.
     *
     * @return void
     */
    public function viewAction()
    {
        $this->_viewObject();
    }

    protected function _implementationProgress(SecurityAuthorization $sa)
    {
        $sasc = Doctrine_Query::create()
            ->from('SaSecurityControl sasc')
            ->where('sasc.securityAuthorizationId = ?', $sa->id)
            ->execute();
        $sasce = Doctrine_Query::create()
            ->from('SaSecurityControlEnhancement sasce, sasce.SaSecurityControl sasc')
            ->where('sasc.securityAuthorizationId = ?', $sa->id)
            ->execute();
        $sasca = new Doctrine_Collection('SaSecurityControlAggregate');
        $sasca->merge($sasc);
        $sasca->merge($sasce);
        $ids = $sasca->toKeyValueArray('id', 'id');
        $allCount = Doctrine_Query::create()
            ->from('SaImplementation sai, sai.SaSecurityControlAggregate sasca')
            ->whereIn('sasca.id', $ids)
            ->count();
        if ($allCount == 0) {
            return '(No implementations)';
        }
        $completeCount = Doctrine_Query::create()
            ->from('SaImplementation sai, sai.SaSecurityControlAggregate sasca')
            ->whereIn('sasca.id', $ids)
            ->andWhere('sai.status = ?', 'Complete')
            ->count();
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

    public function controlTableAction()
    {
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
}
