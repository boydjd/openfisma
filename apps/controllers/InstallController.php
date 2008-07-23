<?php
/**
 * @file InstallController.php
 *
 * Install Controller
 *
 * @author     Jim <jimc@reyosoft.com>
 * @copyright  (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license    http://www.openfisma.org/mw/index.php?title=License
 * @version $Id$
*/

require_once 'Zend/Filter/Input.php';
require_once 'Zend/Validate/Hostname.php';
require_once 'Zend/Validate/Between.php';
require_once 'Zend/Validate/InArray.php';


class InstallController extends Zend_Controller_Action
{
    public function preDispatch()
    {
        $this->_helper->layout->setLayout('install');
        //Judge if there is necessary to install
    }
    
    public function indexAction()
    {
        $this->view->back = '';
        $this->view->next = '/zfentry.php/install/envcheck';
        $this->render();
    }

    public function envcheckAction()
    {
        define('REQUEST_PHP_VERSION','5');
        $this->view->back = '/zfentry.php/install';
        if(version_compare(phpversion(),REQUEST_PHP_VERSION,'>')){
            $this->view->next = '/zfentry.php/install/checking';
            $this->view->checklist = array('version' => 'ok');
        }else{
            $this->view->checklist = array('version' => 'failure');
            $this->view->next = '';
        }
        $this->render();
    }
    
    public function checkingAction()
    {
        $w_directories = array( WEB_ROOT . DS . 'temp',
                                ROOT . DS . 'log',
                                WEB_ROOT . DS . 'evidence',
                                CONFIGS . DS . CONFIGFILE_NAME);
        $notwritables = array();
        foreach ($w_directories as $k => $wok) {
            if ( !  is_writeable($wok) ) {
                array_push( $notwritables, $wok ) ;
                unset($w_directories[$k]);
            }
        }
        $this->view->notwritables = $notwritables;
        $this->view->writables = $w_directories;
        $this->view->back = '/zfentry.php/install/envcheck';
        if( empty($notwritables) ) { 
            $this->view->next = '/zfentry.php/install/dbsetting';
        }else{
            $this->view->next = '';
        }
        $this->render();
    }
    
    public function dbsettingAction()
    {
        $this->view->installpath=dirname(dirname(dirname(__FILE__)));
        $this->view->dsn = array('host'=>'localhost',
                                 'port'=>'3306');
        $this->view->title = 'General settings';
        $this->view->back = '/zfentry.php/install/checking';
        $this->view->next = '/zfentry.php/install/dbreview';
        $this->render();
    }
    
    public function dbreviewAction()
    {
        $dsn = $this->_getParam('dsn');
        if(empty($dsn['name_c']) && empty($dsn['pass_c']) && empty($dsn['pass_c_ag']))
        {
            $dsn['name_c']=$dsn['uname'];
            $dsn['pass_c']=$dsn['upass'];
            $dsn['pass_c_ag']=$dsn['upass'];
        }
        $password_compare=array($dsn['pass_c']);
        $filter=array('*'=>array('StringTrim','stripTags'));
        $validator= array('type'=>'Alnum',
                          'host'=>array('NotEmpty',new Zend_Validate_Hostname(
                          Zend_Validate_Hostname::ALLOW_LOCAL | Zend_Validate_Hostname::ALLOW_IP)),
                          'port'=>array('Int', new Zend_Validate_Between(0,65535)),
                          'uname'=>'NotEmpty',
                          'upass'=>'NotEmpty',
                          'dbname'=>'NotEmpty',
                          'name_c'=>'NotEmpty',
                          'pass_c'=>'NotEmpty',
                          'pass_c_ag'=>array('NotEmpty',new Zend_Validate_InArray($password_compare)
));
        $fv=new Zend_Filter_Input($filter,$validator);
        $input = $fv->setData($dsn);
        $this->view->title = 'General settings';
        $this->view->dsn = $dsn;
        if($input->hasInvalid() || $input->hasMissing())
        {
            $message=$input->getMessages();
            $this->view->back = '/zfentry.php/install/checking';
            $this->view->next = '/zfentry.php/install/dbreview';
            $this->view->message=$message;
            $this->render('dbsetting'); 
        } else {
            $this->view->back = '/zfentry.php/install/dbsetting';
            $this->view->next = '/zfentry.php/install/initial';
            $this->render();  
        }
    }
    
