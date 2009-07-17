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
 * @author    Jim Chen <xhorse@users.sourceforge.net>
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/mw/index.php?title=License
 * @version   $Id$
 * @package   Controller
 */

/**
 * @see Zend_View_Helper_Abstract
 */

/**
 * The asset controller deals with creating, updating, and managing assets
 * on the system.
 *
 * @package   Controller
 * @see application/controller/PoamBaseController.php
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/mw/index.php?title=License
 */
class AssetController extends BaseController
{
    /**
     * The main name of the model.
     * 
     * This model is the main subject which the controller operates on.
     */
    protected $_modelName = 'Asset';

    /**
     * asset columns which need to displayed on the list page, PDF and Excel
     */
    protected $_assetColumns = array('name'  => 'Asset Name',
                                     'orgsys_name' => 'System',
                                     'addressIp'  => 'IP Address',
                                     'addressPort'=> 'Port',
                                     'pro_name'   => 'Product Name',
                                     'pro_vendor' => 'Vendor',
                                     'pro_version'=> 'Version');

    /**
     * init() - Initialize internal members.
     */
    function init()
    {
        parent::init();
        $swCtx = $this->_helper->contextSwitch();
        if (!$swCtx->hasContext('pdf')) {
            $swCtx->addContext('pdf', array(
                'suffix' => 'pdf',
                'headers' => array(
                    'Content-Disposition' =>'attachement;filename="export.pdf"',
                    'Content-Type' => 'application/pdf'
                )
            ));
        }
        if (!$swCtx->hasContext('xls')) {
            $swCtx->addContext('xls', array(
                'suffix' => 'xls'
            ));
        }
    }

    /**
     * preDispatch() - invoked before each Actions
     */
    public function preDispatch()
    {
        parent::preDispatch();
        $this->req = $this->getRequest();
        $swCtx = $this->_helper->contextSwitch();
        $swCtx->addActionContext('search', array(
            'pdf',
            'xls'
        ))->initContext();
    }
    
    /**
     * Get the specific form of the subject model
     */
    public function getForm()
    {
        $form = Fisma_Form_Manager::loadForm($this->_modelName);
        
        $systems = $this->_me->getOrganizations();
        $selectArray = $this->view->treeToSelect($systems, 'nickname');
        $form->getElement('orgSystemId')->addMultiOptions($selectArray);
        
        $networks = Doctrine::getTable('Network')->findAll()->toArray();
        $networkList = array();
        foreach ($networks as $network) {
            $networkList[$network['id']] = $network['nickname'].'-'.$network['name'];
        }
        $form->getElement('networkId')->addMultiOptions($networkList);
        $form->getElement('productId')->setRegisterInArrayValidator(false);
        $form = Fisma_Form_Manager::prepareForm($form);
        return $form;
    }
    
    /** 
     * Hooks for manipulating the values before setting to a form
     *
     * @param Zend_Form $form
     * @param Doctrine_Record|null $subject
     * @return Zend_Form
     */
    protected function setForm($subject, $form)
    {
        $product = $subject->Product;
        $form->getElement('productId')->addMultiOptions(array($product->id => $product->id 
                                        . ' | ' . $product->name . ' | ' . $product->vendor
                                        . ' | ' . $product->version));
        $form->setDefaults($subject->toArray());
        return $form;
    }
    
    /** 
     * Hooks for manipulating and saveing the values retrieved by Forms
     *
     * @param Zend_Form $form
     * @param Doctrine_Record|null $subject
     */
    protected function saveValue($form, $subject=null)
    {
        if (is_null($subject)) {
            $subject = new $this->_modelName();
        } elseif (!$subject instanceof Doctrine_Record) {
            throw new Fisma_Exception('Invalid parameter: Expected a Doctrine_Record');
        }
        $values = $form->getValues();
        $product = Doctrine::getTable('Product')->find($values['productId']);
        $form->getElement('productId')->addMultiOptions(array($product->id => $product->id 
                                        . ' | ' . $product->name . ' | ' . $product->vendor
                                        . ' | ' . $product->version));
        $subject->merge($values);
        $subject->save();
    }
    
