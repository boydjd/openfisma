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

/**
 * get the root path
 *
 */
class RootPath
{
    /**
     * for keeping the path
     *
     * @var string $_root
     */
    private static $_root = null;
    
    /**
     * get the root path and keep it in a private variable
     * return the path from the private variable
     *
     * @return root path
     */
    static function getRootPath()
    {
        if (self::$_root == null) {
            $path = dirname(__FILE__);
            $pathPart = explode(DIRECTORY_SEPARATOR, $path);
            array_pop($pathPart);
            self::$_root = implode(DIRECTORY_SEPARATOR, $pathPart);
        }
        return self::$_root;
    }
}
// initialize the include path
set_include_path(RootPath::getRootPath() .'/library' . PATH_SEPARATOR . 
                 RootPath::getRootPath() .'/application' . PATH_SEPARATOR . get_include_path());
// Zend should be linked/located at the root path/library/Zend
require_once 'Zend/Loader.php';
Zend_Loader::registerAutoload();
