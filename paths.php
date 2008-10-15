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
 * The application root. This is the directory which contains paths.php.
 */
define('ROOT', realpath(dirname(__FILE__)));

if (!defined('DS')) {
    /**
     * Eventually this definition should be removed.
     * It doesn't serve any purpose
     *
     * but unfortunately it is used heavily in our legacy php code.
     */
    define('DS', DIRECTORY_SEPARATOR);
}

define('APPS', ROOT . '/apps');
define('CONFIGS', APPS . '/config');
define('CONTROLLERS', APPS . '/controllers');
define('FORMS', APPS . '/Form');
define('LIBS', ROOT . '/include');
define('LOG', ROOT . '/log');
define('MIGRATIONS', ROOT . '/migrations');
define('MODELS', APPS . '/models');
define('TEST', ROOT . '/test');
define('VENDORS', ROOT . '/vendor');
define('VIEWS', APPS . '/views');
define('WEB_ROOT', ROOT . '/public');


// Update the class path for includes
$includeDirectories = array(
    CONTROLLERS,
    MODELS,
    VENDORS,
    APPS,
    LIBS,
    // Have to hack in the path to Pear since it doesn't follow ZF standards:
    VENDORS . '/Pear'
);
ini_set('include_path',
    implode(':', $includeDirectories) . ':' . ini_get('include_path'));
