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
 * @version    $Id$
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
     * List the systems from the search. If search none, it list all systems
     * 
     * @return void
     * @throws Fisma_Zend_Exception if the order parameter invalid
     */
    public function searchAction()
    {
        $this->_acl->requirePrivilegeForClass('read', 'Organization');
        
        $keywords = trim($this->_request->getParam('keywords'));
        
        $sortBy = $this->_request->getParam('sortby', 'o_nickname');
        // Replace the HYDRATE_SCALAR alias syntax with the regular Doctrine alias syntax
        $sortBy = str_replace('_', '.', $sortBy);
        $order = $this->_request->getParam('order', 'ASC');
        
        if (!in_array(strtolower($order), array('asc', 'desc'))) {
            throw new Fisma_Zend_Exception('Invalid "order" parameter');
        }
        
        $myOrganizations = $this->_me->getOrganizationsByPrivilege('organization', 'read')->toKeyValueArray('id', 'id');
        
        $q = Doctrine_Query::create()
             ->from('Organization o')
             ->select(
                 'o.id, 
                  b.nickname,
                  o.nickname, 
                  o.name, 
                  s.type, 
                  s.confidentiality, 
                  s.integrity, 
                  s.availability, 
                  s.fipsCategory'
             )
             ->innerJoin('o.System s')
             ->leftJoin("s.Organization b ON b.orgType = 'bureau' AND o.lft BETWEEN b.lft and b.rgt")
             ->whereIn('o.id', $myOrganizations)
             ->addWhere('o.orgType = ?', 'system')
             ->orderBy("$sortBy $order")
             ->offset($this->_paging['startIndex'])
             ->setHydrationMode(Doctrine::HYDRATE_SCALAR);

        if (!empty($keywords)) {
            $index = new Fisma_Index('System');
            $systemIds = $index->findIds($keywords);
            if (empty($systemIds)) {
                // set ids as a not exist value in database if search results is none.
                $systemIds = array(-1);
            }
            $q->whereIn('s.id', $systemIds);
        }

        $totalRecords = $q->count();
        $q->limit($this->_paging['count']);
        $organizations = $q->execute();

        $tableData = array('table' => array(
            'recordsReturned' => count($organizations),
            'totalRecords' => $totalRecords,
            'startIndex' => $this->_paging['startIndex'],
            'sort' => $sortBy,
            'dir' => $order,
            'pageSize' => $this->_paging['count'],
            'records' => $organizations
        ));

        $this->_helper->json($tableData);
    }
    
    /**
     * View the specified system
     * 
     * @return void
     */
    public function viewAction() 
    {
        $id = $this->getRequest()->getParam('id');
        $organization = Doctrine::getTable('Organization')->find($id);
        $this->_acl->requirePrivilegeForObject('read', $organization);
        
        $organization = Doctrine::getTable('Organization')->find($id);
        $this->view->organization = $organization;

        $tabView = new Fisma_Yui_TabView('SystemView', $id);

        $firstTab = $this->view->escape($organization->name)
                  . ' (' . $this->view->escape($organization->nickname) . ')';
        $tabView->addTab($firstTab, "/system/system/id/$id");
        $tabView->addTab("FIPS-199", "/system/fips/id/$id");
        $tabView->addTab("FISMA Data", "/system/fisma/id/$id");
        $tabView->addTab("Documentation", "/system/artifacts/id/$id");
        
        $this->view->showFindingsButton = new Fisma_Yui_Form_Button_Link(
            'showFindings', 
            array(
                'value' => 'Show Findings',
                'href' => "/finding/remediation/search/responsibleOrganizationId/$id"
            )
        );

        $this->view->tabView = $tabView;
    }
    
    /**
     * Display basic system properties such as name, creation date, etc.
     * 
     * @return void
     */
    public function systemAction() 
    {
        $id = $this->getRequest()->getParam('id');
        $organization = Doctrine::getTable('Organization')->find($id);
        $this->_acl->requirePrivilegeForObject('read', $organization);
        $this->_helper->layout()->disableLayout();
        
        $this->view->organization = Doctrine::getTable('Organization')->find($id);
        $this->view->system = $this->view->organization->System;

        $this->render();
    }
    
    /**
     * Display CIA criteria and FIPS-199 categorization
     * 
     * @return void
     */
    public function fipsAction() 
    {
        $id = $this->getRequest()->getParam('id');
        $organization = Doctrine::getTable('Organization')->find($id);
        $this->_acl->requirePrivilegeForObject('read', $organization);
        $this->_helper->layout()->disableLayout();

        $this->view->organization = Doctrine::getTable('Organization')->find($id);
        $this->view->system = $this->view->organization->System;
        
        $this->render();
    }
    
    /**
     * Display FISMA attributes for the system
     * 
     * @return void
     */
    public function fismaAction() 
    {
        $id = $this->getRequest()->getParam('id');
        $organization = Doctrine::getTable('Organization')->find($id);
        $this->_acl->requirePrivilegeForObject('read', $organization);
        $this->_helper->layout()->disableLayout();

        $this->view->organization = Doctrine::getTable('Organization')->find($id);
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
        $organization = Doctrine::getTable('Organization')->find($id);
        $this->_acl->requirePrivilegeForObject('read', $organization);
        $this->_helper->layout()->disableLayout();

        $organization = Doctrine::getTable('Organization')->find($id);
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

        $organization = Doctrine::getTable('Organization')->find($id);
        $system = $organization->System;

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

        $this->_redirect("/system/view/id/$id");
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
            $system->Organization->orgType = 'system';
            
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
        
        // Add the system to the user's ACL if the flag was set above
        if ($addSystemToUserAcl) {
            $userRoles = CurrentUser::getInstance()->getRolesByPrivilege('organization', 'create');

            foreach ($userRoles as $role) {
                $role->Organizations[] = $system->Organization;
            }

            $userRoles->save();

            CurrentUser::getInstance()->invalidateAcl();
        }

        return $system->Organization->id;
    }

    /**
     * Display a form inside a panel for uploading a document
     * 
     * @return void
     */
    public function uploadDocumentAction()
    {
        $this->_helper->layout->disableLayout();

        $organizationId = $this->getRequest()->getParam('id');
        $organization = Doctrine::getTable('Organization')->find($organizationId);
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
            $destinationPath = Fisma::getPath('systemDocument') . "/$organizationId";
            if (!is_dir($destinationPath)) {
                mkdir($destinationPath);
            }
            $fileName = preg_replace('/^(.*)\.(.*)$/', '$1-' . date('Ymd-His') . '.$2', $file['name'], 2, $count);
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

            Fisma::getLogInstance($this->_me)->err($e->getMessage() . "\n" . $e->getTraceAsString());
        }
        
        $this->view->response = json_encode($response);
        
        if ($response->success) {
            $this->view->priorityMessenger('Artifact uploaded successfully', 'notice');
        }
    }  
}
