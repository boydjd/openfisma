<?php
/**
 * Copyright (c) 2012 Endeavor Systems, Inc.
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

require_once(realpath(dirname(__FILE__) . '/../../Case/Unit.php'));
require_once(realpath(dirname(__FILE__) . '/../../vfsStream/vfsStream.php'));
/**
 * Initial test suite for /library/Fisma/FileSystem.php.
 *
 * @author     Mark ma <mark.ma@reyosoft.com>
 * @copyright  (c) Endeavor Systems, Inc. 2012 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Test
 * @subpackage Test_Library
 */
class Test_Library_Fisma_FileSystem extends Test_Case_Unit
{
    /**
     * Mocking the File system and setting up environment for virtual file system
     *
     * @return void
     */
    public function setUp()
    {
        vfsStreamWrapper::register();
        $root = new vfsStreamDirectory('aDir');
        vfsStreamWrapper::setRoot($root);
    }

    /**
     * Test the recursiveDelete() method
     *
     * @return void
     */
    public function testRecursiveDelete()
    {
        $url = vfsStream::url('aDir/id');

        // Create a new directory.
        $directory = vfsStream::url('aDir') . '/id';
        if (file_exists($directory) === false) {
            mkdir($directory, 0700, true);
        }
        
        $this->assertTrue(file_exists($url));

        Fisma_FileSystem::recursiveDelete($url);
        $this->assertFalse(file_exists($url));
    } 
}
