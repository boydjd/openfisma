<?php
/**
 * setting.php
 *
 * Setting for the whole sysetm
 *
 * @package Config
 * @author     Xhorse xhorse at users.sourceforge.net
 * @copyright  (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license    http://www.openfisma.org/mw/index.php?title=License
 * @version $Id$
 */
require_once 'Zend/Registry.php';
require_once 'Zend/Config.php';
require_once 'Zend/Config/Ini.php';
define( 'CONFIGFILE_NAME', 'install.conf');


//assuming not installed first unless it is
Zend_Registry::set('installed', false);
if (is_file(CONFIGS . DS . CONFIGFILE_NAME)) {
    $config = new Zend_Config_Ini(CONFIGS . DS . CONFIGFILE_NAME);
    if (!empty($config->database)) {
        Zend_Registry::set('datasource', $config->database);
        Zend_Registry::set('installed', true);
    }
    // Debug setting
    if (!empty($config->debug)) {
        if ($config->debug->level > 0) {
            error_reporting(E_ALL);
            ini_set('display_errors', 1);
            foreach($config->debug->xdebug as $k => $v) {
                if ($k == 'start_trace') {
                    if (1 == $v && function_exists('xdebug_start_trace')) {
                        xdebug_start_trace();
                    }
                } else {
                    @ini_set('xdebug.' . $k, $v);
                }
            }
        }
    }
    ///@todo system wide log setting
    
}