    public function initialAction()
    {
        $dsn = $this->_getParam('dsn');
        $checklist=array('connection'=>'failure',
                         'creation'=>'failure',
                         'grant'=>'failure',
                         'schema'=>'failure', 
                         'savingconfig'=>'failure');
        $method='connection';
        $ret = false;
        if(mysql_connect($dsn['host'].':'.$dsn['port'], $dsn['uname'], $dsn['upass'])){
            $checklist['connection'] = 'ok';
            if(mysql_select_db($dsn['dbname'])){
                $ret = true;
            } else {
                $qry="CREATE DATABASE `{$dsn['dbname']}`;" ;
                $ret = mysql_query($qry); 
                $method='creation';
                $checklist['creation'] = 'ok';
            }
            if($ret && ($dsn['uname'] != $dsn['name_c']) ){
                $qry="GRANT ALL PRIVILEGES ON `{$dsn['dbname']}` . * TO '{$dsn['name_c']}'@'{$dsn['host']}' IDENTIFIED BY '{$dsn['pass_c']}' WITH GRANT OPTION;";
                if( TRUE == ($ret = mysql_query($qry)) ){
                    $checklist['grant'] = 'ok';
                }
            }   
            if($ret) {
                require_once( CONTROLLERS . DS . 'components' . DS . 'sqlimport.php');
                $zend_dsn = array(
                    'adapter' => 'mysqli',
                    'params' => array(
                        'host' => $dsn['host'],
                        'port' => $dsn['port'],
                        'username' => $dsn['name_c'],
                        'password' => $dsn['pass_c'],
                        'dbname' => $dsn['dbname'],
                        'profiler' => false
                        ) );
                $db = Zend_DB::factory(new Zend_Config($zend_dsn));
                $init_db_path=WEB_ROOT . DS . 'install' . DS . 'db';
                $init_files=array(
                    $init_db_path . DS . 'schema.sql',
                    $init_db_path . DS . 'init_data.sql'
                    );
                try {
                    if( $ret = import_data($db,$init_files) ) {
                        $checklist['schema'] = 'ok';
                    }
                } catch (Zend_Exception $e){
                    $err = $e->getMessage();
                    $ret = false;
                }
            } 
        }
        $this->view->dsn = $dsn;
        if($ret){
            if( file_exists(CONFIGS . DS . CONFIGFILE_NAME) ) {
                $conf_tpl = $this->_helper->viewRenderer->getViewScript('config');
                $dbconfig = $this->view->render($conf_tpl);
                if( $ret = file_put_contents(CONFIGS . DS . CONFIGFILE_NAME ,$dbconfig) ) {
                    $checklist['savingconfig'] = 'ok';
                }
            }else{
                //throw new Zend_Exception("initial table error.");
                //$this->render('config','configration');
            }
        }
        $this->view->title = 'Initial Database';
        $this->view->method = $method;
        if($checklist['savingconfig']=='ok'){
            $this->view->next = '/zfentry.php/install/complete';
        } else {
            $this->view->next = '/zfentry.php/install/dbsetting';
        }
        $this->view->checklist=$checklist;
        $this->view->back = '/zfentry.php/install/dbsetting';
        $this->render('initial');
    }
    
    public function completeAction()
    {
        $this->view->title = 'Install complete';
        $this->view->next = '/zfentry.php/user/login';
        $this->render();
    }

    public function errorAction()
    {
        $content = null;
        $errors = $this->_getParam ('error_handler') ;
        //$this->_helper->layout->setLayout('error');
        switch ($errors->type) {
            case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_CONTROLLER :
            case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_ACTION :
            default :
                // 404 error -- controller or action not found
                $this->getResponse ()->setRawHeader ( 'HTTP/1.1 404 Not Found' ) ;
                break;
        }
        $this->getResponse()->clearBody();
        $this->render();
    }
}
?>
