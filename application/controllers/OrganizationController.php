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
 * @version   $Id: SysgroupController.php 940 2008-09-27 13:40:22Z ryanyang $
 */

/**
 * Handles CRUD for organization objects.
 *
 * @package   Controller
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/mw/index.php?title=License
 */
class OrganizationController extends SecurityController
{
    private $_paging = array(
        'mode' => 'Sliding',
        'append' => false,
        'urlVar' => 'p',
        'path' => '',
        'currentPage' => 1,
        'perPage' => 20
    );

    public function init()
    {
        parent::init();
        $this->_organization = new Organization();
    }

    public function preDispatch()
    {
        $req = $this->getRequest();
        $this->_pagingBasePath = $req->getBaseUrl()
                                   . '/panel/organization/sub/list';
        $this->_paging['currentPage'] = $req->getParam('p', 1);
        if (!in_array($req->getActionName(), array(
            'login',
            'logout'
        ))) {
            // by pass the authentication when login
            parent::preDispatch();
        }
    }

    /**
     * Returns the standard form for creating, reading, and
     * updating organizations.
     *
     * @return Zend_Form
     */
    public function getOrganizationForm()
    {
        $form = Form_Manager::loadForm('organization');
        
        $db = $this->_organization->getAdapter();
        $query = $db->select()->from(array('o'=>'organizations'), '*')
                                     ->where('father = 0');
        $ret =  $db->fetchAll($query);
        array_push($ret, array('id'=>'0', 'name'=>'NONE'));
        foreach ($ret as $row) {
            $form->getElement('father')->addMultiOptions(array($row['id'] => $row['name']));
        }
        return Form_Manager::prepareForm($form);
    }

    /**
     *  Render the form for searching the organizations.
     */
    public function searchboxAction()
    {
        Config_Fisma::requirePrivilege('admin_organizations', 'read');
        
        $req = $this->getRequest();
        $fid = $req->getParam('fid');
        $qv = $req->getParam('qv');
        $query = $this->_organization->select()->from(array(
            'o' => 'organizations'
        ), array(
            'count' => 'COUNT(o.id)'
        ));
        $res = $this->_organization->fetchRow($query)->toArray();
        $count = $res['count'];
        $this->_paging['totalItems'] = $count;
        $this->_paging['fileName'] = "{$this->_pagingBasePath}/p/%d";
        $pager = & Pager::factory($this->_paging);
        $this->view->assign('fid', $fid);
        $this->view->assign('qv', $qv);
        $this->view->assign('total', $count);
        $this->view->assign('links', $pager->getLinks());
    }

    /*
     * list the organizations from the search, if search none, it list all organizations
     */     
    public function listAction()
    {
        Config_Fisma::requirePrivilege('admin_organizations', 'read');
        
        $req = $this->getRequest();
        $field = $req->getParam('fid');
        $value = trim($req->getParam('qv'));
        $query = $this->_organization->select()->from('organizations', '*');
        if (!empty($value)) {
            $query->where("$field = ?", $value);
        }
        $query->order('name ASC')->limitPage($this->_paging['currentPage'],
                                             $this->_paging['perPage']);
        $organizationList = $this->_organization->fetchAll($query)->toArray();
        $this->view->assign('organization_list', $organizationList);
    }

    /**
     * Display a single organization record with all details.
     */
    public function viewAction()
    {
        Config_Fisma::requirePrivilege('admin_organizations', 'read');
        
        $form = $this->getOrganizationForm();
        $id = $this->_request->getParam('id');
        $v = $this->_request->getParam('v');

        $res = $this->_organization->find($id)->toArray();
        $organization = $res[0];
        $res = $this->_organization->find($organization['father'])->toArray();
        if (!empty($res)) {
            $father = $res[0]['id'];
        } else {
            $father = '0';
        }
        $organization['father'] = $father;
        
        if ($v == 'edit') {
            $this->view->assign('viewLink',
                                "/panel/organization/sub/view/id/$id");
            $form->setAction("/panel/organization/sub/update/id/$id");
        } else {
            // In view mode, disable all of the form controls
            $this->view->assign('editLink',
                                "/panel/organization/sub/view/id/$id/v/edit");
            foreach ($form->getElements() as $element) {
                $element->setAttrib('disabled', 'disabled');
            }
        }
        $form->setDefaults($organization);
        $this->view->form = $form;
        $this->view->assign('id', $id);
        $this->render($v);
    }

