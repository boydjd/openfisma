<?php
/**
 * AccountController.php
 *
 * Account Controller
 *
 * @package   Controller
 * @author     Ryan  ryan at users.sourceforge.net
 * @copyright  (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license    http://www.openfisma.org/mw/index.php?title=License
 * @version $Id$
 */
require_once (CONTROLLERS . DS . 'PoamBaseController.php');
require_once (MODELS . DS . 'user.php');
require_once (MODELS . DS . 'system.php');
require_once ('Pager.php');
require_once 'Zend/Date.php';
require_once 'Zend/Filter/Input.php';
require_once 'Zend/Validate/Between.php';
/**
 * Maintaining the user account
 * @package Controller
 * @author     Ryan  ryan at users.sourceforge.net
 * @copyright  (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license    http://www.openfisma.org/mw/index.php?title=License
 */
class AccountController extends PoamBaseController
{
    private $_user = null;
    protected $_sanity = array(
        'data' => 'user',
        'filter' => array(
            '*' => array(
                'StringTrim',
                'StripTags'
            )
        ) ,
        'validator' => array(
            'name_first' => 'Alnum',
            'name_last' => 'Alnum',
            'phone_office' => 'Alnum',
            'phone_mobile' => array(
                'allowEmpty' => TRUE,
                'Digits'
            ) ,
            'email' => 'EmailAddress',
            'title' => 'Alnum',
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
    public function init()
    {
        parent::init();
        $this->_user = new User();
    }
    public function preDispatch()
    {
        parent::preDispatch();
        $this->_paging_base_path.= '/panel/account/sub/list';
    }
    /**
     *  render the searching boxes and keep the searching criteria
     */
    public function searchboxAction()
    {
        $db = Zend_Registry::get('db');
        $fid_array = array(
            'lastname' => 'Last Name',
            'firstname' => 'First Name',
            'officephone' => 'Office Phone',
            'mobile' => 'Mobile Phone',
            'email' => 'Email',
            'role' => 'Role',
            'title' => 'Title',
            'status' => 'Status',
            'account' => 'Username'
        );
        $this->view->assign('fid_array', $fid_array);
        $req = $this->_request;
        $this->_paging_base_path = $req->getBaseUrl() . 
                '/panel/account/sub/list';
        $this->_paging['currentPage'] = $req->getParam('p', 1);
        $fid = $req->getParam('fid');
        $qv = $req->getParam('qv');
        $query = $db->select()->from(array(
                'u' => 'users'
            ), array(
                'count' => 'COUNT(u.id)'
            ));
        $res = $db->fetchRow($query);
        $count = $res['count'];
        $this->_paging['totalItems'] = $count;
        $this->_paging['fileName'] = "{$this->_paging_base_path}/p/%d";
        $pager = & Pager::factory($this->_paging);
        $this->view->assign('fid', $fid);
        $this->view->assign('qv', $qv);
        $this->view->assign('total', $count);
        $this->view->assign('links', $pager->getLinks());
        $this->render();
    }
    /**
     * List all the users
     */
    public function listAction()
    {
        $user = new user();
        $db = Zend_Registry::get('db');
        $req = $this->getRequest();
        $qv = $req->getParam('qv');
        $fid = $req->getParam('fid');
        $qry = $user->select()->setIntegrityCheck(false)->from(array(
            'u' => 'users'
        ) , array(
            'id' => 'id',
            'username' => 'account',
            'lastname' => 'name_last',
            'firstname' => 'name_first',
            'officephone' => 'phone_office',
            'mobile' => 'phone_mobile',
            'email' => 'email'
        ));
        if (!empty($qv)) {
            $fid_array = array(
                'name_last' => 'lastname',
                'name_first' => 'firstname',
                'phone_office' => 'officephone',
                'phone_mobile' => 'mobile',
                'email' => 'email',
                'r.role_name' => 'role',
                'title' => 'title',
                'is_active' => 'status',
                'account' => 'account'
            );
            foreach ($fid_array as $k => $v) {
                if ($v == $fid) {
                    $qry->where("$k = '$qv'");
                }
            }
        }
        $qry->order("name_last ASC");
        $qry->limitPage($this->_paging['currentPage'], 
                        $this->_paging['perPage']);
        $data = $user->fetchAll($qry);
        $user_list = $data->toArray();
        foreach ($user_list as $row) {
            $ret = $user->getRoles($row['id'], array(
                'nickname' => 'nickname',
                'id' => 'id'
            ));
            $role_list[$row['id']] = '';
            foreach ($ret as $v) {
                $role_list[$row['id']].= $v['nickname'] . ', ';
            }
            $role_list[$row['id']] = substr($role_list[$row['id']], 0, -2);
        }
        $this->view->assign('role_list', $role_list);
        $this->view->assign('user_list', $user_list);
        $this->render();
    }
    /**
     *  view the user's detail information
     */
    public function viewAction()
    {
        $req = $this->getRequest();
        $id = $req->getParam('id');
        $v = $req->getParam('v');
        assert($id);
        $user = new User();
        $sys = new System();
        $db = $user->getAdapter();
        $qry = $user->select()->setIntegrityCheck(false);
        /** get user detail */
        $qry->from(array(
            'u' => 'users'
        ), array(
            'lastname' => 'name_last',
            'firstname' => 'name_first',
            'officephone' => 'phone_office',
            'mobilephone' => 'phone_mobile',
            'email' => 'email',
            'title' => 'title',
            'status' => 'is_active',
            'username' => 'account',
            'password' => 'password'
        ))->where("u.id = $id");
        $user_detail = $user->fetchRow($qry)->toArray();
        $ret = $user->getRoles($id, array(
            'role_name' => 'name',
            'role_id' => 'id'
        ));
        $count = count($ret);
        if ($count > 1) {
            $roles = '';
            foreach ($ret as $row) {
                $roles.= ' ' . $row['role_name'] . ', ';
            }
            $roles = substr($roles, 0, -2);
        } elseif ($count == 1) {
            $roles = $ret[0]['role_id'];
        } else {
            $roles = null;
        }
        $query = $user->getAdapter()->select()->from('roles', array(
            'id',
            'name'
        ))->where('nickname != ?', 'auto_role')->order('name ASC');
        $role_list = $user->getAdapter()->fetchPairs($query);
        $this->view->assign('id', $id);
        $this->view->assign('user', $user_detail);
        $this->view->assign('role_count', $count);
        $this->view->assign('roles', $roles);
        $this->view->assign('role_list', $role_list);
        $this->view->assign('my_systems', $user->getMySystems($id));
        $this->view->assign('all_sys', $sys->getList());
        $this->render($v);
    }
    /**
     *  update user's information
     */
    public function updateAction()
    {
        $req = $this->getRequest();
        $id = $req->getParam('id');
        $u_data = $req->getPost('user');
        $u_role = $req->getPost('user_role');
        $sys_data = $req->getPost('system');
        $confirm_pwd = $req->getPost('password_confirm');
        $db = $this->_user->getAdapter();
        if ( 'database' == readSysConfig('auth_type') && 
            empty($u_data['account']) ) {
            $msg = "Account can not be null.";
            $this->message($msg, self::M_WARNING);
            $this->_forward('view', null, null, array(
                'v' => 'edit'
            ));
            return;
        }
        if ( 'ldap' == readSysConfig('auth_type') ) {
            $u_data['account'] = $u_data['ldap_dn'];
        }
        if( !empty($u_data['password']) ) {
            /// @todo validate the password complexity
            if ($u_data['password'] != $confirm_pwd) {
                $msg = "Password does not match confirmation.";
                $this->message($msg, self::M_WARNING);
                $this->_forward('view', null, null, array(
                    'v' => 'edit'
                ));
                return;
            }
            $u_data['password'] = md5($u_data['password']);
        } else {
            unset($u_data['password']);
        }
        if (!empty($u_data)) {
            if ($u_data['is_active'] == 0) {
                $u_data['termination_ts'] = self::$now->toString("Y-m-d H:i:s");
            }
            $n = $this->_user->update($u_data, "id=$id");
            if ($n > 0) {
                $this->_user->log(User::MODIFICATION, 
                                   $this->me->id,
                                   $u_data['account']);
            }
            if (!empty($sys_data)) {
                $my_sys = $this->_user->getMySystems($id);
                $new_sys = array_diff($sys_data, $my_sys);
                $remove_sys = array_diff($my_sys, $sys_data);
                $n = $this->_user->associate($id, User::SYS, $new_sys);
                $n = $this->_user->associate($id, User::SYS, $remove_sys,
                                              true);
            }
        }
        if (!empty($u_role)) {
            $qry = $db->select()->from(array(
                'ur' => 'user_roles'
            ) , 'ur.*')->join(array(
                'r' => 'roles'
            ) , 'ur.role_id = r.id', array())
            ->where('user_id = ?', $id)
            ->where('r.nickname != ?', 'auto_role');
            $ret = $db->fetchAll($qry);
            $count = count($ret);
            if (1 == $count) {
                $db->update('user_roles', array(
                    'role_id' => $u_role
                ) , 'user_id =' . $id);
            } elseif (0 == $count) {
                $db->insert('user_roles', array(
                    'role_id' => $u_role,
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
     * Delete an account
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
    /**
     *  only render the account creation page
     */
    public function createAction()
    {
        $system = new system();
        $db = $system->getAdapter();
        $qry = $db->select()->from('roles', array(
            'id',
            'name'
        ))->where('nickname != ?', 'auto_role');
        $ret = $db->fetchAll($qry);
        foreach ($ret as $row) {
            $roles[$row['id']] = $row['name'];
        }
        $this->view->roles = $roles;
        $this->view->systems = $system->getList();
        $this->render();
    }
    /**
     *  create a new account
     */
    public function saveAction()
    {
        $newAccount=$this->_request->getParam('user');
        $systems=$this->_request->getParam('system');
        if ( 'ldap' == readSysConfig('auth_type') ) {
            $newAccount['account'] = $newAccount['ldap_dn'];
        }
        if ( 'database' == readSysConfig('auth_type') ) {
            $newAccount['password'] = md5($newAccount['password']);
        }
        $newAccount['created_ts'] = self::$now->toString('Y-m-d H:i:s');
        $newAccount['auto_role'] = $newAccount['account'].'_r';

        $userId = $this->_user->insert($newAccount);
        $roleId = $this->_request->getParam('user_role_id');
        $this->_user->associate($userId, User::ROLE, $roleId);
     
        if ( !empty($systems) ) {
            $this->_user->associate($userId, User::SYS, $systems);
        }

        $this->_user->log(User::CREATION, $this->me->id,
                         'create user('.$newAccount['account'].')');
        $this->message("User ({$newAccount['account']}) added",
                       self::M_NOTICE);
        $this->_forward('create');
    }
    /**
     * Assign role to an account 
     */
    /**
     * Make sure if the dn provided by operator does exist on 
     * the configured LDAP service.
     *
     * @todo language check
     */
    public function checkdnAction()
    {
        $dn = $this->_request->getParam('dn');
        $this->_helper->layout->setLayout('ajax');
        if ( empty($dn) ) {
            echo '<font color="red">Dn is missing</font>';
        } else {
            $multiOptions = readLdapConfig();
            foreach ($multiOptions as $name=>$options) {
                @$ds = ldap_connect($options['host'], $options['port']);
                ldap_set_option($ds, LDAP_OPT_PROTOCOL_VERSION, 3);
                ldap_bind($ds, $options['username'], $options['password']);
                $ret = ldap_explode_dn($dn, 3);
                $result = @ldap_search($ds, $options['baseDn'],
                          'uid='.$ret[0]);
                $entries = @ldap_get_entries($ds, $result);
                if ( $entries['count'] != 0 && $dn == $entries[0]['dn'] ) {
                    $flag = true;
                    echo'<font color="green">The Dn exists</font>';
                    return;
                }
            }
            if ( empty($flag) ) {
                echo'<font color="red">The Dn does not exist</font>';
            }
        }
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
        ) , array(
            'role_id' => 'r.id',
            'role_name' => 'r.name'
        ))
        ->join(array(
            'ur' => 'user_roles'
        ) , 'ur.role_id = r.id', array())
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
        ) , array(
            'function_id' => 'f.id',
            'function_name' => 'f.name'
        ))
        ->join(array(
            'rf' => 'role_functions'
        ) , 'rf.function_id = f.id', array())
        ->join(array(
            'ur' => 'user_roles'
        ) , 'ur.role_id = rf.role_id', array())
        ->join(array(
            'r' => 'roles'
        ) , 'r.id = ur.role_id', array())
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
            ) , array(
                'function_id' => 'f.id',
                'function_name' => 'f.name'
            ))
            ->join(array(
                'rf' => 'role_functions'
            ) , 'rf.function_id = f.id', array())
            ->where('rf.role_id in (' . $roles . ')');
            $exist_privileges = array_merge(
                                 $db->fetchAll($qry) , $assign_privileges
                                );
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
}
