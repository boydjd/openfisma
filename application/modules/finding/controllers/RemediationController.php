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
            // If this is a mitigation, evidence approval, or evidence upload, then redirect to the
            // corresponding controller action
            if (isset($_POST['submit_msa'])) {
                $request->setParam('sub', null);
                $this->_forward('msa');
            } elseif (isset($_POST['submit_ea'])) {
                $request->setParam('sub', null);
                $this->_forward('evidence');
            } elseif (isset($_POST['upload_evidence'])) {
                $request->setParam('sub', null);
                $this->_forward('upload-evidence');
            } elseif (isset($_POST['reject_evidence'])) {
                $request->setParam('sub', null);
                $this->_forward('evidence');
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
        $tabView->addTab("Mitigation Strategy", $mitigationUrl);
        $tabView->addTab("Risk Analysis", "/finding/remediation/risk-analysis/id/$id/format/html$fromSearchUrl");
        $tabView->addTab("Security Control", "/finding/remediation/security-control/id/$id/format/html");
        $tabView->addTab("Comments ($commentCount)", "/finding/remediation/comments/id/$id/format/html");
        $tabView->addTab(
            "Evidence (" . $finding->Attachments->count() . ")",
            "/finding/remediation/artifacts/id/$id/format/html$fromSearchUrl"
        );
        $tabView->addTab("Audit Log", "/finding/remediation/audit-log/id/$id/format/html");

        $this->view->tabView = $tabView;

        $buttons = $this->getToolbarButtons($finding);

        // Only display controls if the finding has not been deleted
        if (!$finding->isDeleted()) {
            // The "save" and "discard" buttons are only displayed if the user can update any of the findings fields
            if ($this->view->acl()->hasPrivilegeForObject('update_*', $finding)) {
                $buttons['submitButton'] = new Fisma_Yui_Form_Button_Submit(
                    'saveChanges',
                    array(
                        'label' => 'Save',
                        'imageSrc' => '/images/ok.png',
                    )
                );

                $buttons['discardButton'] = new Fisma_Yui_Form_Button_Link(
                    'discardChanges',
                    array(
                        'value' => 'Discard',
                        'imageSrc' => '/images/no_entry.png',
                        'href' => '/finding/remediation/view/id/' . $finding->id . $fromSearchUrl
                    )
                );
            }

            // Display the delete finding button if the user has the delete finding privilege
            if ($this->view->acl()->hasPrivilegeForObject('delete', $finding)) {
                $args = array(null, '/finding/remediation/delete/', $id);
                $buttons['delete'] = new Fisma_Yui_Form_Button(
                    'deleteFinding',
                    array(
                          'label' => 'Delete',
                          'imageSrc' => '/images/trash_recyclebin_empty_closed.png',
                          'onClickFunction' => 'Fisma.Util.showConfirmDialog',
                          'onClickArgument' => array(
                              'args' => $args,
                              'text' => "WARNING: You are about to delete the finding record. This action cannot be "
                                        . "undone. Do you want to continue?",
                              'func' => 'Fisma.Util.formPostAction'
                        )
                    )
                );
            }
        }

        // printer friendly version
        $buttons['print'] = new Fisma_Yui_Form_Button_Link(
            'toolbarPrintButton',
            array(
                'value' => 'Printer Friendly Version',
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
            $commentRows[] = array(
                'timestamp' => $comment['createdTs'],
                'username' => $this->view->userInfo($comment['User']['displayName'], $comment['User']['id']),
                'Comment' =>  $this->view->textToHtml($this->view->escape($comment['comment']))
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
                'Comment',
                false,
                'Fisma.TableFormat.formatHtml',
                null,
                'comment'
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
        // ACL for finding objects is handled inside the finding listener, because it has to do some
        // very fine-grained error checking

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
                    $this->view->priorityMessenger($error, 'notice');
                }
            } else {
                $error = 'Expected completion date provided is not a valid date. Unable to update finding.';
                $this->view->priorityMessenger($error, 'warning');
                return;
            }
        }

        if (isset($findingData['threatLevel']) && $findingData['threatLevel'] === '') {
            $error = 'Threat Level is a required field.';
            $this->view->priorityMessenger($error, 'warning');
            return;
        }

        if (
            isset($findingData['countermeasuresEffectiveness']) &&
            $findingData['countermeasuresEffectiveness'] === ''
        ) {
            $error = 'Countermeasures Effectiveness is a required field.';
            $this->view->priorityMessenger($error, 'warning');
            return;
        }

        $finding = $this->_getSubject($id);

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
            $this->view->priorityMessenger($e->getMessage(), 'warning');
        } catch (Exception $e) {
            Doctrine_Manager::connection()->rollback();
            $message = "Error: Unable to update finding. ";
            if (Fisma::debug()) {
                $message .= $e->getMessage();
            }
            $model = 'warning';
            $this->view->priorityMessenger($message, $model);
        }
    }

    /**
     * Mitigation Strategy Approval Process
     *
     * @return void
     */
    public function msaAction()
    {
        $id       = $this->_request->getParam('id');
        $do       = $this->_request->getParam('do');
        $decision = $this->_request->getPost('decision');

        $fromSearchParams = $this->_getFromSearchParams($this->_request);
        $fromSearchUrl = $this->_helper->makeUrlParams($fromSearchParams);

        $finding  = $this->_getSubject($id);
        if (!empty($decision)) {
            $this->_acl->requirePrivilegeForObject($finding->CurrentEvaluation->Privilege->action, $finding);
        }

        try {
            Doctrine_Manager::connection()->beginTransaction();

            if ('submitmitigation' == $do) {
                $this->_acl->requirePrivilegeForObject('mitigation_strategy_submit', $finding);
                $finding->submitMitigation(CurrentUser::getInstance());
            }
            if ('revisemitigation' == $do) {
                $this->_acl->requirePrivilegeForObject('mitigation_strategy_revise', $finding);
                $finding->reviseMitigation(CurrentUser::getInstance());
            }

            if ('APPROVED' == $decision) {
                $comment = $this->_request->getParam('comment');
                $finding->approve(CurrentUser::getInstance(), $comment);
            }

            if ('DENIED' == $decision) {
                $comment = $this->_request->getParam('comment');
                $finding->deny(CurrentUser::getInstance(), $comment);
            }
            Doctrine_Manager::connection()->commit();
        } catch (Doctrine_Connection_Exception $e) {
            Doctrine_Manager::connection()->rollback();
            $message = 'Failure in this operation. '
                     . $e->getPortableMessage()
                     . ' ('
                     . $e->getPortableCode()
                     . ')';
            if (Fisma::debug()) {
                $message .= $e->getMessage();
            }
            $model = 'warning';
            $this->view->priorityMessenger($message, $model);
        }

        $this->_redirect("/finding/remediation/view/id/$id$fromSearchUrl");
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

        $this->_acl->requirePrivilegeForObject('upload_evidence', $finding);

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
            $this->view->priorityMessenger($e->getMessage(), 'warning');
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
        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        $id = $this->_request->getParam('id');
        $attachmentId = $this->_request->getParam('attachmentId');

        $finding = Doctrine::getTable('Finding')->getAttachmentQuery($id, $attachmentId)->execute()->getLast();

        if (empty($finding)) {
            throw new Fisma_Zend_Exception_User('Invalid finding ID');
        }

        if (!in_array($finding->status, array('EN', 'EA'))) {
            $message = "Evidence Package can only be modified in EN and EA status.";
            throw new Fisma_Zend_Exception_User($message);
        }

        if ($finding->Attachments->count() <= 0) {
            throw new Fisma_Zend_Exception_User('Invalid evidence ID');
        }

        // There is no ACL defined for evidence objects, access is only based on the associated finding:
        $this->_acl->requirePrivilegeForObject('upload_evidence', $finding);

        $message = "Evidence deleted: {$finding->Attachments[0]->fileName} (#{$finding->Attachments[0]->id})";
        $finding->Attachments->remove(0);
        $finding->save();

        $finding->getAuditLog()->write($message);
    }

    /**
     * Handle the submit evidence package action
     *
     * @return void
     */
    public function submitEvidenceAction()
    {
        $id = $this->_request->getParam('id');
        $finding = $this->_getSubject($id);

        $fromSearchParams = $this->_getFromSearchParams($this->_request);
        $fromSearchUrl = $this->_helper->makeUrlParams($fromSearchParams);

        if ($finding->isDeleted()) {
            $message = "Evidence cannot be uploaded to a deleted finding.";
            throw new Fisma_Zend_Exception_User($message);
        }

        $this->_acl->requirePrivilegeForObject('upload_evidence', $finding);

        try {
            $finding->submitEvidence();
        } catch (Fisma_Zend_Exception $e) {
            $this->view->priorityMessenger($e->getMessage(), 'warning');
        }

        $this->_redirect("/finding/remediation/view/id/$id$fromSearchUrl");

    }

    /**
     * Handle the evidence evaluations
     *
     * @return void
     */
    public function evidenceAction()
    {
        $id       = $this->_request->getParam('id');
        $decision = $this->_request->getPost('decision');

        $finding  = $this->_getSubject($id);

        $fromSearchParams = $this->_getFromSearchParams($this->_request);
        $fromSearchUrl = $this->_helper->makeUrlParams($fromSearchParams);

        if ($fromSearchUrl) {
            $this->view->fromSearchUrl = $fromSearchUrl;
        }
        if (!empty($decision)) {
            $this->_acl->requirePrivilegeForObject($finding->CurrentEvaluation->Privilege->action, $finding);
        }

        try {
            Doctrine_Manager::connection()->beginTransaction();
            $comment = $this->_request->getParam('comment');

            if ('APPROVED' == $decision) {
                $finding->approve(CurrentUser::getInstance(), $comment);
            }

            if ('DENIED' == $decision) {
                $finding->deny(CurrentUser::getInstance(), $comment);
            }

            if ('REJECTED' == $decision) {
                $targetStatus = $this->_request->getPost('target_status');
                $finding->rejectTo(CurrentUser::getInstance(), $comment, $targetStatus);
            }
            Doctrine_Manager::connection()->commit();
        } catch (Doctrine_Exception $e) {
            Doctrine_Manager::connection()->rollback();
            $message = "Failure in this operation. ";
            if (Fisma::debug()) {
                $message .= $e->getMessage();
            }
            $model = 'warning';
            $this->view->priorityMessenger($message, $model);
        } catch (Fisma_Zend_Exception_User $e) {
            $message = $e->getMessage();
            $model = 'warning';
            $this->view->priorityMessenger($message, $model);
        }

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
            $this->view->priorityMessenger($message, 'warning');
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

        $this->view->organizationViewUrl = "/$controller/view/$idParameter/$organization->id";

        $this->view->keywords = $this->_request->getParam('keywords');

        $nextDueDate = new Zend_Date($finding->nextDueDate, Fisma_Date::FORMAT_DATE);
        if (is_null($finding->nextDueDate)) {
            $onTimeState = 'N/A';
        } else {
            $onTimeState = ($nextDueDate->compareDate(new Zend_Date()) >= 0) ? 'On Time' : 'Overdue';
        }

        $this->view->onTimeState = $onTimeState;
        $discoveredDate = new Zend_Date($finding->discoveredDate, Fisma_Date::FORMAT_DATE);
        $this->view->discoveredDate = $discoveredDate->toString(Fisma_Date::FORMAT_MONTH_DAY_YEAR);
        $createdTs = new Zend_Date($finding->createdTs, Fisma_Date::FORMAT_DATE);
        $this->view->createdTs = $createdTs->toString(Fisma_Date::FORMAT_MONTH_DAY_YEAR);

        if (!is_null($finding->closedTs)) {
            $closedDate = new Zend_Date($finding->closedTs, Fisma_Date::FORMAT_DATE);
            $this->view->closedTs = $closedDate->toString(Fisma_Date::FORMAT_MONTH_DAY_YEAR);
        }
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

        $this->view->isTypeEditable = $this->_isEditable('type', $table, $finding);
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
                          ->leftJoin('f.FindingEvaluations fe')
                          ->leftJoin('fe.User u2')
                          ->where('f.id = ?', $this->view->finding->id);

        $this->view->finding = $artifactsQuery->fetchOne();

        // Get a list of all evaluations so that the ones which are skipped or pending can still be rendered.
        $evaluationsQuery = Doctrine_Query::create()
                            ->from('Evaluation e')
                            ->where('e.approvalGroup = ?', 'evidence')
                            ->orderBy('e.precedence');

        $this->view->evaluations = $evaluationsQuery->execute();

        // Build the Evidence Package table
        $attachmentCollection = $this->view->finding->Attachments;
        $attachmentRows = array();

        foreach ($attachmentCollection as $attachment) {
            $baseUrl = '/finding/remediation/';
            $currentUrl = '/id/' . $this->view->finding->id . '/attachmentId/' . $attachment->id;
            $attachmentRows[] = array(
                'iconUrl'      => "<a href=\"{$baseUrl}download-evidence{$currentUrl}\">"
                                 . "<img src=\"{$attachment->getIconUrl()}\"></a>",
                'fileName'     => $this->view->escape($attachment->fileName),
                'fileNameLink' => "<a href=\"{$baseUrl}download-evidence{$currentUrl}\">"
                                . $this->view->escape($attachment->fileName) . "</a>",
                'fileSize'     => $attachment->getFileSize(),
                'user'         => $this->view->userInfo($attachment->User->displayName, $attachment->User->id),
                'date'         => $attachment->createdTs,
                'action'       => 'Delete',
                'id'           => $this->view->finding->id,
                'attachmentId' => $attachment->id
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
                'size',
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
                'uploadedBy'
            )
        );

        $dataTable->addColumn(
            new Fisma_Yui_DataTable_Column(
                'Upload Date',
                true,
                null,
                null,
                'uploadDate'
            )
        );

        if (
            $this->_acl->hasPrivilegeForObject('upload_evidence', $this->view->finding) &&
            !$this->view->finding->isDeleted() &&
            in_array($this->view->finding->status, array('EN', 'EA'))
        ):
            $dataTable->addColumn(
                new Fisma_Yui_DataTable_Column(
                    'Action',
                    true,
                    'YAHOO.widget.DataTable.formatButton',
                    null,
                    null
                )
            );

            $dataTable->addColumn(
                new Fisma_Yui_DataTable_Column(
                    'id',
                    null,
                    null,
                    null,
                    null,
                    true
                )
            );

            $dataTable->addColumn(
                new Fisma_Yui_DataTable_Column(
                    'attachmentId',
                    null,
                    null,
                    null,
                    null,
                    true
                )
            );

            $dataTable->addEventListener("buttonClickEvent", 'Fisma.Finding.deleteEvidence');
        endif;

        $dataTable->setData($attachmentRows);
        $this->view->evidencePackage = $dataTable;

        // Build the Evidence Package approval history
        $approvalHistory = array();
        for ($i = $this->view->finding->FindingEvaluations->count(); $i > 0; $i--) {
            $findingEvaluation = $this->view->finding->FindingEvaluations->get($i - 1);
            if ($findingEvaluation->Evaluation->approvalGroup != 'evidence'):
                continue;
            endif;
            $approvalHistory[] = $findingEvaluation;
        }
        $this->view->approvalHistory = $approvalHistory;
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

        if ($this->view->finding->status != 'NEW' &&  $this->view->finding->status != 'DRAFT') {
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
     * Renders the form for rejecting evidence.
     *
     * @GETAllowed
     * @return void
     */
    function rejectEvidenceAction()
    {
        $this->_helper->layout()->disableLayout();
        $id = $this->_request->getParam('id');
        $previousEvaluationsQuery = Doctrine::getTable('Evaluation')->getPreviousEvaluationsQuery($id);
        $this->view->previousEvaluations = $previousEvaluationsQuery->execute();
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
        $orgNickname = $finding->Organization->nickname;

        $fromSearchParams = $this->_getFromSearchParams($this->_request);
        $fromSearchUrl = $this->_helper->makeUrlParams($fromSearchParams);

        // Check that the user is permitted to view this finding
        $this->_acl->requirePrivilegeForObject('read', $finding);
        $this->view->finding = $finding;
        $this->view->fromSearchUrl = $fromSearchUrl;
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
            $this->view->priorityMessenger($message, 'warning');
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
        $editable = false;

        $fieldDefinition = $table->getDefinitionOf($column);

        if (isset($fieldDefinition['extra'])
             && isset ($fieldDefinition['extra']['requiredUpdateStatus'])) {

            $updateStatus = $fieldDefinition['extra']['requiredUpdateStatus'];
        }

        if (isset($fieldDefinition['extra'])
             && isset ($fieldDefinition['extra']['requiredPrivilege'])) {

            $updatePrivilege = $fieldDefinition['extra']['requiredPrivilege'];
        }

        if (!$finding->isDeleted()
            && isset($updatePrivilege) && $this->_acl->hasPrivilegeForObject($updatePrivilege, $finding)) {

            // Some fields might not need to check status such as POC
            if (!isset($updateStatus) || (isset($updateStatus) && in_array($finding->status, $updateStatus))) {
                $editable = true ;
            }
        }

        return $editable;
    }
}
