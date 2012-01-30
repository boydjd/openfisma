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

require_once(realpath(dirname(__FILE__) . '/../../Case/Unit.php'));

/**
 * Initial test suite for /library/Fisma/FileManager.php, one-lined methods are left out
 *
 * @author     Duy K. Bui <duy.bui@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2011 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Test
 * @subpackage Test_Library
 */
class Test_Library_Fisma_FileManager extends Test_Case_Unit
{
    /**
     * The base directory for FileManager
     */
    var $baseDir = "test_upload";

    /**
     * A mock object for FileInfo library
     */
    var $fi;

    /**
     * The default hash for all tests
     */
    var $hash = '1234567890123456789012345678901234567890';
    
    /**
     * Mocking the FileInfo and setting up environment for virtual file system
     *
     * Might be implemented more gracefully using https://github.com/mikey179/vfsStream
     *  
     * @return void
     */
    public function setup()
    {
        $this->fi = $this->getMock('finfo', array('file'));
    }

    /**
     * Test the getMimeType() method
     *
     * @return void
     */
    public function testGetMimeType()
    {
        $mime = 'image/png';

        $this->fi->expects($this->any())->method('file')->will($this->returnValue($mime));
        $fm = $this->getMock('Fisma_FileManager', array('_fileExists'), array($this->baseDir, $this->fi));
        $fm->expects($this->any())->method('_fileExists')->will($this->onConsecutiveCalls(true, false));
        
        $this->assertEquals($mime, $fm->getMimeType($this->hash));
        $this->setExpectedException('Fisma_FileManager_Exception');
        $fm->getMimeType($this->hash);
    }

    /**
     * Test the stream() method
     * 
     * @return void
     */
    public function testStream()
    {
        $fm = $this->getMock('Fisma_FileManager', array('_fileExists', '_readfile'), array($this->baseDir, $this->fi));
        $fm->expects($this->any())->method('_fileExists')->will($this->onConsecutiveCalls(true, false));
        $fm->expects($this->once())->method('_readfile');
        
        $fm->stream($this->hash);

        $this->setExpectedException('Fisma_FileManager_Exception');
        $fm->stream($this->hash);
    }

    /**
     * Test the copyTo() method
     * 
     * @return void
     */
    public function testCopyTo()
    { 
        $fm = $this->getMock('Fisma_FileManager', array('_fileExists', '_copy'), array($this->baseDir, $this->fi));
        $fm->expects($this->any())->method('_fileExists')->will($this->returnValue(true));
        $fm->expects($this->exactly(2))->method('_copy')->will($this->onConsecutiveCalls(true, false));
        $fm->copyTo($this->hash, 'admin_avatar.png');
        $this->setExpectedException('Fisma_FileManager_Exception');
        $fm->copyTo($this->hash, 'admin_avatar.png');
    }

    /**
     * Test the copyTo() when file_exists() returns false, must be split out 
     * because setExpectedException suppressed all subsequent assertions
     * 
     * @return void
     */
    public function testCopyToWithInvalidHash()
    {
        $fm = $this->getMock('Fisma_FileManager', array('_fileExists', '_copy'), array($this->baseDir, $this->fi));
        $fm->expects($this->any())->method('_fileExists')->will($this->returnValue(false));
        $this->setExpectedException('Fisma_FileManager_Exception');
        $fm->copyTo($this->hash, 'admin_avatar.png');
    }

    /**
     * Test the store() method with the scenario where every step gets run
     * 
     * @return void
     */
    public function testStoreRunThrough()
    {
        $fm = $this->getMock(
            'Fisma_FileManager',
            array(
                '_sha1File',
                '_fileExists',
                '_mkdir',
                '_copy'
            ),
            array($this->baseDir, $this->fi)
        );
        $fm->expects($this->any())->method('_sha1File')->will($this->returnValue($this->hash));
        $fm->expects($this->any())->method('_fileExists')->will($this->returnValue(false));
        $fm->expects($this->any())->method('_mkdir')->will($this->returnValue(true));
        $fm->expects($this->any())->method('_copy')->will($this->onConsecutiveCalls(true, false));

        $fm->store('temp_file'); 
        $this->setExpectedException('Fisma_FileManager_Exception');
        $fm->store('temp_file'); 
    }

    /**
     * Test the store() method with the scenario where it doesn't have to do anything
     * 
     * @return void
     */
    public function testStoreDoNothing()
    {
        $fm = $this->getMock(
            'Fisma_FileManager',
            array(
                '_sha1File',
                '_fileExists',
            ),
            array($this->baseDir, $this->fi)
        );
        $fm->expects($this->any())->method('_sha1File')->will($this->returnValue($this->hash));
        $fm->expects($this->any())->method('_fileExists')->will($this->returnValue(true));

        $fm->store('temp_file'); 
    }

    /**
     * Test the store() method when calculating hash fails 
     * 
     * @return void
     */
    public function testStoreHashFail()
    {
        $fm = $this->getMock(
            'Fisma_FileManager',
            array(
                '_sha1File',
                '_fileExists',
                '_mkdir',
                '_copy'
            ),
            array($this->baseDir, $this->fi)
        );
        $fm->expects($this->any())->method('_sha1File')->will($this->returnValue(null));

        $this->setExpectedException('Fisma_FileManager_Exception');
        $fm->store('temp_file'); 
    }

    /**
     * Test the store() method when making directories fails
     * 
     * @return void
     */
    public function testStpreMkdirFail()
    {
        $fm = $this->getMock(
            'Fisma_FileManager',
            array(
                '_sha1File',
                '_fileExists',
                '_mkdir',
            ),
            array($this->baseDir, $this->fi)
        );
        $fm->expects($this->any())->method('_sha1File')->will($this->returnValue($this->hash));
        $fm->expects($this->any())->method('_fileExists')->will($this->returnValue(false));
        $fm->expects($this->any())->method('_mkdir')->will($this->returnValue(false));

        $this->setExpectedException('Fisma_FileManager_Exception');
        $fm->store('temp_file'); 

    }
    
    /**
     * Test the getFileSize() method
     *
     * @return void
     */
    public function testGetFileSize()
    {
        $fm = $this->getMock(
            'Fisma_FileManager',
            array(
                '_fileExists',
                '_filesize'
            ),
            array($this->baseDir, $this->fi)
        );
        $fm->expects($this->any())->method('_fileExists')->will($this->onConsecutiveCalls(true, false));
        $fm->expects($this->once())->method('_filesize')->will($this->returnValue(0));
        
        $this->assertEquals(0, $fm->getFileSize($this->hash));
    }

    /**
     * Test the remove() method
     * 
     * @return void
     */
    public function testRemove()
    {
        $fm = $this->getMock(
            'Fisma_FileManager',
            array(
                '_fileExists',
                '_unlink'
            ),
            array($this->baseDir, $this->fi)
        );
        $fm->expects($this->any())->method('_fileExists')->will($this->onConsecutiveCalls(true, true, false));
        $fm->expects($this->exactly(2))->method('_unlink')->will($this->onConsecutiveCalls(true, false));
        $this->assertTrue($fm->remove($this->hash));
        $this->assertFalse($fm->remove($this->hash));
        $this->assertTrue($fm->remove($this->hash));
    }
}

