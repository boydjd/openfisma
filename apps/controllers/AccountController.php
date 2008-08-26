<?php
/**
 * Copyright (c) 2008 Endeavor Systems, Inc.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 * @author    Ryan <ryan@users.sourceforge.net>
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/mw/index.php?title=License
 * @version   $Id$
 */
 
require_once (CONTROLLERS . '/PoamBaseController.php');
require_once (MODELS . '/user.php');
require_once (MODELS . '/system.php');
require_once ('Pager.php');
require_once 'Zend/Date.php';
require_once 'Zend/Filter/Input.php';
require_once 'Zend/Form/Element/Checkbox.php';
require_once 'Zend/Validate/Between.php';
require_once (FORMS . '/Manager.php');
require_once (FORMS . '/CheckboxMatrix.php');
require_once (MODELS . DS . 'event.php');

/**
 * The account controller deals with creating, updating, and managing user
 * accounts on the system.
 *
 * @package   Controller
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/mw/index.php?title=License
 */
class AccountController extends PoamBaseController
{
    private $_user;
    protected $_sanity = array(
        'data' => 'user',
        'filter' => array(
            '*' => array(
                'StringTrim',
                'StripTags'
            )
        ) ,
        'validator' => array(
            'name_first' => array('Alnum' => true),
            'name_last' => array('Alnum' => true),
            'phone_office' => array('Alnum' => true),
            'phone_mobile' => array(
                'allowEmpty' => TRUE,
                'Digits'
            ) ,
            'email' => 'EmailAddress',
            'title' => array('Alnum' => true, 'allowEmpty' => TRUE),
            'is_active' => array(
                'Int'
            ) ,
            'account' => 'Alnum',
            'password' => array(
                'allowEmpty' => TRUE
            )
        ) ,
        'flag' => TRUE
    );
    
    /**
     * init() - Initialize internal members.
     */
    public function init()
    {
        parent::init();
        $this->_user = new User();
        $ajaxContext = $this->_helper->getHelper('AjaxContext');
        $ajaxContext->addActionContext('checkdn', 'html')
                    ->initContext();
    }

    /**
     * searchboxAction() - Render the form for searching the user accounts.
     */
    public function searchboxAction()
    {
        // These are the fields which can be searched, the key is the physical
        // name and the value is the logical name which is displayed in the
        // interface.
        $criteria = array(
            'name_last' => 'Last Name',
            'name_first' => 'First Name',
            'account' => 'Username',
            'email' => 'Email',
            'title' => 'Title',
            'phone_office' => 'Office Phone',
            'phone_mobile' => 'Mobile Phone'
        );
        $this->view->assign('criteria', $criteria);
        
        // Count the total number of users and configure the pager
        $user = new User();
        $userCount = $user->count();
        $this->_paging['currentPage'] = $this->_request->getParam('p', 1);
        $this->_paging['totalItems'] = $userCount;
        $this->_paging['fileName'] = "/panel/account/sub/list/p/%d";
        $pager = &Pager::factory($this->_paging);
        
        // Assign view outputs
        $this->view->assign('fid', $this->_request->getParam('fid'));
        $this->view->assign('qv', $this->_request->getParam('qv'));
        $this->view->assign('total', $userCount);
        $this->view->assign('links', $pager->getLinks());
        $this->render();
    }
    
    /**
     * listAction() - List all the users.
     */
    public function listAction()
    {
        // Set up the query to get the full list of users
        $user = new User();
        $qry = $user->select()
                    ->setIntegrityCheck(false)
                    ->from(array('u' => 'users'),
                           array('id',
                                 'account',
                                 'name_last',
                                 'name_first',
                                 'phone_office',
                                 'phone_mobile',
                                 'email'));

        // $fid is the name of the field
        $fid = $user->getAdapter()->quote($this->getRequest()->getParam('fid'));
        // $qv is the value to search for in the field
        $qv = $user->getAdapter()->quote($this->getRequest()->getParam('qv'));
        $qry->where("$fid = $qv");

        $qry->order("name_last ASC");
        $qry->limitPage($this->_paging['currentPage'], 
                        $this->_paging['perPage']);
        $data = $user->fetchAll($qry);
        
        // Format the query results appropriately for passing to the view script
        $userList = $data->toArray();
        foreach ($userList as $row) {
            $ret = $user->getRoles($row['id'],
                                   array('nickname', 'id'));
            $roleList[$row['id']] = '';
            foreach ($ret as $v) {
                $roleList[$row['id']].= $v['nickname'] . ', ';
            }
            $roleList[$row['id']] = substr($roleList[$row['id']], 0, -2);
        }
        
        // Assign view outputs
        $this->view->assign('roleList', $roleList);
        $this->view->assign('userList', $userList);
        $this->render();
    }
    
