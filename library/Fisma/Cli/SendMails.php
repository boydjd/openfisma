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
 * Get all the records from queue mail table and send out each email record one by one.
 * The record is deleted once the email is sent out.
 * 
 * @author     Ben Zheng <ben.zheng@reyosoft.com>
 * @copyright  (c) Endeavor Systems, Inc. 2012 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Fisma
 * @subpackage Fisma_Cli
 */
class Fisma_Cli_SendMails extends Fisma_Cli_Abstract
{
    /**
     * Iterate through send the mail.
     * 
     * @return void
     */
    public function _run()
    {
        // Get all mail
        $mails = Doctrine::getTable('Mail')->findAll()->toArray();

        // Send mail immediately and delete mail after send successful
        $conn = Doctrine_Manager::connection();

        try {
            $conn->beginTransaction();

            foreach ($mails as $mail) {
                $this->_sendMail($mail['id']);
                $this->_purgeMail($mail['id']);
            }

            $conn->commit();
        } catch (Exception $e) {
            $conn->rollback();

            throw $e;
        }
    }

    /**
     * Send mail immediately to recipient.
     * 
     * @param integer $id mail id
     * @return void
     */
    private function _sendMail($id)
    {
        $mail = Doctrine::getTable('Mail')->find($id);

        try {
            $mailHandler = new Fisma_MailHandler_Immediate();
            $mailHandler->setMail($mail)->send();

            echo Fisma::now() . " Email was sent to {$mail->recipient}\n";
        } catch (Zend_Mail_Exception $e) {
            throw new Fisma_Zend_Exception_User("Failed Sending Email: " . $e->getMessage());
        }
    }

    /**
     * Remove mail from the mail table.
     * 
     * @param integer $id A mail id
     * @return void
     */
    private function _purgeMail($id)
    {
        Doctrine_Query::create()
            ->delete()
            ->from('Mail')
            ->where('id = ?', $id)
            ->execute();
    }
}