    /**
     * Enter description here...
     *
     */
    private function parseCriteria(){
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
     *  Searching the asset and list them.
     *
     *  it is the ajax version of searchbox action
     *  @todo merge the two actions into one
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
     *  Create an asset
     */
    public function createAction()
    {
        Fisma_Acl::requirePrivilege('asset', 'create');
        $this->_request->setParam('source', 'MANUAL');
        parent::createAction();
    }
    
    /**
     * Search assets and list them
     */
    public function searchboxAction()
    {
        Fisma_Acl::requirePrivilege('asset', 'read');
        
        $params = $this->parseCriteria();
        $systems = $this->_me->getOrganizations();
        $systemList[0] = "--select--";
        foreach ($systems as $system) {
            $systemList[$system['id']] = $system['nickname'].'-'.$system['name'];
        }
        $this->view->systemList = $systemList;
        $this->view->assign('criteria', $params);
        $this->render('searchbox');
    }
    
    /**
     *  Searching the asset and list them.
     *
     *  it is the ajax version of searchbox action
     *  @todo merge the two actions into one
     */
    public function searchAction()
    {
        Fisma_Acl::requirePrivilege('asset', 'read');

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
        $orgSystems = $this->_me->getOrganizations()->toArray();
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
            foreach ($asset->Organization as $k => $v) {
                if ($v instanceof Doctrine_Null) {
                    $v = '';
                }
                $assetArray[$i]['orgsys_'.$k] = $v;
            }
            foreach ($asset->Product as $k => $v) {
                if ($v instanceof Doctrine_Null) {
                    $v = '';
                }
                $assetArray[$i]['pro_'.$k] = $v;
            }
            $i ++;
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
            $this->view->asset_list = $assetArray;
        }
    }
    

    /**
     * View detail information of the subject model
     *
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
                throw new Fisma_Exception("Invalid asset ID");
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
     * Delete a asset
     */
    public function deleteAction()
    {
        Fisma_Acl::requirePrivilege($this->_modelName, 'delete');
        $id = $this->_request->getParam('id');
        $asset = Doctrine::getTable($this->_modelName)->find($id);
        if (!$asset) {
            $msg   = "Invalid {$this->_modelName} ID";
            $type = self::M_WARNING;
        } else {
            try {
                if (count($asset->Findings)) {
                    $msg   = $msg = 'This asset cannot be deleted because it has findings against it';
                    $type = self::M_WARNING;
                } else {
                    Doctrine_Manager::connection()->beginTransaction();
                    $asset->delete();
                    Doctrine_Manager::connection()->commit();
                    $msg   = "Asset deleted successfully";
                    $type = self::M_NOTICE;
                }
            } catch (Doctrine_Exception $e) {
                Doctrine_Manager::connection()->rollback();
                if (Fisma::debug()) {
                    $msg .= $e->getMessage();
                }
                $type = self::M_WARNING;
            } 
        }
        $this->message($msg, $type);
        $this->_forward('list');
    }
    
    /**
     *  Delete assets
     */
    public function multideleteAction()
    {
        Fisma_Acl::requirePrivilege('asset', 'delete');

        $req = $this->getRequest();
        $post = $req->getPost();
        $errno = 0;
        if (!empty($post['aid'])) {
            $aids = $post['aid'];
            foreach ($aids as $id) {
                $assetIds[] = $id;
                $res = Doctrine::getTable('Asset')->find($id);
                if (!$res) {
                    $errno++;
                } else {
                    if (count($res->Findings)) {
                        $errno++;
                    } else {
                        $res->delete();
                    }
                }
            }
        } else {
            $errno = -1;
        }
        if ($errno < 0) {
            $msg = "You did not select any assets to delete";
            $this->message($msg, self::M_WARNING);
        } else if ($errno > 0) {
            $msg = "Failed to delete the asset[s]";
            $this->message($msg, self::M_WARNING);
        } else {
            $msg = "Asset[s] deleted successfully";
            $this->message($msg, self::M_NOTICE);
        }
        $this->_forward('list');
    }
}
