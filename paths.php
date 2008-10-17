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

define('APPS', ROOT . '/apps');
define('CONFIGS', APPS . '/config');
define('CONTROLLERS', APPS . '/controllers');
define('MODELS', APPS . '/models');
define('LOG', ROOT . '/log');
define('MIGRATIONS', ROOT . '/migrations');
define('TEST', ROOT . '/test');
define('VENDORS', ROOT . '/library');
define('LOCAL', ROOT . '/library/local');
define('VIEWS', APPS . '/views');
define('WEB_ROOT', ROOT . '/public');


// Update the class path for includes
$includeDirectories = array(
    CONTROLLERS,
    MODELS,
    VENDORS,
    APPS,
    LIBS,
    LOCAL,
    // Have to hack in the path to Pear since it doesn't follow ZF standards:
    VENDORS . '/Pear'
);
ini_set('include_path',
    implode(PATH_SEPARATOR, $includeDirectories) . PATH_SEPARATOR . ini_get('include_path'));
