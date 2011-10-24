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
     * Setup the context switch for ajax
     * 
     * @return void
     */
    public function init()
    {
        parent::init();
        $this->_helper->ajaxContext
                      ->setActionContext('create', 'html')
                      ->setActionContext('available-information-types', 'html')
                      ->initContext('');

        $this->_helper->contextSwitch
                      ->addActionContext('add-information-type', 'json')
                      ->initContext('');
    }

    /**
     * View the specified system
     *
     * @return void
     */
    public function viewAction()
    {
        // Either 'id' (system ID) or 'oid' (organization ID) is required
        $id = $this->getRequest()->getParam('id');
        $organizationId = $this->getRequest()->getParam('oid');
        
        if ($id) {
            $organization = Doctrine::getTable('Organization')->findOneBySystemId($id);            
        } elseif ($organizationId) {
            $organization = Doctrine::getTable('Organization')->find($organizationId);            
            $id = $organization->System->id;
        } else {
            throw new Fisma_Zend_Exception("Required parameter 'id' or 'oid' is missing.");
        }

        $this->_acl->requirePrivilegeForObject('read', $organization);

        $this->view->organization = $organization;

        $tabView = new Fisma_Yui_TabView('SystemView', $id);

        $firstTab = $this->view->escape($organization->name)
                  . ' (' . $this->view->escape($organization->nickname) . ')';
        $tabView->addTab($firstTab, "/system/system/id/$id");
        $tabView->addTab("Information Types", "/system/fips/id/$id");
        $tabView->addTab("FISMA Data", "/system/fisma/id/$id");
        $tabView->addTab("Documentation", "/system/artifacts/id/$id");
        $tabView->addTab("Users", "/system/user/id/$id");
        $tabView->addTab("Step 6 - Monitor", "/system/monitor/id/$id");

        $findingSearchUrl = '/finding/remediation/list?q=/organization/textExactMatch/'
                          . $this->view->escape($organization->nickname, 'url');

        $this->view->showFindingsButton = new Fisma_Yui_Form_Button_Link(
            'showFindings',
            array(
                'value' => 'Show Findings',
                'href' => $findingSearchUrl
            )
        );

        $this->view->tabView = $tabView;
    }

    public function _isDeletable()
    {
        return false;
    }

    /**
     * Display basic system properties such as name, creation date, etc.
     *
     * @return void
     */
    public function systemAction()
    {
        $id = $this->getRequest()->getParam('id');
        $organization = Doctrine::getTable('Organization')->findOneBySystemId($id);
        $this->_acl->requirePrivilegeForObject('read', $organization);
        $this->_helper->layout()->disableLayout();

        $this->view->organization = $organization;
        $this->view->system = $this->view->organization->System;

        $query = Doctrine_Query::create()
            ->from('SystemDocument sd, sd.DocumentType dt')
            ->select('sd.filename')
            ->where('sd.systemid = ?', $id)
            ->andWhere('dt.name = "Architecture Diagram"')
            ->fetchOne();

        $architectureDiagramId = $query['id'];

        if ($architectureDiagramId) {
            $architectureDiagramFile = Fisma::getPath('systemDocument') . '/'
                . $organization->id . '/' . $query['fileName'];

            $imageValidator = new Zend_Validate_File_IsImage();

            if ($imageValidator->isValid($architectureDiagramFile)) {
                $this->view->architectureDiagramId = $architectureDiagramId;
            } else {
                $logger = $this->getInvokeArg('bootstrap')->getResource('Log');
                $logger->log("$architectureDiagramFile is not a valid image", Zend_Log::WARN);
            }
        }

        $artifactUploadButton = new Fisma_Yui_Form_Button(
            'uploadPanelButton',
            array(
                'label' => 'Upload Document',
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
                    )
                )
            )
        );

        $this->view->artifactUploadButton = $artifactUploadButton;

        $this->render();
    }

    /**
     * Display CIA criteria and FIPS-199 categorization
     *
     * @return void
     */
    public function fipsAction()
    {
        $said = $this->getRequest()->getParam('said');
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
                              ->setGlobalVariableName('Fisma.System.assignedInformationTypesTable')
                              ->setDataUrl("/system/information-types/id/{$id}/format/json");
        
        $this->view->informationTypesTable = $informationTypesTable;

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

        $completeStepButton = new Fisma_Yui_Form_Button(
            'completeSelection',
            array('label' => 'Complete Step 1', 'onClickFunction' => 'Fisma.SecurityAuthorization.completeForm')
        );

        if (isset($said)) {
            $completeForm = new Fisma_Zend_Form();
            $completeForm->setAction('/sa/security-authorization/complete-step')
                ->setAttrib('id', 'completeForm')
                ->addElement(new Zend_Form_Element_Hidden('id'))
                ->addElement(new Zend_Form_Element_Hidden('step'))
                ->setElementDecorators(array('ViewHelper'))
                ->setDefaults(array('id' => $said, 'step' => 'Categorize'));
            $this->view->completeForm = $completeForm;
            $this->view->completeStepButton = $completeStepButton;
        }

        $this->view->addInformationTypeButton = $addInformationTypeButton;
        // END: Building of data table

        $availableInformationTypesTable = new Fisma_Yui_DataTable_Remote();
        $availableInformationTypesTable->addColumn(
            new Fisma_Yui_DataTable_Column('Category', true, null, null, 'category')
        )
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
            ->setDataUrl("/system/active-types/systemId/{$id}/format/json")
            ->setClickEventHandler('Fisma.System.handleAvailableInformationTypesTableClick')
            ->setClickEventHandlerArgs($id)
            ->setGlobalVariableName('Fisma.System.availableInformationTypesTable');

        $this->view->availableInformationTypesTable = $availableInformationTypesTable;
        $this->render();
        // END: Building of the data table
    }

    /**
     * Return all information types currently assigned to the system
     *
     * @return void
     */
    public function informationTypesAction()
    {
        $this->_helper->layout->setLayout('ajax');

        $id    = $this->getRequest()->getParam('id');
        $count = $this->getRequest()->getParam('count', 10);
        $start = $this->getRequest()->getParam('start', 0);
        $sort  = $this->getRequest()->getParam('sort', 'category');
        $dir   = $this->getRequest()->getParam('dir', 'asc');

        $system = Doctrine::getTable('System')->find($id);

        $this->_acl->requirePrivilegeForObject('read', $system->Organization);

        $systemId = $system->id;

        $informationTypes = Doctrine_Query::create()
            ->select("sat.*, {$system->id} as system")
            ->from('SaInformationType sat, SaInformationTypeSystem sats')
            ->where('sats.systemid = ?', $systemId)
            ->andWhere('sats.sainformationtypeid = sat.id')
            ->andWhere('sat.hidden = FALSE')
            ->orderBy("sat.{$sort} {$dir}")
            ->limit($count)
            ->offset($start);

        $informationTypesData = array();
        $informationTypesData['totalRecords'] = $informationTypes->count();
        $informationTypesData['informationTypes'] = $informationTypes->execute()->toArray();
        $this->view->informationTypesData = $informationTypesData;
    }

    /**
     * Add a single information type to a system
     *
     * @return void
     */
    public function addInformationTypeAction()
    {
        $response = new Fisma_AsyncResponse();
        try {
            $informationTypeId = $this->getRequest()->getParam('sitId');
            $id = $this->getRequest()->getParam('id');

            $system = Doctrine::getTable('System')->find($id);
            $this->_acl->requirePrivilegeForObject('update', $system->Organization);

            $systemId = $system->id;
            $informationTypeSystem = new SaInformationTypeSystem();
            $informationTypeSystem->sainformationtypeid = $informationTypeId;
            $informationTypeSystem->systemid = $systemId;
            $informationTypeSystem->save();
        } catch (Exception $e) {
            $this->getInvokeArg('bootstrap')->getResource('Log')->log($e, Zend_Log::ERR);
            $response->fail($e);
        }
        $this->view->response = $response;
    }

    /**
     * Remove a single information type from a system
     *
     * @return void
     */
    public function removeInformationTypeAction()
    {
        $informationTypeId = $this->getRequest()->getParam('sitId');
        $id = $this->getRequest()->getParam('id');

        $system = Doctrine::getTable('System')->find($id);

        $this->_acl->requirePrivilegeForObject('update', $system->Organization);

        $informationType = Doctrine_Query::create()
            ->from('SaInformationTypeSystem saits')
            ->where('saits.sainformationtypeid = ?', $informationTypeId)
            ->andWhere('saits.systemid = ?', $id)
            ->execute();

        $informationType->delete();

        $this->_redirect("/system/view/id/$id");
    }

    /**
     * Return types which can be assigned to a system
     * The system ID is included in the data for use on the System FIPS-199 page
     *
     * @return void
     */
    public function activeTypesAction()
    {
        $this->_helper->layout->setLayout('ajax');

        $systemId = $this->getRequest()->getParam('systemId');
        $count          = $this->getRequest()->getParam('count', 10);
        $start          = $this->getRequest()->getParam('start', 0);
        $sort           = $this->getRequest()->getParam('sort', 'category');
        $dir            = $this->getRequest()->getParam('dir', 'asc');

        $system = Doctrine::getTable('System')->find($systemId);
        $organizationId = $system->Organization->id;

        $this->_acl->requirePrivilegeForObject('read', $system->Organization);

        $informationTypes = Doctrine_Query::create()
        // TODO: Make sure not vulnerable to injection
            ->select("*, {$systemId} as system")
            ->from('SaInformationType sat')
            ->where('sat.hidden = FALSE')
            ->andWhere(
            'sat.id NOT IN (' .
                'SELECT s.sainformationtypeid FROM SaInformationTypeSystem s where s.systemid = ?' .
                ')', $systemId
        )
            ->limit($count)
            ->offset($start)
            ->orderBy("sat.{$sort} {$dir}");

        $informationTypesData = array();
        $informationTypesData['totalRecords'] = $informationTypes->count();
        $informationTypesData['informationTypes'] = $informationTypes->execute()->toArray();
        $this->view->informationTypesData = $informationTypesData;
    }

    /**
     * Display FISMA attributes for the system
     *
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
                'label' => 'Upload Document',
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
                    )
                )
            )
        );

        if (!$this->_acl->hasPrivilegeForObject('update', $organization)) {
            $uploadPanelButton->readOnly = true;
        }

        $this->view->uploadPanelButton = $uploadPanelButton;

        $generateScdButton = new Fisma_Yui_Form_Button_Link(
            'generateScdButton',
            array(
                'value' => 'Generate SCD',
                'href' => '/system/generate-scd/format/pdf/id/' . $id
            )
        );

        if (!$this->_acl->hasPrivilegeForObject('update', $organization)) {
            $generateScdButton->readOnly = true;
        }

        $this->view->generateScdButton = $generateScdButton;

        // Get all documents for current system, sorted alphabetically on the document type name
        $documentQuery = Doctrine_Query::create()
                         ->from('SystemDocument d INNER JOIN d.DocumentType t')
                         ->where('d.systemId = ?', $system->id)
                         ->orderBy('t.name');
        $this->view->documents = $documentQuery->execute();

        $this->render();
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
                $organization->merge($post);

                if ($organization->isValid(true)) {
                    $organization->save();
                } else {
                    $msg = "Error while trying to save: <br />" . $organization->getErrorStackAsString();
                    $type = "warning";
                }

                $system = $organization->System;
                $system->merge($post);

                if ($system->isValid(true)) {
                    $system->save();
                } else {
                    $msg = "Error while trying to save: <br />" . $system->getErrorStackAsString();
                    $type = "warning";
                }
            } catch (Doctrine_Exception $e) {
                $msg = "Error while trying to save: " . $e->getMessage();
                $type = "warning";
            }

            if (empty($msg)) {
                $msg = "System updated successfully.";
                $type = "notice";
            }

            $this->view->priorityMessenger($msg, $type);
        }

        $this->_redirect("/system/view/oid/$id");
    }

    /**
     * Override the base class to handle the saving of attributes into the Organization AND System models
     *
     * @param Zend_Form $form
     * @param Doctrine_Record $system
     * @throws Fisma_Zend_Exception if the subject is not instance of Doctrine_Record
     * @returns integer Organization id of new system
     */
    protected function saveValue($form, $system=null)
    {
        // Create a new object if one is not provided (this indicates a "create" action rather than an "update")
        if (is_null($system)) {
            $system = new System();
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

        return $system->id;
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

            // Validate file extension and mime type. Failure will trigger exception handler at the end of this block
            $artifactsGenerator = new Fisma_Doctrine_Behavior_AttachArtifacts_Generator();
            $artifactsGenerator->checkFileBlackList($_FILES['file']);

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
                $document->User = CurrentUser::getInstance();
            } else {
                $document = $documents[0];
                $document->User = CurrentUser::getInstance();
            }

            // Move file into its correct place
            $error = '';
            if (empty($_FILES['file']['name'])) {
                throw new Fisma_Zend_Exception_User("You did not specify a file to upload.");
            }
            $file = $_FILES['file'];
            $destinationPath = Fisma::getPath('systemDocument') . '/' . $organization->id;
            if (!is_dir($destinationPath)) {
                mkdir($destinationPath);
            }
            $dateTime = Zend_Date::now()->toString(Fisma_Date::FORMAT_FILENAME_DATETIMESTAMP);
            $fileName = preg_replace('/^(.*)\.(.*)$/', '$1-' . $dateTime . '.$2', $file['name'], 2, $count);
            $filePath = "$destinationPath/$fileName";

            if (!move_uploaded_file($file['tmp_name'], $filePath)) {
                throw new Fisma_Zend_Exception(
                    "The file could not be stored due to the server's permissions settings."
                );
            }

            // Update the document object and save
            if ('' == trim($versionNotes)) {
                throw new Fisma_Zend_Exception_User("Version notes are required.");
            }

            $document->description = $versionNotes;
            $document->fileName = $fileName;
            $document->mimeType = $file['type'];
            $document->size = $file['size'];
            $document->save();
        } catch (Fisma_Zend_Exception_User $e) {
            $response->fail($e->getMessage());
        } catch (Fisma_Zend_Exception $e) {
            if (Fisma::debug()) {
                $response->fail("Failure (debug mode): " . $e->getMessage());
            } else {
                $response->fail("Internal system error. File not uploaded.");
            }

            $this->getInvokeArg('bootstrap')->getResource('log')->err($e->getMessage() . "\n" . $e->getTraceAsString());
        }

        $this->view->response = json_encode($response);

        if ($response->success) {
            $this->view->priorityMessenger('Artifact uploaded successfully', 'notice');
        }
    }

    /**
     * userAction 
     * 
     * @access public
     * @return void
     */
    public function userAction()
    {
        $this->_helper->layout->disableLayout();

        $id             = $this->getRequest()->getParam('id');
        $organization   = Doctrine::getTable('Organization')->findOneBySystemId($id);

        $this->_acl->requirePrivilegeForObject('read', $organization);

        $currentUserAccessForm = new Zend_Form_SubForm();

        $rolesAndUsers = Doctrine::getTable('UserRole')
                         ->getRolesAndUsersByOrganizationIdQuery($organization->id)
                         ->orderBy('r.nickname, u.username')
                         ->execute();

        $tree = new Fisma_Zend_Form_Element_CheckboxTree('rolesAndUsers');
        $tree->clearDecorators();

        // Add roles and their users to the checkbox tree
        foreach ($rolesAndUsers as $role) {
            $tree->addCheckbox(NULL, $role->nickname, 0);

            foreach ($role->UserRole as $userRole) {
                $tree->addCheckbox($userRole->userRoleId, $userRole->User->username, 1);
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
                 ->select('r.id, r.nickname')
                 ->from('Role r')
                 ->orderBy('r.nickname')
                 ->setHydrationMode(Doctrine::HYDRATE_ARRAY)
                 ->execute();
        
        $select = new Zend_Form_Element_Select('roles');
        $select->setLabel('Role');

        foreach ($roles as $role) {
            $select->addMultiOption($role['id'], $role['nickname']);
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

        $addUserAccessForm->addElement($userAutoComplete);
        $addUserAccessForm->addElement($select);
        $addUserAccessForm->addElement($addButton);
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
                'onClickFunction' => 'selectAllByName',
                'onClickArgument' => array(
                    'name' => 'copyUserAccessTree[][]'
                )
            )
        );

        $copyUserAccessForm->addElement($systemAutoComplete);
        $copyUserAccessForm->addElement($selectAllButton);
        $copyUserAccessForm->addElement($addSelectedButton);
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
                $tree->addCheckbox(NULL, $role->nickname, 0);

                foreach ($role->UserRole as $userRole) {
                    $tree->addCheckbox($userRole->userRoleId, $userRole->User->username, 1);
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
     * @access public
     * @return void
     */
    public function getSystemsAction()
    {
        $this->_acl->requirePrivilegeForClass('read', 'Organization');

        $query = $this->getRequest()->getParam('query');

        $systems = Doctrine::getTable('Organization')->getSystemsLikeNameQuery($query)
                   ->select('o.id, o.name')
                   ->setHydrationMode(Doctrine::HYDRATE_ARRAY)
                   ->execute();

        $list = array('systems' => $systems);
        
        return $this->_helper->json($list);
    }

    public function generateScdAction()
    {
        $id = $this->getRequest()->getParam('id');
        $system = Doctrine::getTable('System')->find($id);
        $pdf = new Fisma_PDF_SCD($system);
        $this->_helper->pdf($pdf);
    }

    /**
     * Display step 6 - monintor tab view 
     *
     * @return void
     */
    public function monitorAction()
    {
        $this->_helper->layout()->disableLayout();
    }
}
