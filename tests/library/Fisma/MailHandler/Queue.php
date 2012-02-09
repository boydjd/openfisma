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

require_once(realpath(dirname(__FILE__) . '/../../../Case/Unit.php'));

/**
 * test suite for /library/Fisma/MailHandler/Queue.php
 *
 * @author     Ben Zheng <ben.zheng@reyosoft.com>
 * @copyright  (c) Endeavor Systems, Inc. 2012 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Test
 * @subpackage Test_Library
 */
class Test_Library_Fisma_MailHandler_Queue extends Test_Case_Unit
{
    /**
     * setUp 
     * 
     * @access public
     * @return void
     */
    public function setUp()
    {
        Fisma::setConfiguration(new Fisma_Configuration_Array(), true);

        Fisma::configuration()->setConfig('sender', 'test@openfisma.org');
        Fisma::configuration()->setConfig('system_name', 'OpenFISMA');

        $mail = new Mail();
        $mail->recipient     = 'recipient@example.com';
        $mail->recipientName = 'recipient';
        $mail->subject       = 'my subject';
        $mail->body          = 'test mail';

        $this->mail = $mail;

        $this->queue = new Fisma_MailHandler_Queue;
    }

    /**
     * Test case for getting mail sender and send name from parameter
     * 
     * @return void
     */
    public function testGetSenderAndSenderNameFromParameter()
    {
        $this->mail->sender        = 'testmail@example.com';
        $this->mail->senderName    = 'testmail';

        $this->queue->setMail($this->mail);

        $mail = $this->queue->getMail();

        $this->assertEquals('recipient@example.com', $mail->recipient);
        $this->assertEquals('recipient', $mail->recipientName);
        $this->assertEquals('testmail@example.com', $mail->sender);
        $this->assertEquals('testmail', $mail->senderName);
        $this->assertEquals('my subject', $mail->subject);
        $this->assertEquals('test mail', $mail->body);
    }

    /**
     * Test case for getting mail sender and send name from configuration
     * 
     * @return void
     */
    public function testGetSenderAndSenderNameFromConfig()
    {
        $this->queue->setMail($this->mail);

        $mail = $this->queue->getMail();

        $this->assertEquals('recipient@example.com', $mail->recipient);
        $this->assertEquals('recipient', $mail->recipientName);
        $this->assertEquals('my subject', $mail->subject);
        $this->assertEquals('test mail', $mail->body);

        // Default sender and senderName from configuration
        $this->assertEquals('test@openfisma.org', $mail->sender);
        $this->assertEquals('OpenFISMA', $mail->senderName);
    }
}