    /**
     * Display the form for creating a new organization.
     */
    public function createAction()
    {
        Config_Fisma::requirePrivilege('admin_organizations', 'create');
        
        $form = $this->getOrganizationForm();
        $organization = $this->_request->getPost();
        if ($organization) {
            if ($form->isValid($organization)) {
                $organization = $form->getValues();
                unset($organization['submit']);
                unset($organization['reset']);
                $organizationId = $this->_organization->insert($organization);
                if (! $organizationId) {
                    //@REVIEW 3 lines
                    $msg = "Failure in creation";
                    $model = self::M_WARNING;
                } else {
                    $this->_notification
                         ->add(Notification::ORGANIZATION_CREATED,
                             $this->_me->account, $organizationId);

                    $msg = "The organization is created";
                    $model = self::M_NOTICE;
                }
                $this->message($msg, $model);
                $this->_forward('view', null, null, array('id' => $organizationId));
                return;
            } else {
                /**
                 * @todo this error display code needs to go into the decorator,
                 * but before that can be done, the function it calls needs to be
                 * put in a more convenient place
                 */
                $errorString = '';
                foreach ($form->getMessages() as $field => $fieldErrors) {
                    if (count($fieldErrors>0)) {
                        foreach ($fieldErrors as $error) {
                            $label = $form->getElement($field)->getLabel();
                            $errorString .= "$label: $error<br>";
                        }
                    }
                }
                // Error message
                $this->message("Unable to create organization:<br>$errorString", self::M_WARNING);
            }
        }
        $this->view->title = "Create ";
        $this->view->form = $form;
    }

    /**
     *  Delete a specified organization.
     */
    public function deleteAction()
    {
        Config_Fisma::requirePrivilege('admin_organizations', 'delete');
        
        $req = $this->getRequest();
        $id = $req->getParam('id');
        $db = $this->_organization->getAdapter();
        $qry = $db->select()->from('systems')
            ->where('organization_id = ' . $id);
        $result = $db->fetchCol($qry);
        $model = self::M_WARNING;
        if (!empty($result)) {
            //@REVIEW 3 lines
            $msg = 'Deletion aborted! One or more systems exist within it.';
        } else {
            $res = $this->_organization->delete('id = ' . $id);
            if (!$res) {
                $msg = "Failure during deletion";
            } else {
                $this->_notification
                     ->add(Notification::ORGANIZATION_DELETED,
                        $this->_me->account, $id);

                $msg = "The organization is deleted";
                $model = self::M_NOTICE;
            }
        }
        $this->message($msg, $model);
        $this->_forward('list');
    }

    /**
     * Updates account information after submitting an edit form.
     *
     * @todo cleanup this function
     */
    public function updateAction ()
    {
        Config_Fisma::requirePrivilege('admin_organizations', 'update');
        
        $form = $this->getOrganizationForm();
        $formValid = $form->isValid($_POST);
        $organization = $form->getValues();

        $id = $this->_request->getParam('id');
        if ($formValid) {
            unset($organization['submit']);
            unset($organization['reset']);
            $res = $this->_organization->update($organization, 'id = ' . $id);
            if ($res) {
                //@REVIEW 3 lines
                $this->_notification
                     ->add(Notification::ORGANIZATION_MODIFIED,
                         $this->_me->account, $id);

                $msg = "The organization is saved";
                $model = self::M_NOTICE;
            } else {
                $msg = "Nothing changes";
                $model = self::M_WARNING;
            }
            $this->message($msg, $model);
            $this->_forward('view', null, null, array('id' => $id));
        } else {
            $errorString = '';
            foreach ($form->getMessages() as $field => $fieldErrors) {
                if (count($fieldErrors>0)) {
                    foreach ($fieldErrors as $error) {
                        $label = $form->getElement($field)->getLabel();
                        $errorString .= "$label: $error<br>";
                    }
                }
            }
            $errorString = addslashes($errorString);

            // Error message
            $this->message("Unable to update organization<br>$errorString", self::M_WARNING);
            // On error, redirect back to the edit action.
            $this->_forward('view', null, null, array('id' => $id, 'v' => 'edit'));
        }
    }
}
