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
 * The account controller deals with creating, updating, and managing user
 * accounts on the system.
 *
 * @package    Controller
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/mw/index.php?title=License
 */
class AccountController extends PoamBaseController
{
    private $_user;
    
    protected $_sanity = array(
        // @todo remove this array
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
     * getAccountForm() - Returns the standard form for creating, reading, and
     * updating accounts.
     *
     * @return Zend_Form
     */
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

        $checkboxMatrix = new Form_CheckboxMatrix('systems');
        foreach ($system->getList() as $id => $systemData) {
            $checkboxMatrix->addCheckbox($id, $systemData['name']);
        }
        
        // If the application is in database authentication mode, then remove
        // the LDAP DN fields. If the application is in LDAP authentication
        // mode, then remove the database authentication fields.
        $systemAuthType = Config_Fisma::readSysConfig('auth_type');
        if ($systemAuthType == 'ldap') {
            $form->removeElement('account');
            $form->removeElement('password');
            $form->removeElement('confirmPassword');
        } else if ($systemAuthType == 'database') {
            $form->removeElement('ldap_dn');
            $form->removeElement('checkdn');
        } else {
            throw new FismaException("The account form cannot handle"
                                    . " the current authentication type: "
                                    . $systemAuthType);
        }
        // Add the checkbox matrix to a separate display group
        $form->addElement($checkboxMatrix);
        $form->addDisplayGroup(array('systems'), 'systemsGroup');

        // I don't think it's possible to load the status menu correctly in
        // the .ini file.
        $form->getElement('is_active')
             ->setMultiOptions(array(1 => 'Active', 0 => 'Locked'));

        return $form;
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
        $postAction = "/panel/account/sub/list";

        // Count the total number of users and configure the pager
        $user = new User();
        $userCount = $user->count();
        $this->_paging['currentPage'] = $this->_request->getParam('p', 1);
        $this->_paging['totalItems'] = $userCount;
        $this->_paging['fileName'] = "/panel/account/sub/list/p/%d";

        if ('log' == $this->_request->getParam('sub')) {
            $criteria = array(
                'event'=>'Event Name',
                'account'=>'Account Name');
            $postAction = "/panel/account/sub/log";

            $query = $user->getAdapter()->select()->from('account_logs',
                         array('count'=>'count(*)'));
            $ret = $user->getAdapter()->fetchRow($query);
            $logCount = $ret['count'];
            $this->_paging['totalItems'] = $logCount;
            $this->_paging['fileName'] = "/panel/account/sub/log/p/%d";
        }

        $pager = &Pager::factory($this->_paging);
        
        // Assign view outputs
        $this->view->assign('criteria', $criteria);
        $this->view->assign('fid', $this->_request->getParam('fid'));
        $this->view->assign('qv', $this->_request->getParam('qv'));
        $this->view->assign('postAction', $postAction);
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

        $qv = $this->_request->getParam('qv');
        if (!empty($qv)) {
            $fid = $this->_request->getParam('fid');
            $qry->where("$fid = '$qv'");
        }

        $qry->order("name_last ASC");
        $qry->limitPage($this->_paging['currentPage'], 
                        $this->_paging['perPage']);
        $data = $user->fetchAll($qry);
        
        // Format the query results appropriately for passing to the view script
        $userList = $data->toArray();
        $roleList = array();
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
     *  viewAction() - Display a single user record with all details. If the
     * parameter
     */
    public function viewAction()
    {
        $form = $this->getAccountForm();
        
        // $id is the user id of the record that should be displayed
        $id = $this->getRequest()->getParam('id');
        // $v is either "view" or "edit" and indicates which view to use
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
        $userDetail['systems'] = $user->getMySystems($id);

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
        // @todo this will break if more than 1 role
        $userDetail['role'] = $roles;
        
        if ($v == 'edit') {
            // Prepare the password requirements explanation:
            $requirements = $this->_getPasswordRequirements();
            $this->view->assign('requirements', $requirements);
            $this->view->assign('viewLink',
                                "/panel/account/sub/view/id/$id");
            $form->setAction("/panel/account/sub/update/id/$id");
        } else {
            // In view mode, disable all of the form controls
            $this->view->assign('editLink',
                                "/panel/account/sub/view/id/$id/v/edit");
            foreach ($form->getElements() as $element) {
                $element->setAttrib('disabled', 'disabled');
            }
        }
        
        // Assign view outputs
        // @hack This is to work around a bug in Zend_View_FormHelper: the
        // option value for is_active needs to be converted from int to string.
        $userDetail['is_active'] = "{$userDetail['is_active']}";
        $form->setDefaults($userDetail);

        $this->view->assign('form', Form_Manager::prepareForm($form));

        // Notice that the view is rendered conditionally based on the $v
        // parameter. This can be "edit" or "view"
        $this->render($v);
    }
    
    /**
     * updateAction() - Updates account information after submitting an edit
     * form.
     *
     * @todo cleanup this function
     */
    public function updateAction()
    {
        // Load the account form in order to perform validations.
        $form = $this->getAccountForm();
        $pass = $form->getElement('password');
        $pass->addValidator(new Form_Validator_Password());
        $formValid = $form->isValid($_POST);
        $accountData = $form->getValues();

        $id = $this->getRequest()->getParam('id');
        $db = $this->_user->getAdapter();
        // Compare the two passwords
        // @todo when we get ZF 1.6, use the addError function here and in
        // saveAction()
        if ( isset($accountData['password'])
             && ($accountData['password'] !=
                 $accountData['confirmPassword']) ) {
            $this->message("The two passwords do not match",
                           self::M_WARNING);
            $this->_forward('view', null, null, array('id' => $id,
                                                      'v' => 'edit'));
        } else if ($formValid) {
            if ( Config_Fisma::readSysConfig('auth_type') == 'database'
                 && empty($accountData['account']) ) {
                $msg = "Account can not be null.";
                $this->message($msg, self::M_WARNING);
                $this->_forward('view', null, null, array(
                    'v' => 'edit'
                ));
                return;
            }
            if ( Config_Fisma::readSysConfig('auth_type') == 'ldap' ) {
                $accountData['account'] = $accountData['ldap_dn'];
            }
            if ( !empty($accountData['password']) ) {
                /// @todo validate the password complexity
                if ($accountData['password'] !=
                    $accountData['confirmPassword']) {
                    $msg = "The two passwords do not match.";
                    $this->message($msg, self::M_WARNING);
                    $this->_forward('view', null, null, array(
                        'v' => 'edit'
                    ));
                    return;
                }
                $accountData['password'] = Config_Fisma::encrypt($accountData['password']);
            } else {
                unset($accountData['password']);
            }
            unset($accountData['confirmPassword']);
            $roleId = $accountData['role'];
            unset($accountData['role']);
            unset($accountData['submit']);
            $systems = $accountData['systems'];
            if (!is_array($systems)) {
                $systems = array();
            }
            unset($accountData['systems']);
            unset($accountData['checkdn']);

            if ($accountData['is_active'] == 0) {
                $accountData['termination_ts'] =
                    self::$now->toString("Y-m-d H:i:s");
            } elseif ($accountData['is_active'] == 1) {
                $accountData['failure_count'] = 0;
                $accountData['last_login_ts'] = new Zend_Db_Expr('NOW()');
                $accountData['termination_ts'] = NULL;
            }

            $n = $this->_user->update($accountData, "id=$id");
            if ($n > 0) {
                $this->_notification
                     ->add(Notification::ACCOUNT_MODIFIED,
                        $this->_me->account, $id);
                $this->_user->log(User::MODIFICATION,
                                   $this->_me->id,
                                   "Modified user {$accountData['account']}");
            }

            $mySystems = $this->_user->getMySystems($id);
            $addSystems = array_diff($systems, $mySystems);
            $removeSystems = array_diff($mySystems, $systems);
            $n = $this->_user->associate($id, User::SYS, $addSystems);
            // The last parameter "true" inverts the association, i.e. removes
            // the specified systems from this user's account
            $n = $this->_user->associate($id, User::SYS, $removeSystems, true);

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
                    'role_id' => $roleId
                ), 'user_id =' . $id);
            } elseif (0 == $count) {
                $db->insert('user_roles', array(
                    'role_id' => $roleId,
                    'user_id' => $id
                ));
            } else {
                throw new
                    FismaException('The user has more than 1 role.');
            }

