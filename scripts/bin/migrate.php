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
 * @author    Ryan yang<ryan.yang@reyosoft.com>
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/mw/index.php?title=License
 * @version   $Id:$
 * @package   
 */

/**
 * This script migrates the database schema from its existing version to the
 * version designated in the param.
 *
 * @package
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/mw/index.php?title=License
 */
require_once('../../application/init.php');
$plSetting = new Fisma_Controller_Plugin_Setting();

if (!$plSetting->installed()) {
    die('Please install!');
}

// Initialize our DB connection
$datasource = Zend_Registry::get('datasource');
$dsn        = $datasource->params->toArray();

$migrationDir = '../../application/doctrine/migrations/';

Doctrine_Manager::connection("mysql://$dsn[username]:$dsn[password]@$dsn[host]/$dsn[dbname]");
$migration  = new Doctrine_Migration($migrationDir);

//Migrate to the latest version, also ,you can type "$migration->migrate(1)" to the version 1
$migration->migrate(0); 

print('Migration successful.Current version is ' . $migration->getCurrentVersion() . '\n'); 
