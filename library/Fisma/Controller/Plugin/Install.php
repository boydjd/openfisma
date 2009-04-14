<?php
///$Id$
class Fisma_Controller_Plugin_Install extends Fisma_Controller_Plugin_Web
{
    //overload the parent initDb doing nothing here
    public function initDb() { }

    public function initPlugins()
    {
        // The installer has its own error handler which is registered here:
        $this->_front->registerPlugin(new Zend_Controller_Plugin_ErrorHandler(
            array( 'controller' => 'Install', 'action' => 'error')));
    }
}