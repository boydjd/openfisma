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
 * The asset controller deals with creating, updating, and managing assets on the system.
 *
 * @author     Jim Chen <xhorse@users.sourceforge.net>
 * @copyright  (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Controller
 * @version    $Id$
 * 
 * @see        Zend_View_Helper_Abstract
 */
class AssetController extends BaseController
{
    /**
     * The main name of the model.
     * 
     * This model is the main subject which the controller operates on.
     * 
     * @var string
     */
    protected $_modelName = 'Asset';

    /**
     * Asset columns which need to displayed on the list page, PDF and Excel
     * 
     * @var array
     */
    protected $_assetColumns = array('name'  => 'Asset Name',
                                     'orgsys_name' => 'System',
                                     'addressIp'  => 'IP Address',
                                     'addressPort'=> 'Port',
                                     'pro_name'   => 'Product Name',
                                     'pro_vendor' => 'Vendor',
                                     'pro_version'=> 'Version');

    /**
     * Invokes a contract with BaseController regarding privileges.
     * 
     * @var string
     * @link http://jira.openfisma.org/browse/OFJ-24
     */
    protected $_organizations = '*';

    /**
     * Initialize internal members.
     * 
     * @return void
     */
    function init()
    {
        parent::init();
        
        $swCtx = $this->_helper->contextSwitch();
        if (!$swCtx->hasContext('pdf')) {
            $swCtx->addContext(
                'pdf', 
                array(
                    'suffix' => 'pdf',
                    'headers' => array(
                        'Content-Disposition' =>'attachement;filename="export.pdf"',
                        'Content-Type' => 'application/pdf'
                    )
                )
            );
        }
    }

    /**
     * Invoked before each Actions
     * 
     * @return void
     */
    public function preDispatch()
    {
        parent::preDispatch();
        $this->req = $this->getRequest();
        $swCtx = $this->_helper->contextSwitch();
        $swCtx->addActionContext(
            'search', 
            array(
                'pdf'
            )
        );
        $swCtx->initContext();
    }
    
    /**
     * Get the specified form of the subject model
     * 
     * @param string|null $formName The name of the specified form
     * @return Zend_Form The specified form of the subject model
     */
    public function getForm($formName=null)
    {
        $form = parent::getForm($formName);
        $systems = $this->_me->getOrganizationsByPrivilege('asset', 'read');
        $selectArray = $this->view->treeToSelect($systems, 'nickname');
        $form->getElement('orgSystemId')->addMultiOptions($selectArray);
        
        $networks = Doctrine::getTable('Network')->findAll()->toArray();
        $networkList = array();
        foreach ($networks as $network) {
            $networkList[$network['id']] = $network['nickname'].'-'.$network['name'];
        }
        $form->getElement('networkId')->addMultiOptions($networkList);
        $form = Fisma_Zend_Form_Manager::prepareForm($form);
        return $form;
    }
    
    /**
     * Hooks for manipulating the values before setting to a form
     * 
     * @param Doctrine_Record $subject The specified subject model
     * @param Zend_Form $form The specified form
     * @return Zend_Form The manipulated form
     */
    protected function setForm($subject, $form)
    {
        $product = $subject->Product;
        $form->getElement('product')->setValue($product->name);

        if ($this->getRequest()->getParam('sub') != 'edit') 
            $form->getElement('product')->setAttrib('readonly', true);

        return parent::setForm($subject, $form);
    }

    /**
     * Hooks for manipulating and saving the values retrieved by Forms
     *
     * @param Zend_Form $form The specified form
     * @param Doctrine_Record|null $subject The specified subject model
     * @return void
     * @throws Fisma_Zend_Exception if the subject is not instance of Doctrine_Record
     */
    protected function saveValue($form, $subject=null)
    {
        if (is_null($subject)) {
            $subject = new $this->_modelName();
        } elseif (!$subject instanceof Doctrine_Record) {
            throw new Fisma_Zend_Exception('Invalid parameter: Expected a Doctrine_Record');
        }
        $values = $form->getValues();

        if (empty($values['productId'])) {
            unset($values['productId']);
        }

        $subject->merge($values);
        $subject->save();
    }
    
    /**
     * Extract specified criteria parameters from request and assemble them
     * 
     * @return array The array of criteria parameters
     */
    private function parseCriteria()
    {
        static $params;
        if ($params == null) {
            $req = $this->getRequest();
            $params['system_id'] = $req->get('system_id');
            $params['product'] = $req->get('product');
            $params['name'] = $req->get('name');
            $params['vendor'] = $req->get('vendor');
            $params['version'] = $req->get('version');
            $params['ip'] = $req->get('ip');
            $params['port'] = $req->get('port');
        }
        return $params;
    }
    
    /**
     * Searching the asset and list them
     * 
     * It is the ajax version of searchbox action
     * 
     * @return void
     * @todo merge the two actions into one
     */
    public function listAction()
    {
        $this->searchboxAction();
        $this->view->columns = $this->_assetColumns;

        $params = $this->parseCriteria();
        $this->view->url = '';
        foreach ($params as $k => $v) {
            if (!empty($v)) {
                $this->view->url .= '/'.$k.'/'.$v;
            }
        }
        parent::listAction();
    }
    
    /**
     * Create an asset
     * 
     * @return void
     */
    public function createAction()
    {
        Fisma_Zend_Acl::requirePrivilegeForClass('create', 'Asset');
        $this->_request->setParam('source', 'MANUAL');
        parent::createAction();
    }

    /**
     * Search assets and list them
     * 
     * @return void
     */
    public function searchboxAction()
    {
        Fisma_Zend_Acl::requirePrivilegeForClass('read', 'Asset');
        
        $params = $this->parseCriteria();
        $systems = $this->_me->getOrganizationsByPrivilege('asset', 'read');
        $systemList[0] = "--select--";
        foreach ($systems as $system) {
            $systemList[$system['id']] = $system['nickname'].'-'.$system['name'];
        }
        $this->view->systemList = $systemList;
        $this->view->assign('criteria', $params);
        $this->render('searchbox');
    }
    
    /**
     * Searching the asset and list them.
     * 
     * It is the ajax version of searchbox action
     * 
     * @return void
     * @todo merge the two actions into one
     */
    public function searchAction()
    {
        Fisma_Zend_Acl::requirePrivilegeForClass('read', 'Asset');

        $params = $this->parseCriteria();
        $q = Doctrine_Query::create()
             ->select()
             ->from('Asset a')
             ->leftJoin('a.Product p')
             ->orderBy('a.name ASC');
        if (!empty($params['system_id'])) {
            $q->andWhere('a.orgSystemId = ?', $params['system_id']);
        }
        if (!empty($params['name'])) {
            $q->andWhere('a.name LIKE ?', $params['name'] . '%');
        }
        if (!empty($params['product'])) {
            $q->andWhere('p.name LIKE ?', $params['product'] . '%');
        }
        if (!empty($params['ip'])) {
            $q->andWhere('a.addressIp LIKE ?', $params['ip'] . '%');
        }
        if (!empty($params['port'])) {
            $q->andWhere('a.addressport LIKE ?', $params['port'] . '%');
        }
        if (!empty($params['vendor'])) {
            $q->andWhere('p.vendor LIKE ?', $params['vendor'] . '%');
        }
        if (!empty($params['version'])) {
            $q->andWhere('p.version LIKE ?', $params['version'] . '%');
        }
        // get the assets whitch are belongs to current user's systems
        $orgSystems = $this->_me->getOrganizationsByPrivilege('asset', 'read')->toArray();
        $orgSystemIds = array();
        foreach ($orgSystems as $orgSystem) {
            $orgSystemIds[] = $orgSystem['id'];
        }
        $q->andWhereIn('a.orgSystemId', $orgSystemIds);
        
        if ($this->_request->getParam('format') == null) {
            $q->limit($this->_paging['count'])
            ->offset($this->_paging['startIndex']);
            $totalRecords = $q->count();
        }
        $assets = $q->execute();
        $assetArray = array();
        $i = 0;
        foreach ($assets as $asset) {
            $assetArray[$i] = $asset->toArray();

            if ($asset->Organization) {
                foreach ($asset->Organization as $k => $v) {
                    if ($v instanceof Doctrine_Null) {
                        $v = '';
                    }
                    $assetArray[$i]['orgsys_'.$k] = $v;
                }
            }

            if ($asset->Product) {
                foreach ($asset->Product as $k => $v) {
                    if ($v instanceof Doctrine_Null) {
                        $v = '';
                    }
                    $assetArray[$i]['pro_'.$k] = $v;
                }
            }
            $i++;
        }
        if ($this->_request->getParam('format') == null) {
            $tableData = array('table' => array(
                'recordsReturned' => count($assetArray),
                'totalRecords' => $totalRecords,
                'startIndex' => $this->_paging['startIndex'],
                'pageSize' => $this->_paging['count'],
                'records' => $assetArray
            ));
            $this->_helper->json($tableData);
        } else {
            $this->view->assetColumns = $this->_assetColumns;
            $this->view->assetList = $assetArray;
        }
    }

    /**
     * View detail information of the subject model
     * 
     * @return void
     * @throws Fisma_Zend_Exception if the asset id is invalid
     */
    public function viewAction()
    {
        // supply searching support for create finding page
        if ($this->_request->getParam('format') == 'ajax') {
            $this->_helper->layout->setLayout('ajax');
            $id = $this->_request->getParam('id');
            $asset = new Asset();
            $asset = $asset->getTable('Asset')->find($id);
            if (!$asset) {
                throw new Fisma_Zend_Exception("Invalid asset ID");
            }
            $assetInfo = $asset->toArray();
            $assetInfo['systemName'] = $asset->Organization->name;
            $assetInfo['productName'] = $asset->Product->name;
            $assetInfo['vendor'] = $asset->Product->vendor;
            $assetInfo['version'] = $asset->Product->version;
            $this->view->asset = $assetInfo;
            $this->render('detail');
        } else {
            parent::viewAction();
        }
    }
    
    /**
     * Delete an asset
     * 
     * @return void
     */
    public function deleteAction()
    {
        $id = $this->_request->getParam('id');
        $asset = Doctrine::getTable($this->_modelName)->find($id);
        Fisma_Zend_Acl::requirePrivilegeForObject('delete', $asset);
        
        if (!$asset) {
            $msg   = "Invalid {$this->_modelName} ID";
            $type = 'warning';
        } else {
            try {
                if (count($asset->Findings)) {
                    $msg   = $msg = 'This asset cannot be deleted because it has findings against it';
                    $type = 'warning';
                } else {
                    Doctrine_Manager::connection()->beginTransaction();
                    $asset->delete();
                    Doctrine_Manager::connection()->commit();
                    $msg   = "Asset deleted successfully";
                    $type = 'notice';
                }
            } catch (Doctrine_Exception $e) {
                Doctrine_Manager::connection()->rollback();
                if (Fisma::debug()) {
                    $msg .= $e->getMessage();
                }
                $type = 'warning';
            } 
        }
        $this->view->priorityMessenger($msg, $type);
        $this->_forward('list');
    }
    
    /**
     * Delete assets
     * 
     * @return void
     */
    public function multideleteAction()
    {
        $req = $this->getRequest();
        $post = $req->getPost();
        $errno = 0;
        if (!empty($post['aid'])) {
            $aids = $post['aid'];
            foreach ($aids as $id) {
                $assetIds[] = $id;
                $asset = Doctrine::getTable('Asset')->find($id);
                Fisma_Zend_Acl::requirePrivilegeForObject('delete', $asset);
                if (!$asset) {
                    $errno++;
                } else {
                    if (count($asset->Findings)) {
                        $errno++;
                    } else {
                        $asset->delete();
                    }
                }
            }
        } else {
            $errno = -1;
        }
        if ($errno < 0) {
            $msg = "You did not select any assets to delete";
            $this->view->priorityMessenger($msg, 'warning');
        } else if ($errno > 0) {
            $msg = "Failed to delete the asset[s]";
            $this->view->priorityMessenger($msg, 'warning');
        } else {
            $msg = "Asset[s] deleted successfully";
            $this->view->priorityMessenger($msg, 'notice');
        }
        $this->_forward('list');
    }

    public function importAction()
    {
        Fisma_Zend_Acl::requirePrivilegeForClass('create', 'Asset');

        $uploadForm = $this->getForm('asset_upload');

        // Configure the file select
        $uploadForm->setAttrib('enctype', 'multipart/form-data');
        $uploadForm->selectFile->setDestination(Fisma::getPath('data') . '/uploads/scanreports');

        $this->view->assign('uploadForm', $uploadForm);

        // Handle the file upload
        if ($postValues = $this->_request->getPost()) {
            $msgs = array();
            $err = FALSE;
            $filesReceived = ($uploadForm->selectFile->receive()) ? TRUE: FALSE;

            if (!$uploadForm->isValid($postValues)) {
                $msgs[] = array('warning' => Fisma_Zend_Form_Manager::getErrors($uploadForm));
                $err = TRUE;
            } elseif (!$filesReceived) {
                $msgs[] = array('warning' => "File not received.");
                $err = TRUE;
            } else {
                $values = $uploadForm->getValues();
                $filePath = $uploadForm->selectFile->getTransferAdapter()->getFileName('selectFile');

                // get original file name
                $originalName = pathinfo(basename($filePath), PATHINFO_FILENAME);
                // get current time and set to a format like '_2009-05-04_11_22_02'
                $dateTime = date('_Y-m-d_H_i_s', time());
                // define new file name
                $newName = str_replace($originalName, $originalName . $dateTime, basename($filePath));
                rename($filePath, $filePath = dirname($filePath) . '/' . $newName);

                $values['filePath'] = $filePath;

                $upload = new Upload();
                $upload->userId = $this->_me->id;
                $upload->fileName = basename($filePath);
                $upload->save();
                    
                $import = Fisma_Import_Factory::create('asset', $values);
                $success = $import->parse();

                if (!$success) {
                    foreach ($import->getErrors() as $error)
                        $msgs[] = array('warning' => $error);

                    $err = TRUE;
                } else {
                    $numCreated = $import->getNumImported();
                    $numSuppressed = $import->getNumSuppressed();
                    $msgs[] = array('notice' => "{$numCreated} asset(s) were imported successfully.");
                    $msgs[] = array('notice' => "{$numSuppressed} asset(s) were not imported.");
                }
            }

            if ($err) {
                if (!empty($upload)) {
                    unlink($filePath);
                    $upload->delete();
                }

                if (!$msgs) 
                    $msgs[] = array('notice' => 'An unrecoverable error has occured.');
            }

            $this->view->priorityMessenger($msgs);
        }
    }
}
