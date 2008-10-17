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
require  '../apps/bootstrap.php';

$front = Zend_Controller_Front::getInstance();
// It's wrapped in a try-catch to provide a high-level error facility.
try {
    $front->dispatch();
} catch (Exception $e) {
    $log = Config_Fisma::getInstance()->getLogInstance();
    // Get the stack trace and indent it by 4 spaces
    $stackTrace = $e->getTraceAsString();
    $stackTrace = preg_replace("/^/", "    ", $stackTrace);
    $stackTrace = preg_replace("/\n/", "\n    ", $stackTrace);

    // Log the error message and stack trace.
    $log->log($e->getMessage() . "\n$stackTrace",
              Zend_Log::ERR);
              
    // @todo This needs to be improved. Ideally we'd show a real page that has
    // administrator contact info.
    echo "An unrecoverable error has occured. The error has been logged and"
       . " an administrator will review the issue shortly";
}
