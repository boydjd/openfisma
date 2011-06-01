<?php
/**
 * Copyright (c) 2010 Endeavor Systems, Inc.
 *
 * This file is part of OpenFISMA.
 *
 * OpenFISMA is free software: you can redistribute it and/or modify it under the terms of the GNU General Public
 * License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later
 * version.
 *
 * OpenFISMA is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied
 * warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for more
 * details.
 *
 * You should have received a copy of the GNU General Public License along with OpenFISMA.  If not, see
 * {@link http://www.gnu.org/licenses/}.
 */

require_once(realpath(dirname(__FILE__) . '/../../library/Fisma.php'));

defined('APPLICATION_ENV')
    || define(
        'APPLICATION_ENV',
        (getenv('APPLICATION_ENV') ? getenv('APPLICATION_ENV') : 'production')
    );

defined('APPLICATION_PATH') || define(
    'APPLICATION_PATH',
    realpath(dirname(__FILE__) . '/../../application')
);

set_include_path(
    APPLICATION_PATH . '/../library/Symfony/Components' . PATH_SEPARATOR .
    APPLICATION_PATH . '/../library' .  PATH_SEPARATOR .
    get_include_path()
);

require_once 'Zend/Application.php';

$application = new Zend_Application(
    APPLICATION_ENV,
    APPLICATION_PATH . '/config/application.ini'
);

Fisma::setAppConfig($application->getOptions());
Fisma::initialize(Fisma::RUN_MODE_COMMAND_LINE);
$application->bootstrap('Db');
$application->bootstrap('SearchEngine');
Fisma::setConfiguration(new Fisma_Configuration_Database);

// Warning message if somebody tries to run the bootstrap file directly
if (basename(__FILE__) == $_SERVER['SCRIPT_NAME']) {
    fwrite(STDERR, basename(__FILE__) . " is not an executable script\n");
}
