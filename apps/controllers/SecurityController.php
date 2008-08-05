<?php
/**
 * SecurityController.php
 *
 * Security Controller
 *
 * @package Controller
 * @author     Xhorse   xhorse at users.sourceforge.net
 * @copyright  (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license    http://www.openfisma.org/mw/index.php?title=License
 * @version $Id$
*/

require_once 'Zend/Date.php';
require_once 'Zend/Auth.php';
require_once 'Zend/Db.php';
require_once MODELS . DS . 'user.php';
require_once CONTROLLERS . DS . 'MessageController.php';

require_once 'Zend/Acl.php';
require_once 'Zend/Acl/Role.php';
require_once 'Zend/Acl/Resource.php';

/**
 * Accompany with the Authentication and ACL initialization
 *
 * Every controller that needs to be authenticated or has acl issue should be extended from it.
 * @package Controller
 * @author     Xhorse   xhorse at users.sourceforge.net
 * @copyright  (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license    http://www.openfisma.org/mw/index.php?title=License
 */
class SecurityController extends MessageController
{
    /**
       authenticated user instance
    */
	protected $me = null;

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
            $this->me = $this->_auth->getIdentity();
            $store = $this->_auth->getStorage();
            // refresh the expiring timer
            $exps = new Zend_Session_Namespace($store->getNamespace());
            $exps->setExpirationSeconds(readSysConfig('expiring_seconds'));
            $this->initializeAcl($this->me->id);
        }
    }

    public function preDispatch()
    {
        if( empty($this->me ) ) {
            $this->_forward('login','User');
        }else{
            $this->view->identity = $this->me->account;
        }
    }

    protected function initializeAcl($uid){
        if( !Zend_Registry::isRegistered('acl') )  {
            $acl = new Zend_Acl();
            $db = Zend_Registry::get('db');
            $query = $db->select()->from(array('r'=>'roles'),array('nickname'=>'r.nickname'))
                                  ->where('nickname != ?','auto_role');
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
                                            array('nickname'=>'r.nickname','role_name'=>'r.name'))
                                  ->join(array('rf'=>'role_functions'),'r.id = rf.role_id',
                                            array())
                                  ->join(array('f'=>'functions'),'rf.function_id = f.id',
                                            array('screen'=>'f.screen', 'action'=>'f.action'))
                                  ->where('u.id=?',$uid)
                                  ->where('r.nickname != ?','auto_role');
            $res = $db->fetchAll($query);
            foreach($res as $row){
                $acl->allow($row['nickname'],$row['screen'],$row['action']);
            }
            $query->reset(Zend_Db_Select::WHERE);
            $query->where('u.id = ?',$uid)
                  ->where('r.nickname = ?','auto_role');
            $res = $db->fetchAll($query);
            if(!empty($res)){
                $auto_role = $res[0]['role_name'];
                $acl->addRole(new Zend_Acl_Role($auto_role));
                foreach($res as $row){
                    $acl->allow($auto_role,$row['screen'],$row['action']);
                }
            }
            Zend_Registry::set('acl',$acl);
        }else{
            $acl = Zend_Registry::get('acl');
        }
        return $acl;
    }

    /**
     *  utility to retrieve parameters in batch.
     */
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
