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
 * The remediation controller handles CRUD for findings in remediation.
 *
 * @author     Jim Chen <xhorse@users.sourceforge.net>
 * @copyright  (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Controller
 */
class Finding_RemediationController extends Fisma_Zend_Controller_Action_Object
{
    /**
     * The main name of the model.
     *
     * This model is the main subject which the controller operates on.
     *
     * @var string
     */
    protected $_modelName = 'Finding';

    /**
     * The orgSystems which are belongs to current user.
     *
     * @var Doctrine_Collection
     */
    protected $_organizations = null;

    /**
     * The preDispatch hook is used to split off poam modify actions, mitigation approval actions, and evidence
     * approval actions into separate controller actions.
     *
     * @return void
     */
    public function preDispatch()
    {
        parent::preDispatch();

        $this->_organizations = $this->_me->getOrganizationsByPrivilege('finding', 'read');

        $request = $this->getRequest();
        $this->_paging['startIndex'] = $request->getParam('startIndex', 0);
        if ('modify' == $request->getActionName()) {
            if (isset($_POST['upload_evidence'])) {
                $request->setParam('sub', null);
                $this->_forward('upload-evidence');
            }
        }
    }

    /**
     * Create contexts for printable tab views.
     *
     * @return void
     */
    public function init()
    {
        $this->_helper->ajaxContext()
             ->addActionContext('finding', 'html')
             ->addActionContext('mitigation-strategy', 'html')
             ->addActionContext('risk-analysis', 'html')
             ->addActionContext('security-control', 'html')
             ->addActionContext('comments', 'html')
             ->addActionContext('artifacts', 'html')
             ->addActionContext('audit-log', 'html')
             ->addActionContext('can-submit-mitigation-strategy', 'json')
             ->initContext();

        parent::init();
    }

    /**
     * Default action.
     *
     * It combines the searching and summary into one page.
     *
     * @GETAllowed
     * @return void
     */
    public function indexAction()
    {
        $this->_acl->requirePrivilegeForClass('read', 'Finding');

        $this->_helper->actionStack('searchbox', 'Remediation');
        $this->_helper->actionStack('summary', 'Remediation');
    }

    /**
     * Overriding Hooks
     *
     * @param Zend_Form $form The specified form to save
     * @param Doctrine_Record|null $subject The subject model related to the form
     * @return Fisma_Doctrine_Record The saved record
     * @throws Fisma_Zend_Exception if the subject is not null or the organization of the finding associated
     * to the subject doesn`t exist
     */
    protected function saveValue($form, $finding = null)
    {
        if (is_null($finding)) {
            $finding = new $this->_modelName();
        } else {
            throw new Fisma_Zend_Exception('Invalid parameter expecting a Record model');
        }

        $values = $this->getRequest()->getPost();

        if (empty($values['securityControlId'])) {
            unset($values['securityControlId']);
        }

        $finding->merge($values);

        $organization = Doctrine::getTable('Organization')->find($values['responsibleOrganizationId']);

        if ($organization !== false) {
            $finding->Organization = $organization;
        } else {
            throw new Fisma_Zend_Exception("The user tried to associate a new finding with a"
                                         . " non-existent organization (id={$values['orgSystemId']}).");
        }

        $finding->CreatedBy = CurrentUser::getInstance();

        $finding->save();

        return $finding;
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

        // Default discovered date is today
        $form->getElement('discoveredDate')
             ->setValue(Zend_Date::now()->toString(Fisma_Date::FORMAT_DATE))
             ->addDecorator(new Fisma_Zend_Form_Decorator_Date);

        // Populate <select> for finding sources
        $sources = Doctrine::getTable('Source')->findAll()->toArray();

        $form->getElement('sourceId')->addMultiOptions(array('' => null));

        // Add an option to add Finding source on the fly
        $form->getElement('sourceId')->addMultiOptions(array('new' => '-- Add New Finding Source --'));
        $form->getElement('sourceId')->setOptions(array('onChange' => 'Fisma.Remediation.displaySourcePanel(this)'));

        foreach ($sources as $source) {
            $form->getElement('sourceId')
                 ->addMultiOptions(array($source['id'] => html_entity_decode($source['name'])));
        }

        // Populate <select> for threat level
        $threatLevels = Doctrine::getTable('Finding')->getEnumValues('threatLevel');
        $threatLevels = array('' => '') + array_combine($threatLevels, $threatLevels);

        $form->getElement('threatLevel')->setMultiOptions($threatLevels);

        // Populate <select> for responsible organization
        $systems = $this->_me->getOrganizationsByPrivilege('finding', 'create');
        $selectArray = $this->view->systemSelect($systems);

        $form->getElement('responsibleOrganizationId')->addMultiOptions($selectArray);

        $organizationIds = array_keys($selectArray);
        $defaultOrgId = array_shift($organizationIds);

        $organization = Doctrine::getTable('Organization')->find($defaultOrgId);
        if ($organization->pocId) {
            $value = $organization->Poc->username ? $organization->Poc->username : '<' . $organization->email . '>';
            $form->setDefault('pocAutocomplete', $value);
            $form->setDefault('pocId', $organization->pocId);
        }

        // If the user can't create a POC object, then don't set up the POC create form
        if (!$this->_acl->hasPrivilegeForClass('create', 'User')) {
            $form->getElement('pocAutocomplete')->setAttrib('setupCallback', null);
        }

        return $form;
    }

    /**
     * Override to set some non-trivial default values (such as the security control autocomplete)
     *
     * @param Doctrine_Record $subject The specified subject model
     * @param Zend_Form $form The specified form
     * @return Zend_Form The manipulated form
     */
    protected function setForm($subject, $form)
    {
        parent::setForm($subject, $form);

        $values = $this->getRequest()->getPost();

        // Set default value for security control autocomplete
        if (empty($values['securityControlId'])) {
            unset($values['securityControlId']);
        }

        $form->getElement('securityControlAutocomplete')
             ->setValue($values['securityControlId'])
             ->setDisplayText($values['securityControlAutocomplete']);

        return $form;
    }

    /**
     * Override in order to remove the "Create new" button, which does not make sense for vulnerabilities. Instead,
     * add an "upload vulnerabilities" button.
     *
     * @param Fisma_Doctrine_Record $record The object for which this toolbar applies, or null if not applicable
     * @return array Array of Fisma_Yui_Form_Button
     */
    public function getToolbarButtons(Fisma_Doctrine_Record $record = null, $fromSearchUrlParams = null)
    {
        $buttons = parent::getToolbarButtons($record);
        $isCreate = ($this->getRequest()->getActionName() === 'create');

        if (CurrentUser::getInstance()->acl()->hasPrivilegeForClass('inject', 'Finding') && !$isCreate) {
            array_unshift($buttons, new Fisma_Yui_Form_Button_Link(
                'toolbarUploadFindingsButton',
                array(
                    'value' => 'Import',
                    'imageSrc' => '/images/up.png',
                    'href' => '/finding/index/injection'
                )
            ));
        }

        return $buttons;
    }

    /**
     * View details of a finding object
     *
     * @GETAllowed
     * @return void
     */
    public function viewAction()
    {
        $id = $this->_request->getParam('id');
        $view = $this->view;

        $fromSearchParams = $this->_getFromSearchParams($this->_request);
        $fromSearchUrl = $this->_helper->makeUrlParams($fromSearchParams);

        if ($fromSearchUrl) {
            $this->view->fromSearchUrl = $fromSearchUrl;
        }

        $finding = Doctrine_Query::create()
            ->from('Finding f')->leftJoin('f.Attachments')->where('f.id = ?', $id)
            ->fetchOne();

        if (!$finding) {
             $msg = '%s (%d) not found. Make sure a valid ID is specified.';
             throw new Fisma_Zend_Exception_User(sprintf($msg, $this->_modelName, $id));
        }

        $this->view->finding = $finding;

        $this->_acl->requirePrivilegeForObject('read', $finding);

        // Put a span around the comment count so that it can be updated from Javascript
        $commentCount = '<span id=\'findingCommentsCount\'>' . $finding->getComments()->count() . '</span>';

        $tabView = new Fisma_Yui_TabView('FindingView', $id);

        $tabView->addTab("Finding $id", "/finding/remediation/finding/id/$id/format/html");
        $mitigationUrl = "/finding/remediation/mitigation-strategy/id/$id/format/html$fromSearchUrl";
        $tabView->addTab("Workflow", "/workflow/workflow/format/html/model/finding/id/$id");
        $tabView->addTab("Mitigation Strategy", $mitigationUrl);
        $tabView->addTab("Risk Analysis", "/finding/remediation/risk-analysis/id/$id/format/html$fromSearchUrl");
        $tabView->addTab("Security Control", "/finding/remediation/security-control/id/$id/format/html");
        $tabView->addTab("Comments ($commentCount)", "/finding/remediation/comments/id/$id/format/html");
        $tabView->addTab(
            $this->view->escape($this->view->translate('Finding_Attachments')) .
            " (" . $finding->Attachments->count() . ")",
            "/finding/remediation/artifacts/id/$id/format/html$fromSearchUrl"
        );
        $tabView->addTab("Audit Log", "/finding/remediation/audit-log/id/$id/format/html");

        $this->view->tabView = $tabView;

        $buttons = $this->getToolbarButtons($finding);

        // printer friendly version
        $buttons['print'] = new Fisma_Yui_Form_Button_Link(
            'toolbarPrintButton',
            array(
                'value' => 'Printer Friendly',
                'href' => $this->getBaseUrl() . '/print/id/' . $id,
                'imageSrc' => '/images/printer.png',
                'target' => '_blank'
            )
        );

        $searchButtons = $this->getSearchButtons($finding, $fromSearchParams);
        $this->view->toolbarButtons = $buttons;
        $this->view->searchButtons = $searchButtons;
    }

    /**
     * Printer-friendly version of the finding view page.
     *
     * @GETAllowed
     * @return void
     */
    public function printAction()
    {
        $this->findingAction();
        $this->mitigationStrategyAction();
        $this->riskAnalysisAction();
        $this->securityControlAction();
        $this->commentsAction();
        $this->artifactsAction();
        $this->auditLogAction();
    }

    /**
     * Display comments for this finding
     *
     * @GETAllowed
     */
    public function commentsAction()
    {
        $id = $this->_request->getParam('id');
        $this->view->assign('id', $id);
        $finding = $this->_getSubject($id);

        $this->_acl->requirePrivilegeForObject('read', $finding);

        $comments = $finding->getComments()->fetch(Doctrine::HYDRATE_ARRAY);

        $commentRows = array();

        foreach ($comments as $comment) {
            $commentTs = new Zend_Date($comment['createdTs'], Fisma_Date::FORMAT_DATETIME);
            $commentTs->setTimezone('UTC');
            $commentDateTime = $commentTs->toString(Fisma_Date::FORMAT_MONTH_DAY_YEAR)
                                  . ' at '
                                  . $commentTs->toString(Fisma_Date::FORMAT_AM_PM_TIME);
            $commentTs->setTimezone(CurrentUser::getAttribute('timezone'));
            $commentDateTimeLocal = $commentTs->toString(Fisma_Date::FORMAT_MONTH_DAY_YEAR)
                                  . ' at '
                                  . $commentTs->toString(Fisma_Date::FORMAT_AM_PM_TIME);
            $commentRows[] = array(
                'timestamp' => Zend_Json::encode(array("local" => $commentDateTimeLocal, "utc" => $commentDateTime)),
                'unixtimestamp' => $commentTs->getTimestamp(),
                'username' => $this->view->userInfo($comment['User']['displayName'], $comment['User']['id']),
                'comment' =>  $this->view->textToHtml($this->view->escape($comment['comment'])),
                'delete' => (($comment['User']['id'] === CurrentUser::getAttribute('id'))
                    ? '/comment/remove/format/json/id/' . $id . '/type/Finding/commentId/' . $comment['id']
                    : ''
                )
            );
        }

        $dataTable = new Fisma_Yui_DataTable_Local();

        $dataTable->addColumn(
            new Fisma_Yui_DataTable_Column(
                'Timestamp',
                true,
                'Fisma.TableFormat.formatDateTimeLocal',
                null,
                'timestamp',
                false,
                'string',
                'unixtimestamp'

            )
        );

        $dataTable->addColumn(
            new Fisma_Yui_DataTable_Column(
                'User',
                true,
                'Fisma.TableFormat.formatHtml',
                null,
                'username'
            )
        );

        $dataTable->addColumn(
            new Fisma_Yui_DataTable_Column(
                'Comment',
                false,
                'Fisma.TableFormat.formatHtml',
                null,
                'comment'
            )
        );

        $dataTable->addColumn(
            new Fisma_Yui_DataTable_Column(
                'Action',
                false,
                'Fisma.TableFormat.deleteControl',
                null,
                'delete'
            )
        );

        $dataTable->setData($commentRows);

        $this->view->commentDataTable = $dataTable;

        $commentButton = new Fisma_Yui_Form_Button(
            'commentButton',
            array(
                'label' => 'Add Comment',
                'onClickFunction' => 'Fisma.Commentable.showPanel',
                'onClickArgument' => array(
                    'id' => $id,
                    'type' => 'Finding',
                    'callback' => array(
                        'object' => 'Finding',
                        'method' => 'commentCallback'
                    )
                )
            )
        );

        if (!$this->_acl->hasPrivilegeForObject('comment', $finding) || $finding->isDeleted()) {
            $commentButton->readOnly = true;
        }

        $this->view->commentButton = $commentButton;
    }

    /**
     * Modify the finding
     *
     * @return void
     */
    public function modifyAction()
    {
        $id = $this->_request->getParam('id');

        $fromSearchParams = $this->_getFromSearchParams($this->_request);
        $fromSearchUrl = $this->_helper->makeUrlParams($fromSearchParams);

        $findingData = $this->_request->getPost('finding', array());
        $findingSecurityControlId = $this->getRequest()->getPost('securityControlId');
        if (!empty($findingSecurityControlId)) {
            $findingData['securityControlId'] = $findingSecurityControlId;
        }

        $this->_forward('view', null, null, array('id' => $id));

        if (isset($findingData['currentEcd'])) {
            if (Zend_Validate::is($findingData['currentEcd'], 'Date')) {
                $date = new Zend_Date();
                $ecd  = new Zend_Date($findingData['currentEcd'], Fisma_Date::FORMAT_DATE);

                if ($ecd->isEarlier($date)) {
                    $error = 'Expected completion date has been set before the current date.'
                           . ' Make sure that this is correct.';
                    $this->view->priorityMessenger($error, 'warning');
                }
            } else {
                $error = 'Expected completion date provided is not a valid date. Unable to update finding.';
                $this->view->priorityMessenger($error, 'error');
                return;
            }
        }

        if (isset($findingData['threatLevel']) && $findingData['threatLevel'] === '') {
            $error = 'Threat Level is a required field.';
            $this->view->priorityMessenger($error, 'error');
            return;
        }

        if (
            isset($findingData['countermeasuresEffectiveness']) &&
            $findingData['countermeasuresEffectiveness'] === ''
        ) {
            $error = 'Countermeasures Effectiveness is a required field.';
            $this->view->priorityMessenger($error, 'error');
            return;
        }

        $finding = $this->_getSubject($id);
        $this->_acl->requirePrivilegeForObject('update', $finding);

        // Security control is a hidden field. If it is blank, that means the user did not submit it, and it needs to
        // be unset.
        if (empty($findingData['securityControlId'])) {
            unset($findingData['securityControlId']);
        }

        try {
            Doctrine_Manager::connection()->beginTransaction();
            $finding->merge($findingData);
            $finding->save();
            Doctrine_Manager::connection()->commit();

            $this->_redirect("/finding/remediation/view/id/$id$fromSearchUrl");
        } catch (Fisma_Zend_Exception_User $e) {
            $this->view->priorityMessenger($e->getMessage(), 'error');
        } catch (Exception $e) {
            Doctrine_Manager::connection()->rollback();
            $message = "Error: Unable to update finding. ";
            if (Fisma::debug()) {
                $message .= $e->getMessage();
            }
            $this->view->priorityMessenger($message, 'error');
        }
    }

    /**
     * Upload evidence
     *
     * @return void
     */
    public function uploadEvidenceAction()
    {
        $id = $this->_request->getParam('id');
        $finding = Doctrine_Query::create()
            ->from('Finding f')->leftJoin('f.Attachments')->where('f.id = ?', $id)
            ->fetchOne();

        if ($finding->isDeleted()) {
            $message = "Evidence cannot be uploaded to a deleted finding.";
            throw new Fisma_Zend_Exception_User($message);
        }

        if ($finding->CurrentStep && !$finding->CurrentStep->attachmentEditable) {
            $message = "Evidence cannot be uploaded in the current workflow step.";
            throw new Fisma_Zend_Exception_User($message);
        }

        $this->_acl->requirePrivilegeForObject('update', $finding);

        $fromSearchParams = $this->_getFromSearchParams($this->_request);
        $fromSearchUrl = $this->_helper->makeUrlParams($fromSearchParams);

        try {
            $auditMessages = array();
            $errorMessages = "";
            for ($i = 0; $i<count($_FILES['evidence']['name']); $i++) {
                // PHP handles multiple uploads as $_FILES['element_name']['attribute'][idx]
                // instead of $_FILES['element_name'][idx]['attribute'], so we need to manually remap it
                $file = array();
                foreach ($_FILES['evidence'] as $index => $value) {
                    $file[$index] = $value[$i];
                }

                if (!empty($file['name'])) {
                    if (Fisma_FileManager::getUploadFileError($file)) {
                        $errorMessages .= Fisma_FileManager::getUploadFileError($file);
                        continue;
                    } else {
                        $duplicated = false;
                        foreach ($finding->Attachments as $index => $attachment) {
                            if ($attachment->fileName == $file['name']) {
                                $auditMessages[] = "Evidence replaced: {$attachment->fileName} (#{$attachment->id})";
                                $finding->Attachments->remove($index);
                                $duplicated = true;
                                break;
                            }
                        }
                        if (!$duplicated) {
                            $auditMessages[] = "Evidence uploaded: \"{$file['name']}\"";
                        }
                        $finding->attach($file);
                    }
                }
            }

            // If no uploaded files were successful processed, throw a fatal error
            if (count($auditMessages)==0 && empty($errorMessages)) {
                $message = "You did not select any file to upload. Please select a file and try again.";
                throw new Fisma_Zend_Exception_User($message);
            }

            if (count($auditMessages) > 0) {
                $finding->save();

                foreach ($auditMessages as $auditMessage) {
                    $finding->getAuditLog()->write($auditMessage);
                }
            }

            // Throw non-fatal error(s) after saving the Finding
            if (!empty($errorMessages)) {
                throw new Fisma_Zend_Exception_User($errorMessages);
            }
        } catch (Fisma_Zend_Exception_User $e) {
            $this->view->priorityMessenger($e->getMessage(), 'error');
        }

        $this->_redirect("/finding/remediation/view/id/$id$fromSearchUrl");
    }

    /**
     * Download evidence
     *
     * @GETAllowed
     * @return void
     */
    public function downloadEvidenceAction()
    {
        $id = $this->_request->getParam('id');
        $attachmentId = $this->_request->getParam('attachmentId');

        $finding = Doctrine::getTable('Finding')->getAttachmentQuery($id, $attachmentId)->execute()->getLast();

        if (empty($finding)) {
            throw new Fisma_Zend_Exception_User('Invalid finding ID');
        }
        if ($finding->Attachments->count() <= 0) {
            throw new Fisma_Zend_Exception_User('Invalid evidence ID');
        }

        // There is no ACL defined for evidence objects, access is only based on the associated finding:
        $this->_acl->requirePrivilegeForObject('read', $finding);

        $attachment = $finding->Attachments[0];
        $this->_helper->downloadAttachment($attachment->fileHash, $attachment->fileName);
    }

    /**
     * Delete evidence
     */
    public function deleteEvidenceAction()
    {
        $fromSearchParams = $this->_getFromSearchParams($this->_request);
        $fromSearchUrl = $this->_helper->makeUrlParams($fromSearchParams);

        $id = $this->_request->getParam('id');
        $attachmentId = $this->_request->getParam('attachmentId');

        $finding = Doctrine::getTable('Finding')->getAttachmentQuery($id, $attachmentId)->execute()->getLast();

        if (empty($finding)) {
            throw new Fisma_Zend_Exception_User('Invalid finding ID');
        }

        if ($finding->CurrentStep && !$finding->CurrentStep->attachmentEditable) {
            $message = "Evidence cannot be uploaded in the current workflow step.";
            throw new Fisma_Zend_Exception_User($message);
        }

        if ($finding->Attachments->count() <= 0) {
            throw new Fisma_Zend_Exception_User('Invalid evidence ID');
        }

        // There is no ACL defined for evidence objects, access is only based on the associated finding:
        $this->_acl->requirePrivilegeForObject('update', $finding);

        $message = "Evidence deleted: {$finding->Attachments[0]->fileName} (#{$finding->Attachments[0]->id})";
        $finding->Attachments->remove(0);
        $finding->save();

        $finding->getAuditLog()->write($message);
        $this->_redirect("/finding/remediation/view/id/$id$fromSearchUrl");
    }

    /**
     * Generate RAF report
     *
     * It can handle different format of RAF report.
     *
     * @GETAllowed
     * @return void
     */
    public function rafAction()
    {
        $id = $this->_request->getParam('id');
        $finding = $this->_getSubject($id);

        $this->_acl->requirePrivilegeForObject('read', $finding);

        try {
            if ($finding->threat == '' ||
                empty($finding->threatLevel) ||
                $finding->countermeasures == '' ||
                empty($finding->countermeasuresEffectiveness)) {
                throw new Fisma_Zend_Exception("The Threat or Countermeasures Information is not "
                    ."completed. An analysis of risk cannot be generated, unless these values are defined.");
            }

            $system = $finding->Organization->System;
            if (NULL == $system->fipsCategory) {
                throw new Fisma_Zend_Exception('The security categorization for ' .
                     '(' . $finding->responsibleOrganizationId . ')' .
                     $finding->Organization->name . ' is not defined. An analysis of ' .
                     'risk cannot be generated unless these values are defined.');
            }
            $this->view->securityCategorization = $system->fipsCategory;
        } catch (Fisma_Zend_Exception $e) {
            if ($e instanceof Fisma_Zend_Exception) {
                $message = $e->getMessage();
            }
            $this->view->priorityMessenger($message, 'error');
            $this->_forward('view', null, null, array('id' => $id));
            return;
        }
        $this->_helper->fismaContextSwitch()
               ->setHeader('pdf', 'Content-Disposition', "attachement;filename={$id}_raf.pdf")
               ->addActionContext('raf', array('pdf'))
               ->initContext();
        $this->view->finding = $finding;
    }

    /**
     * Display basic data about the finding and the affected asset
     *
     * @GETAllowed
     * @return void
     */
    function findingAction()
    {
        $this->_viewFinding();
        $table = Doctrine::getTable('Finding');

        $finding = $this->view->finding;
        $organization = $finding->Organization;

        // For users who can view organization or system URLs, construct that URL
        $controller = ($organization->OrganizationType->nickname == 'system' ? 'system' : 'organization');
        $idParameter = ($organization->OrganizationType->nickname == 'system' ? 'oid' : 'id');

        $this->view->isLegacyFindingKeyEditable = $this->_isEditable('legacyFindingKey', $table, $finding);
        $this->view->isPocEditable = $this->_isEditable('pocId', $table, $finding);
        $this->view->isSourceEditable = $this->_isEditable('sourceId', $table, $finding);
        $this->view->isOrganizationEditable = $this->_isEditable('responsibleOrganizationId', $table, $finding);
        $this->view->isDescriptionEditable = $this->_isEditable('description', $table, $finding);
        $this->view->isRecommendationEditable = $this->_isEditable('description', $table, $finding);
        $this->view->isAuditYearEditable = $this->_isEditable('auditYear', $table, $finding);

        $this->view->organizationViewUrl = "/$controller/view/$idParameter/$organization->id";

        $this->view->keywords = $this->_request->getParam('keywords');

        $currentEcd = new Zend_Date($finding->currentEcd, Fisma_Date::FORMAT_DATE);
        if (is_null($finding->currentEcd)) {
            $onTimeState = 'Missing ECD';
        } else {
            $ecdCompare = $currentEcd->compareDate(new Zend_Date());
            $onTimeState = ($ecdCompare >= 0)
                ? (($ecdCompare > 0)
                    ? ('On Time, ' .
                        ceil(abs(($currentEcd->getTimestamp() - time("now"))/(60*60*24))) . ' day(s) until due')
                    : ('Due Today')
                )
                : ('Overdue, ' .
                    floor(abs(($currentEcd->getTimestamp() - time("now"))/(60*60*24))) . ' day(s) late')
            ;
        }
        $this->view->onTimeState = $onTimeState;

        $discoveredDate = new Zend_Date($finding->discoveredDate, Fisma_Date::FORMAT_DATE);
        $discoveredDate->setTimezone(CurrentUser::getAttribute('timezone'));
        $this->view->discoveredDate = $discoveredDate->toString(Fisma_Date::FORMAT_MONTH_DAY_YEAR);
        $createdTs = new Zend_Date($finding->createdTs, Fisma_Date::FORMAT_DATE);
        $createdTs->setTimezone(CurrentUser::getAttribute('timezone'));
        $this->view->createdTs = $createdTs->toString(Fisma_Date::FORMAT_MONTH_DAY_YEAR);

        if (!is_null($finding->closedTs)) {
            $closedDate = new Zend_Date($finding->closedTs, Fisma_Date::FORMAT_DATE);
            $closedDate->setTimezone(CurrentUser::getAttribute('timezone'));
            $this->view->closedTs = $closedDate->toString(Fisma_Date::FORMAT_MONTH_DAY_YEAR);
        }

        $this->view->relationshipEditable = $this->_acl->hasPrivilegeForObject('update', $finding);
    }

    /**
     * Fields for defining the mitigation strategy
     *
     * @GETAllowed
     * @return void
     */
    function mitigationStrategyAction()
    {
        $this->_viewFinding();
        $finding = $this->view->finding;
        $table = Doctrine::getTable('Finding');

        $this->view->isMitigationStrategyEditable = $this->_isEditable('mitigationStrategy', $table, $finding);
        $this->view->isResourcesEditable = $this->_isEditable('resourcesRequired', $table, $finding);
        $this->view->isThreatLevelEditable = $this->_isEditable('threatLevel', $table, $finding);

    }

    /**
     * Display fields related to risk analysis such as threats and countermeasures
     *
     * @GETAllowed
     * @return void
     */
    function riskAnalysisAction()
    {
        $this->_viewFinding();
        $this->view->keywords = $this->_request->getParam('keywords');

        $finding = $this->view->finding;
        $table = Doctrine::getTable('Finding');

        $this->view->isThreatLevelEditable = $this->_isEditable('threatLevel', $table, $finding);
        $this->view->isThreatEditable = $this->_isEditable('threat', $table, $finding);
        $this->view->isCountermeasuresEditable = $this->_isEditable('countermeasures', $table, $finding);
        $this->view->isCountermeasuresEffectivenessEditable = $this->_isEditable(
                                                                                 'countermeasuresEffectiveness',
                                                                                 $table,
                                                                                 $finding);
    }

    /**
     * Display fields related to risk analysis such as threats and countermeasures
     *
     * Display evidence package and evaluations
     *
     * @GETAllowed
     * @return void
     */
    function artifactsAction()
    {
        $this->_viewFinding();

        // Get a list of artifacts related to this finding
        $artifactsQuery = Doctrine_Query::create()
                          ->from('Finding f')
                          ->leftJoin('f.Attachments a')
                          ->where('f.id = ?', $this->view->finding->id);

        $this->view->finding = $artifactsQuery->fetchOne();

        // Build the Evidence Package table
        $attachmentCollection = $this->view->finding->Attachments;
        $attachmentRows = array();

        foreach ($attachmentCollection as $attachment) {
            $createdTs = new Zend_Date($attachment->createdTs, Fisma_Date::FORMAT_DATETIME);
            $createdTs->setTimezone('UTC');
            $createdDateTime = $createdTs->toString(Fisma_Date::FORMAT_MONTH_DAY_YEAR)
                                  . ' at '
                                  . $createdTs->toString(Fisma_Date::FORMAT_AM_PM_TIME);
            $createdTs->setTimezone(CurrentUser::getAttribute('timezone'));
            $createdDateTimeLocal = $createdTs->toString(Fisma_Date::FORMAT_MONTH_DAY_YEAR)
                                  . ' at '
                                  . $createdTs->toString(Fisma_Date::FORMAT_AM_PM_TIME);

            $baseUrl = '/finding/remediation/';
            $currentUrl = '/id/' . $this->view->finding->id . '/attachmentId/' . $attachment->id;
            $attachmentRows[] = array(
                'iconUrl'      => "<a href='{$baseUrl}download-evidence{$currentUrl}'>"
                                 . "<img src='{$attachment->getIconUrl()}' alt='{$attachment->getFileType()}'></a>",
                'fileName'     => $this->view->escape($attachment->fileName),
                'fileNameLink' => "<a href='{$baseUrl}download-evidence{$currentUrl}'>"
                                . $this->view->escape($attachment->fileName) . "</a>",
                'fileSize'     => $attachment->getFileSize(),
                'user'         => $this->view->userInfo($attachment->User->displayName, $attachment->User->id),
                'date'         => Zend_Json::encode(array("local" => $createdDateTimeLocal, "utc" => $createdDateTime)),
                'delete'       => "{$baseUrl}delete-evidence{$currentUrl}{$this->view->fromSearchUrl}"
            );
        }

        $dataTable = new Fisma_Yui_DataTable_Local();

        $dataTable->addColumn(
            new Fisma_Yui_DataTable_Column(
                'Icon',
                false,
                'Fisma.TableFormat.formatHtml',
                null,
                'icon'
            )
        );

        $dataTable->addColumn(
            new Fisma_Yui_DataTable_Column(
                'File Name',
                false,
                null,
                null,
                'fileName',
                true
            )
        );

        $dataTable->addColumn(
            new Fisma_Yui_DataTable_Column(
                'File Name',
                true,
                'Fisma.TableFormat.formatHtml',
                null,
                'fileNameLink',
                false,
                'string',
                'fileName'
            )
        );

        $dataTable->addColumn(
            new Fisma_Yui_DataTable_Column(
                'Size',
                true,
                'Fisma.TableFormat.formatFileSize',
                null,
                'fileSize',
                false,
                'number'
            )
        );

        $dataTable->addColumn(
            new Fisma_Yui_DataTable_Column(
                'Uploaded By',
                true,
                'Fisma.TableFormat.formatHtml',
                null,
                'user'
            )
        );

        $dataTable->addColumn(
            new Fisma_Yui_DataTable_Column(
                'Upload Date',
                true,
                'Fisma.TableFormat.formatDateTimeLocal',
                null,
                'date'
            )
        );

        if (
            $this->_acl->hasPrivilegeForObject('update', $this->view->finding) &&
            !$this->view->finding->isDeleted() &&
            (!$this->view->finding->CurrentStep || $this->view->finding->CurrentStep->attachmentEditable)
        ) {
            $dataTable->addColumn(
                new Fisma_Yui_DataTable_Column(
                    'Action',
                    false,
                    'Fisma.TableFormat.deleteControl',
                    null,
                    'delete'
                )
            );
        }

        $dataTable->setData($attachmentRows);
        $this->view->evidencePackage = $dataTable;
    }

    /**
     * Display the audit log associated with a finding
     *
     * @GETAllowed
     * @return void
     */
    function auditLogAction()
    {
        $this->_viewFinding();

        $logs = $this->view->finding->getAuditLog()->fetch(Doctrine::HYDRATE_SCALAR);

        $logRows = array();
        foreach ($logs as $log) {
            $logRows[] = array(
                'timestamp' => $log['o_createdTs'],
                'user' => empty($log['u_id']) ? '' : $this->view->userInfo($log['u_displayName'], $log['u_id']),
                'message' =>  $this->view->textToHtml($this->view->escape($log['o_message']))
            );
        }

        $dataTable = new Fisma_Yui_DataTable_Local();

        $dataTable->addColumn(
            new Fisma_Yui_DataTable_Column(
                'Timestamp',
                true,
                null,
                null,
                'timestamp'
            )
        );

        $dataTable->addColumn(
            new Fisma_Yui_DataTable_Column(
                'User',
                true,
                'Fisma.TableFormat.formatHtml',
                null,
                'username'
            )
        );

        $dataTable->addColumn(
            new Fisma_Yui_DataTable_Column(
                'Message',
                false,
                'Fisma.TableFormat.formatHtml',
                null,
                'message'
            )
        );

        $dataTable->setData($logRows);

        $this->view->auditLogDataTable = $dataTable;
    }

    /**
     * Display the NIST SP 800-53 control mapping and related information
     *
     * @GETAllowed
     * @return void
     */
    function securityControlAction()
    {
        $this->_viewFinding();

        $form = Fisma_Zend_Form_Manager::loadForm('finding_security_control');

        // Set up the available and default values for the form
        $scId = $this->view->finding->securityControlId;
        if (!empty($scId)) {
            $sc = $this->view->finding->SecurityControl;
            $c = $sc->Catalog;
            $name = sprintf("%s %s [%s]", $sc->code, $sc->name, $c->name);
            $form->getElement('securityControlId')->setValue($scId);
            $form->getElement('securityControlAutocomplete')->setValue($name);
        }

        $form->setDefaults($this->getRequest()->getParams());

        // Don't want any form markup (since this is being embedded into an existing form), just the form elements
        $form->setDecorators(
            array(
                'FormElements',
                array('HtmlTag', array('tag' => 'span'))
            )
        );

        $form->setElementDecorators(array('RenderSelf', 'Label'), array('securityControlAutocomplete'));

        $this->view->form = $form;

        $securityControlSearchButton = new Fisma_Yui_Form_Button(
            'securityControlSearchButton',
            array(
                'label' => 'Edit Security Control Mapping',
                'onClickFunction' => 'Fisma.Finding.showSecurityControlSearch'
            )
        );

        if ($this->view->finding->isDeleted()) {
            $securityControlSearchButton->readOnly = true;
        }

        if (!$this->view->finding->canEdit('securityControlId')) {
            $securityControlSearchButton->readOnly = true;
        }

        $this->view->securityControlSearchButton = $securityControlSearchButton;
    }

    /**
     * Renders the form for uploading artifacts.
     *
     * @GETAllowed
     * @return void
     */
    function uploadFormAction()
    {
        $this->_helper->layout()->disableLayout();

        $form = Fisma_Zend_Form_Manager::loadForm('finding_upload_evidence');
        $form->setAttrib('onsubmit', "return Fisma.Remediation.uploadEvidenceValidate();");
        $form->setAttrib('id', "finding_detail_upload_evidence");
        $form->setAttrib('name', "finding_detail_upload_evidence");
        $this->view->form = $form;
    }

    /**
     * Get the finding and assign it to view
     *
     * @return void
     */
    private function _viewFinding()
    {
        $id = $this->_request->getParam('id');
        $finding = $this->_getSubject($id);
        $finding->loadReference('Organization');
        $finding->loadReference('ParentOrganization');

        $fromSearchParams = $this->_getFromSearchParams($this->_request);
        $fromSearchUrl = $this->_helper->makeUrlParams($fromSearchParams);

        // Check that the user is permitted to view this finding
        $this->_acl->requirePrivilegeForObject('read', $finding);
        $this->view->finding = $finding;
        $this->view->fromSearchUrl = $fromSearchUrl;

        $nextDueDate = new Zend_Date($finding->nextDueDate, Fisma_Date::FORMAT_DATE);
        if (is_null($finding->nextDueDate)) {
            $workflowOnTimeState = 'N/A';
        } else {
            $workflowCompare = $nextDueDate->compareDate(new Zend_Date());
            $workflowOnTimeState = (($workflowCompare >= 0)
                ? (($workflowCompare > 0)
                    ? ('On Time' . ', ' .
                        ceil(abs(($nextDueDate->getTimestamp() - time("now"))/(60*60*24))) .
                        ' day(s) remaining.')
                    : 'Due Today'
                )
                : (
                    'Overdue by ' .
                    floor(abs(($nextDueDate->getTimestamp() - time("now"))/(60*60*24))) .
                    ' day(s).'
                )
            );
        }
        $this->view->workflowOnTimeState = $workflowOnTimeState;
    }

    /**
     * Override createAction() to show the warning message on the finding create page if there is no system.
     *
     * @GETAllowed
     * @return void
     */
    public function createAction()
    {
        parent::createAction();

        $systemCount = $this->_me->getOrganizationsByPrivilegeQuery('finding', 'create')->count();
        if (0 === $systemCount) {
            $message = "There are no organizations or systems to create findings for. "
                     . "Please create an organization or system first.";
            $this->view->priorityMessenger($message, 'error');
        }
    }

    /**
     * Check whether a field is editable by its column metadata of requiredPrivilege and/or requiredUpdateStatus.
     *
     * @param string $column The column name.
     * @param Doctrine_Table $table The finding table object.
     * @param Doctrine_Record $finding The finding object.
     * @return bool
     */
    private function _isEditable($column, $table, $finding)
    {
        $privilege = $this->_acl->hasPrivilegeForObject('update', $finding);
        return $privilege && $finding->canEdit($column);
    }
}
