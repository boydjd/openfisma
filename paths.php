<?php
/**
 * paths.php
 *
 * Define the paths that would be used in the system.
 *
 * @package Root
 * @author     Xhorse   xhorse at users.sourceforge.net
 * @copyright  (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license    http://www.openfisma.org/mw/index.php?title=License
 * @version $Id$
 */

    if (!defined('DS')) {
        define('DS', DIRECTORY_SEPARATOR);
    }

    // ROOT is where the software installed.
    if (!defined('ROOT')) {
        define ('ROOT', dirname(__FILE__));
    }
    // name of the directory which should be the place where HTTP Server pointed at.
    if (!defined('WEBROOT_DIR')) {
        define('WEBROOT_DIR', 'public');
    }
    // absolute path of WEBROOT_DIR directory
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
