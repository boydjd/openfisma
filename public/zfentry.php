<?php

    if (!defined('DS')) {
        define('DS', DIRECTORY_SEPARATOR);
    }

    if (!defined('URL_BASE') ){
        define('URL_BASE', 'http://192.168.0.115/of/zfentry.php/');
    }

    if (!defined('ROOT')) {
        define('ROOT', dirname(dirname(__FILE__)));
    }

    require_once( ROOT . DS . 'paths.php');
    require_once( APPS . DS . 'basic.php');
    include_once( CONFIGS . DS . 'debug.php');
    import(LIBS, VENDORS, VENDORS.DS.'Pear');

    require_once 'Zend/Controller/Front.php';
    require_once 'Zend/Controller/Router/Route/Regex.php';
    require_once 'Zend/Layout.php';
    require_once 'Zend/Registry.php';
    require_once 'Zend/Config.php';
    require_once 'Zend/Config/Ini.php';
    require_once 'Zend/Db.php';
    require_once 'Zend/Db/Table.php';
    require_once MODELS . DS . 'Abstract.php';
    require_once 'Zend/Controller/Plugin/ErrorHandler.php';
    require_once 'Zend/Date.php';

    Zend_Date::setOptions(array('format_type' => 'php'));
    $options = array(
        'layout'     => 'default',
        'layoutPath' => VIEWS . DS . 'layouts',
        'contentKey' => 'CONTENT'           // ignored when MVC not used
    );
    Zend_Layout::startMvc($options)->setViewSuffix('tpl');

    $viewRender = Zend_Controller_Action_HelperBroker::getStaticHelper('viewRenderer');
    $viewRender->setViewSuffix('tpl')->setNeverRender(true);
    Zend_Controller_Action_HelperBroker::addHelper($viewRender);

    $front = Zend_Controller_Front::getInstance();
    $router = $front->getRouter();
    require_once ( CONFIGS . DS . 'setting.php');

    if( ! isInstall() ) {
        $route['install'] = new Zend_Controller_Router_Route_Regex(
        '([^/]*)/?(.*)$',array('controller'=>'install'),array('action'=>2));
        $router->addRoute('default',$route['install']);
        $front->registerPlugin(new Zend_Controller_Plugin_ErrorHandler( array(
                'model'=>null,
                'controller'=>'Install',
                'action'=>'error') ) );
    }else{
        $db = Zend_DB::factory( Zend_Registry::get('dbconf') );
        Zend_Db_Table::setDefaultAdapter($db);
        Zend_Registry::set('db', $db);
        $route['install_end'] = new Zend_Controller_Router_Route_Static(
        'install/complete',array('controller'=>'install','action'=>'complete') );
        $route['install'] = new Zend_Controller_Router_Route_Regex(
        'install/.*',array('controller'=>'user','action'=>'logout') );
        $router->addRoute('noinstall',$route['install']);        //forbiden installation 
        $router->addRoute('install_end',$route['install_end']);  //to permit the last step of installation
        $front->registerPlugin(new Zend_Controller_Plugin_ErrorHandler( array(
                'model'=>null,
                'controller'=>'Error',
                'action'=>'error') ) );
    }

    //$front->throwExceptions(true);
    Zend_Controller_Front::run(APPS . DS . 'controllers' );

?>
