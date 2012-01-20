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

        $this->configs = array(
            'recipient'     => 'recipient@example.com',
            'recipientName' => 'recipient',
            'sender'        => 'testmail@example.com',
            'senderName'    => 'testmail',
            'subject'       => 'my subject',
            'body'          => 'test mail'
        );

        $this->queue = new Fisma_MailHandler_Queue;
    }


    /**
     * Test case for getting mail sender and send name from parameter
     */
    public function testGetSenderAndSenderNameFromParameter()
    {
        $mail = new Fisma_Mail($this->configs);
        $this->queue->setMail($mail);

        $this->assertEquals('recipient@example.com', $this->queue->getMail()->recipient);
        $this->assertEquals('recipient', $this->queue->getMail()->recipientName);
        $this->assertEquals('testmail@example.com', $this->queue->getMail()->sender);
        $this->assertEquals('testmail', $this->queue->getMail()->senderName);
        $this->assertEquals('my subject', $this->queue->getMail()->subject);
        $this->assertEquals('test mail', $this->queue->getMail()->body);
    }

    /**
     * Test case for getting mail sender and send name from configuration
     */
    public function testGetSenderAndSenderNameFromConfig()
    {
        $this->configs['sender'] = null;
        $this->configs['senderName'] = null;

        $mail = new Fisma_Mail($this->configs);
        $this->queue->setMail($mail);

        $this->assertEquals('recipient@example.com', $this->queue->getMail()->recipient);
        $this->assertEquals('recipient', $this->queue->getMail()->recipientName);
        $this->assertEquals('my subject', $this->queue->getMail()->subject);
        $this->assertEquals('test mail', $this->queue->getMail()->body);

        // Default sender and senderName from configuration
        $this->assertEquals('test@openfisma.org', $this->queue->getMail()->sender);
        $this->assertEquals('OpenFISMA', $this->queue->getMail()->senderName);
    }
}

