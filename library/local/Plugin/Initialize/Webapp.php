<?php
/**
 * Copyright (c) 2008 Endeavor Systems, Inc.
 *
 * This file is part of OpenFISMA.
 *
 * OpenFISMA is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * OpenFISMA is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with OpenFISMA.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author    Jim Chen <xhorse@users.sourceforge.net>
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/mw/index.php?title=License
 * @version   $Id$
 *
 */

/**
 * Initialize the web application front
 */
class Plugin_Initialize_Webapp extends Zend_Controller_Plugin_Abstract
{

    /**
     * @var array the path architecture of this mode
     */
    protected $_path = array('CONTROLLER'=>'application/controllers',
                             'MODEL'=>'application/models',
                             'LAYOUT'=> 'application/layouts',
                             'VIEW'=>'application/views');

    /**
     * @var Zend_Controller_Front
     */
    protected $_front;

    /**
     * @var string Path to application root
     */
    protected $_root;

    /**
     * Constructor
     *
     * Initialize environment, root path, and configuration.
     * 
     * @param  string $env 
     * @param  string|null $root 
     * @return void
     */
    public function __construct($root = null)
    {
        if (null === $root) {
            $root = realpath(dirname(__FILE__) . '/../../../../');
        }
        $this->_root = $root;
        $this->_front = Zend_Controller_Front::getInstance();
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
        //Customized helpers
        Zend_Controller_Action_HelperBroker::addPath($this->_root . '/library/local/Action/Helper', 
            'Action_Helper');
    }

    /**
     * Initialize database
     */
    public function initDb()
    {
        if (!Config_Fisma::isInstall()) {
            throw new Sws_Exception('Database setting missing! Is the application properly installed?');
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
        // LAYOUT SETUP - Setup the layout component
        // The Zend_Layout component implements a composite (or two-step-view) pattern
        // In this call we are telling the component where to find the layouts scripts.
        Zend_Layout::startMvc($this->_root . "/{$this->_path['LAYOUT']}/scripts");
        // VIEW SETUP - Initialize properties of the view object
        // The Zend_View component is used for rendering views. Here, we grab a "global"
        // view instance from the layout object, and specify the doctype we wish to
        // use -- in this case, HTML4 Strict.
        $view = Zend_Layout::getMvcInstance()->getView();
        $view->doctype('HTML4_STRICT');
    }

    /**
     * Initialize plugins
     */
    public function initPlugins()
    {
        /*
        // The installer has its own error handler which is registered here:
        $this->_front->registerPlugin(new Zend_Controller_Plugin_ErrorHandler(
            array(
                'model' => null,
                'controller' => 'Install',
                'action' => 'error'
                )
            ));
        */
    }

    /**
     * Initialize the routers
     * 
     * Using the default router
     */
    public function initRouters()
    {
        $router = $this->_front->getRouter();
        // Define an additional route to handle the final page of the installer:
        $route['install_end'] = new 
            Zend_Controller_Router_Route_Static(
                'install/complete',
                array(
                    'controller' => 'install',
                    'action' => 'complete'
                )
            );
        $router->addRoute('install_end', $route['install_end']);
        
        // Disallow any route which invokes the installation controller. This
        // prevents accidental or malicious execution of the installer over an
        // already installed application.
        $route['no_install'] = new
            Zend_Controller_Router_Route_Regex(
            'install/.*',
            array(
                'controller' => 'user',
                'action' => 'logout'
            )
        );
        $router->addRoute('no_install', $route['no_install']);
    }

    public function initControllers()
    {
        $this->_front->setControllerDirectory(array(
            'default'=>"{$this->_root}/{$this->_path['CONTROLLER']}"
            )
        );

        // This configuration option tells Zend_Date to use the standard PHP date format
        // instead of standard ISO format. This is convenient for interfacing Zend_Date
        // with legacy PHP code.
        Zend_Date::setOptions(array('format_type' => 'php'));
        $this->_front->throwExceptions(true);
    }
}
