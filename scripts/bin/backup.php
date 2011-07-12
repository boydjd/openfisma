#!/usr/bin/env php
<?php
/**
 * Copyright (c) 2011 Endeavor Systems, Inc.
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

/**
    backup.php

    This is a script to take a backup of an OpenFISMA application
    instance. The script makes a copy of all source code files and also
    produces a schema dump. The backup is tar'ed and gzip'ed.

    Before running this script, make sure to edit the
    backup-restore.cfg file to specify the proper database access
    properties.

    The script is designed to run in a POSIX environment, but may run
    under windows if a compatible mysqldump and tar executable exists
    in the path.

    @author     Dale Frey
    @copyright  (c) Endeavor Systems, Inc. 2011 {@link http://www.endeavorsystems.com}
    @license    http://www.openfisma.org/content/license GPLv3
    @package  
    @version    $Id$
 */

require_once(realpath(dirname(__FILE__) . '/bootstrap.php'));

$cli = new Fisma_Cli_Backup();
$cli->run();

?>

