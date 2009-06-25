<?php
/**
 * Copyright (c) 2008 Endeavor Systems, Inc.
 *
 * This file is part of OpenFISMA.
 *
 * OpenFISMA is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * OpenFISMA is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with OpenFISMA.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author    Ryan Yang <ryan@users.sourceforge.net>
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/mw/index.php?title=License
 * @version   $Id$
 * @package   Controller
 */

/**
 * The finding controller is used for searching, displaying, and updating
 * findings.
 *
 * @package   Controller
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/mw/index.php?title=License
 */
class FindingController extends BaseController
{
    /**
     * The main name of the model.
     * 
     * This model is the main subject which the controller operates on.
     */
    protected $_modelName = 'Finding';

    /**
     * my OrgSystems
     *
     * @var array
     */
    private $_myOrgSystems = null;
    
    /**
     * my OrgSystem ids
     *
     * @var array
     */
    private $_myOrgSystemIds = null;
    
    /**
     * initialize the basic information, my orgSystems
     *
     */
    public function init()
    {
        parent::init();
        $orgSystems = $this->_me->Organizations->toArray();
        $this->_myOrgSystems = $orgSystems;
        
        $orgSystemIds = array(0);
        foreach ($orgSystems as $orgSystem) {
            $orgSystemIds[] = $orgSystem['id'];
        }
        $this->_myOrgSystemIds = $orgSystemIds;
    }
    
    /**
     * Returns the standard form for creating finding
     *
     * @return Zend_Form
     */
    public function getForm()
    {
        $form = Fisma_Form_Manager::loadForm('finding');
        
        $sources = Doctrine::getTable('Source')->findAll()->toArray();
        $form->getElement('sourceId')->addMultiOptions(array(0 => '--select--'));
        foreach ($sources as $source) {
            $form->getElement('sourceId')->addMultiOptions(array($source['id'] => $source['name']));
        }
    
        $securityControls = Doctrine::getTable('SecurityControl')->findAll()->toArray();
        $form->getElement('securityControlId')->addMultiOptions(array(0 => '--select--'));
        foreach ($securityControls as $securityControl) {
            $form->getElement('securityControlId')
                 ->addMultiOptions(array($securityControl['id'] => $securityControl['code']));
        }
        
        $systems = $this->_me->Organizations;
        $systemList[0] = "--select--";
        foreach ($systems as $system) {
            $systemList[$system['id']] = $system['nickname'].'-'.$system['name'];
        }
        $form->getElement('orgSystemId')->addMultiOptions($systemList);

        // fix: Zend_Form can not support the values which are not in its configuration
        //      The values are set after page loading by Ajax
        $asset = Doctrine::getTable('Asset')->find($this->_request->getParam('assetId'));
        if ($asset) {
            $form->getElement('assetId')->addMultiOptions(array($asset['id'] => $asset['name']));
        }
        
        $form->setDisplayGroupDecorators(array(
            new Zend_Form_Decorator_FormElements(),
            new Fisma_Form_CreateFindingDecorator()
        ));
        
        $form->setElementDecorators(array(new Fisma_Form_CreateFindingDecorator()));
        $dateElement = $form->getElement('discoveredDate');
        $dateElement->clearDecorators();
        $dateElement->addDecorator('ViewScript', array('viewScript'=>'datepicker.phtml'));
        $dateElement->addDecorator(new Fisma_Form_CreateFindingDecorator());
        return $form;
    }

    /** 
     * Overriding Hooks
     *
     * @param Zend_Form $form
     * @param Doctrine_Record|null $subject
     */
    protected function saveValue($form, $subject=null)
    {
        if (is_null($subject)) {
            $subject = new $this->_modelName();
        } else {
            /** @todo English */
            throw new Fisma_Exception('Invalid parameter expecting a Record model');
        }
        $values = $form->getValues();
        
        // find the asset record by asset id
        $asset = Doctrine::getTable('Asset')->find($values['assetId']);
        if ($asset) {
            // set organization id by related asset
            $values['responsibleOrganizationId'] = $asset->Organization->id;
        }
        $subject->merge($values);
        $subject->save();
    }
    
    
    /**
     * Provide searching capability of findings
     * Data is limited in legal systems.
     */
    protected function _search($criteria)
    {
        $fields = array(
            'id',
            'legacy_finding_id',
            'ip',
            'port',
            'status',
            'source_id',
            'system_id',
            'discover_ts',
            'count' => 'count(*)'
        );
        if ($criteria['status'] == 'REMEDIATION') {
            $criteria['status'] = array(
                'DRAFT',
                'MSA',
                'EN',
                'EA'
            );
        }
        $result = $this->_poam->search($this->_me->systems, $fields, $criteria,
                     $this->_paging['currentPage'], $this->_paging['perPage']);
        $total = array_pop($result);
        $this->_paging['totalItems'] = $total;
        $pager = & Pager::factory($this->_paging);
        $this->view->assign('findings', $result);
        $this->view->assign('links', $pager->getLinks());
        $this->render('search');
    }


