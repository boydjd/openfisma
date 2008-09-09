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
 */

require_once CONTROLLERS . DS . 'PoamBaseController.php';
require_once MODELS . DS . 'asset.php';
require_once MODELS . DS . 'system.php';
require_once MODELS . DS . 'source.php';
require_once MODELS . DS . 'product.php';
require_once 'Pager.php';

/**
 * The asset controller deals with creating, updating, and managing assets
 * on the system.
 *
 * @package   Controller
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/mw/index.php?title=License
 */
class AssetController extends PoamBaseController
{
    protected $_asset = null;
    function init()
    {
        parent::init();
        $this->_asset = new Asset();
        $swCtx = $this->_helper->contextSwitch();
        if (!$swCtx->hasContext('pdf')) {
            $swCtx->addContext('pdf', array(
                'suffix' => 'pdf',
                'headers' => array(
                    'Content-Disposition' => 'attachement;filename="export.pdf"',
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
        $req = $this->getRequest();
        $system_id = $req->getParam('sid');
        $asset_name = $req->getParam('name');
        $ip = $req->getParam('ip');
        $port = $req->getParam('port');
        $qry = $this->_asset->select()->from($this->_asset, array(
            'id' => 'id',
            'name' => 'name'
        ))->order('name ASC');
        if (!empty($system_id) && $system_id > 0) {
            $qry->where('system_id = ?', $system_id);
        }
        if (!empty($asset_name)) {
            $qry->where('name=?', $asset_name);
        }
        if (!empty($ip)) {
            $qry->where('address_ip = ?', $ip);
        }
        if (!empty($port)) {
            $qry->where('address_port = ?', $port);
        }
        $this->view->assets = $this->_asset->fetchAll($qry)->toArray();
        $this->_helper->layout->setLayout('ajax');
        $this->render('list');
    }
    /**
     *  Create an asset
     */
    public function createAction()
    {
        $systems = new System();
        $user = new User();
        $product = new Product();
        $systems = $user->getMySystems($this->me->id);
        $sys_id_set = implode(',', $systems);
        $db = Zend_Registry::get('db');
        $qry = $db->select();
        $system_list = $this->_system_list;
        $system_list['select'] = "--select--";
        $qry->reset();
        $network_list = $this->_network_list;
        $network_list['select'] = "--select--";
        $qry->reset();
        $req = $this->getRequest();
        $asset_name = $req->getParam('assetname', '');
        $system_id = $req->getParam('system_list', '');
        $network_id = $req->getParam('network_list', '');
        $asset_ip = $req->getParam('ip', '');
        $asset_port = $req->getParam('port', '');
        $prod_id = $req->getParam('prod_id', '');
        $asset_source = "MANUAL";
        $create_time = date("Y_m_d H:m:s");
        if (!empty($asset_name)) {
            $asset_row = array(
                'prod_id' => $prod_id,
                'name' => $asset_name,
                'create_ts' => $create_time,
                'source' => $asset_source,
                'system_id' => $system_id,
                'network_id' => $network_id,
                'address_ip' => $asset_ip,
                'address_port' => $asset_port
            );
            $assetId = $this->_asset->insert($asset_row);

            $this->_notification->add(Notification::ASSET_CREATED,
                $this->me->account, array($assetId));

            $this->message("Asset created successfully", self::M_NOTICE);
        }
        $this->view->system_list = $system_list;
        $this->view->network_list = $network_list;
        $this->_helper->actionStack('header', 'Panel');
        $this->render();
        $this->_forward('search', 'product');
    }
    /**
     * View detail information of an asset
     */
    public function detailAction()
    {
        $req = $this->getRequest();
        $id = $req->getParam('id');
        if (!empty($id)) {
            $qry = $this->_asset->select()->setIntegrityCheck(false)->from(array(
                'a' => 'assets'
            ) , array(
                'ip' => 'address_ip'
            ))->joinleft(array(
                's' => 'systems'
            ) , 'a.system_id=s.id', array(
                'sname' => 's.name'
            ))->joinleft(array(
                'p' => 'products'
            ) , 'p.id = a.prod_id', array(
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
        $req = $this->getRequest();
        $criteria['system_id'] = $req->get('system_id');
        $criteria['product'] = $req->get('product');
        $criteria['vendor'] = $req->get('vendor');
        $criteria['version'] = $req->get('version');
        $criteria['ip'] = $req->get('ip');
        $criteria['port'] = $req->get('port');
        $criteria['p'] = $req->get('p');
        $this->view->assign('system_list', $this->_system_list);
        $this->view->assign('criteria', $criteria);
        $is_export = $req->getParam('format');
        if ('search' == $req->getParam('s') || isset($is_export)) {
            if (!empty($criteria)) {
                extract($criteria);
            }
            $this->_paging_base_path = $req->getBaseUrl() . '/panel/asset/sub/searchbox/s/search';
            $this->_paging['currentPage'] = $req->getParam('p', 1);
            foreach ($criteria as $key => $value) {
                if (!empty($value)) {
                    $this->_paging_base_path.= '/' . $key . '/' . $value . '';
                }
            }
            $db = $this->_poam->getAdapter();
            $query = $db->select()->from(array(
                'a' => 'assets'
            ) , array(
                'asset_name' => 'a.name',
                'address_ip' => 'a.address_ip',
                'address_port' => 'a.address_port',
                'aid' => 'a.id'
            ))->joinleft(array(
                's' => 'systems'
            ) , 'a.system_id = s.id', array(
                'system_name' => 's.name'
            ))->joinleft(array(
                'p' => 'products'
            ) , 'a.prod_id = p.id', array(
                'prod_name' => 'p.name',
                'prod_vendor' => 'p.vendor',
                'prod_version' => 'p.version'
            ));
            if (!empty($system_id)) {
                $query->where('s.id = ?', $system_id);
            }
            if (!empty($product)) {
                $query->where('p.name = ?', $product);
            }
            if (!empty($vendor)) {
                $query->where('p.vendor = ?', $vendor);
            }
            if (!empty($version)) {
                $query->where('p.version = ?', $version);
            }
            if (!empty($ip)) {
                $query->where('a.address_ip = ?', $ip);
            }
            if (!empty($port)) {
                $query->where('a.address_port = ?', $port);
            }
            $res = $db->fetchCol($query);
            $total = count($res);
            if (!isset($is_export)) {
                $query->limitPage($this->_paging['currentPage'], $this->_paging['perPage']);
            }
            $asset_list = $db->fetchAll($query);
            $this->_paging['totalItems'] = $total;
            $this->_paging['fileName'] = "{$this->_paging_base_path}/p/%d";
            $pager = & Pager::factory($this->_paging);
            $this->view->assign('asset_list', $asset_list);
            $this->view->assign('links', $pager->getLinks());
        }
        $this->render();
    }
    /** 
     *  View an asset in detail
     */
    public function viewAction()
    {
        $req = $this->getRequest();
        $id = $req->getParam('id');
        assert($id);
        $db = $this->_asset->getAdapter();
        $query = $db->select()->from(array(
            'a' => 'assets'
        ) , array(
            'name' => 'a.name',
            'source' => 'a.source',
            'created_date' => 'a.create_ts',
            'ip' => 'a.address_ip',
            'system_id' => 'a.system_id',
            'network_id' => 'a.network_id',
            'port' => 'a.address_port'
        ))->joinLeft(array(
            'p' => 'products'
        ) , 'a.prod_id = p.id', array(
            'prod_name' => 'p.name',
            'prod_vendor' => 'p.vendor',
            'prod_version' => 'p.version'
        ))->joinLeft(array(
            'n' => 'networks'
        ) , 'a.network_id = n.id', array(
            'net_nickname' => 'n.nickname',
            'net_name' => 'n.name'
        ))->where('a.id = ?', $id);
        $asset = $db->fetchRow($query);
        $this->view->assign('asset', $asset);
        $this->view->assign('id', $id);
        if ('edit' == $req->getParam('s')) {
            $this->view->assign('system_list', $this->_system_list);
            $this->view->assign('network_list', $this->_network_list);
            $this->_helper->actionStack('header', 'Panel');
            $this->render('edit');
            $this->_forward('search', 'Product');
        } else {
            $this->render();
        }
    }
    /**
     *  update information of an asset
     */
    public function updateAction()
    {
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
                $this->me->account, $id);

            $msg = 'Asset edited successfully';
            $this->message($msg, self::M_NOTICE);
        } else {
            $msg = 'Failed to edit the asset';
            $this->message($msg, self::M_WARNING);
        }
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
        $req = $this->getRequest();
        $post = $req->getPost();
        $errno = 0;
        foreach ($post as $k => $id) {
            if ('aid_' == substr($k, 0, 4)) {
                $assetIds[] = $id;
                $res = $this->_asset->delete("id = $id");
                if (!$res) {
                    $errno++;
                }
            }
        }
        if ($errno > 0) {
            $msg = $errno . "Failed to delete the asset";
            $this->message($msg, self::M_WARNING);
        } else {
            $this->_notification->add(Notification::ASSET_DELETED,
               $this->me->account, $assetIds);

            $msg = "Asset deleted successfully";
            $this->message($msg, self::M_NOTICE);
        }
        $this->_forward('asset', 'Panel', null, array(
            'sub' => 'searchbox',
            's' => 'search'
        ));
    }
}
