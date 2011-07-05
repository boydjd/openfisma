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
 * {@link http://www.gnu.org/licenses/}.
 */

/**
 * A utility class containing static methods for interacting with the filesystem
 * 
 * @author     Mark E. Haase
 * @copyright  (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Fisma
 * @subpackage Fisma_FileSystem
 */
class Fisma_FileSystem
{
    /**
     * Recursively delete a directory and all of its contents
     * 
     * @param dir
     */
    static function recursiveDelete($dir) 
    {
        if (is_file($dir)) {
            return unlink($dir);
        } elseif (is_dir($dir)) {
            $scan = glob(rtrim($dir, '/') . '/*');
            foreach ($scan as $index => $path) {
                self::recursiveDelete($path);
            }
            return rmdir($dir);
        }
    }
}