    /**
     *  viewAction() - Display a single user record with all details.
     */
    public function viewAction()
    {
        // $id is the user id of the record that should be displayed
        $id = $this->getRequest()->getParam('id');
        // $v is ???
        $v = $this->getRequest()->getParam('v');

        $user = new User();
        $sys = new System();
        
        // Set up the query to get this user's information
        $qry = $user->select()
                    ->setIntegrityCheck(false)
                    ->from('users',
                           array('name_last',
                                 'name_first',
                                 'phone_office',
                                 'phone_mobile',
                                 'email',
                                 'title',
                                 'is_active',
                                 'account',
                                 'password',
                                 'ldap_dn'))
                    ->where("id = ?", $id);
        $userDetail = $user->fetchRow($qry)->toArray();

        // Get the user's roles
        $ret = $user->getRoles($id,
                               array('role_name' => 'name',
                                     'role_id' => 'id'));
        $count = count($ret);
        if ($count > 1) {
            $roles = implode(', ', $ret);
        } elseif ($count == 1) {
            $roles = $ret[0]['role_id'];
        } else {
            $roles = null;
        }
        $query = $user->getAdapter()
                      ->select()
                      ->from('roles', array('id', 'name'))
                      ->where('nickname != \'auto_role\'')
                      ->order('name ASC');
        $roleList = $user->getAdapter()->fetchPairs($query);
        
        // Assign view outputs
        $this->view->assign('id', $id);
        $this->view->assign('user', $userDetail);
        $this->view->assign('roleCount', $count);
        $this->view->assign('roles', $roles);
        $this->view->assign('roleList', $roleList);
        $this->view->assign('mySystems', $user->getMySystems($id));
        $this->view->assign('allSystems', $sys->getList());
        $this->render($v);
    }
    
    /**
     * updateAction() - Displays the form for updating a user's information.
     *
     * @todo cleanup this function
     */
    public function updateAction()
    {
        $req = $this->getRequest();
        $id = $req->getParam('id');
        $userData = $req->getPost('user');
        $userRole = $req->getPost('user_role');
        $systemData = $req->getPost('system');
        $confirmPassword = $req->getPost('password_confirm');
        
        if ( readSysConfig('auth_type') == 'database'
             && empty($userData['account']) ) {
            $msg = "Account can not be null.";
            $this->message($msg, self::M_WARNING);
            $this->_forward('view', null, null, array(
                'v' => 'edit'
            ));
            return;
        }
        if ( readSysConfig('auth_type') == 'ldap' ) {
            $userData['account'] = $userData['ldap_dn'];
        }
        if ( !empty($userData['password']) ) {
            /// @todo validate the password complexity
            if ($userData['password'] != $confirmPassword) {
                $msg = "Password does not match confirmation.";
                $this->message($msg, self::M_WARNING);
                $this->_forward('view', null, null, array(
                    'v' => 'edit'
                ));
                return;
            }
            $userData['password'] = md5($userData['password']);
        } else {
            unset($userData['password']);
        }
        if (!empty($userData)) {
            if ($userData['is_active'] == 0) {
                $userData['termination_ts'] = self::$now->toString("Y-m-d H:i:s");
            } elseif (1 == $userData['is_active']) {
                $userData['failure_count'] = 0;
            }
            $n = $this->_user->update($userData, "id=$id");
            if ($n > 0) {
                $this->_user->log(User::MODIFICATION, 
                                   $this->me->id,
                                   $userData['account']);
            }
            if (!empty($systemData)) {
                $my_sys = $this->_user->getMySystems($id);
                $new_sys = array_diff($systemData, $my_sys);
                $remove_sys = array_diff($my_sys, $systemData);
                $n = $this->_user->associate($id, User::SYS, $new_sys);
                $n = $this->_user->associate($id, User::SYS, $remove_sys,
                                              true);
            }
        }
        if (!empty($userRole)) {
            $qry = $db->select()->from(array(
                'ur' => 'user_roles'
            ), 'ur.*')->join(array(
                'r' => 'roles'
            ), 'ur.role_id = r.id', array())
            ->where('user_id = ?', $id)
            ->where('r.nickname != ?', 'auto_role');
            $ret = $db->fetchAll($qry);
            $count = count($ret);
            if (1 == $count) {
                $db->update('user_roles', array(
                    'role_id' => $userRole
                ), 'user_id =' . $id);
            } elseif (0 == $count) {
                $db->insert('user_roles', array(
                    'role_id' => $userRole,
                    'user_id' => $id
                ));
            } else {
                throw new 
                    fisma_Exception('You can not evade browser to access.');
            }
        }
        $this->_forward('view');
    }
    
