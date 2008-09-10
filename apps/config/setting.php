<?php
/**
 * Copyright (c) 2008 Endeavor Systems, Inc.
 *
 * This file is part of OpenFISMA.
 *
 * OpenFISMA is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * OpenFISMA is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with OpenFISMA.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author    Jim Chen <xhorse@users.sourceforge.net>
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/mw/index.php?title=License
 * @version   $Id$
 */

require_once 'Zend/Registry.php';
require_once 'Zend/Config.php';
require_once 'Zend/Config/Ini.php';
define('CONFIGFILE_NAME', 'install.conf');
define('ERROR_LOG', 'error.log');
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
            foreach ($config->debug->xdebug as $k => $v) {
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


