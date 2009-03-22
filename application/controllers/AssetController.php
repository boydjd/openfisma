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
class AssetController extends PoamBaseController
{
    protected $_asset = null;

    /**
     * init() - Initialize internal members.
     */
    function init()
    {
        parent::init();
        $this->_asset = new Asset();
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
        $swCtx->addActionContext('searchbox', array(
            'pdf',
            'xls'
        ))->initContext();
    }
    /**
     *  Searching the asset and list them.
     *
     *  it is the ajax version of searchbox action
     *  @todo merge the two actions into one
     */
    public function searchAction()
    {
        $this->_acl->requirePrivilege('asset', 'read');
        
        $req = $this->getRequest();
        $systemId = $req->getParam('sid');
        $assetName = $req->getParam('name');
        $ip = $req->getParam('ip');
        $port = $req->getParam('port');
        $qry = $this->_asset->select()->from($this->_asset, array(
            'id' => 'id',
            'name' => 'name'
        ))->order('name ASC');
        if (!empty($systemId) && $systemId > 0) {
            $qry->where('system_id = ?', $systemId);
        }
        if (!empty($assetName)) {
            $qry->where('name=?', $assetName);
        }
        if (!empty($ip)) {
            $qry->where('address_ip = ?', $ip);
        }
        if (!empty($port)) {
            $qry->where('address_port = ?', $port);
        }
        
        $user = new User();
        $qry->where('system_id IN (?)', $user->getMySystems($this->_me->id));
        
        $this->view->assets = $this->_asset->fetchAll($qry)->toArray();
        $this->_helper->layout->setLayout('ajax');
        $this->render('list');
    }
    /**
     *  Create an asset
     */
    public function createAction()
    {
        $this->_acl->requirePrivilege('asset', 'create');
        
        $systems = new System();
        $user = new User();
        $product = new Product();
        $systems = $user->getMySystems($this->_me->id);
        $sysIdSet = implode(',', $systems);
        $db = Zend_Registry::get('db');
        $qry = $db->select();
        $systemList = $this->_systemList;
        $systemList['select'] = "--select--";
        $qry->reset();
        $networkList = $this->_networkList;
        $networkList['select'] = "--select--";
        $qry->reset();
        $req = $this->getRequest();
        $assetName = $req->getParam('assetname', '');
        $systemId = $req->getParam('system_list', '');
        $networkId = $req->getParam('network_list', '');
        $assetIp = $req->getParam('ip', '');
        $assetPort = $req->getParam('port', '');
        $prodId = $req->getParam('prod_id', '');
        $assetSource = "MANUAL";
        $createTime = date("Y_m_d H:m:s");
        if (!empty($assetName)) {
            $assetRow = array(
                'prod_id' => $prodId,
                'name' => $assetName,
                'create_ts' => $createTime,
                'source' => $assetSource,
                'system_id' => $systemId,
                'network_id' => $networkId,
                'address_ip' => $assetIp,
                'address_port' => $assetPort
            );
            $assetId = $this->_asset->insert($assetRow);

            $this->_notification->add(Notification::ASSET_CREATED,
                $this->_me->account, array($assetId));

            $this->message("Asset created successfully", self::M_NOTICE);
        }
        $this->view->system_list = $systemList;
        $this->view->network_list = $networkList;
        $this->_helper->actionStack('header', 'Panel');
        $this->render();
        $this->_forward('search', 'product');
    }
    /**
     * View detail information of an asset
     */
    public function detailAction()
    {
        $this->_acl->requirePrivilege('asset', 'read');
        
        $req = $this->getRequest();
        $id = $req->getParam('id');
        if (!empty($id)) {
            $qry = $this->_asset->select()->setIntegrityCheck(false)
                ->from(array(
                'a' => 'assets'
            ), array(
                'ip' => 'address_ip'
            ))->joinleft(array(
                's' => 'systems'
            ), 'a.system_id=s.id', array(
                'sname' => 's.name'
            ))->joinleft(array(
                'p' => 'products'
            ), 'p.id = a.prod_id', array(
                'pname' => 'p.name',
                'pvendor' => 'p.vendor',
                'pversion' => 'p.version'
            ));
            $qry->where("a.id = $id");
            $result = $this->_asset->fetchRow($qry);
            if (!$result) {
                $result = NULL;
            } else {
                $result = $result->toArray();
            }
            $this->view->asset = $result;
        }
        $this->_helper->layout->setLayout('ajax');
        $this->render('detail');
    }
    /**
     * Search assets and list them
     */
    public function searchboxAction()
    {
        $this->_acl->requirePrivilege('asset', 'read');
        $req = $this->getRequest();
        $params['system_id'] = $req->get('system_id');
        $params['product'] = $req->get('product');
        $params['vendor'] = $req->get('vendor');
        $params['version'] = $req->get('version');
        $params['ip'] = $req->get('ip');
        $params['port'] = $req->get('port');
        $params['p'] = $req->get('p');
        $this->view->assign('system_list', $this->_systemList);
        $this->view->assign('criteria', $params);
        $isExport = $req->getParam('format');
        if ('search' == $req->getParam('s') || isset($isExport)) {
            $this->_pagingBasePath = $req->getBaseUrl() . '/panel/asset/sub/searchbox/s/search';
            $this->_paging['currentPage'] = $req->getParam('p', 1);
            $this->makeUrl($params);

            $db = $this->_poam->getAdapter();
            $query = $db->select()->from(array(
                'a' => 'assets'
            ), array(
                'asset_name' => 'a.name',
                'address_ip' => 'a.address_ip',
                'address_port' => 'a.address_port',
                'aid' => 'a.id'
            ))->joinleft(array(
                's' => 'systems'
            ), 'a.system_id = s.id', array(
                'system_name' => 's.name'
            ))->joinleft(array(
                'p' => 'products'
            ), 'a.prod_id = p.id', array(
                'prod_name' => 'p.name',
                'prod_vendor' => 'p.vendor',
                'prod_version' => 'p.version'
            ));
            if (!empty($params['system_id'])) {
                $query->where('s.id = ?', $params['system_id']);
            }
            if (!empty($params['product'])) {
                $query->where("p.name like '%$params[product]%'");
            }
            if (!empty($params['vendor'])) {
                $query->where("p.vendor like '%$params[vendor]%'");
            }
            if (!empty($params['version'])) {
                $query->where("p.version like '%$params[version]%'");
            }
            if (!empty($params['ip'])) {
                $query->where('a.address_ip = ?', $params['ip']);
            }
            if (!empty($params['port'])) {
                $query->where('a.address_port = ?', $params['port']);
            }
            
            $user = new User();
            $query->where('a.system_id IN (?)', $user->getMySystems($this->_me->id));
            
            $res = $db->fetchCol($query);
            $total = count($res);
            if (!isset($isExport)) {
                $query->limitPage($this->_paging['currentPage'],
                    $this->_paging['perPage']);
            }
            $assetList = $db->fetchAll($query);
            $this->_paging['totalItems'] = $total;
            $this->_paging['fileName'] = "{$this->_pagingBasePath}/p/%d";
            $pager = & Pager::factory($this->_paging);
            $this->view->assign('asset_list', $assetList);
            $this->view->assign('links', $pager->getLinks());
        }
    }
    /** 
     *  View an asset in detail
     */
    public function viewAction()
    {
        $this->_acl->requirePrivilege('asset', 'read');
        
        $req = $this->getRequest();
        $id = $req->getParam('id');
        assert($id);
        $db = $this->_asset->getAdapter();
        $query = $db->select()
                    ->from(array('a' => 'assets'),
                           array('name' => 'a.name',
                                 'source' => 'a.source',
                                 'created_date' => 'a.create_ts',
                                 'ip' => 'a.address_ip',
                                 'system_id' => 'a.system_id',
                                 'network_id' => 'a.network_id',
                                 'port' => 'a.address_port'))
                    ->joinLeft(array('p' => 'products'),
                               'a.prod_id = p.id',
                               array('prod_name' => 'p.name',
                                     'prod_vendor' => 'p.vendor',
                                     'prod_version' => 'p.version'))
                    ->joinLeft(array('n' => 'networks'),
                               'a.network_id = n.id',
                               array('net_nickname' => 'n.nickname',
                                     'net_name' => 'n.name'))
                    ->joinLeft(array('s' => 'systems'),
                               'a.system_id = s.id',
                               array('sys_nickname' => 's.nickname',
                                     'sys_name' => 's.name'))
                    ->where('a.id = ?', $id);
        $asset = $db->fetchRow($query);
        $this->view->assign('asset', $asset);
        $this->view->assign('id', $id);
        if ('edit' == $req->getParam('s')) {
            $this->view->assign('system_list', $this->_systemList);
            $this->view->assign('network_list', $this->_networkList);
            $this->render('edit');
            $this->_helper->actionStack('search', 'Product');
        }
    }


