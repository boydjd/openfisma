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
        return Doctrine_Query::create()
            ->select('n.*, u.email, u.displayName, u.locked, e.description')
            ->from('Notification n, n.User u, n.Event e')
            ->orderBy('n.userId, n.eventId, n.createdTs');
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

        // Loop through the groups of notifications, concatenate all messages
        // per user into a single array, then call the e-mail function for
        // each user. If the e-mail is successful, then remove the notifications
        // from the table and update the most_recent_notify_ts timestamp.
        $currentNotifications = array();
        for ($i = 0; $i < count($notifications); $i++) {
            $currentNotifications[] = $notifications[$i];

            // If this is the last entry OR if the next entry has a different
            // user ID or event ID, then this current message is completed and should be
            // e-mailed to the user.
            if (($i == (count($notifications) - 1)) ||
                ($notifications[$i]->userId != $notifications[$i+1]->userId) ||
                ($notifications[$i]->eventId != $notifications[$i+1]->eventId)
            ) {
                $this->sendNotificationEmail($currentNotifications);
                $this->purgeNotifications($currentNotifications);
                $notifications[$i]->User->updateNotificationTs();
                // Move onto the next user
                $currentNotifications = array();
            }
        }
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
    function sendNotificationEmail($notifications, $mailHandler = null)
    {
        $user = $notifications[0]->User;
        $event = $notifications[0]->Event;

        if (
            ($user->locked || !empty($user->deleted_at))
            && $event->name !== 'USER_LOCKED'
            && $event->name !== 'USER_DISABLED'
        ) {
            return; // don't send anything
        }

        $options = array('notifyData' => $notifications);

        $mail = new Mail();

        $mail->recipient     = $user->email;
        $mail->recipientName = $user->displayName;
        $mail->subject       = "[" . Fisma::configuration()->getConfig('system_name') . "] " . $event->description;

        $mail->mailTemplate('notification', $options);

        try {
            $handler = (isset($mailHandler)) ? $mailHandler : new Fisma_MailHandler_Immediate();
            $handler->setMail($mail)->send();
            $this->getLog()->info(Fisma::now() . " Email was sent to {$user->email}");
        } catch (Zend_Mail_Exception $e) {
            $this->getLog()->err("Failed Sending Email");
            $this->getLog()->err($e);
        }
    }

    /**
     * Remove notifications from the queue table.
     *
     * @param array $notifications A group of rows from the notifications table
     * @return void
     */
    function purgeNotifications($notifications)
    {
        $notificationIds = array();
        foreach ($notifications as $notification) {
            $notification->delete();
        }

    }
}
