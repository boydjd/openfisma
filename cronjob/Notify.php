<?php
/**
 * Copyright (c) 2008 Endeavor Systems, Inc.
 *
 * This file is part of OpenFISMA.
 *
 * OpenFISMA is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * OpenFISMA is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with OpenFISMA.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author    Jim Chen <xhorse@users.sourceforge.net>
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/mw/index.php?title=License
 * @version   $Id$
 */

/**
 * Indicates that we're running a command line tool, not responding to an http
 * request. This prevents the interface from being rendered.
 */
define('COMMAND_LINE', true);

require_once dirname(__FILE__) . '/../apps/bootstrap.php';

require_once (CONFIGS . '/setting.php');

// Kick off the main routine:
Notify::processNotificationQueue();

/**
 * This static class is responsible for scanning for notifications which need to
 * be delivered, delivering the notifications, and then removing the sent
 * notifications from the queue.
 *
 * @package    Cron_Job
 * @subpackage Controller_Subpackage
 * @copyright  (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license    http://www.openfisma.org/mw/index.php?title=License
 *
 * @todo Needs cleanup
 * @todo need to adjust for timezone difference between DB and application when
 * displaying timestamps
 */
class Notify
{
    const EMAIL_VIEW_PATH = '/scripts/mail';
    const EMAIL_VIEW = 'notification.tpl';

    /**
     * processNotificationQueue() - Iterate through the users and check who has
     * notifications pending.
     *
     * @todo log the email send results
     */
    static function processNotificationQueue() {
        $db = Zend_Db::factory(Zend_Registry::get('datasource'));
        Zend_Db_Table::setDefaultAdapter($db);
        Zend_Registry::set('db', $db);
        
        // Get all notifications grouped by user_id
        $query = "SELECT n.id,
                         n.user_id,
                         n.event_text,
                         n.timestamp,
                         e.name AS event_name,
                         u.name_first,
                         u.name_last,
                         u.email,
                         u.email_validate,
                         u.notify_email,
                         u.notify_frequency,
                         u.most_recent_notify_ts
                    FROM notifications n
              INNER JOIN events e on e.id = n.event_id
              INNER JOIN users u ON u.id = n.user_id
                     AND DATE_ADD(u.most_recent_notify_ts,
                                  INTERVAL u.notify_frequency HOUR) < NOW()
                ORDER BY n.user_id";
        $statement = $db->query($query);
        $notifications = $statement->fetchAll();

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
            if (($i == (count($notifications) - 1)
                || $notifications[$i]['user_id'] !=
                   $notifications[$i+1]['user_id'])
                && 1 == $notifications[$i]['email_validate']) {

                Notify::sendNotificationEmail($currentNotifications);
                Notify::purgeNotifications($db, $currentNotifications);
                Notify::updateUserNotificationTimestamp(
                    $db,
                    $notifications[$i]['user_id']
                    //$nowSqlString
                );

                // Move onto the next user
                $currentNotifications = array();
            }

        }
    }

    /**
     * sendNotificationEmail() - ??
     *
     * @todo complete this documentation, use the notify_email if available
     */
    static function sendNotificationEmail($notifications) {
        $mail = new Zend_Mail();
        $contentTpl = new Zend_View();
        $contentTpl->setScriptPath(VIEWS . '/' . self::EMAIL_VIEW_PATH);

        // Set the from: header
        $mail->setFrom(readSysConfig('sender'), "OpenFISMA");

        // Set the to: header
        $receiveEmail = nullGet($notifications[0]['notify_email'], 
            $notifications[0]['email']);
        $mail->addTo(
            $receiveEmail,
            "{$notifications[0]['name_first']} {$notifications[0]['name_last']}"
        );
        
        // Set the subject: header
        $mail->setSubject(readSysConfig('subject'));

        // Render the message body
        $contentTpl->notifyData = $notifications;
        $content = $contentTpl->render(self::EMAIL_VIEW);
        $mail->setBodyText($content);
        
        // Send the e-mail
        $mail->send(Notify::getTransport());
        print("Email was sent to $receiveEmail\n");
    }

    static function purgeNotifications($db, $notifications) {
        $notificationIds = array();
        foreach ($notifications as $notification) {
            $notificationIds[] = $notification['id'];
        }
        $notificationString = implode(', ', $notificationIds);
        
        $query = "DELETE FROM notifications
                        WHERE id IN ($notificationString)";
        $db->query($query);
    }

    static function updateUserNotificationTimestamp($db, $userId) {
        $query = "UPDATE users
                     SET most_recent_notify_ts = NOW()
                   WHERE id = $userId";
        $db->query($query);
    }

    
    /** 
     *  getTransport() - Make the instance of proper transport method according
     *  to the config.
     *
     * @return Zend_Mail_Transport_Smtp|Zend_Mail_Transport_Sendmail
     */
    static function getTransport() {
        $transport = null;
        if ( 'smtp' == readSysConfig('send_type')) {
            $config = array('auth' => 'login',
                'username' => readSysConfig('smtp_username'),
                'password' => readSysConfig('smtp_password'),
                'port' => readSysConfig('smtp_port'));
            $transport = new Zend_Mail_Transport_Smtp(readSysConfig('smtp_host'),
                $config);
        } else {
            $transport = new Zend_Mail_Transport_Sendmail();
        }
        return $transport;
    }
}

