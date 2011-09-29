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
                      ->addActionContext('add-control', 'json')
                      ->addActionContext('import-baseline-security-controls', 'json')
                      ->addActionContext('remove-control', 'json')
                      ->addActionContext('remove-enhancement', 'json')
                      ->addActionContext('select-control-table', 'json')
                      ->addActionContext('edit-enhancements', 'json')
                      ->initContext();

        $this->_helper->ajaxContext()
                      ->addActionContext('add-enhancements', 'html')
                      ->addActionContext('assessment-plan', 'html')
                      ->addActionContext('authorization', 'html')
                      ->addActionContext('edit-common-control', 'html')
                      ->addActionContext('implementation', 'html')
                      ->addActionContext('select-controls', 'html')
                      ->addActionContext('show-add-control-form', 'html')
                      ->addActionContext('select-controls-form', 'html')
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
     * Display the step 3 (implement) user interface
     */
    public function implementationAction()
    {
        $this->_acl->requirePrivilegeForClass('read', 'AssessmentPlanEntry');
        $this->view->id = $this->_request->getParam('id');
        $sa = Doctrine::getTable('SecurityAuthorization')->find($this->view->id);

        // Disable tab if previous step has not been completed.
        if ($sa->compareStatus('Implement') > 0) {
            $this->render('step-is-locked');
            return;
        }
        
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
 
        $this->view->sa = $sa;
        $this->view->progress = $this->_implementationProgress($sa);
        $buttonbar = array();

        $buttonbar[] = new Fisma_Yui_Form_Button(
            'completeImplementation',
            array(
                 'label' => 'Complete Implementation', 
                 'onClickFunction' => 'Fisma.SecurityAuthorization.completeForm'
            )
        );

        $buttonbar[] = $this->_createCompleteStepForm($sa);

        $this->view->buttonbar = $buttonbar;
    }

    /**
     * Show the step 4 (assessment case creation and assessment case evaluation) user interface
     */    
    public function assessmentPlanAction()
    {
        $this->_acl->requirePrivilegeForClass('read', 'AssessmentPlanEntry');
        $this->view->id = $this->_request->getParam('id');

        $sa = Doctrine::getTable('SecurityAuthorization')->find($this->view->id);
        
        // Disable tab if previous step has not been completed.
        if ($sa->compareStatus('Assessment Plan') > 0) {
            $this->render('step-is-locked');
            return;
        }

        $this->view->dataTable = $this->_baseAssessmentPlanDataTable();
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
     * Show the step 5 (authorization) user interface
     */
    public function authorizationAction()
    {
        $this->view->id = $this->_request->getParam('id');

        $sa = Doctrine::getTable('SecurityAuthorization')->find($this->view->id);
        
        // Disable tab if previous step has not been completed.
        if ($sa->compareStatus('Authorization') > 0) {
            $this->render('step-is-locked');
            return;
        }

        $dataTable = $this->_baseAssessmentPlanDataTable();
        $dataTable->setDataUrl('/sa/assessment-plan-entry/search/said/' . $this->view->id . '/otherThanSatisfied/true')
                  ->addColumn(new Fisma_Yui_DataTable_Column('Finding', false, null, null, 'findingId'));
        $this->view->dataTable = $dataTable;

        $this->view->sa = $sa;
        $buttonbar = array();
        $buttonbar[] = new Fisma_Yui_Form_Button(
            'completeAuthorization',
             array('label' => 'Complete Authorization', 'onClickFunction' => 'Fisma.SecurityAuthorization.completeForm')
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
        $id = $this->_request->getParam('id');
        $sa = Doctrine::getTable('SecurityAuthorization')->find($id);
        $systemId = $sa->Organization->System->id;

        $tabView = new Fisma_Yui_TabView('SecurityAuthorizationView', $id);
        $tab1Name = $sa->Organization->nickname . ' Security Authorization';
        $tabView->addTab($tab1Name, "/sa/security-authorization/overview/id/$id");
        $tabView->addTab("1. Categorize", "/system/fips/id/$systemId/said/$id");
        $tabView->addTab("2. Select", "/sa/security-authorization/select-controls/id/$id/format/html");
        $tabView->addTab("3. Implementation", "/sa/security-authorization/implementation/id/$id/format/html");
        $tabView->addTab("4. Assessment", "/sa/security-authorization/assessment-plan/id/$id/format/html");
        $tabView->addTab("5. Authorization", "/sa/security-authorization/authorization/id/$id/format/html");

        $this->view->tabView = $tabView;
    }

    /**
     * Display an overview of the process for an SA object
     * 
     * NOTICE: We are currently confused about whether we are going to have distinct statuses with rigid steps
     * or allow users to move fluidly between steps. What you see here is a mish mash of the two approaches. We'll
     * undoubtedly need to clean this up later.
     */
    public function overviewAction()
    {
        $id = $this->_request->getParam('id');
        $this->_helper->layout()->disableLayout();
        $sa = Doctrine::getTable('SecurityAuthorization')->find($id);
        $this->view->sa = $sa;

        // Set up the names of steps displayed on the overview tab
        $overviewSteps = Doctrine::getTable('SecurityAuthorization')->getEnumValues('status');
        array_splice($overviewSteps, 3, 1);
        array_splice($overviewSteps, -2);

        $completedTerms = array(
            -1 => 'Completed',
             0 => 'In Progress',
             1 => 'Incomplete'
        );

        $steps = array();

        foreach ($overviewSteps as $index => $step) {
            $completedStatus = $sa->compareStatus($step);
            $stepNumber = $index + 1;

            // By default, step progress is binary (either 0% or 100%)
            $steps[] = array(
                'stepNumber' => $stepNumber,
                'name' => $stepNumber . ". $step",
                'completed' => $completedTerms[$completedStatus],
                'numerator' => ($completedStatus < 0 ? 1 : 0),
                'denominator' => 1
            );
        }

        // Calculate progress for implement and assessment steps
        $totalControlCount = $sa->getControlsCount();

        $steps[2]['numerator'] = $sa->getImplementedControlsCount();
        $steps[2]['denominator'] = $totalControlCount;

        $steps[3]['numerator'] = $sa->getAssessedControlsCount();
        $steps[3]['denominator'] = $totalControlCount;

        $this->view->steps = $steps;
    }

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

    /**
     * Display the step 2 (Select) user interface
     */
    public function selectControlsAction()
    {
        $id = $this->_getParam('id');
        $sa = Doctrine::getTable('SecurityAuthorization')->find($id);
        
        // Disable tab if previous step has not been completed.
        if ($sa->compareStatus('Select') > 0) {
            $this->render('step-is-locked');
            return;
        }
        
        $this->view->controlCount = $sa->SecurityControls->count();
        $this->view->systemImpact = $sa->Organization->System->fipsCategory;
        
        $this->view->id = $id;
        $this->_viewObject();
        $this->view->buttons = array(
            new Fisma_Yui_Form_Button(
                'addControl',
                array(
                    'label' => 'Add Control',
                    'onClickFunction' => 'Fisma.SecurityAuthorization.showAddControlForm',
                    'onClickArgument' => $this->view->id
                )
            ),
            new Fisma_Yui_Form_Button(
                'importBaselineControls',
                array(
                    'label' => 'Import Baseline Controls',
                    'onClickFunction' => 'Fisma.SecurityAuthorization.confirmImportBaselineControls',
                    'onClickArgument' => $this->view->id
                )
            ),
            new Fisma_Yui_Form_Button(
                'completeSelection',
                 array('label' => 'Complete Step 2', 'onClickFunction' => 'Fisma.SecurityAuthorization.completeForm')
            )
        );

        $dataTable = new Fisma_Yui_DataTable_Remote();
        $dataTable->addColumn(new Fisma_Yui_DataTable_Column('Control', true, null, null, 'definition_code'))
                  ->addColumn(new Fisma_Yui_DataTable_Column('Family', true, null, null, 'definition_family'))
                  ->addColumn(new Fisma_Yui_DataTable_Column('Class', true, null, null, 'definition_class'))
                  ->addColumn(new Fisma_Yui_DataTable_Column('Name', true, null, null, 'definition_name'))
                  ->addColumn(new Fisma_Yui_DataTable_Column('Description', 
                                                             true, 
                                                             'Fisma.TableFormat.maximumTextLength', 
                                                             150, 
                                                             'definition_control'))
                  ->addColumn(
                        new Fisma_Yui_DataTable_Column(
                            'Enhancements',
                            true,
                            'Fisma.SecurityAuthorization.tableFormatEnhancements',
                            null,
                            'definedEnhancements_enhancements'
                        )
                  )
                  ->addColumn(new Fisma_Yui_DataTable_Column('Common Control', true, null, null, 'instance_common'))
                  ->setDataUrl('/sa/security-authorization/select-control-table/id/' . $id . '/format/json')
                  ->setResultVariable('controls')
                  ->setRowCount(20)
                  ->setInitialSortColumn('definition_code')
                  ->setSortAscending(true)
                  ->setGlobalVariableName("Fisma.SecurityAuthorization.selectControlsTable");
        $this->view->dataTable = $dataTable;

        $completeForm = new Fisma_Zend_Form();
        $completeForm->setAction('/sa/security-authorization/complete-step')
                     ->setAttrib('id', 'completeForm')
                     ->addElement(new Zend_Form_Element_Hidden('id'))
                     ->addElement(new Zend_Form_Element_Hidden('step'))
                     ->setElementDecorators(array('ViewHelper'))
                     ->setDefaults(array('id' => $this->view->id, 'step' => 'Select'));
        $this->view->buttons[] = $completeForm;
    }

    /**
     * Called asynchronously to load the currently selected security controls into a data table
     */
    public function selectControlTableAction()
    {
        $id = $this->_getParam('id');
        $sa = Doctrine::getTable('SecurityAuthorization')->find($id);

        $id    = $this->getRequest()->getParam('id');
        $count = $this->getRequest()->getParam('count', 20);
        $start = $this->getRequest()->getParam('start', 0);
        $sort  = $this->getRequest()->getParam('sort', 'category');
        $dir   = $this->getRequest()->getParam('dir', 'asc');

        $totalEnhancementsQuery = "SUM(IF(definedEnhancements.id IS NOT NULL, 1, 0))";

        $controlsQuery = Doctrine_Query::create()
            ->select("instance.id")
            ->addSelect('instance.securityAuthorizationId')
            ->addSelect('definition.id')
            ->addSelect("definition.code")
            ->addSelect("definition.family")
            ->addSelect("definition.class")
            ->addSelect("definition.name")
            ->addSelect("definition.control")
            ->addSelect("count(definedEnhancements.id) availableEnhancements")
            ->addSelect('count(selectedEnhancements.id) selectedEnhancements')
            ->from('SaSecurityControl instance')
            ->leftJoin('instance.SecurityControl definition')
            ->leftJoin('definition.Enhancements definedEnhancements')
            ->leftJoin(
                'instance.SaSecurityControlEnhancements selectedEnhancements ' .
                'WITH selectedEnhancements.securityControlEnhancementId = definedEnhancements.id'
            )
            ->where('instance.securityAuthorizationId = ?', $sa->id)
            ->groupBy("instance.id")
            ->orderBy("definition.code")
            ->offset($start)
            ->setHydrationMode(Doctrine::HYDRATE_SCALAR);
        $this->view->totalRecords = $controlsQuery->count();
        
        // Doctrine doesn't handle count() well with a limit clause, so apply the limit() after doing count().
        $controlsQuery->limit($count);
        $this->view->controls = $controlsQuery->execute();
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
     * Add a single security control to an SA
     */
    public function addControlAction()
    {
        Doctrine_Manager::connection()->beginTransaction();
        $response = new Fisma_AsyncResponse;

        try {
            $post = $this->_request->getPost();

            $control = new SaSecurityControl();
            $control->merge($post);
            $control->save();

            Doctrine_Manager::connection()->commit();
        } catch (Exception $e) {
            $this->getInvokeArg('bootstrap')->getResource('Log')->log($e, Zend_Log::ERR);
            Doctrine_Manager::connection()->rollback();
            $response->fail($e);
        }

        $this->view->response = $response;
    }
    
    /**
     * Show a user interface for adding a single security control to an SA
     * 
     * This is displayed on the client side in a modal dialog
     */
    public function showAddControlFormAction()
    {
        $id = $this->_request->getParam('id');
        
        // get list of controls for the form
        $currentControls = Doctrine::getTable('SecurityControl')
            ->getSaQuery($id)
            ->execute()
            ->toKeyValueArray('id', 'id');
        $catalogId = Fisma::configuration()->getConfig('default_security_control_catalog_id');
        
        $controls = Doctrine::getTable('SecurityControl')
             ->getCatalogExcludeControlsQuery($catalogId, $currentControls)
             ->execute();
        
        $controlArray = array();
        foreach ($controls as $control) {
            $controlArray[$control->id] = $control->code . ' ' . $control->name;
        }
        
        // build form
        $form = $this->getForm('add_control');
        $form->setDefault('securityAuthorizationId', $id);
        $form->getElement('securityControlId')->addMultiOptions($controlArray);
        $this->view->id = $id;
        $this->view->addControlForm = $form;
    }

    /**
     * Display an empty form for viewing a single control during step 2 (select)
     * 
     * This is displayed on the client side in a modal dialog with the Fisma.FormDialog javascript class.
     */
    public function selectControlsFormAction()
    {
        $this->view->form = $this->getForm('select_control');
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

        $currentControlEnhancements = Doctrine::getTable('SecurityControlEnhancement')
            ->getSaAndControlQuery($id, $securityControlId)
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
     * Import the baseline security controls into this assessment
     */
    public function importBaselineSecurityControlsAction()
    {
        Doctrine_Manager::connection()->beginTransaction();
        $response = new Fisma_AsyncResponse;

        $id = $this->_getParam('id');
        $sa = Doctrine::getTable('SecurityAuthorization')->find($id);
        $catalogId = Fisma::configuration()->getConfig('default_security_control_catalog_id');

        try {
            // Get list of existing controls in this SA
            $existingControls = Doctrine_Query::create()
                                ->select('c.securityControlId')
                                ->from('SaSecurityControl c')
                                ->where('c.securityAuthorizationId = ?', $sa->id)
                                ->execute()
                                ->toKeyValueArray('securityControlId', 'securityControlId');

            // Get a list of baseline controls
            $controls = Doctrine::getTable('SecurityControl')
                        ->getCatalogIdAndImpactQuery($catalogId, $sa->Organization->System->fipsCategory)
                        ->execute();

            // Copy the controls that exist in the baseline but not in the SA
            foreach ($controls as $control) {
                if (isset($existingControls[$control->id])) {
                    continue;
                }

                $sacontrol = new SaSecurityControl();
                $sacontrol->securityAuthorizationId = $sa->id;
                $sacontrol->securityControlId = $control->id;
                $sacontrol->save();
                $sacontrol->free();
            }

            // Get a list of existing control enhancements in this SA
            $existingEnhancements = Doctrine_Query::create()
                                    ->select('e.securityControlEnhancementId')
                                    ->from('SaSecurityControlEnhancement e')
                                    ->leftJoin('SaSecurityControl c')
                                    ->where('c.securityAuthorizationId = ?', $sa->id)
                                    ->execute()
                                    ->toKeyValueArray('securityControlEnhancementId', 'securityControlEnhancementId');

            // Get a list of baseline enhancements
            $saControls = Doctrine::getTable('SaSecurityControl')
                          ->getEnhancementsForSaAndImpactQuery($sa->id, $sa->Organization->System->fipsCategory)
                          ->execute();

            // Copy the enhancements that exist in the baseline but not in the SA                    
            foreach ($saControls as $saControl) {
                $control = $saControl->SecurityControl;

                foreach ($control->Enhancements as $baselineEnhancement) {
                    if (isset($existingEnhancements[$baselineEnhancement->id])) {
                        continue;
                    }
                    
                    $enhancement = new SaSecurityControlEnhancement();
                    $enhancement->securityControlEnhancementId = $baselineEnhancement->id;
                    $enhancement->saSecurityControlId = $saControl->id;
                    $enhancement->save();
                    $enhancement->free();
                }

                $saControl->free();
            }

            Doctrine_Manager::connection()->commit();
        } catch (Exception $e) {
            $this->getInvokeArg('bootstrap')->getResource('Log')->log($e, Zend_Log::ERR);
            Doctrine_Manager::connection()->rollback();
            $response->fail($e);
        }

        // Count how many controls are now on this SA
        $response->addPayload('controlCount', $sa->getControlsCount());

        $this->view->response = $response;
    }

    /**
     * editEnhancementsAction 
     * 
     * @return void
     */
    public function editEnhancementsAction()
    {
        $saId = $this->getRequest()->getParam('id');
        $controlId = $this->getRequest()->getParam('controlId');

        $enhancements = Doctrine::getTable('SecurityControlEnhancement')
            ->findBySecurityControlId($controlId)
            ->toArray();
        $saSc = Doctrine_Query::create()
            ->from('SaSecurityControl saSc')
            ->leftJoin('saSc.SaSecurityControlEnhancements saSce')
            ->where('saSc.securityAuthorizationId = ?', $saId)
            ->andWhere('saSc.securityControlId = ?', $controlId)
            ->fetchOne();
        $eIds = array();
        foreach ($saSc->SaSecurityControlEnhancements as $saSce) {
            $eIds[$saSce->securityControlEnhancementId] = $saSce;
        }
        $this->view->eids = $eIds;

        foreach ($enhancements as &$e) {
            $e['selected'] = isset($eIds[$e['id']]);
        }

        if ($this->getRequest()->isPost()) {
            $selected = $this->getRequest()->getPost('enhancements');
            // insert ids not already present
            foreach ($selected as $s) {
                if (empty($eIds[$s])) {
                    $saSce = new SaSecurityControlEnhancement();
                    $saSce->securityAuthorizationId = $saId;
                    $saSce->saSecurityControlId = $saSc->id;
                    $saSce->securityControlEnhancementId = $s;
                    $saSce->save();
                    unset($saSce);
                }
            }
            // delete enhancements no longer selected
            foreach ($eIds as $saSce) {
                if (!in_array($saSce->securityControlEnhancementId, $selected)) {
                    $saSce->delete();
                }
            }
            $this->view->result = 'success';
        } else {
            $this->view->enhancements = $enhancements;
        }
    }
}
