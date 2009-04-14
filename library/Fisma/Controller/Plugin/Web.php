<?php
///$Id$
/**
 * Initialize the web application front
 * 
 * This requires that the directory layout be 
 * controllers, models, views, layouts
 */
class Fisma_Controller_Plugin_Web extends Zend_Controller_Plugin_Abstract
{
    /**
     * @var Zend_Controller_Front
     */
    protected $_front;

    /**
     * @var Application instance which can be retrived parameters from
     */
    protected $_defaultModulePath = null;

    /**
     * Constructor
     *
     * Initialize environment, root path, and configuration.
     * 
     * @return void
     */
    public function __construct()
    {
        $this->_front = Zend_Controller_Front::getInstance();
        //The error should be throw before dispatching so it doesn't go to routeStartup
        $setting = $this->_front->getPlugin('Fisma_Controller_Plugin_Setting');
        $this->_defaultModulePath = $setting->getConfig(array('path','application'));
    }

    /**
     * Route startup
     * 
     * @return void
     */
    public function routeStartup(Zend_Controller_Request_Abstract $request)
    {
        $this->initControllers();
        $this->initDb();
        $this->initHelpers();
        $this->initView();
        $this->initPlugins();
        $this->initRouters();
    }

    /**
     * Initialize customized helpers
     */
    public function initHelpers()
    {
        Zend_Controller_Action_HelperBroker::addPrefix('Scarab_Controller_Helper');
    }

    /**
     * Initialize database
     */
    public function initDb()
    {
        $config = new Zend_Config_Ini($this->_defaultModulePath . "/config/install.conf");
        if (!empty($config->database)) {
           Zend_Registry::set('datasource', $config->database);
        } else {
            throw new Scarab_Exception_Config('The DSN is not configured while using the database!');
        }
        $db = Zend_Db::factory(Zend_Registry::get('datasource'));
        Zend_Db_Table::setDefaultAdapter($db);
        Zend_Registry::set('db', $db);
    }

    /**
     * Initialize view
     */
    public function initView()
    {
        $view = Zend_Layout::getMvcInstance()->getView();
        $viewRenderer = Zend_Controller_Action_HelperBroker::getStaticHelper('viewRenderer');
        $plSetting = $this->_front->getPlugin('Fisma_Controller_Plugin_Setting');
        $view->addHelperPath($plSetting->getConfig('path.application') . "/views/helpers", 'View_Helper_');
        //$view->doctype('HTML4_STRICT');
        $viewRenderer->setView($view);
        $viewRenderer->setViewSuffix('phtml');
    }

    /**
     * Initialize plugins
     */
    public function initPlugins()
    {
    }

    /**
     * Initialize the routers
     * 
     * Using the default router
     */
    public function initRouters()
    {
    }

    public function initControllers()
    {
        $appPath = $this->_defaultModulePath;
        $this->_front->setControllerDirectory("{$appPath}/controllers");
        Zend_Date::setOptions(array('format_type' => 'php'));
        Zend_Layout::startMvc("$appPath/layouts/scripts");
    }
}
