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
            $links['Edit Privilege Matrix'] = '/role/view-matrix';
        }

        $links = array_merge($links, parent::getViewLinks($subject));

        return $links;
    }

    /**
     * Displays a (checkbox-)table of privileges associated with each role
     *
     * @GETAllowed
     * @return void
     */
     public function viewMatrixAction()
     {
        $this->_acl->requirePrivilegeForClass('read', 'Role');

        // Add button to save changes (submit form)
        $this->view->toolbarButtons = array();

        $expandAll = new Fisma_Yui_Form_Button('expandAll',
                                               array('label' => 'Expand All',
                                                     'imageSrc' => '/images/expand.png',
                                                     'onClickFunction' => 'YAHOO.widget.GroupedDataTable.expandAll'));

        $collapseAll = new Fisma_Yui_Form_Button('collapseAll',
                                                 array('label' => 'Collapse All',
                                                     'imageSrc' => '/images/collapse.png',
                                                     'onClickFunction' => 'YAHOO.widget.GroupedDataTable.collapseAll'));

        $this->view->toolbarButtons[] = $expandAll;
        $this->view->toolbarButtons[] = $collapseAll;

        if ($this->_acl->hasPrivilegeForClass('update', 'Role')) {
            $this->view->toolbarButtons[] = new Fisma_Yui_Form_Button_Submit(
                'saveChanges',
                'Save Changes',
                array(
                    'label' => 'Save Changes'
                )
            );
        }

        // YUI data-table to show user
        $dataTable = new Fisma_Yui_DataTable_Local();
        $dataTable->setGroupBy('privilegeResource');

        // Each row (array) must be an array of ColumnName => CellValue
        $blankRow = array();

        // Add event handler pointer (on checkboxClickEvent, call dataTableCheckboxClick
        $dataTable->addEventListener('checkboxClickEvent', 'Fisma.Role.dataTableCheckboxClick');

        // The first column will the be privilege-description
        $dataTable->addColumn(
            new Fisma_Yui_DataTable_Column(
                'Privilege',
                false,
                null,
                null,
                'privilegeDescription'
            )
        );

        // Add this key (column-name) to the row template
        $blankRow['privilegeDescription'] = '';

        // The second column will be the privilege-id (hidden)
        $dataTable->addColumn(
            new Fisma_Yui_DataTable_Column(
                'Privilege ID',
                false,
                'YAHOO.widget.DataTable.formatText',
                null,
                'privilegeId',
                true
            )
        );

        // Add this key (column-name) to the row template
        $blankRow['privilegeId'] = '';

        // Get a list of all roles
        $rolesQuery = Doctrine_Query::create()
            ->select('r.nickname')
            ->from('Role r')
            ->orderBy('r.nickname')
            ->setHydrationMode(Doctrine::HYDRATE_ARRAY);
        $roles = $rolesQuery->execute();

        // Add a column for each role
        foreach ($roles as $role) {

            // Add column
            $dataTable->addColumn(
                new Fisma_Yui_DataTable_Column(
                    $this->view->escape($role['nickname']),
                    false,
                    'YAHOO.widget.DataTable.formatCheckbox',
                    'dataTableCheckboxClick',
                    $role['nickname']
                )
            );

            // Add this key (column-name) to the row template
            $blankRow[$role['nickname']] = '';

        }

        $dataTable->addColumn(
            new Fisma_Yui_DataTable_Column(
                'privilegeResource',
                false,
                'YAHOO.widget.DataTable.formatText',
                null,
                'privilegeResource',
                true
            )
        );

        // Get a list of what role each privilege is associated with
        $privilegeQuery = Doctrine_Query::create()
            ->select('r.nickname, p.description, p.action, p.resource')
            ->from('Privilege p')
            ->leftJoin('p.Roles r')
            ->orderBy('p.resource, p.description')
            ->setHydrationMode(Doctrine::HYDRATE_ARRAY);
        $privileges = $privilegeQuery->execute();

        // Add a row for each privilege
        $dataTableRows = array();
        foreach ($privileges as $privilege) {

            // Copy from blank row, so that all column-names exists as keys in this row array
            $newRow = $blankRow;

            $newRow['privilegeDescription'] = $privilege['description'];
            $newRow['privilegeId'] = $privilege['id'];
            $newRow['privilegeResource'] = ucfirst($privilege['resource']);

            // Update (set true) any cell of this privilege row, that has this role
            foreach ($privilege['Roles'] as $role) {
                $newRow[$role['nickname']] = true;
            }

            // Add row to data-table
            $dataTableRows[] = $newRow;
        }

        $dataTable->setData($dataTableRows);
        $this->view->dataTable = $dataTable;
     }

    /**
     * If rolePrivChanges exists (post/get), will save the role/privilege changes, Redirects to viewMatrixAction.
     *
     * rolePrivChanges is expected to be a string/json-object, when json-decoded, to be an array of
     * objects, each with a newValue, privilegeId, and roleName property.
     *
     * @return void
     */
    public function saveMatrixAction()
    {
        $this->_acl->requirePrivilegeForClass('update', 'Role');

        // Check if there are changes to apply
        $rolePrivChanges = $this->getRequest()->getParam('rolePrivChanges');
        if (!is_null($rolePrivChanges)) {

            $rolePrivChanges = json_decode($rolePrivChanges, true);

            // Priority messenger
            $msg = array();

            // Apply each requested change
            Doctrine_Manager::connection()->beginTransaction();
            try {
                foreach ($rolePrivChanges as $change) {

                    $roleName = $change['roleName'];
                    $privilegeId = $change['privilegeId'];
                    $roleId = Doctrine::getTable('Role')->findOneByNickname($roleName)->id;
                    $privilegeDescription = Doctrine::getTable('Privilege')->findOneById($privilegeId)->description;

                    // Check if this role has this privilege already
                    $targetRolePrivilegeCount = Doctrine_Query::create()
                        ->select('roleid')
                        ->from('RolePrivilege')
                        ->where('roleid = ' . $roleId)
                        ->andWhere('privilegeid = ' . $privilegeId)
                        ->setHydrationMode(Doctrine::HYDRATE_ARRAY)
                        ->count();
                    $roleHasPrivilege = $targetRolePrivilegeCount > 0 ? true : false;

                    // The checkbox was either checked (add) or unchecked (deleted)
                    $operation = (int) $change['newValue'] === 1 ? 'add' : 'delete';

                    // Add this privilege for this role if that was requested
                    if ($operation === 'add' && $roleHasPrivilege === false) {

                        $newRolePrivilege = new RolePrivilege;
                        $newRolePrivilege->roleId = $roleId;
                        $newRolePrivilege->privilegeId = $privilegeId;
                        $newRolePrivilege->save();

                        // Add to message stack
                        $msg[] = "Added the '" . $privilegeDescription . "' privilege to the " . $roleName . ' role.';

                    } elseif ($operation === 'delete' && $roleHasPrivilege === true) {

                        // Remove this privilege for this role
                        $removeRolePrivilegeQuery = Doctrine_Query::create()
                            ->from('RolePrivilege rp')
                            ->where('rp.roleId = ' . $roleId)
                            ->andWhere('rp.privilegeId = ' . $privilegeId);
                        $removeRolePrivilegeQuery->execute()->delete();

                        // Add to message stack
                        $msg[] = "Removed the '$privilegeDescription' privilege from the $roleName role.";
                    }

                }

                Doctrine_Manager::connection()->commit();
            } catch (Exception $e) {
                Doctrine_Manager::connection()->rollBack();
                $this->view->priorityMessenger('An error occurred while saving privileges', 'warning');
                $this->_redirect('/role/view-matrix');
            }

            // Send priority messenger if there are messeges to send
            if (!empty($msg)) {
                $msg = implode("<br/>", $msg);
                $this->view->priorityMessenger($msg, 'notice');
            }
        }

        // Now that the privileges have been saved, redirect back to the view-mode
        $this->_redirect('/role/view-matrix');
    }

    /**
     * Override parent to extend its returned array with a button to edit the privilege matrix.
     *
     * @param Fisma_Doctrine_Record $record The object for which this toolbar applies, or null if not applicable
     * @return array Array of Fisma_Yui_Form_Button
     */
    public function getToolbarButtons(Fisma_Doctrine_Record $record = null)
    {
        $buttons = parent::getToolbarButtons($record);

        if ($this->_acl->hasPrivilegeForClass('update', 'Role')) {
            $buttons['editMatrix'] = new Fisma_Yui_Form_Button_Link(
                'editMatrix',
                array(
                    'value' => 'View Privilege Matrix',
                    'href' => '/role/view-matrix'
                )
            );
        }

        return $buttons;
    }

    protected function _isDeletable()
    {
        return false;
    }
}
