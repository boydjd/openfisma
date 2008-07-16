<?php
/**
 * @file SystemController.php
 *
 * System Controller
 *
 * @author     Ryan <ryan.yang@reyosoft.com>
 * @copyright  (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license    http://www.openfisma.org/mw/index.php?title=License
 * @version $Id$
*/

require_once 'Zend/Controller/Action.php';
require_once( CONTROLLERS . DS . 'SecurityController.php');
require_once( MODELS . DS .'user.php');
require_once( MODELS . DS .'system.php');
require_once('Pager.php');
require_once 'Zend/Date.php';

class SystemController extends SecurityController
{
    private $_paging = array(
            'mode'        =>'Sliding',
            'append'      =>false,
            'urlVar'      =>'p',
            'path'        =>'',
            'currentPage' => 1,
            'perPage'=>20);
    private $_user = null;

    public function init()
    {
        parent::init();
        $this->_system = new System();
    }

    public function preDispatch()
    {
        $req = $this->getRequest();
        $this->_paging_base_path = $req->getBaseUrl() .'/panel/system/sub/list';
        $this->_paging['currentPage'] = $req->getParam('p',1);
        if(!in_array($req->getActionName(),array('login','logout') )){
            // by pass the authentication when login
            parent::preDispatch();
        }
    }

    public function listAction()
    {
        $req = $this->getRequest();
        $field = $req->getParam('fid');
        $value = trim($req->getParam('qv'));
        $query = $this->_system->select()->from('systems','*');
        if(!empty($value)){
            $query->where("$field = ?",$value);
        }
        $query->order('name ASC')
              ->limitPage($this->_paging['currentPage'],$this->_paging['perPage']);
        $system_list = $this->_system->fetchAll($query)->toArray();                                    
        $this->view->assign('system_list',$system_list);
        $this->render();
    }

    public function searchboxAction()
    {
        $req = $this->getRequest();
        $fid = $req->getParam('fid');
        $qv = $req->getParam('qv');
        $query = $this->_system->select()->from(array('s'=>'systems'),array('count'=>'COUNT(s.id)'));
        $res = $this->_system->fetchRow($query)->toArray();
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

    public function createAction()
    {
        $req = $this->getRequest();
        $db = $this->_system->getAdapter();
        $query = $db->select()->from(array('sg'=>'system_groups'),'*')
                    ->where('is_identity = ?',0);
        $sg_list = $db->fetchAll($query);
        $this->view->assign('sg_list',$sg_list);
        if('save' == $req->getParam('s')){
            $errno = 0;
            $post = $req->getPost();
            foreach($post as $key=>$value){
                if('system_' == substr($key,0,7)){
                    $data[substr($key,7)] = $value;
                }
            }
            $id = $this->_system->insert($data);
            $this->_user = new user();
            $this->me->systems = $this->_user->getMySystems($this->me->id);

            $data = array('name'=>$post['system_name'],
                          'nickname'=>$post['system_nickname'],
                          'is_identity'=>1);
            $res = $db->insert('system_groups',$data);
            if(!$res){
                $errno++;
            }
            $sysgroup_id = $db->LastInsertId();
            $res = $db->delete('systemgroup_systems','system_id = '.$id);
            $data = array('system_id'=>$id,'sysgroup_id'=>$sysgroup_id);
            $res = $db->insert('systemgroup_systems',$data);
            if(!$res){
                $errno++;
            }
            foreach($post as $key=>$value){
                if('sysgroup_' == substr($key,0,9)){
                    $data = array('system_id'=>$id,'sysgroup_id'=>$value);
                    $res = $db->insert('systemgroup_systems',$data);
                    if(!$res){
                        $errno++;
                    }
                }
            }
            if($errno > 0){
                $msg = "Systems added Failed";
            } else {
                $msg = "Systems added Successfully.";
            }
            $this->message($msg,self::M_NOTICE);
        }
        $this->render();
    }

    public function deleteAction()
    {
        $errno = 0;
        $req = $this->getRequest();
        $id  = $req->getParam('id');
        $db = $this->_system->getAdapter();
        $qry = $db->select()->from('poams')->where('system_id = '.$id);
        $result1 = $db->fetchAll($qry);
        $qry->reset();
        $qry = $db->select()->from('assets')->where('system_id = '.$id);
        $result2 = $db->fetchAll($qry);
        if(!empty($result1) || !empty($result2)){
            $msg = "This system have been used,You could not to delete it";
        }else{
            $res = $this->_system->delete('id = '.$id);
            if(!$res){
                $errno++;
            }
            $this->_user = new user();
            $this->me->systems = $this->_user->getMySystems($this->me->id);
            $res = $this->_system->getAdapter()->delete('systemgroup_systems','system_id = '.$id);
            if(!$res){
                $errno++;
            }
            if($errno > 0){
                $msg = "System delete Error";
            } else {
                $msg = "System delete Successfully";
            }
        }
        $this->message($msg,self::M_NOTICE);
        $this->_forward('list');
    }

    public function viewAction()
    {
        $req = $this->getRequest();
        $db = $this->_system->getAdapter();
        $id  = $req->getParam('id');
        $query = $this->_system->select()->from('systems','*')->where('id = '.$id);
        $system = $this->_system->getAdapter()->fetchRow($query);
        $query->reset();
        $query = $db->select()->from(array('sgs'=>'systemgroup_systems'),array())
                              ->join(array('sg'=>'system_groups'),'sg.id = sgs.sysgroup_id','*')
                              ->where('sgs.system_id = ?',$id)
                              ->where('sg.is_identity = 0');
        $user_sysgroup_list = $db->fetchAll($query);
        $this->view->assign('user_sysgroup_list',$user_sysgroup_list);
        $this->view->assign('system',$system);
        if('edit' == $req->getParam('v')){
            $query = $db->select()->from(array('sg'=>'system_groups'),'*')
                                  ->where('is_identity = ?',0);
            $sg_list = $db->fetchAll($query);
            $this->view->assign('id',$id);
            $this->view->assign('sg_list',$sg_list);
            $this->render('edit');
        }else{
            $this->render();
        }
    }

    public function updateAction()
    {
        $req = $this->getRequest();
        $db  = $this->_system->getAdapter();
        $id  = $req->getParam('id');
        $errno = 0;$res = 0;
        $post = $req->getPost();
        foreach($post as $key=>$value){
            if('system_' == substr($key,0,7)){
                $data[substr($key,7)] = $value;
            }
        }
        $res = $this->_system->update($data,'id = '.$id);
        if(!$res){
            $errno++;
        }
        $sysgroup_data['name'] = $data['name'];
        $sysgroup_data['nickname'] = $data['nickname'];
        $query = $db->select()->from(array('sgs'=>'systemgroup_systems'),array())
                              ->join(array('sg'=>'system_groups'),'sgs.sysgroup_id = sg.id','id')
                              ->where('sgs.system_id = ?',$id)
                              ->where('sg.is_identity = 1');
        $result = $db->fetchRow($query);
        $res = $db->update('system_groups',$sysgroup_data,'id = '.$result['id']);
        if(!$res){
            $errno++;
        }
        foreach($post as $key=>$value){
            if('sysgroup_' == substr($key,0,9)){
                $data = array('sysgroup_id'=>$value);
                $res = $db->update('systemgroup_systems',$data,'system_id = '.$id);
                if(!$res){
                    $errno++;
                }
            }
        }
        if($errno > 0){
            $msg = "System update Error";
        } else {
            $msg = "System update Successfully";
        }
        $this->message($msg,self::M_NOTICE);
        $this->_forward('view',null,'id='.$id);

    }

}
