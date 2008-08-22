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
    define('WEB_ROOT', ROOT . '/public');
}

define('APPS', ROOT . '/apps');
define('CONFIGS', APPS . '/config');
define('FORMS', APPS . '/forms');
define('MODELS', APPS . '/models');
define('VIEWS', APPS . '/views');
define('CONTROLLERS', APPS . '/controllers');
define('VENDORS', ROOT . '/vendor');
define('LIBS', ROOT . '/include');