    /**
     *  update information of an asset
     */
    public function updateAction()
    {
        $this->_acl->requirePrivilege('asset', 'update');
        
        $req = $this->getRequest();
        $id = $req->getParam('id');
        assert($id);
        $post = $req->getPost();
        foreach ($post as $k => $v) {
            if (in_array($k, array(
                'prod_id',
                'name',
                'system_id',
                'network_id',
                'address_ip',
                'address_port'
            ))) {
                $data[$k] = $v;
            }
        }
        $res = $this->_asset->update($data, 'id = ' . $id);
        if ($res) {
            $this->_notification->add(Notification::ASSET_MODIFIED,
                $this->_me->account, $id);

            $msg = 'Asset edited successfully';
            $this->message($msg, self::M_NOTICE);
        } else {
            $msg = 'Failed to edit the asset';
            $this->message($msg, self::M_WARNING);
        }
        $this->_helper->_actionStack('header', 'panel');
        $this->_forward('view', null, null, array(
            'id' => $id,
            's' => 'edit'
        ));
    }
    /**
     *  Delete an asset
     */
    public function deleteAction()
    {
        $this->_acl->requirePrivilege('asset', 'delete');
        
        $req = $this->getRequest();
        $post = $req->getPost();
        $errno = 0;
        if (!empty($post['aid'])) {
            $aids = $post['aid'];
            foreach ($aids as $id) {
                $assetIds[] = $id;
                $res = $this->_asset->delete("id = $id");
                if (!$res) {
                    $errno++;
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
            $this->_notification->add(Notification::ASSET_DELETED,
               $this->_me->account, $assetIds);

            $msg = "Asset[s] deleted successfully";
            $this->message($msg, self::M_NOTICE);
        }
        $this->_forward('asset', 'Panel', null, array(
            'sub' => 'searchbox',
            's' => 'search'
        ));
    }
}
