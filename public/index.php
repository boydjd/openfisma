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
 * @package   Application
 */

require_once('../application/init.php');

$dbg = true;

try {
    $plSetting = new Fisma_Controller_Plugin_Setting();
    $dbg = $plSetting->debug();
    $front = Fisma_Controller_Front::getInstance();
    $front->registerPlugin($plSetting, 60); //this should be the highest priority
    if ($plSetting->installed()){
        $pl = new Fisma_Controller_Plugin_Web();
    } else {
        $pl = new Fisma_Controller_Plugin_Install();
    }
    $front->registerPlugin($pl);
    $front->dispatch();
} catch (Exception $exception) {
    echo '<html><body><center>'
       . 'An exception occured while bootstrapping the application.';
    if ($dbg) {
         echo '<br /><br />' . $exception->getMessage() . '<br />'
            . '<div align="left">Stack Trace:' 
            . '<pre>' . $exception->getTraceAsString() . '</pre></div>';
    }
}
