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
 * The finding controller is used for searching, displaying, and updating
 * findings.
 *
 * @author     Ryan Yang <ryan@users.sourceforge.net>
 * @copyright  (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Controller
 * @version    $Id$
 */
class Finding_IndexController extends Fisma_Zend_Controller_Action_Object
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
     * Invokes a contract with Fisma_Zend_Controller_Action_Object regarding privileges
     * 
     * @var string
     * @link http://jira.openfisma.org/browse/OFJ-24
     */
    protected $_organizations = '*';
    
    public function init()
    {
        parent::init();

        $this->_helper->fismaContextSwitch
             ->addActionContext('template', 'xls')
             ->initContext();
    }

    /** 
     * Overriding Hooks
     * 
     * @param Zend_Form $form The specified form to save
     * @param Doctrine_Record|null $subject The subject model related to the form
     * @return integer ID of the object 
     * @throws Fisma_Zend_Exception if the subject is not null or the organization of the finding associated
     * to the subject doesn`t exist
     */
    protected function saveValue($form, $subject=null)
    {
        if (is_null($subject)) {
            $subject = new $this->_modelName();
        } else {
            throw new Fisma_Zend_Exception('Invalid parameter expecting a Record model');
        }

        $values = $this->getRequest()->getPost();

        if (empty($values['securityControlId'])) {
            unset($values['securityControlId']);
        }

        $form->getElement('securityControlAutocomplete')->setValue($values['securityControlId'])
                                                        ->setDisplayText($values['securityControlAutocomplete']);

        $subject->merge($values);
        
        $organization = Doctrine::getTable('Organization')->find($values['orgSystemId']);
        if ($organization !== false) {
            $subject->ResponsibleOrganization = $organization;
        } else {
            throw new Fisma_Zend_Exception("The user tried to associate a new finding with a"
                                         . " non-existent organization (id={$values['orgSystemId']}).");
        }
                
        $subject->save();

        return $subject->id;
    }

    /**
     * Allow the user to upload an XML Excel spreadsheet file containing finding data for multiple findings
     * 
     * @return void
     */
    public function injectionAction()
    {
        $this->_acl->requirePrivilegeForClass('inject', 'Finding');

        // Set up the form for downloading template files
        $downloadForm = Fisma_Zend_Form_Manager::loadForm('finding_spreadsheet_download');
        
        Fisma_Zend_Form_Manager::prepareForm($downloadForm);

        $catalogQuery = Doctrine_Query::create()
                        ->select('id AS key, name AS value')
                        ->from('SecurityControlCatalog')
                        ->orderBy('name')
                        ->setHydrationMode(Doctrine::HYDRATE_ARRAY);

        $catalogs = $catalogQuery->execute();

        $downloadForm->getElement('catalogId')
                     ->setMultiOptions($catalogs)
                     ->setValue(Fisma::configuration()->getConfig('default_security_control_catalog_id'));
        
        $this->view->downloadForm = $downloadForm;
        
        // Set up the form for uploading files
        $uploadForm = Fisma_Zend_Form_Manager::loadForm('finding_spreadsheet_upload');
        
        Fisma_Zend_Form_Manager::prepareForm($uploadForm);
        
        $this->view->uploadForm = $uploadForm;
    }

    /**
     * Handle upload of a spreadsheet template file
     */
    public function uploadSpreadsheetAction()
    {
        $file = $_FILES['excelFile'];
        
        if (!is_array($file)) {
            $this->view->priorityMessenger("The file upload failed.", 'warning');
            return;
        } elseif (empty($file['name'])) {
            $error = 'You did not select a file to upload. Please select a file and try again.';
            $this->view->priorityMessenger($error, 'warning');
        } else {
            // Load the findings from the spreadsheet upload. Return a user error if the parser fails.
            try {
                Doctrine_Manager::connection()->beginTransaction();
                
                // get upload path
                $path = Fisma::getPath('data') . '/uploads/spreadsheet/';
                
                // get original file name
                $originalName = pathinfo($file['name'], PATHINFO_FILENAME);
                
                // get current time and set to a format like '20090504_112202'
                $dateTime = Zend_Date::now()->toString(Fisma_Date::FORMAT_FILENAME_DATETIMESTAMP);
                
                // define new file name
                $newName = str_replace($originalName, $originalName . '_' . $dateTime, $file['name']);
                
                // organize upload data
                $upload = new Upload();
                $upload->userId = $this->_me->id;
                $upload->fileName = $newName;
                $upload->save();

                $injectExcel = new Fisma_Inject_Excel();

                $rowsProcessed = $injectExcel->inject($file['tmp_name'], $upload->id);
                
                // upload file after the file parsed
                move_uploaded_file($file['tmp_name'], $path . $newName);
                
                Doctrine_Manager::connection()->commit();
                $error = "$rowsProcessed findings were created.";
                $type  = 'notice';
            } catch (Fisma_Zend_Exception_InvalidFileFormat $e) {
                Doctrine_Manager::connection()->rollback();
                $error = "The file cannot be processed due to an error: {$e->getMessage()}";
                $type  = 'warning';
            }
            $this->view->priorityMessenger($error, $type);
        }
        
        $this->_redirect('/finding/index/injection');
    }

    /** 
     * Downloading a excel file which is used as a template for uploading findings.
     * 
     * Systems, networks and sources are extracted from the database dynamically.
     * 
     * @return void
     */
    public function templateAction()
    {
        $this->_acl->requirePrivilegeForClass('inject', 'Finding');
        
        // set the filename for the browser to save the file as
        $this->_helper->fismaContextSwitch->setFilename(Fisma_Inject_Excel::TEMPLATE_NAME);

        /* The spreadsheet won't open in Excel if any of these tables are 
         * empty. So we explicitly check for that condition, and if it 
         * exists then we show the user an error message explaining why 
         * the spreadsheet isn't available.
         */
        try {
            $systems = $this->_me
                ->getOrganizationsByPrivilegeQuery('finding', 'inject')
                ->leftJoin('o.System s')
                ->select('o.id, o.nickname')
                ->andWhere('o.orgType <> ? OR s.sdlcPhase <> ?', array('system', 'disposal'))
                ->execute()
                ->toKeyValueArray('id', 'nickname');
            if (count($systems) == 0) {
                throw new Fisma_Zend_Exception("The spreadsheet template can not be
                    prepared because there are no systems defined.");
            } else {
                /** 
                 * @todo This really needs to be reconstructed. We shouldn't sort in PHP when the DBMS
                 * already has this field (nickname) indexed for us. Ideally, the user object would be
                 * able to return a query object that we could then modify.
                 */
                sort($systems);
                $this->view->systems = $systems;
            }
            
            $networks = Doctrine::getTable('Network')->findAll()->toArray();
            $this->view->networks = array();
            foreach ($networks as $network) {
                $this->view->networks[$network['id']] = $network['nickname'];
            }
            if (count($this->view->networks) == 0) {
                throw new Fisma_Zend_Exception("The spreadsheet template can not be
                     prepared because there are no networks defined.");
            }
            
            $sources = Doctrine::getTable('Source')->findAll()->toArray();
            $this->view->sources = array();
            foreach ($sources as $source) {
                $this->view->sources[$source['id']] = $source['nickname'];
            }
            if (count($this->view->sources) == 0) {
                throw new Fisma_Zend_Exception("The spreadsheet template can
                    not be prepared because there are no finding sources
                    defined.");
            }
            
            $securityControlCatalogId = $this->getRequest()->getParam('catalogId');
            $this->view->securityControlCatalogId = $securityControlCatalogId;

            $securityControlCatalog = Doctrine::getTable('SecurityControlCatalog')->find($securityControlCatalogId);
            
            if (!$securityControlCatalog) {
                throw new Fisma_Zend_Exception("No security control exists with id ($securityControlCatalogId)");
            } else {
                $this->view->securityControlCatalogName = $securityControlCatalog->name;
            }
            
            $securityControlQuery = Doctrine_Query::create()
                                    ->from('SecurityControl')
                                    ->where('securityControlCatalogId = ?', array($securityControlCatalogId));
            
            $securityControls = $securityControlQuery->execute();
            
            $this->view->securityControls = array();
            foreach ($securityControls as $securityControl) {
                $this->view->securityControls[$securityControl['id']] = $securityControl['code'];
            }
            if (count($this->view->securityControls) == 0) {
                 throw new Fisma_Zend_Exception('The spreadsheet template can not be ' .
                                                   'prepared because there are no security controls defined.');
            }
            $this->view->risk = array('HIGH', 'MODERATE', 'LOW');
            $this->view->templateVersion = Fisma_Inject_Excel::TEMPLATE_VERSION;
        } catch(Fisma_Zend_Exception $fe) {
            // error condition, remove all stuff from the context switch
            $this->getResponse()->clearAllHeaders();
            $this->_helper->viewRenderer->setViewSuffix('phtml');
            Zend_Layout::getMvcInstance()->enableLayout();
            $this->view->priorityMessenger($fe->getMessage(), 'warning');
            $this->_forward('injection', 'index', 'finding');
        }
    }
    
    /**
     *  Process the form submitted from the approveAction()
     *  
     *  @return void
     */
    public function processApprovalAction() 
    {
        $this->_acl->requirePrivilegeForClass('approve', 'Finding');

        $findings = $this->_request->getPost('findings', array());
        foreach ($findings as $id) {
            $finding = new Finding();
            if ($finding = $finding->getTable()->find($id)) {
                if (isset($_POST['approve_selected'])) {
                    if (in_array($finding->type, array('CAP', 'AR' ,'FP'))) {
                        $finding->status = 'DRAFT';
                    } else {
                        $finding->status = 'NEW';
                    }
                    $finding->save();
                } elseif (isset($_POST['delete_selected'])) {
                    $finding->getAuditLog()->write('Rejected pending finding');
                    $finding->delete();
                }
            }
        }
        $this->_redirect('/finding/index/approve');
    }

    /**
     * Forward to the remediation view action, since view isn't actually implemented in finding (wtf?). 
     * 
     * @access public
     * @return void
     */
    public function viewAction() 
    {
        $this->_forward('view', 'remediation', 'finding');
    }

    /**
     * Forward to the remediation list action. 
     * 
     * @access public
     * @return void
     */
    public function listAction()
    {
        $this->_forward('list', 'remediation');
    }
}
