<?php
/**
 * @file RoleController.php
 *
 * Role Controller
 *
 * @author     Ryan <ryan.yang@reyosoft.com>
 * @copyright  (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license    http://www.openfisma.org/mw/index.php?title=License
 * @version $Id$
*/

require_once CONTROLLERS . DS . 'SecurityController.php';
require_once MODELS . DS . 'role.php';
require_once 'Pager.php';

class RoleController extends SecurityController
{
    private $_paging = array(
            'mode'        =>'Sliding',
            'append'      =>false,
            'urlVar'      =>'p',
            'path'        =>'',
            'currentPage' => 1,
            'perPage'=>20);
    
    public function init()
    {
        parent::init();
        $this->_role = new Role();
    }

    public function preDispatch()
    {
        $req = $this->getRequest();
        $this->_paging_base_path = $req->getBaseUrl() .'/panel/role/sub/list';
        $this->_paging['currentPage'] = $req->getParam('p',1);
        if(!in_array($req->getActionName(),array('login','logout') )){
            parent::preDispatch();
        }
    }   
     
    public function searchboxAction()
    {
        $req = $this->getRequest();
        $fid = $req->getParam('fid');
        $qv = $req->getParam('qv');
        $query = $this->_role->select()->from(array('r'=>'roles'),array('count'=>'COUNT(r.id)'));
        $res = $this->_role->fetchRow($query)->toArray();
        $count = $res['count'];
        $this->_paging['totalItems'] = $count;
        $this->_paging['fileName'] = "{$this->_paging_base_path}/p/%d";
        $pager = &Pager::factory($this->_paging);
        $this->view->assign('fid',$fid);
        $this->view->assign('qv',$qv);
        $this->view->assign('total',$count);
        $this->view->assign('links',$pager->getLinks());
        $this->render();
    }

    public function listAction()
    {
        $req = $this->getRequest();
        $field = $req->getParam('fid');
        $value = trim($req->getParam('qv'));
        $query = $this->_role->select()->from('roles','*');
        if(!empty($value)){
            $query->where("$field = ?",$value);
        }
        $query->order('name ASC')
              ->limitPage($this->_paging['currentPage'],$this->_paging['perPage']);
        $role_list = $this->_role->fetchAll($query)->toArray();                                    
        $this->view->assign('role_list',$role_list);
        $this->render();
    }
   
    public function createAction()
    {
        $req = $this->getRequest();
        if('save' == $req->getParam('s')){
            $post = $req->getPost();
            foreach($post as $k=>$v){
                if('role_' == substr($k,0,5)){
                    $k = substr($k,5);
                    $data[$k] = $v;
                }
            }
            $res = $this->_role->insert($data);
            if(!$res){
                $msg = "Error Create Role";
                $model = self::M_WARNING;
            } else {
                $msg = "Successfully Create a Role.";
                $model = self::M_NOTICE;
            }
            $this->message($msg,$model);
        }
        $this->render();
    }

    public function deleteAction()
    {
        $req = $this->getRequest();
        $id  = $req->getParam('id');
        $db = $this->_role->getAdapter();
        $qry = $db->select()->from('user_roles')->where('role_id = '.$id);
        $result = $db->fetchCol($qry);
        if(!empty($result)){
            $msg = 'This role have been used, You could not to delete';
        }else{
            $res = $this->_role->delete('id = '.$id);
            if(!$res){
                $msg = "Error for Delete Role";
                $model = self::M_WARNING;
            }else {
                $msg = "Successfully Delete a Role.";
                $model = self::M_NOTICE;
            }
        }
        $this->message($msg,$model);
        $this->_forward('list');
    }

    public function viewAction()
    {
        $req = $this->getRequest();
        $id  = $req->getParam('id');
        $result = $this->_role->find($id)->toArray();
        $role_list = $result[0];
        $this->view->assign('id',$id);
        $this->view->assign('role',$role_list);
        if('edit' == $req->getParam('v')){
            $this->render('edit');
        }else{
            $this->render();
        }
    }

    public function updateAction()
    {
        $req = $this->getRequest();
        $id  = $req->getParam('id');
        $post = $req->getPost();
        foreach($post as $k=>$v){
            if('role_' == substr($k,0,5)){
                $k = substr($k,5);
                $data[$k] = $v;
            }
        }
        $res = $this->_role->update($data,'id = '.$id);
        if(!$res){
            $msg = "Edit Role Failed";
            $model = self::M_WARNING;
        } else {
            $msg = "Successfully Edit Role.";
            $model = self::M_NOTICE;
        }
        $this->message($msg,$model);
        $this->_forward('view',null,'id = '.$id);
    }

    public function rightAction()
    {
        $req = $this->getRequest();
        $do = $req->getParam('do');
        $role_id = $req->getParam('id');
        $screen_name = $req->getParam('screen_name');
        $db = $this->_role->getAdapter();
        $qry = $db->select()->from(array('f'=>'functions'),array('function_id'=>'id','function_name'=>'name'))
                ->join(array('rf'=>'role_functions'),'f.id = rf.function_id',array())
                ->where('rf.role_id = ?',$role_id);
        $exist_functions = $db->fetchAll($qry);
        if('search_function' == $do){
            $qry->reset();
            $qry->from('functions',array('function_id'=>'id','function_name'=>'name'));
            if(!empty($screen_name)){
                $qry->where('screen = ?',$screen_name);
            }
            $all_functions = $db->fetchAll($qry);
            foreach($all_functions as $v){
                if(!in_array($v,$exist_functions)){
                    $available_functions[] = $v;
                }
            }
            $this->_helper->layout->setLayout('ajax');
            $this->view->assign('available_functions',$available_functions);
            $this->render('availablefunc');
        }elseif('update' == $do){
            $function_ids = $req->getParam('exist_functions');
            $errno = 0;
            $qry = "DELETE FROM `role_functions` WHERE role_id =".$role_id;
            $res = $db->query($qry);
            if(!$res){
                $errno++;
            }
            foreach($function_ids as $fid){
                $res = $db->insert('role_functions',array('role_id'=>$role_id,'function_id'=>$fid));
                if(!$res){
                    $errno++;
                }
            }
            if($errno > 0){
                $msg = "Set right for role failed.";
                $model = self::M_WARNING;
            }else{
                $msg = "Successfully set right for role.";
                $model = self::M_NOTICE;
            }
            $this->message($msg,$model);
            $this->_redirect('panel/role/sub/right/id/'.$role_id);
        }else{
            $qry = $db->select()->from('roles',array('id','name'))
                                ->where('id = ?',$role_id);
            $role = $db->fetchRow($qry);
            $qry = $db->select()->from('functions',array('screen_name'=>'screen'))->group('screen');
            $screen_list = $db->fetchAll($qry);
            $this->view->assign('role',$role);
            $this->view->assign('screen_list',$screen_list);
            $this->view->assign('exist_functions',$exist_functions);
            $this->render();
        }
    }
}