    /**
     * deleteAction() - Delete a specified user.
     *
     * @todo cleanup this function
     */
    public function deleteAction()
    {
        $req = $this->getRequest();
        $id = $req->getParam('id');
        assert($id);
        $msg = "";
        $ret = $this->_user->find($id)->toArray();
        $user_name = $ret[0]['account'];
        $res = $this->_user->delete('id = ' . $id);
        $res = $this->_user->getAdapter()
                    ->delete('user_systems', 'user_id = ' . $id);
        $res = $this->_user->getAdapter()
                    ->delete('user_roles', 'user_id = ' . $id);
        if ($res) {
            $msg = "User " . $user_name . " deleted successfully.";
            $model = self::M_NOTICE;
            $this->_user->log(USER::TERMINATION, 
                               $this->me->id, 
                               'delete user ' . $user_name);
        } else {
            $msg = "Failed to delete user.";
            $model = self::M_WARNING;
        }
        $this->message($msg, $model);
        $this->_forward('list');
    }
    
    public function getAccountForm() {
        // Get system and roles information
        $system = new System();
        $db = $system->getAdapter();
        $qry = $db->select()
                  ->from('roles', array('id',
                                        'name'))
                  ->where('nickname != ?', 'auto_role')
                  ->order('name');
        $ret = $db->fetchAll($qry);

        // Load the form and populate the dynamic pull downs
        $form = Form_Manager::loadForm('account');
        foreach ($ret as $row) {
            $form->getElement('role')
                 ->addMultiOptions(array($row['id'] => $row['name']));
        }

        $checkboxMatrix = new Form_CheckboxMatrix('systemsMatrix');
        foreach ($system->getList() as $id => $systemData) {
            $checkboxMatrix->addCheckbox($id, $systemData['name']);
        }

        // Add the checkbox matrix to a separate display group
        $form->addElement($checkboxMatrix);
        $form->addDisplayGroup(array('systemsMatrix'), 'systems');

        // I don't think it's possible to load the status menu correctly in
        // the .ini file.
        $form->getElement('is_active')
             ->setMultiOptions(array(1 => 'Active', 0 => 'Locked'));

        // If there are any parameters in the _POST variable, then use that
        // to prepopulate the form.
        $form->setDefaults($_POST);
             
        return $form;
    }
    
    /**
     * createAction() - Display the form for creating a new user account.
     *
     * @todo cleanup this function
     */
    public function createAction()
    {
        // Get the account form
        $form = $this->getAccountForm();

        // Assign view outputs.
        $this->view->form = Form_Manager::prepareForm($form);
        $this->render();
    }
    
    /**
     * saveAction() - Saves information for a newly created user.
     *
     * @todo cleanup this function
     */
    public function saveAction()
    {
        // Load the account form in order to perform validations.
        $form = $this->getAccountForm();
        $accountData = $form->getValues();
        print_r($accountData);
        if ($accountData['password'] != $accountData['confirm_password']) {
            $this->message("The two passwords do not match",
                           self::M_WARNING);
        } else if ($form->isValid($_POST)) {
            unset($accountData['confirm_password']);
            $roleId = $accountData['role'];
            unset($accountData['role']);
            unset($accountData['submit']);
            unset($accountData['systemsMatrix']);
            
            // Create the user's main record.
            if ( 'ldap' == readSysConfig('auth_type') ) {
                $accountData['account'] = $accountData['ldap_dn'];
            } else if ( 'database' == readSysConfig('auth_type') ) {
                $accountData['password'] = md5($accountData['password']);
            }
            $accountData['created_ts'] = self::$now->toString('Y-m-d H:i:s');
            $accountData['auto_role'] = $accountData['account'].'_r';

            $userId = $this->_user->insert($accountData);
            
            // Create the user's role associations.
            $this->_user->associate($userId, User::ROLE, $roleId);

            // Create the user's system associations.
            if ( !empty($systems) ) {
                $this->_user->associate($userId, User::SYS, $systems);
            }

            // Log the new account creation and display a success message to the
            // user.
            $this->_user->log(User::CREATION, $this->me->id,
                             'create user('.$accountData['account'].')');
            $this->message("User ({$accountData['account']}) added",
                           self::M_NOTICE);
        } else {
            /**
             * @todo this error display code needs to go into the decorator,
             * but before that can be done, the function it calls needs to be
             * put in a more convenient place
             */
            $errorString = '';
            foreach ($form->getErrors() as $field => $fieldErrors) {
                if (count($fieldErrors>0)) {
                    foreach ($fieldErrors as $error) {
                        $errorString .= "$field failed because \"$error\"<br>";
                    }
                }
            }
            $this->message("Unable to create account: $errorString",
                           self::M_WARNING);
        }
        $this->_forward('create');
    }

