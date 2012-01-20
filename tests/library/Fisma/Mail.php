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

/**
 * Tests for the mail class
 * 
 * @author     Ben Zheng <ben.zheng@reyosoft.com>
 * @copyright  (c) Endeavor Systems, Inc. 2012 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Test
 * @subpackage Test_Fisma
 */
class Test_Library_Fisma_Mail extends Test_Case_Unit
{
    protected function setUp()
    {
        Fisma::setConfiguration(new Fisma_Configuration_Array(), true);

        Fisma::configuration()->setConfig('sender', 'test@openfisma.org');
        Fisma::configuration()->setConfig('system_name', 'OpenFISMA');

        $this->configs = array(
            'recipient'     => 'testmail@example.com',
            'recipientName' => 'testmail',
            'subject'       => 'my subject',
            'body'          => 'hello world!',
        );

        $this->mail = new Fisma_Mail($this->configs);
    }

    public function testMagic()
    {
        $this->assertEquals('testmail@example.com', $this->mail->recipient);
        $this->assertEquals('testmail', $this->mail->recipientName);
        $this->assertEquals('test@openfisma.org', $this->mail->sender);
        $this->assertEquals('OpenFISMA', $this->mail->senderName);
        $this->assertEquals('my subject', $this->mail->subject);
        $this->assertEquals('hello world!', $this->mail->body);
        $this->mail->__set('recipient', 'recipient@example.com');
        $this->assertEquals('recipient@example.com', $this->mail->__get('recipient'));
        $this->assertNull($this->mail->__get('test'));

        try {
            $this->mail->__get('hello world');
            $this->fail('key is NOT in variable, should have thrown an exception');
        } catch (Exception $e) {
            $this->assertTrue(true);
        }
    }
}
