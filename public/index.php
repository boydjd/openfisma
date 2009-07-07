<?php
/**
 * Copyright (c) 2008 Endeavor Systems, Inc.
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
 * <http://www.gnu.org/licenses/>.
 *
 * @author    Mark E. Haase <mhaase@endeavorsystems.com>
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/mw/index.php?title=License
 * @version   $Id$
 */

require_once(realpath(dirname(__FILE__) . '/../library/Fisma.php'));

try {
    Fisma::initialize(Fisma::RUN_MODE_WEB_APP);
    Fisma::connectDb();
    Fisma::dispatch();
} catch (Zend_Config_Exception $zce) {
    // A zend config exception indicates that the application may not be installed properly
    echo '<h1>The application is not installed correctly</h1>';
    echo '<p>If you have not run the installer, you should do that now.</p>';
} catch (Exception $exception) {
    // If a bootstrap exception occurs, that indicates a serious problem, such as a syntax error.
    // At this point, we can't rely on any application code, so rather than check for debug mode,
    // we just display the error message.
    echo '<h1>An exception occurred while bootstrapping the application.</h1>';
    echo '<p>' 
         . get_class($exception) 
         . '</p><p>' 
         . $exception->getMessage() 
         . '</p><p>'
         . "<p><pre>Stack Trace:\n" 
         . $exception->getTraceAsString() 
         . '</pre></p>';
}
