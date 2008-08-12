<?php 
/**
 * fileName UserController.php
 *
 * description User Controller
 *
 * @package Controller
 * @author     Xhorse   xhorse at users.sourceforge.net
 * @copyright  (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license    http://www.openfisma.org/mw/index.php?title=License
 * @version $Id$
 */

require_once 'Zend/Auth.php';
require_once 'Zend/Auth/Adapter/DbTable.php';
require_once 'Zend/Auth/Adapter/Ldap.php';
require_once 'Zend/Auth/Exception.php';
require_once( CONTROLLERS . DS . 'MessageController.php');
require_once( MODELS . DS .'user.php');
require_once( MODELS . DS .'system.php');
require_once 'Zend/Date.php';

/**
 * UserController 
 *
 * This controller is not required of authentication and ACLs
 * @package Controller
 * @author     Xhorse   xhorse at users.sourceforge.net
 * @copyright  (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license    http://www.openfisma.org/mw/index.php?title=License
 */
class UserController extends MessageController
{
    private $_user = null;

    public function init()
    {
        $this->_user = new User();
    }
    
    /**
     * User login
     */
    public function loginAction()
    {
        $req = $this->getRequest();
        $username = $req->getPost('username');
        $password = $req->getPost('userpass');
        $this->_helper->layout->setLayout('login');
        if ( empty($username) ) {
            return $this->render();
        }
        $now = new Zend_Date();
        try { 
            $whologin = $this->_user->fetchRow("account = '$username'");
            if ( empty($whologin) ) {
                //to cover the fact
                throw new Zend_Auth_Exception("Incorrect username or password");
            }
            if ( $whologin->is_active == false ) {
                throw new Zend_Auth_Exception('The account has been locked');
            }

            $auth = Zend_Auth::getInstance();
            $authType = readSysConfig('auth_type');
            $authAdapter = $this->authenticate($authType,
                                             $username, $password);
            $result = $auth->authenticate($authAdapter);
            if ( !$result->isValid() ) {
                if ( Zend_Auth_Result::FAILURE_CREDENTIAL_INVALID == 
                     $result->getCode() ) {
                    $this->_user->log(User::LOGINFAILURE, $whologin->id, 
                                      'Password Error');
                    throw new Zend_Auth_Exception("Password Error");
                }
                throw new Zend_Auth_Exception("Incorrect username or password");
            }
            $me = (object)($whologin->toArray());
            $period = readSysConfig('max_absent_time');
            $deactiveTime = clone $now;
            $deactiveTime->sub($period, Zend_Date::DAY);
            $lastLogin = new Zend_Date($me->last_login_ts,
                                       'YYYY-MM-DD HH-MI-SS');

            if ( !$lastLogin->equals(new Zend_Date('0000-00-00 00:00:00')) 
                && $lastLogin->isEarlier($deactiveTime) ) {
                throw new Zend_Auth_Exception("Your account has been locked
                    because you have not logged in for $period or more days.
                    Please contact an administrator.");
            }
            
            $this->_user->log(User::LOGIN, $me->id, "Success");
            $nickname = $this->_user->getRoles($me->id);
            foreach ($nickname as $n) {
                $me->roleArray[] = $n['nickname'];
            }
            if ( empty( $me->roleArray ) ) {
                $me->roleArray[] = $me->account . '_r';
            }
            $me->systems = $this->_user->getMySystems($me->id);
            $store = $auth->getStorage();
            $exps = new Zend_Session_Namespace($store->getNamespace());
            $exps->setExpirationSeconds(readSysConfig('expiring_seconds'));
            $store->write($me);
            return $this->_forward('index', 'Panel');

        }catch(Zend_Auth_Exception $e) {
            $this->view->assign('error', $e->getMessage());
            $this->render();
        }
    } 
    

     /**
        Exam the Acl to decide permission or denial.
        @param $user array of User's roles
        @param $resource resources
        @param $action actions
        @return bool permit or not
    */
    
    public function logoutAction()
    {
        $auth = Zend_Auth::getInstance();
        $me = $auth->getIdentity();
        if( !empty($me) ) {
            $this->_user->log(User::LOGOUT, $me->id,$me->account.' logout');
            Zend_Auth::getInstance()->clearIdentity();
        }
        $this->_forward('login');
    }

    /**
     * Change user's password
     */
    public function pwdchangeAction()
    {
        $req = $this->getRequest();
        if('save' == $req->getParam('s')){
            $auth = Zend_Auth::getInstance();
            $me = $auth->getIdentity();
            $id   = $me->id;
            $pwds = $req->getPost('pwd');
            $oldpass = md5($pwds['old']);
            $newpass = md5($pwds['new']);
            $res = $this->_user->find($id)->toArray();
            $password = $res[0]['password'];
            $history_pass = $res[0]['history_password'];
            if($pwds['new'] != $pwds['confirm']){
                $msg = 'The new password does not match the confirm password, please try again.';
                $model = self::M_WARNING;
            }else{
                if($oldpass != $password){
                    $msg = 'The old password supplied is incorrect, please try again.';
                    $model = self::M_WARNING;
                }else{
                    if(!$this->checkPassword($pwds['new'],2)){
                        $msg = 'This password does not meet the password complexity requirements.<br>
Please create a password that adheres to these complexity requirements:<br>
--The password must be at least 8 character long<br>
--The password must contain at least 1 lower case letter (a-z), 1 upper case letter (A-Z), and 1 digit (0-9)<br>
--The password can also contain National Characters if desired (Non-Alphanumeric, !,@,#,$,% etc.)<br>
--The password cannot be the same as your last 3 passwords<br>
--The password cannot contain your first name or last name<br>";';
                        throw new fisma_Exception($msg);
                        //$msg = "The password doesn\'t meet the required complexity!";

                    }else{
                        if($newpass == $password){
                            $msg = 'Your new password cannot be the same as your old password.';
                            $model = self::M_WARNING;
                        }else{
                            if(strpos($history_pass,$newpass) > 0 ){
                                $msg = 'Your password must be different from the last three passwords you have used. Please pick a different password.';
                                $model = self::M_WARNING;
                            }else{
                                if(strpos($history_pass,$password) > 0){
                                    $history_pass = ':'.$newpass.$history_pass;
                                }else{
                                    $history_pass = ':'.$newpass.':'.$password.$history_pass;
                                }
                                $history_pass = substr($history_pass,0,99);
                                $now = date('Y-m-d H:i:s');
                                $data = array('password'=>$newpass,
                                              'history_password'=>$history_pass,
                                              'password_ts'=>$now);
                                $result = $this->_user->update($data,'id = '.$id);
                                if(!$result){
                                    $msg = 'Failed to change the password';
                                    $model = self::M_WARNING;
                                }else{
                                    $msg = 'Password changed successfully';
                                    $model = self::M_NOTICE;
                                }
                            }
                        }
                    }   
                }
            }
            $this->message($msg,$model);
        }
        $this->_helper->actionStack('header','Panel');
        $this->render();
    }
    
    /**
     * Check User's password
     * @param $pass the new password for changed
     * @param $level check level
     * @return true or false
     */
    function checkPassword($pass, $level = 1) {
        if($level > 1) {

            $nameincluded = true;
            // check last name
            if(empty($this->user_name_last) || strpos($pass, $this->user_name_last) === false) {
                $nameincluded = false;
            }
            if(!$nameincluded) {
                // check first name
                if(empty($this->user_name_first) || strpos($pass, $this->user_name_first) === false)
                    $nameincluded = false;
                else
                    $nameincluded = true;
            }
            if($nameincluded)
                return false; // include first name or last name

            // high level
            if(strlen($pass) < 8)
                return false;
            // must be include three style among upper case letter, lower case letter, symbol, digit.
            // following rule: at least three type in four type, or symbol and any of other three types
            $num = 0;
            if(preg_match("/[0-9]+/", $pass)) // all are digit
                $num++;
            if(preg_match("/[a-z]+/", $pass)) // all are digit
                $num++;
            if(preg_match("/[A-Z]+/", $pass)) // all are digit
                $num++;
            if(preg_match("/[^0-9a-zA-Z]+/", $pass)) // all are digit
                $num += 2;

            if($num < 3)
                return false;
        }
        else if($level == 1) {
            // low level
            if(strlen($pass) < 3)
                return false;
            // must include three style among upper case letter, lower case letter, symbol, digit.
            // following rule: at least two type in four type
            if(preg_match("/^[0-9]+$/", $pass)) // all are digit
                return false;

            if(preg_match("/^[a-z]+$/", $pass)) // all are lower case letter
                return false;

            if(preg_match("/^[A-Z]+$/", $pass)) // all are upper case letter
                return false;
        }

        return true;
    }

    /**
     * Authenticate the user according to the auth setting 
     *
     * @param string $type auth_type
     * @param string $username post username for login
     * @param string $password post password for login
     * @return Zend_Auth_Adapter 
     */
    protected function authenticate($type, $username, $password)
    {
        $db = Zend_Registry::get('db');
        if ( 'ldap' == $type ) {
            $multiOptions = readLdapConfig();
            $auth = Zend_Auth::getInstance();
            foreach ($multiOptions as $name=>$options) {
                $authAdapter = new Zend_Auth_Adapter_Ldap(
                              array($name=>$options), $username, $password);
                $result = $auth->authenticate($authAdapter);
                if ( true == $result->isValid() ) {
                    return $authAdapter;
                }
            }
        }
        if ( 'database' == $type ) {
            $authAdapter = new Zend_Auth_Adapter_DbTable($db, 'users',
                                                        'account', 'password');
            $authAdapter->setIdentity($username)->setCredential(md5($password));
        }
        return $authAdapter;
    }

}