    /**
     * Edit finding information
     */
    public function editAction()
    {
        Fisma_Acl::requirePrivilege('finding', 'update');
        
        $req = $this->getRequest();
        $id = $req->getParam('id');
        assert($id);
        $finding = new Finding();
        $do = $req->getParam('do');
        if ($do == 'update') {
            $status = $req->getParam('status');
            $db = Zend_Registry::get('db');
            $result = $db->query("UPDATE FINDINGS SET finding_status = '$status'
                                  WHERE finding_id = $id");
            if ($result) {
                $this->view->assign('msg', "Finding updated successfully");
            } else {
                $this->view->assign('msg', "Failed to update the finding");
            }
        }
        $this->view->assign('act', 'edit');
        $this->_forward('view', 'Finding');
    }
    
    /**
     * Allow the user to upload an XML Excel spreadsheet file containing finding data for multiple findings
     */
    public function injectionAction()
    {
        Fisma_Acl::requirePrivilege('finding', 'inject');

        /** @todo convert this to a Zend_Form */
        // If the form isn't submitted, then there is no work to do
        if (!isset($_POST['uploadExcelSubmit'])) {
            return;
        }
        
        // If the form is submitted, then the file object should contain an array
        $file = $_FILES['excelFile'];
        if (!is_array($file)) {
            $this->message("The file upload failed.", self::M_WARNING);
            return;
        } elseif (empty($file['name'])) {
            $this->message('You did not select a file to upload. Please select a file and try again.', 
                           self::M_WARNING);
        } else {
            // Load the findings from the spreadsheet upload. Return a user error if the parser fails.
            try {
                Doctrine_Manager::connection()->beginTransaction();
                
                // get upload path
                $path = Fisma::getPath('data') . '/uploads/spreadsheet/';
                // get original file name
                $originalName = pathinfo($file['name'], PATHINFO_FILENAME);
                // get current time and set to a format like '_2009-05-04_11_22_02'
                $ts = time();
                $dateTime = date('_Y-m-d_H_i_s', $ts);
                // define new file name
                $newName = str_replace($originalName, $originalName . $dateTime, $file['name']);
                // organize upload data
                $upload = new Upload();
                $upload->userId = $this->_me->id;
                $upload->fileName = $newName;
                $upload->save();

                $injectExcel = new Fisma_Inject_Excel();
                if (!empty($injectExcel->_findingIds)
                            && is_dir(Fisma::getPath('data') . '/index/finding/')) {
                    foreach ($injectExcel->_findingIds as $id) {
                        $this->createIndex($id);
                    }
                }

                $rowsProcessed = $injectExcel->inject($file['tmp_name'], $upload->id);
                // upload file after the file parsed
                move_uploaded_file($file['tmp_name'], $path . $newName);
                
                Doctrine_Manager::connection()->commit();
                $this->message("$rowsProcessed findings were created.", self::M_NOTICE);
            } catch (Fisma_Exception_InvalidFileFormat $e) {
                Doctrine_Manager::connection()->rollback();
                $this->message("The file cannot be processed due to an error.<br>{$e->getMessage()}",
                               self::M_WARNING);
            }
        }
        $this->render();
    }

    /**
     *  Delete findings
     */
    public function deleteAction()
    {
        Fisma_Acl::requirePrivilege('finding', 'delete');
        
        $req = $this->getRequest();
        $post = $req->getPost();
        $errno = 0;
        $successno = 0;
        $poam = new poam();
        foreach ($post as $key => $id) {
            if (substr($key, 0, 3) == 'id_') {
                $poamId[] = $id;
                $res = $poam->update(array(
                    'status' => 'DELETED'
                ), 'id = ' . $id);
                if ($res) {
                    $successno++;
                } else {
                    $errno++;
                }
            }
        }
        $msg = 'Delete ' . $successno . ' Findings Successfully,'
                . $errno . ' Failed!';
        // @todo The delete action isn't support right now, but when it is,
        // the notification needs to be limited to the system from which the
        // finding is being deleted.
        //$this->_notification->add(Notification::FINDING_DELETED,
        //    $this->_me->account, $poamId);
        $this->message($msg, self::M_NOTICE);
        $this->_forward('searchbox', 'finding', null, array(
            's' => 'search'
        ));
    }
    
    /** 
     * Downloading a excel file which is used as a template 
     * for uploading findings.
     * systems, networks and sources are extracted from the
     * database dynamically.
     */
    public function templateAction()
    {
        Fisma_Acl::requirePrivilege('finding', 'inject');
        
        $contextSwitch = $this->_helper->getHelper('contextSwitch');
        $contextSwitch->addContext('xls', array(
            'suffix' => 'xls',
            'headers' => array(
                'Content-type' => 'application/vnd.ms-excel',
                'Content-Disposition' => 'filename=' . Fisma_Inject_Excel::TEMPLATE_NAME
            )
        ));
        $contextSwitch->addActionContext('template', 'xls');
        /* The spreadsheet won't open in Excel if any of these tables are 
         * empty. So we explicitly check for that condition, and if it 
         * exists then we show the user an error message explaining why 
         * the spreadsheet isn't available.
         */
        try {
            $this->_myOrgSystems;
            $this->view->systems = array();
            foreach ($this->_myOrgSystems as $orgSystem) {
                $this->view->systems[$orgSystem['id']] = $orgSystem['nickname'];
            }
            if (count($this->view->systems) == 0) {
                throw new Fisma_Exception(
                    "The spreadsheet template can not be " .
                    "prepared because there are no systems defined.");
            }
            
            $networks = Doctrine::getTable('Network')->findAll()->toArray();
            $this->view->networks = array();
            foreach ($networks as $network) {
                $this->view->networks[$network['id']] = $network['nickname'];
            }
            if (count($this->view->networks) == 0) {
                 throw new Fisma_Exception("The spreadsheet template can not be
                     prepared because there are no networks defined.");
            }
            
            $sources = Doctrine::getTable('Source')->findAll()->toArray();
            $this->view->sources = array();
            foreach ($sources as $source) {
                $this->view->sources[$source['id']] = $source['nickname'];
            }
            if (count($this->view->sources) == 0) {
                 throw new Fisma_Exception("The spreadsheet template can
                     not be prepared because there are no finding sources
                     defined.");
            }
            
            $securityControls = Doctrine::getTable('SecurityControl')->findAll()->toArray();
            $this->view->securityControls = array();
            foreach ($securityControls as $securityControl) {
                $this->view->securityControls[$securityControl['id']] = $securityControl['code'];
            }
            if (count($this->view->securityControls) == 0) {
                 throw new Fisma_Exception('The spreadsheet template can not be ' .
                                                   'prepared because there are no security controls defined.');
            }
            $this->view->risk = array('HIGH', 'MODERATE', 'LOW');
            $this->view->templateVersion = Fisma_Inject_Excel::TEMPLATE_VERSION;

            // Context switch is called only after the above code executes successfully. Otherwise if there is an error,
            // the error handler will be confused by context switch and will look for error.xls.tpl instead of error.tpl
            $contextSwitch->initContext('xls');
            
            /* Bug fix #2507318 - 'OVMS Unable to open Spreadsheet upload file'
             * This fixes a bug in IE6 where some mime types get deleted if IE
             * has caching enabled with SSL. By setting the cache to 'private' 
             * we can tell IE not to cache this file.
             */                                       
            $this->getResponse()->setHeader('Pragma', 'private', true);
            $this->getResponse()->setHeader('Cache-Control', 'private', true);
        } catch(Fisma_Exception $fe) {
            Zend_Controller_Action_HelperBroker::getStaticHelper('viewRenderer');
            $this->message($fe->getMessage(), self::M_WARNING);
            $this->_forward('injection', 'Finding');
        }
    }

    /** 
     * pluginAction() - Import scan results via a plug-in
     */
    public function pluginAction()
    {       
        Fisma_Acl::requirePrivilege('finding', 'inject');

        // Load the finding plugin form
        $uploadForm = Fisma_Form_Manager::loadForm('finding_upload');
        $uploadForm = Fisma_Form_Manager::prepareForm($uploadForm);
        $uploadForm->setAttrib('id', 'injectionForm');

        // Populate the drop menu options
        $uploadForm->plugin->addMultiOption('', '');
        $plugins = Doctrine::getTable('Plugin')->findAll()->toArray();
        $pluginList = array();
        foreach ($plugins as $plugin) {
            $pluginList[$plugin['id']] = $plugin['name'];
        }
        $uploadForm->plugin->addMultiOptions($pluginList);
        
        $sources = Doctrine::getTable('Source')->findAll()->toArray();
        $sourceList = array();
        foreach ($sources as $source) {
            $sourceList[$source['id']] = $source['nickname'] . ' - ' . $source['name'];
        }
        $uploadForm->findingSource->addMultiOption('', '');
        $uploadForm->findingSource->addMultiOptions($sourceList);
        
        $orgSystems = $this->_me->Organizations->toArray();
        $orgSystemList = array();
        foreach ($orgSystems as $orgSystem) {
            $orgSystemList[$orgSystem['id']] = $orgSystem['nickname'] . ' - ' . $orgSystem['name'];
        }
        $uploadForm->system->addMultiOption('', '');
        $uploadForm->system->addMultiOptions($orgSystemList);

        $networks = Doctrine::getTable('Network')->findAll()->toArray();
        $networkList = array();
        foreach ($networks as $network) {
            $networkList[$network['id']] = $network['nickname'] . ' - ' . $network['name'];
        }
        $uploadForm->network->addMultiOption('', '');
        $uploadForm->network->addMultiOptions($networkList);
        
        // Configure the file select
        $uploadForm->setAttrib('enctype', 'multipart/form-data');
        $uploadForm->selectFile->setDestination(Fisma::getPath('data') . '/uploads/scanreports');

        // Setup the view
        $this->view->assign('uploadForm', $uploadForm);

        // Handle the file upload, if necessary
        $fileReceived = false;
        $postValues = $this->_request->getPost();
        if ($postValues) {
            if ($uploadForm->isValid($postValues) && $fileReceived = $uploadForm->selectFile->receive()) {
                $filePath = $uploadForm->selectFile->getTransferAdapter()->getFileName('selectFile');
                $values = $uploadForm->getValues();
                
                // Get information about the plugin, and then create a new instance of the plugin.
                $pluginTbl = new Plugin();
                $pluginTbl = $pluginTbl->getTable('Plugin')->find($values['plugin']);
                $pluginClass = $pluginTbl->class;
                $pluginName = $pluginTbl->name;
                $plugin = new $pluginClass($filePath,
                                           $values['network'],
                                           $values['system'],
                                           $values['findingSource']);
                
                // Execute the plugin with the received file
                try {
                    Doctrine_Manager::connection()->beginTransaction();
                    // get original file name
                    $originalName = pathinfo(basename($filePath), PATHINFO_FILENAME);
                    // get current time and set to a format like '_2009-05-04_11_22_02'
                    $ts = time();
                    $dateTime = date('_Y-m-d_H_i_s', $ts);
                    // define new file name
                    $newName = str_replace($originalName, $originalName . $dateTime, basename($filePath));
                    // organize upload data
                    $upload = new Upload();
                    $upload->userId = $this->_me->id;
                    $upload->fileName = $newName;
                    $upload->save();
                    
                    // parse the file
                    $plugin->parse($upload->id);
                    // rename the file by ts
                    rename($filePath, dirname($filePath) . '/' . $newName);

                    if (!empty($plugin->_findingIds)
                        && is_dir(Fisma::getPath('data') . '/index/finding/')) {
                        foreach ($plugin->_findingIds as $id) {
                            $this->createIndex($id);
                        }
                    }
                    $this->message("Your scan report was successfully uploaded.<br>"
                                   . "{$plugin->created} findings were created.<br>"
                                   . "{$plugin->reviewed} findings need review.<br>"
                                   . "{$plugin->deleted} findings were suppressed.",
                                   self::M_NOTICE);
                    Doctrine_Manager::connection()->commit();
                } catch (Fisma_Exception_InvalidFileFormat $e) {
                    $this->message("The uploaded file is not a valid format for {$pluginName}: {$e->getMessage()}",
                                   self::M_WARNING);
                    Doctrine_Manager::connection()->rollback();
                }
            } else {
                $errorString = Fisma_Form_Manager::getErrors($uploadForm);

                if (!$fileReceived) {
                    $errorString .= "File not received<br>";
                }

                // Error message
                $this->message("Scan upload failed:<br>$errorString", self::M_WARNING);
            }
            // This is a hack to make the submit button work with YUI:
            /** @yui */ $uploadForm->upload->setValue('Upload');
            $this->render(); // Not sure why this view doesn't auto-render?? It doesn't render when the POST is set.
        }
    }

    /** 
     * approveAction() - Allows a user to approve or delete pending findings
     *
     * @todo Use YUI pager
     */
    public function approveAction()
    {
        Fisma_Acl::requirePrivilege('finding', 'approve');
        
        $q = Doctrine_Query::create()
             ->select('f.id AS nId')
             ->addSelect('f.description AS nDesc')
             ->addSelect('f.duplicateFindingId AS duplicateFindingId')
             ->addSelect('nr.nickname AS nNickname')
             ->addSelect('df.id AS oId')
             ->addSelect('df.status AS oStatus')
             ->addSelect('df.type AS oType')
             ->addSelect('or.nickname AS oNickname')
             ->from('Finding f')
             ->leftJoin('f.ResponsibleOrganization nr')
             ->leftJoin('f.DuplicateFinding df')
             ->leftJoin('df.ResponsibleOrganization or')
             ->where('f.status = ?', 'PEND')
             ->orderBy('f.responsibleOrganizationId, f.id');

        $findings = $q->execute()->toArray();
        $this->view->assign('findings', $findings);
    }
    
    /**
     * processApprovalAction() - Process the form submitted from the approveAction()
     *
     */
    public function processApprovalAction() {
        Fisma_Acl::requirePrivilege('finding', 'approve');
        
        $db = Zend_Registry::get('db');
        $post = $this->getRequest()->getPost();
        if (isset($post['findings'])) {
            foreach ($post['findings'] as $findingId) {
                $finding = new Finding();
                if ($finding = $finding->getTable('Finding')->find($findingId)) {
                    if (isset($_POST['approve_selected'])) {
                        $finding->status = 'NEW';
                        $finding->save();
                        if (is_dir(Fisma::getPath('data') . '/index/finding/')) {
                            $this->createIndex($findingId);
                        }
                    } elseif (isset($_POST['delete_selected'])) {
                        $finding->AuditLogs->delete();
                        $finding->delete();
                    }
                }
            }
        }
        $this->_forward('approve', 'Finding');
    }

    /**
     * Create finding lucene index
     *
     * @param int $id a specific id
     */
    private function createIndex($id)
    {
        set_time_limit(0);
        $result = $this->_poam->find($id)->current();
        $systemId = $result->system_id;
        $sourceId = $result->source_id;
        $assetId  = $result->asset_id;
        $indexData['finding_data']           = $result->finding_data;
        $indexData['action_suggested']       = $result->action_suggested;
        $indexData['action_planned']         = $result->action_planned;
        $indexData['action_resources']       = $result->action_resources;
        $indexData['threat_source']          = $result->threat_source;
        $indexData['threat_justification']   = $result->threat_justification;
        $indexData['cmeasure']               = $result->cmeasure;
        $indexData['cmeasure_justification'] = $result->cmeasure_justification;
        $indexData['action_resources']       = $result->action_resources;
        $indexData['threat_source']          = $result->threat_source;

        $system = new System();
        $source = new Source();
        $asset  = new Asset();

        $ret = $system->find($systemId)->current();
        if (!empty($ret)) {
            $indexData['system'] = $ret->name . ' ' . $ret->nickname;
        }

        $ret = $source->find($sourceId)->current();
        if (!empty($ret)) {
            $indexData['source'] = $ret->name . ' ' . $ret->nickname;
        }

        $ret = $asset->find($assetId)->current();
        if (!empty($ret)) {
            $indexData['asset']  = $ret->name;
        }

        $this->_helper->updateIndex('finding', $id, $indexData);
    }
}
