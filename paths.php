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

define('APPS', ROOT . '/apps');
define('CONFIGS', APPS . '/config');
define('CONTROLLERS', APPS . '/controllers');
define('FORMS', APPS . '/forms');
define('LIBS', ROOT . '/include');
define('LOG', ROOT . '/log');
define('MIGRATIONS', ROOT . '/migrations');
define('MODELS', APPS . '/models');
define('TEST', ROOT . '/test');
define('VENDORS', ROOT . '/vendor');
define('VIEWS', APPS . '/views');
