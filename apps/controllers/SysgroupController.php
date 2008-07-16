<?php
/**
 * @file Sys_GroupController.php
 *
 * System_group Controller
 *
 * @author     Ryan <ryan.yang@reyosoft.com>
 * @copyright  (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license    http://www.openfisma.org/mw/index.php?title=License
 * @version $Id$
*/

require_once CONTROLLERS . DS . 'SecurityController.php';
require_once MODELS . DS . 'sysgroup.php';
require_once 'Pager.php';

class SysgroupController extends SecurityController
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
        $this->_sysgroup = new Sysgroup();
    }

    public function preDispatch()
    {
        $req = $this->getRequest();
        $this->_paging_base_path = $req->getBaseUrl() .'/panel/sysgroup/sub/list';
        $this->_paging['currentPage'] = $req->getParam('p',1);
        if(!in_array($req->getActionName(),array('login','logout') )){
            // by pass the authentication when login
            parent::preDispatch();
        }
    }   
     
    public function searchboxAction()
    {
        $req = $this->getRequest();
        $fid = $req->getParam('fid');
        $qv = $req->getParam('qv');
        $query = $this->_sysgroup->select()->from(array('sg'=>'system_groups'),array('count'=>'COUNT(sg.id)'))
                                           ->where('sg.is_identity = 0');
        $res = $this->_sysgroup->fetchRow($query)->toArray();
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
        $query = $this->_sysgroup->select()->from('system_groups','*')
                                          ->where('is_identity = 0');
        if(!empty($value)){
            $query->where("$field = ?",$value);
        }
        $query->order('name ASC')
              ->limitPage($this->_paging['currentPage'],$this->_paging['perPage']);
        $sysgroup_list = $this->_sysgroup->fetchAll($query)->toArray();
        $this->view->assign('sysgroup_list',$sysgroup_list);
        $this->render();
    }
   
    public function createAction()
    {
        $req = $this->getRequest();
        if('save' == $req->getParam('s')){
            $post = $req->getPost();
            foreach($post as $k=>$v){
                if('sysgroup_' == substr($k,0,9)){
                    $k = substr($k,9);
                    $data[$k] = $v;
                }
            }
            $data['is_identity'] = 0;
            $res = $this->_sysgroup->insert($data);
            if(!$res){
                $msg = "Error Create System Group";
            } else {
                $msg = "Successfully Create a System Group.";
            }
            $this->message($msg,self::M_NOTICE);
        }
        $this->render();
    }

    public function deleteAction()
    {
        $req = $this->getRequest();
        $id  = $req->getParam('id');
        $db = $this->_sysgroup->getAdapter();
        $qry = $db->select()->from('systemgroup_systems')->where('sysgroup_id = '.$id);
        $result = $db->fetchCol($qry);
        if(!empty($result)){
            $msg = 'This System Group have been used, You could not delete it';
        }else{
            //$res = $this->_sysgroup->delete('id = '.$id);
            if(!$res){
                $msg = "Error for Delete System Group";
            } else {
                $msg = "Successfully Delete a System Group.";
            }
        }
        $this->message($msg,self::M_NOTICE);
        $this->_forward('list');
    }

    public function viewAction()
    {
        $req = $this->getRequest();
        $id  = $req->getParam('id');
        $result = $this->_sysgroup->find($id)->toArray();
        foreach($result as $v){
            $sysgroup_list = $v;
        }
        $this->view->assign('id',$id);
        $this->view->assign('sysgroup',$sysgroup_list);
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
            if('sysgroup_' == substr($k,0,9)){
                $k = substr($k,9);
                $data[$k] = $v;
            }
        }
        $res = $this->_sysgroup->update($data,'id = '.$id);
        if(!$res){
            $msg = "Edit System Group Failed";
        } else {
            $msg = "Successfully Edit System Group.";
        }
        $this->message($msg,self::M_NOTICE);
        $this->_forward('view',null,'id = '.$id);
    }

}
