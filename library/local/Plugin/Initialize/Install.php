<?php
class Plugin_Initialize_Install extends Plugin_Initialize_Webapp
{
    public function initDb()
    {//overload the parent initDb doing nothing here
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
