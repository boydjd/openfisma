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
 * @todo       Needs cleanup
 * @todo       Needs to be adjusted for timezone difference between DB and application when displaying timestamps
 */
class Fisma_Cli_Notify
{
    /**
     * Get all notifications grouped by user_id
     *
     * @todo can't find a way to do this in DQL... substituting a mysql raw connection for now.
     * @return Doctrine_RawSql
     */
    function getNotificationQuery()
    {
         /*$query = Doctrine_Query::create()
                    ->select('n.*, u.email, u.notifyFrequency')
                    ->from('Notification n')
                    ->innerJoin('n.User u')
                    ->where('u.emailValidate = 1')
                    ->addWhere('u.mostRecentNotifyTs is NULL OR u.mostRecentNotifyTs <= ?'. 
                               new Doctrine_Expression("DATE_SUB(NOW(), INTERVAL u.notifyFrequency HOUR)"))
                    ->orderBy('n.userId');*/
        $query = new Doctrine_RawSql();
        $query->select('{n.eventtext}, {n.createdts}, {u.email}, {u.nameFirst}, {u.nameLast}')
              ->addComponent('n', 'Notification n')
              ->addComponent('u', 'n.User u')
              ->from('poc u INNER JOIN notification n on u.id = n.userid')
              ->where('u.type = "User"')
              ->andWhere(
                  '(u.mostrecentnotifyts IS NULL '
                 .'OR u.mostrecentnotifyts <= DATE_SUB(NOW(), INTERVAL u.notifyFrequency HOUR))'
              )
              ->andWhere('(u.locked = FALSE OR (u.locked = TRUE AND u.locktype = "manual"))')
              ->orderBy('u.id, n.createdts');
        return $query;
    }

    /**
     * Iterate through the users and check who has notifications pending.
     * 
     * @return void
     * @todo log the email send results
     */
    function processNotificationQueue() 
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
            // user ID, then this current message is completed and should be
            // e-mailed to the user.
            if ($i == (count($notifications) - 1)
                || ($notifications[$i]->userId !=
                    $notifications[$i+1]->userId)) {

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
     * @param Fisma_Zend_Mail $mailEngine
     * @return void
     */
    function sendNotificationEmail($notifications, $mailEngine = null) 
    {
        $mail = (isset($mailEngine)) ? $mailEngine : new Fisma_Zend_Mail();
        // Send the e-mail
        $mail->sendNotification($notifications);
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

