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
class SystemController extends BaseController
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
     * Invokes a contract with BaseController regarding privileges.
     * 
     * @var string
     * @link http://jira.openfisma.org/browse/OFJ-24
     */
    protected $_organizations = '*';

    /**
     * Setup the _organization member so that the base controller knows how to query the ACL
     * 
     * @return void
     */
    public function init() 
    {
        parent::init();
    }

    /**
     * Returns the standard form for creating, reading, and updating systems.
     * 
     * @param string|null $formName The specified form name
     * @return Zend_Form The assembled from
     */
    public function getForm($formName = null)
    {
        $form = Fisma_Form_Manager::loadForm('system');
        $organizationTreeObject = Doctrine::getTable('Organization')->getTree();
        $q = User::currentUser()->getOrganizationsQuery();
        $organizationTreeObject->setBaseQuery($q);
        $organizationTree = $organizationTreeObject->fetchTree();
        if (!empty($organizationTree)) {
            foreach ($organizationTree as $organization) {
                $value = $organization['id'];
                $text = str_repeat('--', $organization['level']) . $organization['name'];
                $form->getElement('parentOrganizationId')->addMultiOptions(array($value => $text));
            }
        }
        
        $systemTable = Doctrine::getTable('System');
        
        $array = $systemTable->getEnumValues('confidentiality');
        $form->getElement('confidentiality')->addMultiOptions(array_combine($array, $array));
        
        $array = $systemTable->getEnumValues('integrity');
        $form->getElement('integrity')->addMultiOptions(array_combine($array, $array));
        
        $array = $systemTable->getEnumValues('availability');
        $form->getElement('availability')->addMultiOptions(array_combine($array, $array));
        
        $type = $systemTable->getEnumValues('type');
        $form->getElement('type')->addMultiOptions(array_combine($type, $type));
        
        return Fisma_Form_Manager::prepareForm($form);
    }
    
    /**
     * List the systems from the search. If search none, it list all systems
     * 
     * @return void
     * @throws Fisma_Exception if the order parameter invalid
     */
    public function searchAction()
    {
        Fisma_Acl::requirePrivilegeForClass('read', 'System');
        
        $keywords = trim($this->_request->getParam('keywords'));
        
        $sortBy = $this->_request->getParam('sortby', 'name');
        // Replace the HYDRATE_SCALAR alias syntax with the regular Doctrine alias syntax
        $sortBy = str_replace('_', '.', $sortBy);
        $order = $this->_request->getParam('order', 'ASC');
        
        if (!in_array(strtolower($order), array('asc', 'desc'))) {
            throw new Fisma_Exception('Invalid "order" parameter');
        }
        
        $q = User::currentUser()
             ->getOrganizationsQuery()
             ->select(
                 'o.id, 
                  o.name, 
                  o.nickname, 
                  s.type, 
                  s.confidentiality, 
                  s.integrity, 
                  s.availability, 
                  s.fipsCategory'
             )
             ->innerJoin('o.System s')
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
        Fisma_Acl::requirePrivilegeForObject('read', $organization);
        
        $organization = Doctrine::getTable('Organization')->find($id);
        $this->view->organization = $organization;
        $this->view->system = $organization->System;

        $this->render();
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
        Fisma_Acl::requirePrivilegeForObject('read', $organization);
        
        $this->view->organization = Doctrine::getTable('Organization')->find($id);
        $this->view->system = $this->view->organization->System;

        // Assign the parent organization link
        $parentOrganization = $this->view->organization->getNode()->getParent();
        if (isset($parentOrganization)) {
            if (Fisma_Acl::hasPrivilegeForObject('read', $parentOrganization)) {
                if ('system' == $parentOrganization->orgType) {
                    $this->view->parentOrganization = "<a href='/panel/system/sub/view/id/"
                                                    . $parentOrganization->id
                                                    . "'>"
                                                    . "$parentOrganization->nickname - $parentOrganization->name"
                                                    . "</a>";
                } else {
                    $this->view->parentOrganization = "<a href='/panel/organization/sub/view/id/"
                                                    . $parentOrganization->id
                                                    . "'>"
                                                    . "$parentOrganization->nickname - $parentOrganization->name"
                                                    . "</a>";
                
                }
            } else {
                $this->view->parentOrganization = "$parentOrganization->nickname - $parentOrganization->name";
            }
        } else {
            $this->view->parentOrganization = "<i>None</i>";
        }

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
        Fisma_Acl::requirePrivilegeForObject('read', $organization);
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
        Fisma_Acl::requirePrivilegeForObject('read', $organization);
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
        Fisma_Acl::requirePrivilegeForObject('read', $organization);
        $this->_helper->layout()->disableLayout();

        $organization = Doctrine::getTable('Organization')->find($id);
        $system = $organization->System;
        $documents = $system->Documents;
        
        $this->view->organization = $organization;
        $this->view->system = $system;
        
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
        Fisma_Acl::requirePrivilegeForObject('update', $organization);
        $this->_helper->layout()->disableLayout();

        $organization = Doctrine::getTable('Organization')->find($id);
        $system = $organization->System;

        $post = $this->_request->getPost();

        if ($post) {
            $organization->merge($post);
            $system->merge($post);
            if ($organization->isValid(true) && $system->isValid(true)) {
                $organization->save();
                $system->save();
            }
        }

        $this->_redirect("/panel/system/sub/view/id/$id");
    }

    /**
     * Upload file artifacts for a system
     * 
     * @return void
     */
    public function attachFileAction() 
    {
        $id = $this->getRequest()->getParam('id');
        $organization = Doctrine::getTable('Organization')->find($id);
        Fisma_Acl::requirePrivilegeForObject('update', $organization);
        $this->_helper->layout()->disableLayout();

        $this->view->organization = Doctrine::getTable('Organization')->find($id);
        $this->view->system = $this->view->organization->System;
    }

    /**
     * Override the base class to handle the saving of attributes into the Organization AND System models
     *
     * @param Zend_Form $form
     * @param Doctrine_Record $system
     * @throws Fisma_Exception if the subject is not instance of Doctrine_Record
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
            throw new Fisma_Exception('Expected a Doctrine_Record object');
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
            throw new Fisma_Exception("No parent organization with id={$systemData['parentOrganizationId']}ß");
        }
        $system->Organization->getNode()->insertAsLastChildOf($parentNode);
        $system->Organization->save();
        
        // Add the system to the user's ACL if the flag was set above
        if ($addSystemToUserAcl) {
            User::currentUser()->Organizations[] = $system->Organization;
            User::currentUser()->save();
            User::currentUser()->invalidateAcl();
        }
    }

    /**
     * Display a form inside a panel for uploading a document
     * 
     * Notice that IE has its own method, since it does not support the flash uploader
     * 
     * @return void
     */
    public function uploadDocumentFormAction()
    {
        $id = $this->getRequest()->getParam('id');
        $organization = Doctrine::getTable('Organization')->find($id);
        Fisma_Acl::requirePrivilegeForObject('update', $organization);
        $this->_helper->layout()->disableLayout();

        $this->view->organizationId = $id;        
        $this->view->documentTypes  = Doctrine_Query::create()->from('DocumentType')->orderBy('name')->execute();
    }
  
    /**
     * Display a form inside a panel for uploading a document
     * 
     * @return void
     */
    public function uploadDocumentAction()
    {
        $id = $this->getRequest()->getParam('id');
        $organization = Doctrine::getTable('Organization')->find($id);
        Fisma_Acl::requirePrivilegeForObject('update', $organization);
                
        $organization = Doctrine::getTable('Organization')->find($id);
        $documentTypeId = $this->getRequest()->getParam('documentTypeId');
        $description = $this->getRequest()->getParam('description');

        try {
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
                $document->User = User::currentUser();
            } else {
                $document = $documents[0];
            }

            // Move file into its correct place
            $error = '';
            if (empty($_FILES['systemdoc']['name'])) {
                throw new Fisma_Exception("You did not specify a file to upload.");
            }
            $file = $_FILES['systemdoc'];
            $destinationPath = Fisma::getPath('systemDocument') . "/$id";
            if (!is_dir($destinationPath)) {
                mkdir($destinationPath);
            }
            $fileName = preg_replace('/^(.*)\.(.*)$/', '$1-' . date('Ymd-His') . '.$2', $file['name'], 2, $count);
            $filePath = "$destinationPath/$fileName";

            if (!move_uploaded_file($file['tmp_name'], $filePath)) {
                throw new Fisma_Exception("The file could not be stored due to the server's permissions settings.");
            }
    
            // Update the document object and save
            if (empty($description)) {
                throw new Fisma_Exception("You did not enter the version notes.");
            }
            $document->description = $description;
            $document->fileName = $fileName;
            $document->mimeType = $file['type'];
            $document->size = $file['size'];
            $document->save();
        } catch (Fisma_Exception $e) {
            $error = "Upload failed: " . $e->getMessage();
        }
        
        if ('ie' == $this->getRequest()->getParam('browser')) {
            // Special handling for IE
            if (!empty($error)) {
                $this->view->priorityMessenger($error, 'warning');
                $this->_forward('system', 'Panel', null, array('sub' => 'upload-for-ie', 'error' => $error));
            } else {
                $this->_redirect("/panel/system/sub/view/id/$id");
            }
        } else {
            // For all other browsers, send back a JSON status
            $this->_helper->layout()->disableLayout();
            $this->_helper->viewRenderer->setNoRender(true);
            
            $success = empty($error) ? true : false;
            echo(json_encode(array('success' => $success, 'error' => $error)));
        }
    }  
    
    /**
     * Download the specified system document
     * 
     * @return void
     * @throws Fisma_Exception if requested file doesn`t exist
     */
    public function downloadDocumentAction()
    {
        $id = $this->getRequest()->getParam('id');
        $version = $this->getRequest()->getParam('version');
        $document = Doctrine::getTable('SystemDocument')->find($id);
        
        // Documents don't have their own privileges, access control is based on the associated organization
        Fisma_Acl::requirePrivilegeForObject('read', $document->System->Organization);

        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        $document = Doctrine::getTable('SystemDocument')->find($id);
        if (isset($version)) {
            $versionInfo = $document->getAuditLog()->getVersion($document, $version);
            // This is awkward. Doctrine's Versionable returns versions as arrays, not objects.
            // So we have to create a temporary object in order to execute the required logic.
            $document = new SystemDocument();
            $document->merge($versionInfo[0]);
        }

        if (is_null($document)) {
            throw new Fisma_Exception("Requested file does not exist.");
        }

        /** @todo better error checking */
        $this->getResponse()
             ->setHeader('Content-Type', $document->mimeType)
             ->setHeader('Content-Disposition', "attachment; filename=\"$document->fileName\"");
         $path = $document->getPath();
         readfile($path);
    }
    
    /**
     * A special upload page just for IE.
     * 
     * IE doesn't work with the flash uploader, so it uses a static upload page.
     * 
     * @return void
     */
    public function uploadForIeAction() 
    {
        $id = $this->getRequest()->getParam('id');
        $organization = Doctrine::getTable('Organization')->find($id);
        Fisma_Acl::requirePrivilegeForObject('update', $organization);

        $error = $this->getRequest()->getParam('error');
        if (!empty($error)) {
            $this->view->priorityMessenger($error, 'warning');
        }

        $this->view->organizationId = $id;        
        $this->view->documentTypes  = Doctrine_Query::create()->from('DocumentType')->orderBy('name')->execute();
    }
}
