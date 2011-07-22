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

try {
    $notify = new Notify();
    $notify->processNotificationQueue();
    print ("Notify finished at " . Fisma::now() . "\n");
} catch (Exception $e) {
    print $e->getMessage();
}

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
class Notify
{
    /**
     * Default constructor
     * 
     * @return void
     */
    public function __construct()
    {
        defined('APPLICATION_ENV')
            || define(
                'APPLICATION_ENV',
                (getenv('APPLICATION_ENV') ? getenv('APPLICATION_ENV') : 'production')
            );
        defined('APPLICATION_PATH') || define(
            'APPLICATION_PATH',
            realpath(dirname(__FILE__) . '/../../application')
        );

        set_include_path(
            APPLICATION_PATH . '/../library/Symfony/Components' . PATH_SEPARATOR .
            APPLICATION_PATH . '/../library' .  PATH_SEPARATOR .
            get_include_path()
        );

        require_once 'Fisma.php';
        require_once 'Zend/Application.php';

        $application = new Zend_Application(
            APPLICATION_ENV,
            APPLICATION_PATH . '/config/application.ini'
        );
        Fisma::setAppConfig($application->getOptions());
        Fisma::initialize(Fisma::RUN_MODE_COMMAND_LINE);
        Fisma::setConfiguration(new Fisma_Configuration_Database());
        $application->bootstrap('Db');
        $application->bootstrap('SearchEngine');
    }
    
    /**
     * Iterate through the users and check who has notifications pending.
     * 
     * @return void
     * @todo log the email send results
     */
    function processNotificationQueue() 
    {
        // Get all notifications grouped by user_id
        /**
         * @todo can't find a way to do this in DQL... substituting a mysql raw connection for now.
         */
        /*$query = Doctrine_Query::create()
                    ->select('n.*, u.email, u.notifyFrequency')
                    ->from('Notification n')
                    ->innerJoin('n.User u')
                    ->where('u.emailValidate = 1')
                    ->addWhere('u.mostRecentNotifyTs is NULL OR u.mostRecentNotifyTs <= ?'. 
                               new Doctrine_Expression("DATE_SUB(NOW(), INTERVAL u.notifyFrequency HOUR)"))
                    ->orderBy('n.userId');
        $notifications = $query->execute();*/
        $query = new Doctrine_RawSql();
        $query->select('{n.eventtext}, {n.createdts}, {u.email}, {u.nameFirst}, {u.nameLast}')
              ->addComponent('n', 'Notification n')
              ->addComponent('u', 'n.User u')
              ->from('user u INNER JOIN notification n on u.id = n.userid')
              ->where(
                  '(u.mostrecentnotifyts IS NULL OR u.mostrecentnotifyts <= DATE_SUB(NOW(), 
                  INTERVAL u.notifyFrequency HOUR))'
              )
              ->andWhere('(u.locked = FALSE OR (u.locked = TRUE AND u.locktype = "manual"))')
              ->orderBy('u.id, n.createdts');

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

                Notify::sendNotificationEmail($currentNotifications);
                Notify::purgeNotifications($currentNotifications);
                Notify::updateUserNotificationTimestamp($notifications[$i]->userId);

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
     * @return void
     */
    static function sendNotificationEmail($notifications) 
    {
        $mail = new Fisma_Zend_Mail();
        // Send the e-mail
        $mail->sendNotification($notifications);
    }

    /**
     * Remove notifications from the queue table.
     * 
     * @param array $notifications A group of rows from the notifications table
     * @return void
     */
    static function purgeNotifications($notifications) 
    {
        $notificationIds = array();
        foreach ($notifications as $notification) {
            $notificationIds[] = $notification['id'];
        }

        Doctrine_Query::create()
            ->delete()
            ->from('Notification')
            ->whereIn('id', $notificationIds)
            ->execute();
    }

    /**
     * Update the timestamp for the specified user so that he will not receieve too many e-mail in too 
     * short of a time period.
     *
     * @param integer $userId The Id of the user to update
     * @return void
     */
    static function updateUserNotificationTimestamp($userId) 
    {
        $user = new User();
        $user = $user->getTable()->find($userId);
        $user->mostRecentNotifyTs = Fisma::now();
        $user->save();
    }
}

