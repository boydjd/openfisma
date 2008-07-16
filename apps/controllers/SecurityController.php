<?php
/**
 * @file SecurityController.php
 *
 * Security Controller
 *
 * @author     Jim <jimc@reyosoft.com>
 * @copyright  (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license    http://www.openfisma.org/mw/index.php?title=License
 * @version $Id$
*/

require_once 'Zend/Controller/Action.php';
require_once 'Zend/Date.php';
require_once 'Zend/Auth.php';
require_once 'Zend/Db.php';
require_once MODELS . DS . 'user.php';

require_once 'Zend/Acl.php';
require_once 'Zend/Acl/Role.php';
require_once 'Zend/Acl/Resource.php';



class SecurityController extends Zend_Controller_Action
{
    /**
       authenticated user instance
    */
	protected $me = null;
    const M_NOTICE = 'notice';
    const M_WARNING= 'warning';

    public static $now = null;
    protected $_auth = null;
    
    /**
     * Authentication check and ACL initialization
     * @todo cache the acl
     */
    public function init()
    {
        if( empty(self::$now) ) {
            self::$now = Zend_Date::now();
        }
        $this->_auth = Zend_Auth::getInstance();
        if($this->_auth->hasIdentity()){
            if( empty($this->me) ){
                $this->me = $this->_auth->getIdentity();
                $this->initializeAcl($this->me->id);
            }
            $this->view->identity = $this->me->account;
        }
    }

    public function preDispatch()
    {
        if( empty($this->me ) ) {
            $this->_forward('login','User');
        }
    }

    protected function initializeAcl($uid){
        if( !Zend_Registry::isRegistered('acl') )  {
            $acl = new Zend_Acl();
            $db = Zend_Registry::get('db');
            $query = $db->select()->from(array('r'=>'roles'),array('nickname'=>'r.nickname'));
            $role_array = $db->fetchAll($query);
            foreach($role_array as $row){
                $acl->addRole(new Zend_Acl_Role($row['nickname']));
            }

            $query->reset();
            $query = $db->select()->distinct()->from(array('f'=>'functions'),array('screen'=>'screen'));
            $resource = $db->fetchAll($query);
            foreach($resource as $row){
                $acl->add(new Zend_Acl_Resource($row['screen']));
            }

            $query->reset();
            $query = $db->select()->from(array('u'=>'users'),array('account'))
                                  ->join(array('ur'=>'user_roles'),'u.id = ur.user_id',array())
                                  ->join(array('r'=>'roles'),'ur.role_id = r.id',
                                            array('nickname'=>'r.nickname'))
                                  ->join(array('rf'=>'role_functions'),'r.id = rf.role_id',
                                            array())
                                  ->join(array('f'=>'functions'),'rf.function_id = f.id',
                                            array('screen'=>'f.screen', 'action'=>'f.action'))
                                  ->where('u.id=?',$uid);
            $res = $db->fetchAll($query);
            foreach($res as $row){
                $acl->allow($row['nickname'],$row['screen'],$row['action']);
            }
            Zend_Registry::set('acl',$acl);
        }else{
            $acl = Zend_Registry::get('acl');
        }
        return $acl;
    }

    /**
     * Show messages to Users
     */
    public function message( $msg , $model ){
        assert(in_array($model, array(self::M_NOTICE, self::M_WARNING) ));
        $this->view->msg = $msg;
        $this->view->model= $model;
        $this->_helper->viewRenderer->renderScript('message.tpl');
    }


    public function & retrieveParam($req, $params,$default=null)
    {
        assert($req instanceof Zend_Controller_Request_Abstract);
        $crit = array();
        foreach($params as $k=>&$v) {
            $crit[$k] = $req->getParam($v);
        }
        return $crit;
    }
}
?>
