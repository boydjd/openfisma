<?php
/**
 * Bootstrap.php
 *
 * @package Public
 * @author     Xhorse   xhorse at users.sourceforge.net
 * @copyright  (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license    http://www.openfisma.org/mw/index.php?title=License
 * @version $Id$
 */
if (!defined('DS')) {
    define('DS', DIRECTORY_SEPARATOR);
}
if (!defined('ROOT')) {
    define('ROOT', dirname(dirname(__FILE__)));
}
require_once (ROOT . DS . 'paths.php');
require_once (APPS . DS . 'basic.php');
//adding the searching path
import(LIBS, VENDORS, VENDORS . DS . 'Pear');
// Initializing the configuration
// !this should be called at the very first stage after path being set
require_once (CONFIGS . DS . 'setting.php');
require_once 'Zend/Controller/Front.php';
require_once 'Zend/Controller/Router/Route/Regex.php';
require_once 'Zend/Layout.php';
require_once 'Zend/Db.php';
require_once 'Zend/Db/Table.php';
require_once MODELS . DS . 'Abstract.php';
require_once 'Zend/Controller/Plugin/ErrorHandler.php';
require_once 'Zend/Date.php';

// sets the doctype() helper to utilize XHTML1_STRICT which is passed to the default layout
// DocType must be set before making such calls; otherwise, they will use the default (which is HTML4 transitional).
// Other options are as follows:
// XHTML1_STRICT, XHTML1_TRANSITIONAL, XHTML1_FRAMESET, HTML4_STRICT
// HTML4_LOOSE, HTML4_FRAMESET, CUSTOM_XHTML
$viewRenderer = Zend_Controller_Action_HelperBroker::getStaticHelper('viewRenderer');
$viewRenderer->initView();
$viewRenderer->view->doctype('XHTML1_STRICT');

//Make the php convention as the toString default format
Zend_Date::setOptions(array(
    'format_type' => 'php'
));
//Set layout options
$options = array(
    'layout' => 'default',
    'layoutPath' => VIEWS . DS . 'layouts',
    'contentKey' => 'CONTENT'
);
//use tpl as the default layout file surfix.
Zend_Layout::startMvc($options)->setViewSuffix('tpl');
//use tpl as the default template surfix 
$viewRender = 
    Zend_Controller_Action_HelperBroker::getStaticHelper('viewRenderer');
//and stop automatically rendering page
$viewRender->setViewSuffix('tpl')->setNeverRender(true);
Zend_Controller_Action_HelperBroker::addHelper($viewRender);
$front = Zend_Controller_Front::getInstance();
$router = $front->getRouter();
if (!isInstall()) {
    // Define route that permit only install controller
    $route['install'] = new Zend_Controller_Router_Route_Regex('([^/]*)/?(.*)$', array(
        'controller' => 'install'
    ), array(
        'action' => 2
    ), 'install/%2$s');
    $router->addRoute('default', $route['install']);
    // register install only error handler
    $front->registerPlugin(new Zend_Controller_Plugin_ErrorHandler(array(
        'model' => null,
        'controller' => 'Install',
        'action' => 'error'
    )));
} else {
    $db = Zend_DB::factory(Zend_Registry::get('datasource'));
    Zend_Db_Table::setDefaultAdapter($db);
    Zend_Registry::set('db', $db);
    // Define additional route to permit the last page(complete) of installation
    $route['install_end'] = new Zend_Controller_Router_Route_Static('install/complete', array(
        'controller' => 'install',
        'action' => 'complete'
    ));
    // Define route for safty
    // Any hack attempt to access installation would result in logout.
    $route['install'] = new Zend_Controller_Router_Route_Regex('install/.*', array(
        'controller' => 'user',
        'action' => 'logout'
    ));
    $router->addRoute('noinstall', $route['install']);
    $router->addRoute('install_end', $route['install_end']);
    //Normal error handler
    $front->registerPlugin(new Zend_Controller_Plugin_ErrorHandler(array(
        'model' => null,
        'controller' => 'Error',
        'action' => 'error'
    )));
}
Zend_Controller_Front::run(APPS . DS . 'controllers');