            $this->message("User ({$accountData['account']}) modified",
                           self::M_NOTICE);
            $this->_forward('view', null, null, array('id' => $id));
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
            $errorString = addslashes($errorString);

            // Error message
            $this->message("Unable to update account:<br>$errorString",
                           self::M_WARNING);

            // On error, redirect back to the edit action.
            $this->_forward('view', null, null, array('id' => $id,
                                                      'v' => 'edit'));
        }
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
        $userName = $ret[0]['account'];
        $res = $this->_user->delete('id = ' . $id);
        $res = $this->_user->getAdapter()
                    ->delete('user_systems', 'user_id = ' . $id);
        $res = $this->_user->getAdapter()
                    ->delete('user_roles', 'user_id = ' . $id);
        if ($res) {
            $this->_notification->add(Notification::ACCOUNT_DELETED,
                $this->_me->account, $id);
            $msg = "User " . $userName . " deleted successfully.";
            $model = self::M_NOTICE;
            $this->_user->log(USER::TERMINATION,
                               $this->_me->id,
                               'delete user ' . $userName);
        } else {
            $msg = "Failed to delete user.";
            $model = self::M_WARNING;
        }
        $this->message($msg, $model);
        $this->_forward('list');
    }
    
    /**
     * createAction() - Display the form for creating a new user account.
     */
    public function createAction()
    {
        // Get the account form
        $form = $this->getAccountForm();
        $form->setAction('/panel/account/sub/save');
        
        // The password fields are required during creation *if* we are in
        // database authentication mode
        if (Config_Fisma::readSysConfig('auth_type') == 'database') {
            $form->getElement('password')->setRequired(true);
            $form->getElement('confirmPassword')->setRequired(true);
            $form->getElement('password')->setValue($this->_randomPassword());
            $form->getElement('confirmPassword')->setValue($this->_randomPassword());
             // Prepare the password requirements explanation:
            $requirements = $this->_getPasswordRequirements();
            $this->view->assign('requirements', $requirements);
        }
        
        // If there is data in the _POST variable, then use that to
        // pre-populate the form.
        $form->setDefaults($_POST);
        
        // Assign view outputs.
        $this->view->form = Form_Manager::prepareForm($form);
        $this->render();
    }
    
    /**
     * saveAction() - Saves information for a newly created user.
     */
    public function saveAction()
    {
        // Load the account form in order to perform validations.
        $form = $this->getAccountForm();
        $post = $this->_request->getPost();

        // The password fields are required during creation *if* we are in
        // database authentication mode
        if (Config_Fisma::readSysConfig('auth_type') == 'database') {
            $form->getElement('password')->setRequired(true);
            $form->getElement('confirmPassword')->setRequired(true);
            $password = $form->getElement('password');
            $password->addValidator(new Form_Validator_Password());
        }

        // Validate forms and get the submitted values
        $formValid = $form->isValid($post);
        $accountData = $form->getValues();
        
        // Compare the two passwords
        if ( isset($accountData['password'])
             && ($accountData['password'] !=
                 $accountData['confirmPassword']) ) {
            $this->message("The two passwords do not match",
                           self::M_WARNING);
            $this->_forward('create');
        } else if ($formValid) {
            // Need to unset any parameters which aren't going into the db, due
            // to the way the insert() function works below.
            // @todo fix the insert function and then clean this up
            unset($accountData['confirmPassword']);
            $roleId = $accountData['role'];
            unset($accountData['role']);
            unset($accountData['submit']);
            // @todo see
            $systems = $accountData['systems'];
            unset($accountData['systems']);
            unset($accountData['checkdn']);
            
            // Create the user's main record.
            if ( 'ldap' == Config_Fisma::readSysConfig('auth_type') ) {
                $accountData['account'] = $accountData['ldap_dn'];
            } else if ( 'database' == Config_Fisma::readSysConfig('auth_type') ) {
                $password = $accountData['password'];
                $accountData['password'] = Config_Fisma::encrypt($accountData['password']);
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

            $this->_notification->add(Notification::ACCOUNT_CREATED,
                $this->_me->account, $userId);
            // Log the new account creation and display a success message to the
            // user.
            $this->_user->log(User::CREATION, $this->_me->id,
                             'create user('.$accountData['account'].')');
            $this->message("User ({$accountData['account']}) added, and a validate email has been sent to this user",
                           self::M_NOTICE);

            $this->emailvalidate($userId, $accountData['email'], 'create',
                array('account'=>$accountData['account'], 'password'=>$password));
                           
            // On success, redirect to read view
            $this->view->setScriptPath(VIEWS . '/scripts');
            $this->_forward('view', null, null, array('id' => $userId));
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
            $this->message("Unable to create account:<br>$errorString",
                           self::M_WARNING);
                           
            // On error, redirect back to the create action.
            $this->_forward('create');
        }
    }

    /**
     * checkDnAction() - Check to see if the specified LDAP
     * distinguished name (DN) exists in the system's specified LDAP directory.
     */
    public function checkdnAction()
    {
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
                // The expected error is LDAP_NO_SUCH_OBJECT, meaning that the
                // DN does not exist.
                if ($e->getErrorCode() ==
                    Zend_Ldap_Exception::LDAP_NO_SUCH_OBJECT) {
                    $msg = "$dn does NOT exist";
                } else {
                    $msg .= 'Unknown error while checking DN: '
                          . $e->getMessage();
                }
            }
        }
        echo $msg;
    }

    /**
     * assignroleAction() - ???
     *
     * @todo fix the camelCase in the name of this function
     * @todo clean up this function
     */
    public function assignroleAction()
    {
        $req = $this->getRequest();
        $userId = $req->getParam('id');
        $db = $this->_user->getAdapter();
        $ret = $this->_user->find($userId)->toArray();
        $userName = $ret[0]['account'];
        $qry = $db->select()->from(array(
            'r' => 'roles'
        ), array(
            'role_id' => 'r.id',
            'role_name' => 'r.name'
        ))
        ->join(array(
            'ur' => 'user_roles'
        ), 'ur.role_id = r.id', array())
        ->where('ur.user_id = ?', $userId)
        ->where('r.nickname !=?', 'auto_role');
        $assignRoles = $db->fetchAll($qry);
        $qry->reset();
        $ret = $this->_user->find($userId)->toArray();
        $autoRole = $ret[0]['auto_role'];
        $qry->from('roles', array(
            'role_id' => 'id',
            'role_name' => 'name'
        ))->where('nickname != ?', 'auto_role');
        $allRoles = $db->fetchAll($qry);
        foreach ($allRoles as $v) {
            if (!in_array($v, $assignRoles)) {
                $availableRoles[] = $v;
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
        ->where('r.name = ?', $autoRole);
        $assignPrivileges = $db->fetchAll($qry);
        $this->view->assign('user_id', $userId);
        $this->view->assign('user_name', $userName);
        $this->view->assign('assign_roles', $assignRoles);
        $this->view->assign('available_roles', $availableRoles);
        $this->view->assign('assign_privileges', $assignPrivileges);
        if ('assign' == $req->getParam('do')) {
            $assignRoles = $req->getParam('assign_roles');
            $assignPrivileges = $req->getParam('assign_privileges');
            $db->delete('user_roles', 'user_id = ' . $userId);
            foreach ($assignRoles as $v) {
                $db->insert('user_roles', array(
                    'user_id' => $userId,
                    'role_id' => $v
                ));
            }
            if (!empty($assignPrivileges)) {
                $qry = $db->select()->from(array(
                    'r' => 'roles'
                ), array(
                    'role_id' => 'r.id'
                ))->where('r.name = ?', $autoRole);
                $ret = $db->fetchRow($qry);
                if (!empty($ret)) {
                    $roleId = $ret['role_id'];
                    $db->insert('user_roles', array(
                        'user_id' => $userId,
                        'role_id' => $roleId
                    ));
                    $db->delete('role_functions', 'role_id = ' . $roleId);
                    foreach ($assignPrivileges as $v) {
                        $db->insert('role_functions', array(
                            'role_id' => $roleId,
                            'function_id' => $v
                        ));
                    }
                } else {
                    $db->insert('roles', array(
                        'name' => $autoRole,
                        'nickname' => 'auto_role',
                        'desc' => 'extra role for user'
                    ));
                    $roleId = $db->LastInsertId();
                    $db->insert('user_roles', array(
                        'user_id' => $userId,
                        'role_id' => $roleId
                    ));
                    foreach ($assignPrivileges as $v) {
                        $db->insert('role_functions', array(
                            'role_id' => $roleId,
                            'function_id' => $v
                        ));
                    }
                }
            }
            $this->message('assign role and privileges successfully.', 
                            self::M_NOTICE);
            $this->_redirect('panel/account/sub/assignrole/id/' . $userId);
        } else {
            $this->render();
        }
    }
    /**
     * searchprivilegeAction() - ???
     */
    public function searchprivilegeAction()
    {
        $req = $this->_request;
        $db = $this->_user->getAdapter();
        $userId = $req->getParam('id');
        $ret = $this->_user->find($userId)->toArray();
        $autoRole = $ret[0]['auto_role'];
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
        ->where('r.name = ?', $autoRole);
        $assignPrivileges = $db->fetchAll($qry);
        $roles = substr(str_replace('-', ',', $req->getParam('assign_roles')),
                         0, -1);
        $qry->reset();
        $qry->from('functions', array(
            'function_id' => 'id',
            'function_name' => 'name'
        ));
        $allPrivileges = $db->fetchAll($qry);
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
            $existPrivileges = array_merge($db->fetchAll($qry),
                $assignPrivileges);
        } else {
            $existPrivileges = $assignPrivileges;
        }
        foreach ($allPrivileges as $v) {
            if (!in_array($v, $existPrivileges)) {
                $availablePrivileges[] = $v;
            }
        }
        $this->view->assign('available_privileges', $availablePrivileges);
        $this->_helper->layout->setLayout('ajax');
        $this->render('availableprivi');
    }

    /**
     * logAction() - List all the users log message.
     */
    public function logAction()
    {
        // Set up the query to get the full list of user logs
        $db = $this->_user->getAdapter();
        $qry = $db->select()
                  ->from(array('al' => 'account_logs'),
                         array('timestamp', 'event', 'user_id', 'message'))
                  ->joinLeft(array('u'=>'users'),
                             'al.user_id = u.id',
                             'account');

        $qv = $this->_request->getParam('qv');
        if (!empty($qv)) {
            $fid = $this->_request->getParam('fid');
            $qry->where("$fid = '$qv'");
        }
        $qry->order("timestamp DESC");
        $qry->limitPage($this->_paging['currentPage'], 
                        $this->_paging['perPage']);
        $logList = $db->fetchAll($qry);
        
        // Assign view outputs
        $this->view->assign('logList', $logList);
        $this->render();
    }

    /**
     * random a complexity password when created a user
     */
    protected function _randomPassword()
    {
        $passLengthMin = Config_Fisma::readSysConfig('pass_min');
        $passLengthMax = $passLengthMin+5;
        $passNum = Config_Fisma::readSysConfig('pass_numerical');
        $passUpper = Config_Fisma::readSysConfig('pass_uppercase');
        $passLower = Config_Fisma::readSysConfig('pass_lowercase');
        $passSpecial = Config_Fisma::readSysConfig('pass_special');
        
        $flag = 0;
        $password = "";
        $length = rand($passLengthMin, $passLengthMax);
        if (true == $passUpper) {
            $possibleCharactors[] = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
            $flag++;
        }
        if (true == $passLower) {
            $possibleCharactors[] = "abcdefghijklmnopqrstuvwxyz";
            $flag++;
        }
        if (true == $passNum) {
            $possibleCharactors[] = "0123456789";
            $flag++;
        }
        if (true == $passSpecial) {
            $possibleCharactors[] = "!@#$%^&*()_+=-`~\|':;?><,.[]{}/";
            $flag++;
        }

        while (strlen($password) < $length) {
            if (0 == $flag) {
                $password .= rand();
            } else {
                foreach ($possibleCharactors as $row) {
                    $password .= substr($row, (rand()%(strlen($row))), 1);
                }
            }
        }
        return $password;
    }

    protected function _getPasswordRequirements()
    {
        $requirements[] = "Length must be between "
                        . Config_Fisma::readSysConfig('pass_min')
                        . " and "
                        . Config_Fisma::readSysConfig('pass_max')
                        . " characters long.";
        if (Config_Fisma::readSysConfig('pass_uppercase') == 1) {
            $requirements[] = "Must contain at least 1 upper case character (A-Z)";
        }
        if (Config_Fisma::readSysConfig('pass_lowercase') == 1) {
            $requirements[] = "Must contain at least 1 lower case character (a-z)";
        }
        if (Config_Fisma::readSysConfig('pass_numerical') == 1) {
            $requirements[] = "Must contain at least 1 numeric digit (0-9)";
        }
        if (Config_Fisma::readSysConfig('pass_special') == 1) {
            $requirements[] = htmlentities("Must contain at least 1 special character (!@#$%^&*-=+~`_)");
        }
        return $requirements;
    }
}