    /**
     * checkDnAction() - Check to see if the specified LDAP
     * distinguished name (DN) exists in the system's specified LDAP directory.
     *
     * @todo language check
     */
    public function checkdnAction()
    {
        require_once "Zend/Ldap.php";
        $config = new Config();
        $data = $config->getLdap();
        $dn = $this->_request->getParam('dn');

        $msg = '';
        foreach ($data as $opt) {
            unset($opt['id']);
            $srv = new Zend_Ldap($opt);
            try {
                $dn = $srv->getCanonicalAccountName($dn,
                            Zend_Ldap::ACCTNAME_FORM_DN); 
                $msg = "$dn exists";
            } catch (Zend_Ldap_Exception $e) {
                $msg .= $e->getMessage();
            }
        }
        echo $msg;
    }

    public function assignroleAction()
    {
        $req = $this->getRequest();
        $user_id = $req->getParam('id');
        $db = $this->_user->getAdapter();
        $ret = $this->_user->find($user_id)->toArray();
        $user_name = $ret[0]['account'];
        $qry = $db->select()->from(array(
            'r' => 'roles'
        ), array(
            'role_id' => 'r.id',
            'role_name' => 'r.name'
        ))
        ->join(array(
            'ur' => 'user_roles'
        ), 'ur.role_id = r.id', array())
        ->where('ur.user_id = ?', $user_id)
        ->where('r.nickname !=?', 'auto_role');
        $assign_roles = $db->fetchAll($qry);
        $qry->reset();
        $ret = $this->_user->find($user_id)->toArray();
        $auto_role = $ret[0]['auto_role'];
        $qry->from('roles', array(
            'role_id' => 'id',
            'role_name' => 'name'
        ))->where('nickname != ?', 'auto_role');
        $all_roles = $db->fetchAll($qry);
        foreach ($all_roles as $v) {
            if (!in_array($v, $assign_roles)) {
                $available_roles[] = $v;
            }
        }
        $qry->reset();
        $qry->from(array(
            'f' => 'functions'
        ), array(
            'function_id' => 'f.id',
            'function_name' => 'f.name'
        ))
        ->join(array(
            'rf' => 'role_functions'
        ), 'rf.function_id = f.id', array())
        ->join(array(
            'ur' => 'user_roles'
        ), 'ur.role_id = rf.role_id', array())
        ->join(array(
            'r' => 'roles'
        ), 'r.id = ur.role_id', array())
        ->where('r.name = ?', $auto_role);
        $assign_privileges = $db->fetchAll($qry);
        $this->view->assign('user_id', $user_id);
        $this->view->assign('user_name', $user_name);
        $this->view->assign('assign_roles', $assign_roles);
        $this->view->assign('available_roles', $available_roles);
        $this->view->assign('assign_privileges', $assign_privileges);
        if ('assign' == $req->getParam('do')) {
            $assign_roles = $req->getParam('assign_roles');
            $assign_privileges = $req->getParam('assign_privileges');
            $db->delete('user_roles', 'user_id = ' . $user_id);
            foreach ($assign_roles as $v) {
                $db->insert('user_roles', array(
                    'user_id' => $user_id,
                    'role_id' => $v
                ));
            }
            if (!empty($assign_privileges)) {
                $qry = $db->select()->from(array(
                    'r' => 'roles'
                ), array(
                    'role_id' => 'r.id'
                ))->where('r.name = ?', $auto_role);
                $ret = $db->fetchRow($qry);
                if (!empty($ret)) {
                    $role_id = $ret['role_id'];
                    $db->insert('user_roles', array(
                        'user_id' => $user_id,
                        'role_id' => $role_id
                    ));
                    $db->delete('role_functions', 'role_id = ' . $role_id);
                    foreach ($assign_privileges as $v) {
                        $db->insert('role_functions', array(
                            'role_id' => $role_id,
                            'function_id' => $v
                        ));
                    }
                } else {
                    $db->insert('roles', array(
                        'name' => $auto_role,
                        'nickname' => 'auto_role',
                        'desc' => 'extra role for user'
                    ));
                    $role_id = $db->LastInsertId();
                    $db->insert('user_roles', array(
                        'user_id' => $user_id,
                        'role_id' => $role_id
                    ));
                    foreach ($assign_privileges as $v) {
                        $db->insert('role_functions', array(
                            'role_id' => $role_id,
                            'function_id' => $v
                        ));
                    }
                }
            }
            $this->message('assign role and privileges successfully.', 
                            self::M_NOTICE);
            $this->_redirect('panel/account/sub/assignrole/id/' . $user_id);
        } else {
            $this->render();
        }
    }
    /**
     * Search avaliable privileges 
     */
    public function searchprivilegeAction()
    {
        $req = $this->_request;
        $db = $this->_user->getAdapter();
        $user_id = $req->getParam('id');
        $ret = $this->_user->find($user_id)->toArray();
        $auto_role = $ret[0]['auto_role'];
        $qry = $db->select()->from(array(
            'f' => 'functions'
        ), array(
            'function_id' => 'f.id',
            'function_name' => 'f.name'
        ))
        ->join(array(
            'rf' => 'role_functions'
        ), 'rf.function_id = f.id', array())
        ->join(array(
            'ur' => 'user_roles'
        ), 'ur.role_id = rf.role_id', array())
        ->join(array(
            'r' => 'roles'
        ), 'r.id = ur.role_id', array())
        ->where('r.name = ?', $auto_role);
        $assign_privileges = $db->fetchAll($qry);
        $roles = substr(str_replace('-', ',', $req->getParam('assign_roles')),
                         0, -1);
        $qry->reset();
        $qry->from('functions', array(
            'function_id' => 'id',
            'function_name' => 'name'
        ));
        $all_privileges = $db->fetchAll($qry);
        if (!empty($roles)) {
            $qry->reset();
            $qry->from(array(
                'f' => 'functions'
            ), array(
                'function_id' => 'f.id',
                'function_name' => 'f.name'
            ))
            ->join(array(
                'rf' => 'role_functions'
            ), 'rf.function_id = f.id', array())
            ->where('rf.role_id in (' . $roles . ')');
            $exist_privileges = array_merge($db->fetchAll($qry),
                $assign_privileges);
        } else {
            $exist_privileges = $assign_privileges;
        }
        foreach ($all_privileges as $v) {
            if (!in_array($v, $exist_privileges)) {
                $available_privileges[] = $v;
            }
        }
        $this->view->assign('available_privileges', $available_privileges);
        $this->_helper->layout->setLayout('ajax');
        $this->render('availableprivi');
    }
    /**
     * For setting events which user interested in
     *
     */
    public function notificationeventAction()
    {
        $user_id = $this->_request->getParam('id');
        $event = new Event();

        if ($this->_request->isPost()) {
            $data = $this->_request->getPost();
            if (!isset($data['enableEvents'])) {
                $data['enableEvents'] = array();
            }
            $event->saveEnabledEvents($user_id, $data['enableEvents']);
            if ($data['notify_frequency']) {
                $where = $this->_user->getAdapter()
                    ->quoteInto('`id` = ?', $user_id);
                $this->_user->update(array('notify_frequency' => 
                    $data['notify_frequency']), $where);
            } 
        }
        
        $ret = $this->_user->find($user_id);
        $this->view->notify_frequency = $ret->current()->notify_frequency;
        $allEvent = $event->getUserAllEvents($user_id);
        $enabledEvent = $event->getEnabledEvents($user_id);
        
        $this->view->availableList = array_diff($allEvent, $enabledEvent);
        $this->view->enableList = array_intersect($allEvent, $enabledEvent);
        $this->render();
    }
    
}
