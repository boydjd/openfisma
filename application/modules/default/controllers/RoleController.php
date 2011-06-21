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
 * The role controller handles CRUD for role objects.
 *
 * @author     Ryan Yang <ryan@users.sourceforge.net>
 * @copyright  (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Controller
 */
class RoleController extends Fisma_Zend_Controller_Action_Object
{
    /**
     * The main name of the model.
     * 
     * This model is the main subject which the controller operates on.
     * 
     * @var string
     */
    protected $_modelName = 'Role';

    /**
     * Override the parent class to add a link for editing privileges
     * 
     * @param Fisma_Doctrine_Record $subject
     */
    public function getViewLinks(Fisma_Doctrine_Record $subject)
    {
        $links = array();
        
        if ($this->_acl->hasPrivilegeForObject('read', $subject)) {
            $links['Privileges'] = "{$this->_moduleName}/{$this->_controllerName}"
                                 . "/right/id/{$subject->id}";
            
            $links['Edit Privilege Matrix'] = '/role/edit-matrix';
        }
        
        $links = array_merge($links, parent::getViewLinks($subject));

        return $links;
    }
    
    /**
     * Assign privileges to a single role
     * 
     * @return void
     */
    public function rightAction()
    {   
        $req = $this->getRequest();
        $do = $req->getParam('do');
        $roleId = $req->getParam('id');
        $screenName = $req->getParam('screen_name');
        
        $role = Doctrine::getTable('Role')->find($roleId);
        $this->_acl->requirePrivilegeForObject('assignPrivileges', $role);
                
        $existFunctions = $role->Privileges->toArray();
        if ('availableFunctions' == $do) {
            $existFunctionIds = explode(',', $req->getParam('existFunctions'));
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
        } elseif ('existFunctions' == $do) {
            $this->_helper->layout->setLayout('ajax');
            $this->view->assign('functions', $existFunctions);
            $this->render('funcoptions');
        } elseif ('update' == $do) {
            $functionIds = $req->getParam('existFunctions');
            $errno = 0;
            if (!Doctrine::getTable('RolePrivilege')->findByRoleId($roleId)->delete()) {
                $errno++;
            }

            if ($functionIds) {
                foreach ($functionIds as $fid) {
                    $rolePrivilege = new RolePrivilege();
                    $rolePrivilege->roleId = $roleId;
                    $rolePrivilege->privilegeId = $fid;
                    if (!$rolePrivilege->trySave()) {
                        $errno++;
                    }
                }
            }

            if ($errno > 0) {
                $msg = "Set right for role failed.";
                $model = 'warning';
            } else {
                $msg = "Successfully set right for role.";
                $model = 'notice';
            }
            $this->view->priorityMessenger($msg, $model);
            $this->_redirect('role/right/id/' . $roleId);
        } else {
            $role = Doctrine::getTable('Role')->find($roleId)->toArray();
            $q = Doctrine_Query::create()
                          ->from('Privilege')
                          ->groupBy('resource');
            $screenList = $q->execute()->toArray();
            $this->view->assign('role', $role);
            $this->view->assign('screenList', $screenList);
            $this->view->assign('existFunctions', $existFunctions);
            $this->render('right');
        }
    }

     public function editMatrixAction()
     {
        // If this is caled from a form-post, save the changes
        $this->_saveMatrix();

        // Add button to save changes (submit form)
        $this->view->toolbarButtons = array();
        $this->view->toolbarButtons[] = new Fisma_Yui_Form_Button_Submit(
            'SaveChanges',
            'SaveChanges',
            array(
                'label' => 'Save Changes'
            )
        );

        // YUI data-table to print
        $dataTable = new Fisma_Yui_DataTable_Local();
        $rowStructure = array();
        
        // Add event handeler pointer (on checkboxClickEvent, call dataTableCheckboxClick
        $dataTable->eventListeners['checkboxClickEvent'] = 'dataTableCheckboxClick';
        
        // The first columns will the be privilege-description
        $dataTable->addColumn(
            new Fisma_Yui_DataTable_Column(
                'Privilege',
                true,
                'YAHOO.widget.DataTable.formatText',
                null,
                'privDesc'
            )
        );
        $rowStructure['privDesc'] = '';
        
        // The second columns will be the privilege-id (hidden)
        $dataTable->addColumn(
            new Fisma_Yui_DataTable_Column(
                'Privilege ID',
                true,
                'YAHOO.widget.DataTable.formatText',
                null,
                'privId',
                true
            )
        );
        $rowStructure['privId'] = '';

        // Get a list of all roles
        $q = Doctrine_Query::create()
            ->select('r.nickname')
            ->from('Role r')
            ->orderBy('r.nickname')
            ->setHydrationMode(Doctrine::HYDRATE_ARRAY);
        $roles = $q->execute();

        // Add a column for each role
        foreach ($roles as $role) {
        
            // Add column
            $dataTable->addColumn(
                new Fisma_Yui_DataTable_Column(
                    $role['nickname'],
                    true,
                    'YAHOO.widget.DataTable.formatCheckbox',
                    'dataTableCheckboxClick',
                    $role['nickname']
                )
            );
            
            // Add column to row-structure variable
            $rowStructure[$role['nickname']] = '';
        }

        // Get a list of what role each privilege is associated with
        $q = Doctrine_Query::create()
            ->select('r.nickname, p.description, p.action')
            ->from('Privilege p')
            ->leftJoin('p.Roles r')
            ->orderBy('p.description')
            ->setHydrationMode(Doctrine::HYDRATE_ARRAY);
        $privileges = $q->execute();

        // Add a row for each privilege
        $dataTableRows = array();
        foreach ($privileges as $privilege) {

            $newRow = $rowStructure;
            $newRow['privDesc'] = $privilege['description'];
            $newRow['privId'] = $privilege['id'];

            // Update (set true) any cell of this privilege row, that has this role
            foreach ($privilege['Roles'] as $role) {
                $newRow[$role['nickname']] = true;
            }

            // Add row to data-table
            $dataTableRows[] = array_values($newRow);
        }
        
        // Set the rows into the table, and push to view
        $dataTable->setData($dataTableRows);
        $this->view->dataTable = $dataTable;
     }
    
    private function _saveMatrix()
    {
        // Check if there is changes to apply
        $rolePrivChanges = $this->getRequest()->getParam('rolePrivChanges');
        if (is_null($rolePrivChanges)) {
            return;
        }
        
        // json string to array
        $rolePrivChanges = json_decode($rolePrivChanges, true);
        
        // Priority messanger
        $msg = array();
        
        // Apply each requested change
        foreach ($rolePrivChanges as $change) {
        
            $privId = $change['privilegeId'];
            $roleId = Doctrine::getTable('Role')->findOneByNickname($change['roleName'])->id;
            $privDesc = Doctrine::getTable('Privilege')->findOneById($privId)->description;
            
            // Remove this privilege for this role
            $q = Doctrine_Query::create()
                ->delete('RolePrivilege rp')
                ->where('rp.roleId = ' . $roleId)
                ->andWhere('rp.privilegeId = ' . $privId);
            $q->execute();

            // Add this privilege for this role if that was requested
            if ((int) $change['newValue'] === 1) {
                $newRolePrivilege = new RolePrivilege;
                $newRolePrivilege->roleId = $roleId;
                $newRolePrivilege->privilegeId = $privId;
                $newRolePrivilege->save();
            }
            
            // Add to message stack
            if ((int) $change['newValue'] === 1) {
                $verb = 'Added ';
                $adj = 'to';
            } else {
                $verb = 'Removed ';
                $adj = 'from';
            }
            
            $msg[] = $verb . " the '" . $privDesc . "' ability " . $adj . " the " . $change['roleName'] . ' role.';
        }
        
        // Send priority messenger
        $msg = implode("<br/>", $msg);
        $this->view->priorityMessenger($msg, 'notice');
    }
    
    protected function _isDeletable()
    {
        return false;
    }
}
