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
 * Handles CRUD for "system" objects.
 *
 * @author     Ryan Yang <ryan@users.sourceforge.net>
 * @copyright  (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Controller
 */
class SystemController extends Fisma_Zend_Controller_Action_Object
{
    /**
     * The main name of the model.
     *
     * This model is the main subject which the controller operates on.
     *
     * @var string
     */
    protected $_modelName = 'System';

    /**
     * All privileges to system objects are based on the parent 'Organization' objects
     */
    protected $_aclResource = 'Organization';

    /**
     * Initialize internal members.
     *
     * @return void
     */
    public function init()
    {
        parent::init();

        $this->_helper->ajaxContext()
            ->addActionContext('convert-to-organization-form', 'html')
            ->addActionContext('assets', 'html')
            ->addActionContext('form', 'html')
            ->initContext();
    }

    /**
     * View the specified system
     *
     * @GETAllowed
     * @return void
     */
    public function viewAction()
    {
        // Either 'id' (system ID) or 'oid' (organization ID) is required
        $id = $this->getRequest()->getParam('id');
        $organizationId = $this->getRequest()->getParam('oid');

        $fromSearchParams = $this->_getFromSearchParams($this->getRequest());
        $fromSearchUrl = $this->_helper->makeUrlParams($fromSearchParams);

        if ($id) {
            $organization = Doctrine::getTable('Organization')->findOneBySystemId($id);
        } elseif ($organizationId) {
            $organization = Doctrine::getTable('Organization')->find($organizationId);
            $id = $organization->System->id;
            $this->getRequest()->setParam('id', $id);
        } else {
            throw new Fisma_Zend_Exception("Required parameter 'id' or 'oid' is missing.");
        }

        $this->_acl->requirePrivilegeForObject('read', $organization);

        $this->view->organization = $organization;

        $tabView = new Fisma_Yui_TabView('SystemView', $id);

        $firstTab = $this->view->escape($organization->name)
                  . ' (' . $this->view->escape($organization->nickname) . ')';
        $tabView->addTab($firstTab, "/system/system/id/$id");
        $tabView->addTab("FIPS-199", "/sa/security-authorization/cat/id/$id/format/html/readonly/true");
        $tabView->addTab("FISMA Data", "/system/fisma/id/$id");
        $tabView->addTab($this->view->escape($this->view->translate('System_Attachments')), "/system/artifacts/id/$id");
        $tabView->addTab("Assets", "/system/assets/id/$id/format/html");
        $tabView->addTab("Users", "/system/user/id/$id");

        $findingSearchUrl = '/finding/remediation/list?q=/organization/textExactMatch/'
                          . $this->view->escape($organization->nickname, 'url');

        $view = Zend_Layout::getMvcInstance()->getView();

        $buttons = $this->getToolbarButtons($organization);

        if ($this->_acl->hasPrivilegeForClass('create', 'Organization')) {
            $buttons['convertToOrgButton'] = new Fisma_Yui_Form_Button(
                'convertToOrg',
                array(
                    'label' => 'Convert To Organization',
                    'onClickFunction' => 'Fisma.System.convertToOrgOrSystem',
                    'onClickArgument' => array(
                        'id' => $id,
                        'text' => "WARNING: You are about to convert this system to an organization. "
                                . "After this conversion all system information (FIPS-199 and FISMA Data) will be "
                                . "permanently lost.\n\n"
                                . "Do you want to continue?",
                        'func' => 'Fisma.System.askForSysToOrgInput'
                    ),
                    'imageSrc' => '/images/convert.png'
                )
            );
        }

        if ($this->_acl->hasPrivilegeForObject('sa', $organization)) {
            $buttons['sa'] = new Fisma_Yui_Form_Button_Link(
                'sa',
                array(
                    'label' => 'Security Authorization',
                    'icon' => 'lock',
                    'href' => '/sa/security-authorization/view/id/' . $id
                )
            );
        }

        if ($this->_acl->hasPrivilegeForClass('read', 'Finding')) {
            $buttons['showFindings'] = new Fisma_Yui_Form_Button_Link(
                'showFindings',
                array(
                    'value' => 'Show Findings',
                    'href' => $findingSearchUrl,
                    'imageSrc' => '/images/application_view_columns.png'
                )
            );
        }

        $this->view->toolbarButtons = $buttons;
        $this->view->searchButtons = $this->getSearchButtons($organization->System, $fromSearchParams);
        $this->view->tabView = $tabView;
    }

    public function _isDeletable()
    {
        return false;
    }

    /**
     * Display basic system properties such as name, creation date, etc.
     *
     * @GETAllowed
     * @return void
     */
    public function systemAction()
    {
        $id = $this->getRequest()->getParam('id');
        $organization = Doctrine::getTable('Organization')->findOneBySystemId($id);
        $this->_acl->requirePrivilegeForObject('read', $organization);
        $this->_helper->layout()->disableLayout();

        $system = $organization->System;
        $system->loadReference('AggregateSystem');

        $this->view->organization = $organization;
        $this->view->system = $system;

        $createdDate = new Zend_Date($organization->createdTs, Fisma_Date::FORMAT_DATE);
        $createdDate->setTimezone(CurrentUser::getAttribute('timezone'));
        $this->view->createdDate = $createdDate->toString(Fisma_Date::FORMAT_MONTH_DAY_YEAR);

        $updatedDate = new Zend_Date($organization->modifiedTs, Fisma_Date::FORMAT_DATE);
        $updatedDate->setTimezone(CurrentUser::getAttribute('timezone'));
        $this->view->updatedDate = $updatedDate->toString(Fisma_Date::FORMAT_MONTH_DAY_YEAR);

        $this->view->editable = (
            (!($this->getRequest()->getParam('readonly'))) &&
            ($this->_acl->hasPrivilegeForObject('update', $organization))
        );

        $this->view->findingCount = Doctrine_Query::create()
            ->select('f.id')
            ->from('Finding f')
            ->where('f.responsibleOrganizationId = ?', $organization->id)
            ->andWhere('f.isResolved <> ?', true)
            ->count();
        $this->view->incidentCount = Doctrine_Query::create()
            ->select('i.id')
            ->from('Incident i')
            ->where('i.organizationId = ?', $organization->id)
            ->andWhere('i.status <> ?', 'closed')
            ->count();
        $this->view->vulnerabilityCount = Doctrine_Query::create()
            ->select('v.id')
            ->from('Vulnerability v')
            ->leftJoin('v.Asset a')
            ->where('a.orgSystemId = ?', $organization->id)
            ->andWhere('v.isResolved <> ?', true)
            ->count();

        $this->render();
    }

    /**
     * Convert a system to an organization.
     *
     * @return void
     */
    public function convertToOrgAction()
    {
        if (!$this->_acl->hasPrivilegeForClass('create', 'Organization')) {
            throw new Fisma_Zend_Exception('Insufficient privileges to convert organization to system - ' .
                'cannot create Organization');
        }

        $systemId = Inspekt::getDigits($this->getRequest()->getParam('id'));

        if ($systemId) {
            $organization = Doctrine::getTable('Organization')->findOneBySystemId($systemId);
        } else {
            throw new Fisma_Zend_Exception("Required parameter 'id' is missing.");
        }

        $countSystemDoc = $organization->System->Documents->count();
        if ($countSystemDoc > 0) {
            $msg = "Cannot convert this system to an organization because it has documents attached to it.";

            $this->view->priorityMessenger($msg, 'error');
            $this->_redirect("/system/view/id/$systemId");
        }

        $form = $this->getForm('system_converttoorganization');
        if ($form->isValid($this->getRequest()->getPost())) {
            $organization->convertToOrganization(
                $form->getElement('orgType')->getValue()
            );

            $this->view->priorityMessenger('Converted to organization successfully', 'success');
            $this->_redirect("/organization/view/id/" . $organization->id);
        }
    }

    /**
     * Display FISMA attributes for the system
     *
     * @GETAllowed
     * @return void
     */
    public function fismaAction()
    {
        $id = $this->getRequest()->getParam('id');
        $organization = Doctrine::getTable('Organization')->findOneBySystemId($id);
        $this->_acl->requirePrivilegeForObject('read', $organization);
        $this->_helper->layout()->disableLayout();

        $this->view->organization = $organization;
        $this->view->system = $this->view->organization->System;

        $this->render();
    }

    /**
     * Display FISMA attributes for the system
     *
     * @GETAllowed
     * @return void
     */
    public function artifactsAction()
    {
        $id = $this->getRequest()->getParam('id');
        $organization = Doctrine::getTable('Organization')->findOneBySystemId($id);
        $this->_acl->requirePrivilegeForObject('read', $organization);
        $this->_helper->layout()->disableLayout();

        $system = $organization->System;
        $documents = $system->Documents;

        $this->view->organization = $organization;
        $this->view->system = $system;

        // Use the attach artifacts uploader, even though we're not using the behavior
        $uploadPanelButton = new Fisma_Yui_Form_Button(
            'uploadPanelButton',
            array(
                'label' => 'Upload New ' . $this->view->escape($this->view->translate('System_Attachment')),
                'onClickFunction' => 'Fisma.AttachArtifacts.showPanel',
                'onClickArgument' => array(
                    'id' => $id,
                    'form' => 'upload_system_document',
                    'server' => array(
                        'controller' => 'system',
                        'action' => 'upload-document'
                    ),
                    'callback' => array(
                        'object' => 'System',
                        'method' => 'uploadDocumentCallback'
                    ),
                    'title' => 'Upload New ' . $this->view->escape($this->view->translate('System_Attachment'))
                )
            )
        );

        if (!$this->_acl->hasPrivilegeForObject('update', $organization)) {
            $uploadPanelButton->readOnly = true;
        }

        $this->view->uploadPanelButton = $uploadPanelButton;

        // Get all documents for current system, sorted alphabetically on the document type name
        $documentQuery = Doctrine_Query::create()
                         ->from('SystemDocument d INNER JOIN d.DocumentType t')
                         ->where('d.systemId = ?', $system->id)
                         ->orderBy('t.name');
        $documents = $documentQuery->execute();

        $documentRows = array();

        foreach ($documents as $document) {
            $documentRows[] = array(
                'iconUrl'      => "<a href='/system-document/download/id/{$document->id}'>"
                                 . "<img alt='{$this->view->escape($document->getFileType())}' "
                                 . "src='{$this->view->escape($document->getIconUrl())}'></a>",
                'fileName' => $this->view->escape($document->DocumentType->name),
                'fileNameLink' => "<a href=\"/system-document/download/id/{$document->id}\">"
                                . $this->view->escape($document->DocumentType->name) . "</a>",
                'size' => $document->getSizeKb(),
                'version' => $document->version,
                'description' => $this->view->textToHtml($this->view->escape($document->description)),
                'username' => $this->view->userInfo($document->Upload->User->displayName, $document->Upload->User->id),
                'date' => $document->Upload->createdTs,
                'view' => "<a href=/system-document/view/id/{$document->id}>Version History</a>"
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
                false,
                null,
                null,
                'size'
            )
        );

        $dataTable->addColumn(
            new Fisma_Yui_DataTable_Column(
                'Version',
                false,
                null,
                null,
                'version'
            )
        );

        $dataTable->addColumn(
            new Fisma_Yui_DataTable_Column(
                'Version Notes',
                false,
                'Fisma.TableFormat.formatHtml',
                null,
                'description'
            )
        );

        $dataTable->addColumn(
            new Fisma_Yui_DataTable_Column(
                'Last Modified By User',
                true,
                'Fisma.TableFormat.formatHtml',
                null,
                'username'
            )
        );

        $dataTable->addColumn(
            new Fisma_Yui_DataTable_Column(
                'Last Modified Date',
                true,
                null,
                null,
                'date'
            )
        );

        $dataTable->addColumn(
            new Fisma_Yui_DataTable_Column(
                'View History',
                true,
                'Fisma.TableFormat.formatHtml',
                null,
                'id'
            )
        );

        $dataTable->setData($documentRows);

        $this->view->dataTable = $dataTable;
    }

    /**
     * Edit the system data
     *
     * @return void
     */
    public function editAction()
    {
        $id = $this->getRequest()->getParam('id');
        $organization = Doctrine::getTable('Organization')->find($id);
        $this->_acl->requirePrivilegeForObject('update', $organization);
        $this->_helper->layout()->disableLayout();

        $post = $this->_request->getPost();

        if ($post) {
            try {
                if (isset($post['pocId']) && empty($post['pocId'])) {
                    $post['pocId'] = null;
                }

                $organization->merge($post);

                if (isset($post['poc'])) {
                    $tags = explode(',', Fisma::configuration()->getConfig('organization_poc_list'));
                    foreach ($post['poc'] as $key => $value) {
                        if ($value) {
                            $organization->getPocs()->addPoc($value, $tags[$key]);
                        } else {
                            $organization->getPocs()->removePoc($tags[$key]);
                        }
                    }
                }

                if ($organization->isValid(true)) {
                    $organization->save();
                } else {
                    $msg = "Error while trying to save: <br />" . $organization->getErrorStackAsString();
                    $type = "error";
                }

                $system = $organization->System;
                $system->merge($post);

                if ($system->isValid(true)) {
                    $system->save();
                } else {
                    $msg = "Error while trying to save: <br />" . $system->getErrorStackAsString();
                    $type = "error";
                }
            } catch (Doctrine_Exception $e) {
                $msg = "Error while trying to save: " . $e->getMessage();
                $type = "error";
            }

            if (empty($msg)) {
                $msg = "System updated successfully.";
                $type = "success";
            }

            $this->view->priorityMessenger($msg, $type);
        }

        $fromSearchParams = $this->_getFromSearchParams($this->getRequest());
        $fromSearchUrl = '';

        if (!empty($fromSearchParams)) {
            $fromSearchUrl = $this->_helper->makeUrlParams($fromSearchParams);
        }

        $this->_redirect("/system/view/oid/$id$fromSearchUrl");
    }

    /**
     * Override the base class to handle the saving of attributes into the Organization AND System models
     *
     * @param Zend_Form $form
     * @param Doctrine_Record $system
     * @throws Fisma_Zend_Exception if the subject is not instance of Doctrine_Record
     * @return Fisma_Doctrine_Record The saved record
     */
    protected function saveValue($form, $system=null)
    {
        $createNew = false;
        // Create a new object if one is not provided (this indicates a "create" action rather than an "update")
        if (is_null($system)) {
            $system = new System();
            $createNew = true;
            $system->Organization = new Organization();
            $systemType = Doctrine::getTable('OrganizationType')->findOneByNickname('system');
            $system->Organization->orgTypeId = $systemType->id;

            /**
             * Set a flag indicating that this system needs to be added to the current user's ACL... this is used below.
             * It cant't be done here because of Doctrine's Unit of Work idiosyncracies.
             */
            $addSystemToUserAcl = true;
        } elseif (!$subject instanceof Doctrine_Record) {
            throw new Fisma_Zend_Exception('Expected a Doctrine_Record object');
        }

        // Merge form data into the organization model and system model. The variables are named such that each model
        // will merge the values it needs automatically.
        $systemData = $form->getValues();
        $system->Organization->merge($systemData);
        $system->merge($systemData);
        $system->save();

        // Create the tree structure for this system
        $parentNode = Doctrine::getTable('Organization')->find($systemData['parentOrganizationId']);
        if (!$parentNode) {
            throw new Fisma_Zend_Exception("No parent organization with id={$systemData['parentOrganizationId']}ß");
        }
        $system->Organization->getNode()->insertAsLastChildOf($parentNode);
        $system->Organization->save();

        // Quick hack to force re-indexing of the system, since intially it won't index its organization fields
        $system->state(Doctrine_Record::STATE_DIRTY);
        $system->save();

        // Add the system to the user's ACL if the flag was set above
        if ($addSystemToUserAcl) {
            $userRoles = CurrentUser::getInstance()->getRolesByPrivilege('organization', 'create');

            foreach ($userRoles as $role) {
                $role->Organizations[] = $system->Organization;
            }

            $userRoles->save();

            CurrentUser::getInstance()->invalidateAcl();
        }

        // Copy users and roles from another organization
        if (!empty($systemData['cloneOrganizationId'])) {
            $userRoles = Doctrine_Query::create()
                 ->from('UserRole ur')
                 ->leftJoin('ur.User u')
                 ->leftJoin('ur.UserRoleOrganization uro')
                 ->leftJoin('uro.Organization o')
                 ->where('o.id = ?', $systemData['cloneOrganizationId'])
                 ->execute();

            foreach ($userRoles as $userRole) {
                $userRole->Organizations[] = $system->Organization;
            }

            $userRoles->save();
        }

        return $system;
    }

    /**
     * Display a form inside a panel for uploading a document
     *
     * @return void
     */
    public function uploadDocumentAction()
    {
        $this->_helper->layout->disableLayout();

        $id = $this->getRequest()->getParam('id');
        $organization = Doctrine::getTable('Organization')->findOneBySystemId($id);
        $this->_acl->requirePrivilegeForObject('update', $organization);

        $documentTypeId = $this->getRequest()->getParam('documentTypeId');
        $versionNotes = $this->getRequest()->getParam('versionNotes');

        $response = new Fisma_AsyncResponse();

        try {
            if (empty($documentTypeId)) {
                throw new Fisma_Zend_Exception_User('Select a Document Type');
            }
            if ('' == trim($versionNotes)) {
                throw new Fisma_Zend_Exception_User("Version notes are required.");
            }

            $file = $_FILES['file'];
            if (Fisma_FileManager::getUploadFileError($file)) {
                $error = Fisma_FileManager::getUploadFileError($file);
                throw new Fisma_Zend_Exception_User($error);
            }

            // Get the existing document
            $documentQuery = Doctrine_Query::create()
                             ->from('SystemDocument sd')
                             ->where(
                                 'sd.systemId = ? AND sd.documentTypeId = ?',
                                 array($organization->System->id, $documentTypeId)
                             )
                             ->limit(1);

            $documents = $documentQuery->execute();

            // If no existing document, then create a new one
            if (count($documents) == 0) {
                $document = new SystemDocument();
                $document->documentTypeId = $documentTypeId;
                $document->System = $organization->System;
            } else {
                $document = $documents[0];
            }

            $document->Upload  = new Upload();
            $document->Upload->instantiate($_FILES['file']);
            $document->description = $versionNotes;
            $document->save();
        } catch (Fisma_Zend_Exception_User $e) {
            $response->fail($e->getMessage());
        } catch (Exception $e) {
            if (Fisma::debug()) {
                $response->fail("Failure (debug mode): " . $e->getMessage());
            } else {
                $response->fail("Internal system error. File not uploaded.");
            }

            $this->getInvokeArg('bootstrap')->getResource('log')->err($e->getMessage() . "\n" . $e->getTraceAsString());
        }

        $this->view->response = json_encode($response);

        if ($response->success) {
            $this->view->priorityMessenger('Artifact uploaded successfully', 'success');
        }
    }

    /**
     * userAction
     *
     * @GETAllowed
     * @access public
     * @return void
     */
    public function userAction()
    {
        $this->_helper->layout->disableLayout();
        $id = $this->getRequest()->getParam('id');

        /**
         * Both OrganizationController and SystemController use this action. So, when the OrganizationController
         * uses this action, it would pass a param of type which indicates the id param is organizationId.
         */
        $type = $this->getRequest()->getParam('type');
        if (!is_null($type) && 'organization' == $type) {
            $organization = Doctrine::getTable('Organization')->findOneById($id);
        } else {
            $organization = Doctrine::getTable('Organization')->findOneBySystemId($id);
        }

        $this->_acl->requirePrivilegeForObject('read', $organization);

        $currentUserAccessForm = new Zend_Form_SubForm();

        $rolesAndUsers = Doctrine::getTable('UserRole')
                         ->getRolesAndUsersByOrganizationIdQuery($organization->id)
                         ->where('r.type = ?', 'ACCOUNT_TYPE')
                         ->orderBy('r.nickname, u.username')
                         ->execute();

        $tree = new Fisma_Zend_Form_Element_CheckboxTree('rolesAndUsers');
        $tree->clearDecorators();

        // Add roles and their users to the checkbox tree
        foreach ($rolesAndUsers as $role) {
            $tree->addCheckbox(array('label' => $role->name, 'level' => 0));
            foreach ($role->UserRole as $userRole) {
                $tree->addCheckbox(
                    array('name' => $userRole->userRoleId, 'label' => $userRole->User->username, 'level' => 1)
                );
            }
        }

        $removeUsersButton = new Fisma_Yui_Form_Button(
            'removeUsers',
            array(
                'label' => 'Remove Selected Users',
                'onClickFunction' => 'Fisma.System.removeSelectedUsers',
                'onClickArgument' => array(
                    'organizationId' => $organization->id
                )
            )
        );

        $currentUserAccessForm->addElement($removeUsersButton);
        $currentUserAccessForm->addElement($tree);
        $currentUserAccessForm->setElementDecorators(array(new Fisma_Zend_Form_Decorator()));

        $addUserAccessForm = new Zend_Form_SubForm();

        $roles = Doctrine_Query::create()
                 ->select('r.id, r.name')
                 ->from('Role r')
                 ->where('r.type = ?', 'ACCOUNT_TYPE')
                 ->orderBy('r.name')
                 ->setHydrationMode(Doctrine::HYDRATE_ARRAY)
                 ->execute();

        $select = new Zend_Form_Element_Select('roles');
        $select->setLabel('Account');

        foreach ($roles as $role) {
            $select->addMultiOption($role['id'], $role['name']);
        }

        $userAutoComplete = new Fisma_Yui_Form_AutoComplete(
            'userAutoComplete',
            array(
                'resultsList' => 'users',
                'fields' => 'username',
                'xhr' => '/user/get-users',
                'hiddenField' => 'addUserId',
                'queryPrepend' => '/query/',
                'containerId' => 'userAutocompleteContainer',
            )
        );

        $userAutoComplete->setLabel('Username');

        $addButton = new Fisma_Yui_Form_Button(
            'addUser',
            array(
                'label' => 'Add',
                'onClickFunction' => 'Fisma.System.addUser',
                'onClickArgument' => array(
                    'organizationId' => $organization->id
                )
            )
        );

        $addUserId = new Zend_Form_Element_Hidden('addUserId');
        $addUserAccessForm->addElement($userAutoComplete);
        $addUserAccessForm->addElement($select);
        $addUserAccessForm->addElement($addButton);
        $addUserAccessForm->addElement($addUserId);
        $addUserAccessForm->setElementDecorators(array(new Fisma_Zend_Form_Decorator()));

        $copyUserAccessForm = new Zend_Form_SubForm();

        $systemAutoComplete = new Fisma_Yui_Form_AutoComplete(
            'systemAutoComplete',
            array(
                'resultsList' => 'systems',
                'fields' => 'name',
                'xhr' => '/system/get-systems',
                'hiddenField' => 'copySystemId',
                'queryPrepend' => '/query/',
                'containerId' => 'systemAutocompleteContainer'
            )
        );

        $systemAutoComplete->setLabel('Copy From');

        $addSelectedButton = new Fisma_Yui_Form_Button(
            'addSelectedUsers',
            array(
                'label' => 'Add Selected Users',
                'onClickFunction' => 'Fisma.System.addSelectedUsers',
                'onClickArgument' => array(
                    'organizationId' => $organization->id
                )
            )
        );

        $selectAllButton = new Fisma_Yui_Form_Button(
            'selectAll',
            array(
                'label' => 'Select All',
                'onClickFunction' => 'Fisma.System.selectAllByName',
                'onClickArgument' => array(
                    'name' => 'copyUserAccessTree[][]'
                )
            )
        );

        $copySystemId = new Zend_Form_Element_Hidden('copySystemId');
        $copyUserAccessForm->addElement($systemAutoComplete);
        $copyUserAccessForm->addElement($selectAllButton);
        $copyUserAccessForm->addElement($addSelectedButton);
        $copyUserAccessForm->addElement($copySystemId);
        $copyUserAccessForm->setElementDecorators(array(new Fisma_Zend_Form_Decorator()));

        $this->view->currentUserAccessForm = $currentUserAccessForm;
        $this->view->addUserAccessForm = $addUserAccessForm;
        $this->view->copyUserAccessForm = $copyUserAccessForm;
        $this->view->organization = $organization;
        $this->view->tree = $tree;
    }

    /**
     * addUserAction
     *
     * @access public
     * @return void
     */
    public function addUserAction()
    {
        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        $this->_acl->requirePrivilegeForClass('update', 'User');

        $organizationId = $this->getRequest()->getParam('organizationId');
        $userId = $this->getRequest()->getParam('userId');
        $roleId = $this->getRequest()->getParam('roleId');

        /**
         * Get the existing user role ID, or create a new user role if one doesn't exist
         */
        $userRoleId = Doctrine::getTable('UserRole')->getByUserIdAndRoleIdQuery($userId, $roleId)
                      ->select('ur.userroleid')
                      ->setHydrationMode(Doctrine::HYDRATE_NONE)
                      ->fetchOne();

        if ($userRoleId) {
            $userRoleId = $userRoleId[0];
        } else {
            $userRole = new UserRole();

            $userRole->userId = (int) $userId;
            $userRole->roleId = (int) $roleId;

            $userRole->save();

            $userRoleId = $userRole->userRoleId;
        }

        $deleteUro = Doctrine_Query::create()
                     ->delete('UserRoleOrganization uro')
                     ->where('uro.organizationId = ?', $organizationId)
                     ->andWhere('uro.userRoleId = ?', $userRoleId);

        $userRoleOrganization = new UserRoleOrganization();
        $userRoleOrganization->organizationId = (int) $organizationId;
        $userRoleOrganization->userRoleId = $userRoleId;
        $userRoleOrganization->save();
    }

    /**
     * getUserAccessTreeAction
     *
     * @GETAllowed
     * @access public
     * @return void
     */
    public function getUserAccessTreeAction()
    {
        $this->_helper->layout->disableLayout();

        $organizationId = $this->getRequest()->getParam('id');
        $treeName       = $this->getRequest()->getParam('name', 'copyUserAccessTree');

        $rolesAndUsers = Doctrine::getTable('UserRole')
                         ->getRolesAndUsersByOrganizationIdQuery($organizationId)
                         ->orderBy('r.nickname, u.username')
                         ->execute();

        if (count($rolesAndUsers)) {
            $tree = new Fisma_Zend_Form_Element_CheckboxTree($treeName);
            $tree->clearDecorators();

            // Add roles and their users to the checkbox tree
            foreach ($rolesAndUsers as $role) {
                $tree->addCheckbox(array('label' => $role->nickname, 'level' => 0));
                foreach ($role->UserRole as $userRole) {
                    $tree->addCheckbox(
                        array('name' => $userRole->userRoleId, 'label' => $userRole->User->username, 'level' => 1)
                    );
                }
            }
        } else {
            $tree = "No users. Please select another system.";
        }

        $this->view->tree = $tree;
    }

    /**
     * getSystemsAction
     *
     * @GETAllowed
     * @access public
     * @return void
     */
    public function getSystemsAction()
    {
        $this->_acl->requirePrivilegeForClass('read', 'Organization');

        $query = $this->getRequest()->getParam('query');

        $systems = Doctrine::getTable('Organization')->getSystemsLikeNameQuery($query)
                   ->select('o.id')
                   ->addSelect("CONCAT(o.nickname, ' - ', o.name) AS name")
                   ->setHydrationMode(Doctrine::HYDRATE_ARRAY)
                   ->execute();

        $list = array('systems' => $systems);

        return $this->_helper->json($list);
    }

    /**
     * AJAX action to render the form for converting a System to an Organization.
     *
     * @GETAllowed
     * @return void
     */
    public function convertToOrganizationFormAction()
    {
        $id = Inspekt::getDigits($this->getRequest()->getParam('id'));
        $this->view->form = $this->getForm('system_converttoorganization');
        $this->view->form->setAction('/system/convert-to-org/id/' . $id);
    }

    /**
     * Override to set some unique form elements.
     *
     * @param string|null $formName The name of the specified form
     * @return Zend_Form The specified form of the subject model
     */
    public function getForm($formName = null)
    {
        $form = parent::getForm($formName);

        if (empty($formName) || $formName == 'system') {
            $systemTypeArray = Doctrine::getTable('SystemType')->getTypeList();
            $form->getElement('systemTypeId')->addMultiOptions($systemTypeArray);
        }

        return $form;
    }

    /**
     * List all associated assets as requested in OFJ-1958
     *
     * @GETAllowed
     */
    public function assetsAction()
    {
        $id = $this->getRequest()->getParam('id');
        if (empty($id)) {
            throw new Fisma_Zend_Exception_User('System ID required.');
        }
        $organization = Doctrine::getTable('Organization')->findOneBySystemId($id);
        if (!$organization) {
            throw new Fisma_Zend_Exception_User('Invalid ID provided: ' . $id);
        }

        $this->_acl->requirePrivilegeForObject('read', $organization);

        $assets = $organization->Assets;
        $assets->loadRelated('Product');

        $assetRows = array();
        foreach ($assets as $asset) {
            $assetRows[] = array(
                'name' => "<a href='/asset/view/id/{$asset->id}'>{$asset->name}</a>",
                'addressIp' => $asset->addressIp,
                'serviceTag' => ($asset->serviceTag) ? $asset->serviceTag : '',
                'network' => $asset->Network->displayName,
                'product' => ($asset->productId) ? $asset->Product->name : ''
            );
        }
        $assetTable = Doctrine::getTable('Asset');

        $dataTable = new Fisma_Yui_DataTable_Local();
        $dataTable->addColumn(
            new Fisma_Yui_DataTable_Column(
                $assetTable->getLogicalName('name'),
                false,
                'Fisma.TableFormat.formatHtml',
                null,
                'name'
            )
        );
        $dataTable->addColumn(
            new Fisma_Yui_DataTable_Column(
                $assetTable->getLogicalName('addressIp'),
                false,
                null,
                null,
                'addressIp'
            )
        );
        $dataTable->addColumn(
            new Fisma_Yui_DataTable_Column(
                $assetTable->getLogicalName('serviceTag'),
                false,
                null,
                null,
                'serviceTag'
            )
        );
        $dataTable->addColumn(
            new Fisma_Yui_DataTable_Column(
                $assetTable->getLogicalName('networkId'),
                false,
                null,
                null,
                'network'
            )
        );
        $dataTable->addColumn(
            new Fisma_Yui_DataTable_Column(
                $assetTable->getLogicalName('productId'),
                false,
                null,
                null,
                'product'
            )
        );

        $dataTable->setData($assetRows);
        $this->view->dataTable = $dataTable;
    }

    /**
     * Display the create form without any layout
     *
     * @GETAllowed
     */
    public function formAction()
    {
        $this->view->form = $this->getForm();
        $this->view->toolbarButtons = array(
            new Fisma_Yui_Form_Button(
                'submitPanel',
                array(
                    'label' => 'Submit',
                    'onClickFunction' => 'Fisma.Vulnerability.submitPanel',
                    'imageSrc' => '/images/ok.png'
                )
            ),
            new Fisma_Yui_Form_Button(
                'closePanel',
                array(
                    'label' => 'Cancel',
                    'onClickFunction' => 'Fisma.Vulnerability.closePanel',
                    'imageSrc' => '/images/no_entry.png'
                )
            )
        );
    }
}
