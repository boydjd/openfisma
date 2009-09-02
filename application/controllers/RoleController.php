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
 * The role controller handles CRUD for role objects.
 *
 * @package   Controller
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/mw/index.php?title=License
 */
class RoleController extends BaseController
{
    protected $_modelName = 'Role';

    
    public function viewAction()
    {
        $id = $this->_request->getParam('id');
        $this->view->assign('rightLink', "/panel/Role/sub/right/id/$id");
        parent::viewAction();
    }
    
    /**
     * Delete a role
     */
    public function deleteAction()
    {
        Fisma_Acl::requirePrivilege('role', 'delete');
        
        $req = $this->getRequest();
        $id = $req->getParam('id');
        $role = Doctrine::getTable('Role')->find($id);
        if (!$role) {
            $msg   = "Invalid Role ID";
            $type = self::M_WARNING;
        } else {
            $users = $role->Users->toArray();
            if (!empty($users)) {
                $msg = 'This role cannot be deleted because it is in use by one or more users';
                $type = self::M_WARNING;
            } else {
                Doctrine::getTable('RolePrivilege')
                ->findByRoleId($id)
                ->delete();
                parent::deleteAction();
                // parent method will take care 
                // of the message and forword the page
                return;
            }
        }
        $this->message($msg, $type);
        $this->_forward('list');
    }

    /**
     * assign privileges to a single role
     */
    public function rightAction()
    {
        Fisma_Acl::requirePrivilege('role', 'assignPrivileges');
        $req = $this->getRequest();
        $do = $req->getParam('do');
        $roleId = $req->getParam('id');
        $screenName = $req->getParam('screen_name');
        
        $role = Doctrine::getTable('Role')->find($roleId);
        $existFunctions = $role->Privileges->toArray();
        if ('available_functions' == $do) {
            $existFunctionIds = explode(',', $req->getParam('exist_functions'));
            $q = Doctrine_Query::create()
                 ->from('Privilege');
            if (!empty($screenName)) {
                $q->where('resource = ?', $screenName);
            }
            $allFunctions = $q->execute()->toArray();
            $availableFunctions = array();
            foreach ($allFunctions as $v) {
                if (!in_array($v['id'], $existFunctionIds)) {
                    $availableFunctions[] = $v;
                }
            }
            $this->_helper->layout->setLayout('ajax');
            $this->view->assign('functions', $availableFunctions);
            $this->render('funcoptions');
        } elseif ('exist_functions' == $do) {
            $this->_helper->layout->setLayout('ajax');
            $this->view->assign('functions', $existFunctions);
            $this->render('funcoptions');
        } elseif ('update' == $do) {
            $functionIds = $req->getParam('exist_functions');
            $errno = 0;
            if (!Doctrine::getTable('RolePrivilege')->findByRoleId($roleId)->delete()) {
                $errno++;
            }
            foreach ($functionIds as $fid) {
                $rolePrivilege = new RolePrivilege();
                $rolePrivilege->roleId = $roleId;
                $rolePrivilege->privilegeId = $fid;
                if (!$rolePrivilege->trySave()) {
                    $errno++;
                }
            }
            if ($errno > 0) {
                $msg = "Set right for role failed.";
                $model = self::M_WARNING;
            } else {
                $msg = "Successfully set right for role.";
                $model = self::M_NOTICE;
            }
            $this->message($msg, $model);
            $this->_redirect('panel/role/sub/right/id/' . $roleId);
        } else {
            $role = Doctrine::getTable('Role')->find($roleId)->toArray();
            $q = Doctrine_Query::create()
                          ->from('Privilege')
                          ->groupBy('resource');
            $screenList = $q->execute()->toArray();
            $this->view->assign('role', $role);
            $this->view->assign('screen_list', $screenList);
            $this->view->assign('exist_functions', $existFunctions);
            $this->render('right');
        }
    }
}
