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

class InstallController extends Zend_Controller_Action
{
    public function preDispatch()
    {
        session_start();
        $this->_helper->layout->setLayout('install');
        //Judge if there is necessary to install
    }

    public function indexAction()
    {
        $this->view->back = '';
        $this->view->next = '/zfentry.php/install/intro';
        $this->render();
    }
    
    public function introAction()
    {
        $this->view->back = '';
        $this->view->next = '/zfentry.php/install/envcheck';
        $this->render();
    }

    public function envcheckAction()
    {
        define(REQUEST_PHP_VERSION, 5);
        $this->view->back = 'install/intro';

        if(version_compare(phpversion(),REQUEST_PHP_VERSION)){
            $this->view->next = '/zfentry.php/install/checking';
            $this->view->checklist = array('version' => 'ok');
        }else{
            $this->view->next = '';
        }
        $this->render();
    }
    
    public function checkingAction()
    {
        $w_directories = array( WEB_ROOT . DS . 'temp',
                                ROOT . DS . 'log',
                                WEB_ROOT . DS . 'evidence',
                                WEB_ROOT . DS . 'ovms.ini.php');
        $notwritables = array();
        foreach ($w_directories as $k => $wok) {
            if ( !  is_writeable($wok) ) {
                array_push( $notwritables, $wok ) ;
                unset($w_directories[$k]);
            }
        }
        $this->view->notwritables = $notwritables;
        $this->view->writables = $w_directories;
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
        $this->view->title = 'General settings';
        $this->view->back = 'install/intro';
        $this->view->next = '/zfentry.php/install/dbreview';
        $this->render();
    }
    
    public function dbreviewAction()
    {
        $dsn = $this->_getParam('dsn');
        ///@Todo sanity check
        $this->view->title = 'General settings';
        $this->view->back = 'install/intro';
        $this->view->next = '/zfentry.php/install/initial';
        $this->view->dsn = $dsn;
        $this->render();
    }
    
    public function initialAction()
    {
        $dsn = $this->_getParam('dsn');
        $checklist=array('is_connect'=>'','is_grant'=>'','is_create_table'=>'', 'is_write_config'=>'');
        $qry="CREATE DATABASE IF NOT EXISTS `{$dsn['dbname']}`;" ;
        $checklist['is_connect']= mysql_connect($dsn['host'].':'.$dsn['port'], $dsn['uname'], $dsn['upass']) && mysql_query($qry); 
        if($checklist['is_connect'])
        {
            $qry="GRANT ALL PRIVILEGES ON `{$dsn['dbname']}` . * TO '{$dsn['name_c']}'@'{$dsn['host']}' IDENTIFIED BY '{$dsn['pass_c']}' WITH GRANT OPTION;";
            $checklist['is_grant']=mysql_query($qry); 
            if($checklist['is_grant'])
            {
                require_once( CONTROLLERS . DS . 'components' . DS . 'sqlimport.php');
                $zend_dsn = array(
                    'adapter' => 'mysqli',
                    'params' => array(
                    'host' => $dsn['host'],
                    'port' => $dsn['port'],
                    'username' => $dsn['uname'],
                    'password' => $dsn['upass'],
                    'dbname' => $dsn['dbname'],
                    'profiler' => false));
                $db = Zend_DB::factory(new Zend_Config($zend_dsn));
                $init_db_path=WEB_ROOT . DS . 'install' . DS . 'db';
                $init_files=array(
                    $init_db_path . DS . 'schema.sql',
                    $init_db_path . DS . 'init_data.sql'
                );
                $ret = import_data($db,$init_files);
                $checklist['is_create_table']=$ret; 
            }
        }
        
        if($checklist['is_create_table'] && is_writable(CONFIGS) )
        {
            $dbconfig="<? 
             Zend_Registry::set('datasource', new Zend_Config(
                array(
                'default' => array(
                    'adapter' => 'mysqli',
                    'params' => array(
                        'host' => '{$dsn['host']}',
                        'port' => '{$dsn['port']}',
                        'username' => '{$dsn['name_c']}',
                    'password' => '{$dsn['pass_c']}',
                    'dbname' => '{$dsn['dbname']}',
                    'profiler' => false
                    )
                ))
            ));
            ?>";
            $checklist['is_write_config']=file_put_contents(CONFIGS . DS . 'database2.php',$dbconfig);
        }
        $this->view->title = 'Initial Database';
        $this->view->dbname=$dsn['dbname'];
        $this->view->uname=$dsn['name_c'];
        foreach ($checklist as &$check)
        {
            $check=$check?"ok":"failure";
        }
        $this->view->checklist=$checklist;
        $this->view->back = '/zfentry.php/install/dbsetting';
        $this->view->next = '/zfentry.php/install/complete';
        $this->render();
    }
    
    public function completeAction()
    {
        $this->view->title = 'install complete';
        $this->view->next = '/zfentry.php/user/login';
        $this->render();
    }
}
?>