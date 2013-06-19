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
 * This static class is responsible for scanning for notifications which need to
 * be delivered, delivering the notifications, and then removing the sent
 * notifications from the queue.
 *
 * @author     Jim Chen <xhorse@users.sourceforge.net>
 * @copyright  (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Cron_Job
 *
 * @todo       Needs to be adjusted for timezone difference between DB and application when displaying timestamps
 */
class Fisma_Cli_Notify extends Fisma_Cli_Abstract
{
    /**
     * Get all notifications grouped by user_id
     *
     * @return Doctrine_Query
     */
    function getNotificationQuery()
    {
        $query = Doctrine_Query::create()
            ->from('Notification n')
            ->orderBy('n.email, n.eventId, n.createdTs');
        return $query;
    }

    /**
     * Iterate through the users and check who has notifications pending.
     *
     * @return void
     * @todo log the email send results
     */
    protected function _run()
    {
        $query = $this->getNotificationQuery();
        $notifications = $query->execute();

        self::sendNotificationEmail($notifications, $this->getLog());
    }

    /**
     * Compose and send the notification email for this user.
     *
     * Notice that there is a bit of a hack -- the addressing information is
     * stored in the 0 row of $notifications.
     *
     * @param array $notifications A group of rows from the notification table
     * @param Fisma_MailHandler_Abstract $mailHandler
     * @return void
     */
    static function sendNotificationEmail($notifications, $log, $mailHandler = null)
    {
        foreach ($notifications as $notification) {
            $options = array(
                'notification'  => $notification,
                'detail'        => Fisma::configuration()->getConfig('email_detail')
            );

            $mail = new Mail();

            $mail->recipient     = $notification->denormalizedEmail;
            $mail->recipientName = $notification->denormalizedRecipient;
            $mail->subject       = $notification->eventTitle;
            $mail->format        = 'html';

            $mail->mailTemplate('notification', $options);

            try {
                $handler = (isset($mailHandler)) ? $mailHandler : new Fisma_MailHandler_Immediate();
                $handler->setMail($mail)->send();
                $log->info(
                    Fisma::now() . " Email {$notification->id} was sent to {$mail->recipientName} <{$mail->recipient}>"
                );
                self::purgeNotification($notification);
            } catch (Zend_Mail_Exception $e) {
                $log->err("Failed Sending Email", $e);
            }
        }
    }

    /**
     * Remove notifications from the queue table.
     *
     * @param array $notifications A group of rows from the notifications table
     * @return void
     */
    static function purgeNotification($notification)
    {
        $notification->delete();
    }
}
