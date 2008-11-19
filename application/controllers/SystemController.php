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
 */

/**
 * Handles CRUD for "system" objects.
 *
 * @package   Controller
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/mw/index.php?title=License
 */
class SystemController extends SecurityController
{
    private $_paging = array(
        'mode' => 'Sliding',
        'append' => false,
        'urlVar' => 'p',
        'path' => '',
        'currentPage' => 1,
        'perPage' => 20
    );
    private $_user = null;
    protected $_sanity = array(
        'data' => 'system',
        'filter' => array(
            '*' => array(
                'StringTrim',
                'StripTags'
            )
        ) ,
        'validator' => array(
            'name' => array('Alnum' => true),
            'nickname' => array('Alnum' => true),
            'primary_office' => 'Digits',
            'confidentiality' => 'NotEmpty',
            'integrity' => 'NotEmpty',
            'availability' => 'NotEmpty',
            'type' => 'NotEmpty',
            'desc' => array(
                'allowEmpty' => TRUE
            ) ,
            'criticality_justification' => array(
                'allowEmpty' => TRUE
            ) ,
            'sensitivity_justification' => array(
                'allowEmpty' => TRUE
            )
        ) ,
        'flag' => TRUE
    );
    public function init()
    {
        parent::init();
        $this->_system = new System();
    }
    public function preDispatch()
    {
        $req = $this->getRequest();
        $this->_pagingBasePath = $req->getBaseUrl() .
            '/panel/system/sub/list';
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
     * Returns the standard form for creating, reading, and updating systems.
     *
     * @return Zend_Form
     */
    public function getSystemForm()
    {
        $form = Form_Manager::loadForm('system');
        
        $db = $this->_system->getAdapter();
        $query = $db->select()->from(array('o'=>'organizations'), '*');
        $ret =  $db->fetchAll($query);
        if (!empty($ret)) {
            foreach ($ret as $row) {
                $form->getElement('organization_id')->addMultiOptions(array($row['id'] => $row['name']));
            }
        }
        
        $array = $this->_system->getEnumColumns('confidentiality');
        $form->getElement('confidentiality')->addMultiOptions($array);
        
        $array = $this->_system->getEnumColumns('integrity');
        $form->getElement('integrity')->addMultiOptions($array);
        
        $array = $this->_system->getEnumColumns('availability');
        $form->getElement('availability')->addMultiOptions($array);
        
        $type = $this->_system->getEnumColumns('type');
        $form->getElement('type')->addMultiOptions($type);
        
        return Form_Manager::prepareForm($form);
    }

    /*
     * list the systems from the search, if search none, it list all systems
     */     
    public function listAction()
    {
        $this->_helper->requirePrivilege('admin_systems', 'read');
        
        $req = $this->getRequest();
        $field = $req->getParam('fid');
        $value = trim($req->getParam('qv'));
        $db = $this->_system->getAdapter();
        $query = $db->select()->from(array('s'=>'systems'), 's.*')
                               ->join(array('o'=>'organizations'), 's.organization_id = o.id',
                                   array('organization'=>'o.name'));
        if (!empty($value)) {
            if ('organization' == $field) {
                $query->where("o.name = ?", $value);
            } else {
                $query->where("s.$field = ?", $value);
            }
        }
        $query->order('s.name ASC')->limitPage($this->_paging['currentPage'],
            $this->_paging['perPage']);
        $systemList = $db->fetchAll($query);
        $this->view->assign('system_list', $systemList);
    }

    /**
     *  Render the form for searching the systems.
     */
    public function searchboxAction()
    {
        $this->_helper->requirePrivilege('admin_systems', 'read');
        
        $req = $this->getRequest();
        $fid = $req->getParam('fid');
        $qv = $req->getParam('qv');
        $query = $this->_system->select()->from(array(
            's' => 'systems'
        ), array(
            'count' => 'COUNT(s.id)'
        ));
        $res = $this->_system->fetchRow($query)->toArray();
        $count = $res['count'];
        $this->_paging['totalItems'] = $count;
        $this->_paging['fileName'] = "{$this->_pagingBasePath}/p/%d";
        $pager = & Pager::factory($this->_paging);
        $this->view->assign('fid', $fid);
        $this->view->assign('qv', $qv);
        $this->view->assign('total', $count);
        $this->view->assign('links', $pager->getLinks());
    }

    /**
     * Display the form for creating a new system.
     */
    public function createAction()
    {
        $this->_helper->requirePrivilege('admin_systems', 'create');
        
        $form = $this->getSystemForm('system');
        $system = $this->_request->getPost();
        if ($system) {
            if ($form->isValid($system)) {
                $system = $form->getValues();
                unset($system['submit']);
                unset($system['reset']);
                
                $array = $this->_system->getEnumColumns('security_categorization');
                $index = max((int)$system['confidentiality'],
                             (int)$system['integrity'], (int)$system['type']);
                $system['security_categorization'] = $array[$index];
                
                $array = $this->_system->getEnumColumns('confidentiality');
                $system['confidentiality'] = $array[$system['confidentiality']];
                
                $array = $this->_system->getEnumColumns('integrity');
                $system['integrity'] = $array[$system['integrity']];
                
                $array = $this->_system->getEnumColumns('availability');
                $system['availability'] = $array[$system['availability']];
                
                $type = $this->_system->getEnumColumns('type');
                $system['type'] = $array[$system['type']];
                
                $systemId = $this->_system->insert($system);
                if (! $systemId) {
                    //@REVIEW 3 lines
                    $msg = "Failure in creation";
                    $model = self::M_WARNING;
                } else {
                    $this->_notification
                         ->add(Notification::SYSTEM_CREATED,
                             $this->_me->account, $systemId);

                    $msg = "The system is created";
                    $model = self::M_NOTICE;
                }
                $this->message($msg, $model);
                $this->_forward('view', null, null, array('id' => $systemId));
                return;
            } else {
                /**
                 * @todo this error display code needs to go into the decorator,
                 * but before that can be done, the function it calls needs to be
                 * put in a more convenient place
                 */
                $errorString = '';
                foreach ($form->getMessages() as $field => $fieldErrors) {
                    if (count($fieldErrors)>0) {
                        foreach ($fieldErrors as $error) {
                            $label = $form->getElement($field)->getLabel();
                            $errorString .= "$label: $error<br>";
                        }
                    }
                }
                // Error message
                $this->message("Unable to create system:<br>$errorString", self::M_WARNING);
            }
        }
        $this->view->title = "Create ";
        $this->view->form = $form;
    }

    /**
     *  Delete a specified system.
     */
    public function deleteAction()
    {
        $this->_helper->requirePrivilege('admin_systems', 'delete');
        
        $errno = 0;
        $req = $this->getRequest();
        $id = $req->getParam('id');
        $db = $this->_system->getAdapter();
        $qry = $db->select()->from('poams')
             ->where('system_id = ' . $id);
        $resultA = $db->fetchAll($qry);
        $qry->reset();
        $qry = $db->select()->from('assets')
            ->where('system_id = ' . $id);
        $resultB = $db->fetchAll($qry);
        if (!empty($resultA) || !empty($resultB)) {
            $msg = "This system cannot be deleted because it is already".
                   " associated with one or more POAMS or assets";
            $model = self::M_WARNING;
        } else {
            $res = $this->_system->delete('id = ' . $id);
            if (!$res) {
                $errno++;
            }
            if ($errno > 0) {
                $msg = "Failed to delete the system";
                $model = self::M_WARNING;
            } else {
                $this->_notification
                     ->add(Notification::SYSTEM_DELETED,
                        $this->_me->account, $id);

                $msg = "System deleted successfully";
                $model = self::M_NOTICE;
            }
        }
        $this->message($msg, $model);
        $this->_forward('list');
    }
    
    /**
     * Display a single system record with all details.
     */
    public function viewAction()
    {
        $this->_helper->requirePrivilege('admin_systems', 'read');
        
        $form = $this->getSystemForm();
        $id = $this->_request->getParam('id');
        $v = $this->_request->getParam('v');

        $res = $this->_system->find($id)->toArray();
        $system = $res[0];

        $array = $this->_system->getEnumColumns('confidentiality');
        $system['confidentiality'] = array_search($system['confidentiality'], $array);
        
        $array = $this->_system->getEnumColumns('integrity');
        $system['integrity'] = array_search($system['integrity'], $array);
        
        $array = $this->_system->getEnumColumns('availability');
        $system['availability'] = array_search($system['availability'], $array);
        
        $type = $this->_system->getEnumColumns('type');
        $system['type'] = array_search($system['type'], $type);
        
        $organization = new Organization();
        $res = $organization->find($system['organization_id'])->toArray();
        if (!empty($res)) {
            $organizationName = $res[0]['name'];
        } else {
            $organizationName = 'NONE';
        }
        $system['organization'] = $organizationName;

        if ($v == 'edit') {
            $this->view->assign('viewLink',
                                "/panel/system/sub/view/id/$id");
            $form->setAction("/panel/system/sub/update/id/$id");
        } else {
            // In view mode, disable all of the form controls
            $this->view->assign('editLink',
                                "/panel/system/sub/view/id/$id/v/edit");
            foreach ($form->getElements() as $element) {
                $element->setAttrib('disabled', 'disabled');
            }
        }
        $form->setDefaults($system);
        $this->view->form = $form;
        $this->view->assign('id', $id);
        $this->render($v);
    }

    /**
     * Updates system information after submitting an edit form.
     *
     * @todo cleanup this function
     */
    public function updateAction ()
    {
        $this->_helper->requirePrivilege('admin_systems', 'update');
        
        $form = $this->getSystemForm();
        $formValid = $form->isValid($_POST);
        $system = $form->getValues();

        $id = $this->_request->getParam('id');
        if ($formValid) {
            unset($system['submit']);
            unset($system['reset']);
            
            $array = $this->_system->getEnumColumns('security_categorization');
            $index = max((int)$system['confidentiality'],
                         (int)$system['integrity'], (int)$system['type']);
            $system['security_categorization'] = $array[$index];
            
            $array = $this->_system->getEnumColumns('confidentiality');
            $system['confidentiality'] = $array[$system['confidentiality']];
            
            $array = $this->_system->getEnumColumns('integrity');
            $system['integrity'] = $array[$system['integrity']];
            
            $array = $this->_system->getEnumColumns('availability');
            $system['availability'] = $array[$system['availability']];
            
            unset($array);
            $type = $this->_system->getEnumColumns('type');
            $system['type'] = $type[$system['type']];
            unset($type);
            
            $res = $this->_system->update($system, 'id = ' . $id);
            if ($res) {
                //@REVIEW 3 lines
                $this->_notification
                     ->add(Notification::SYSTEM_MODIFIED,
                         $this->_me->account, $id);

                $msg = "The system is saved";
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
                if (count($fieldErrors)>0) {
                    foreach ($fieldErrors as $error) {
                        $label = $form->getElement($field)->getLabel();
                        $errorString .= "$label: $error<br>";
                    }
                }
            }
            $errorString = addslashes($errorString);

            // Error message
            $this->message("Unable to update system:<br>$errorString", self::M_WARNING);
            // On error, redirect back to the edit action.
            $this->_forward('view', null, null, array('id' => $id, 'v' => 'edit'));
        }
    }
}
