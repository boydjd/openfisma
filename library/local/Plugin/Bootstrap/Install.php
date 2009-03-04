<?php
class Plugin_Bootstrap_Install extends Plugin_Bootstrap_Webapp
{

    /**
     * Constructor
     *
     * Initialize environment, root path, and configuration.
     * 
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

    public function initDb()
    {//overload the parent initDb doing nothing here
    }

    public function initPlugins()
    {
        // The installer has its own error handler which is registered here:
        $this->_front->registerPlugin(new Zend_Controller_Plugin_ErrorHandler(array(
                'module' => null,
                'controller' => 'Install',
                'action' => 'error'
                )));
    }

    public function initRouters()
    {
        $router = $this->_front->getRouter();
        // If the application has not been installed yet, then define the route so
        // that only the installController can be invoked. This forces the user to
        // complete installation before using the application.
        $route['install'] = new Zend_Controller_Router_Route_Regex (
                                    '([^/]*)/?(.*)$',
                                    array('controller' => 'install'),
                                    array('action' => 2),
                                    'install/%2$s'
                                );
        $router->addRoute('default', $route['install']);
    }
}
