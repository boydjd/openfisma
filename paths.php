<?php

    if (!defined('DS')) {
        define('DS', DIRECTORY_SEPARATOR);
    }

    if (!defined('ROOT')) {
        define ('ROOT', dirname(__FILE__));
    }

    if (!defined('WEBROOT_DIR')) {
        define('WEBROOT_DIR', 'public');
    }

    if (!defined('WEB_ROOT')) {
        define('WEB_ROOT', ROOT .DS . WEBROOT_DIR);
    }

    define('APPS', ROOT . DS . 'apps');
    define('CONFIGS', APPS . DS . 'config');
    define('MODELS', APPS . DS . 'models');
    define('VIEWS', APPS . DS . 'views');
    define('CONTROLLERS', APPS . DS . 'controllers');
    define('VENDORS', ROOT . DS . 'vendor');
    define('LIBS', ROOT . DS . 'include');


?>
