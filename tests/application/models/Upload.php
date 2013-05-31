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
 * Test_Application_Models_Upload
 *
 * @uses Test_Case_Unit
 * @package Test
 * @copyright (c) Endeavor Systems, Inc. 2011 {@link http://www.endeavorsystems.com}
 * @author Duy K. Bui <duy.bui@endeavorsystems.com>
 * @license http://www.openfisma.org/content/license GPLv3
 */
class Test_Application_Models_Upload extends Test_Case_Unit
{
    /**
     * Mask Fisma_FileManager in Zend Registry with a mock
     *
     * @return void
     */
    public function setUp()
    {
        $fm = $this->getMock(
            'Fisma_FileManager',
            array(
                'getFileSize',
                'store'
            ),
            array('test_uploads', $this->getMock('finfo', array('file')))
        );
        $fm->expects($this->any())->method('_fileExists')->will($this->returnValue(true));
        $fm->getFileSize('a');
        Zend_Registry::set('fileManager', $fm);
    }

    /**
     * Test the checkBlackList() method with blacklisted extension
     *
     * @return void
     */
    public function testCheckBlackListInvalidExtension()
    {
        $u = new Upload;

        $validFile = array(
            'name' => 'specs.docx',
            'type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
        );
        $u->checkFileBlackList($validFile);

        $invalidFile = array(
            'name' => 'specs.html',
            'type' => 'application/xml'
        );
        $this->setExpectedException('Fisma_Zend_Exception_User');
        $u->checkFileBlackList($invalidFile);
    }

    /**
     * Test the checkFileBlackList() method with blacklisted MIME type
     *
     * @return void
     */
    public function testCheckBlackListInvalidMime()
    {
        $u = new Upload;

        $invalidFile = array(
            'name' => 'specs',
            'type' => 'text/javascript'
        );
        $this->setExpectedException('Fisma_Zend_Exception_User');
        $u->checkFileBlackList($invalidFile);
    }

    /**
     * Test the getDisplayFileSize() method
     *
     * @return void
     */
    public function testGetDisplayFileSize()
    {
        $fm = Zend_Registry::get('fileManager');
        $fm->expects($this->any())->method('getFileSize')->will($this->onConsecutiveCalls(
            500,
            512000,
            524288000,
            536870912000
        ));

        $u = new Upload;
        $this->assertEquals('500 bytes', $u->getDisplayFileSize());
        $this->assertEquals('500.0 KB', $u->getDisplayFileSize());
        $this->assertEquals('500.0 MB', $u->getDisplayFileSize());
        $this->assertEquals('500.0 GB', $u->getDisplayFileSize());
    }

    /**
     * Test the getIconUrl() method
     *
     * @return void
     */
    public function testGetIconUrl()
    {
        $u = new Upload;

        $this->assertContains('unknown', $u->getIconUrl());

        $u->fileName = 'specs.docx';
        $this->assertContains('docx', $u->getIconUrl());
    }

    /**
     * Test the instantiate() method
     *
     * @return void
     */
    public function testInstantiation()
    {
        $u = new Upload;

        $name = 'specs.dox';
        $file = array('name' => $name);

        $hash = '1234567890123456789012345678901234567890';
        $fm = Zend_Registry::get('fileManager');
        $fm->expects($this->any())->method('store')->will($this->returnValue($hash));

        $uId = 1;
        $user = new User;
        $user->id = $uId;
        CurrentUser::setInstance($user);

        $uIp = '127.0.0.1';
        $_SERVER['REMOTE_ADDR'] = $uIp;

        $u->instantiate($file);

        $this->assertEquals($name, $u->fileName);
        $this->assertEquals($hash, $u->fileHash);
        $this->assertEquals($uId, $u->User->id);
        $this->assertEquals($uIp, $u->uploadIp);
    }
}
