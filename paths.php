<?php
/**
 * Define common paths used for locating specific types of files.
 *
 * @package    Root
 * @author     Xhorse <xhorse@users.sourceforge.net>
 * @copyright  (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license    http://www.openfisma.org/mw/index.php?title=License
 * @version    $Id$
 */

/**
 * @todo remove DS from all files
 */
if (!defined('DS')) {
    define('DS', DIRECTORY_SEPARATOR);
}

/**
 * The root directory for the application
 */
if (!defined('ROOT')) {
    define ('ROOT', dirname(__FILE__));
}

/**
 * The root directory for the web site
 */
if (!defined('WEB_ROOT')) {
    define('WEB_ROOT', ROOT . DS . 'public');
}

define('APPS', ROOT . DS . 'apps');
define('CONFIGS', APPS . DS . 'config');
define('FORMS', APPS . DS . 'forms');
define('MODELS', APPS . DS . 'models');
define('VIEWS', APPS . DS . 'views');
define('CONTROLLERS', APPS . DS . 'controllers');
define('VENDORS', ROOT . DS . 'vendor');
define('LIBS', ROOT . DS . 'include');
define('LOG', ROOT . DS . 'log');
define('TEST', ROOT . '/test');
