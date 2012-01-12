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

/**
 * A mail handler implementation which send email immediately
 * 
 * @author     Ben Zheng <ben.zheng@reyosoft.com>
 * @copyright  (c) Endeavor Systems, Inc. 2012 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Fisma
 * @subpackage Fisma_MailHandler
 */
class Fisma_MailHandler_Immediate extends Fisma_MailHandler_Abstract
{
    /*
     * Send mail immediately
     */
    public function _send()
    {
        $recipient = $this->getMail()->recipient;
        $recipientName = $this->getMail()->recipientName;
        $sender = $this->getMail()->sender;
        $senderName = $this->getMail()->senderName;
        $subject = $this->getMail()->subject;
        $body = $this->getMail()->body;

        $mail = new Zend_Mail();
        $mail->setFrom($sender, $senderName);
        $mail->addTo($recipient, $recipientName);
        $mail->setSubject($subject);
        $mail->setBodyText($body);

        $transport = $this->getMail()->transport;
        $transport = $transport ? $transport : $this->getTransport();
        try {
            $mail->send($transport);
        } catch (Exception $excetpion) {
        }
    }
}

