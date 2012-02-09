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
 * test suite for /library/Fisma/MailHandler/Immediate.php
 *
 * @author     Ben Zheng <ben.zheng@reyosoft.com>
 * @copyright  (c) Endeavor Systems, Inc. 2012 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Test
 * @subpackage Test_Library
 */
class Test_Library_Fisma_MailHandler_Immediate extends Test_Case_Unit
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
        Fisma::configuration()->setConfig('send_type', 'sendmail');
    }

    /**
     * Test case for mail data by parameter
     * 
     * @return void
     */
    public function testMail()
    {
        $mail = new Mail;
        $mail->recipient     = 'recipient@example.com';
        $mail->recipientName = 'recipient';
        $mail->sender        = 'testmail@example.com';
        $mail->senderName    = 'testmail';
        $mail->subject       = 'my subject';
        $mail->body          = 'test mail';

        $queue = new Fisma_MailHandler_Immediate;
        $queue->setMail($mail);

        $this->assertEquals('recipient@example.com', $queue->getMail()->recipient);
        $this->assertEquals('recipient', $queue->getMail()->recipientName);
        $this->assertEquals('testmail@example.com', $queue->getMail()->sender);
        $this->assertEquals('testmail', $queue->getMail()->senderName);
        $this->assertEquals('my subject', $queue->getMail()->subject);
        $this->assertEquals('test mail', $queue->getMail()->body);
    }

    /**
     * Test case for transport
     * 
     * @return void
     */
    public function testTransport()
    {
        $mailHandler = new Fisma_MailHandler_Immediate;
        $transport = $mailHandler->getTransport();

        $this->assertTrue($transport instanceof Zend_Mail_Transport_Abstract);

        // Default type of send mail is sendmail
        $this->assertEquals('Zend_Mail_Transport_Sendmail', get_class($transport));

        // Set send type of configuration to smtp
        Fisma::configuration()->setConfig('send_type', 'smtp');
        $transport = $mailHandler->getTransport();
        $this->assertEquals('Zend_Mail_Transport_Smtp', get_class($transport));

        // Parameter is smtp object
        $smtp = new Zend_Mail_Transport_Smtp();
        $transport = $mailHandler->setTransport($smtp);
        $this->assertEquals('Zend_Mail_Transport_Smtp', get_class($mailHandler->getTransport()));
    }
}
