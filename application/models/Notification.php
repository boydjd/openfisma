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
 * Notification
 *
 * @author     Ryan Yang <ryan@users.sourceforge.net>
 * @copyright  (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Model
 */
class Notification extends BaseNotification
{
    /**
     * Add notifications for the specified event.
     *
     * @param string $eventName The triggered event name which will be included in the notification
     * @param Doctrine_Record $record  The notification applied model
     * @param User $user  The user which triggers the notification event
     * @param array $extra Optional. The associative array of extra information
     * @return void
     * @throws Fisma_Zend_Exception if the specified event name is not found
     */
    public static function notify($eventName, $record, $user, $extra = null)
    {
        if (!Fisma::getNotificationEnabled()) {
            return;
        }

        $event = Doctrine::getTable('Event')->findOneByName($eventName);

        // Figure out which users are listening for this event
        $eventsQuery = Doctrine_Query::create()
            //without DISTINCT, a user can receive multiple same emails due to multiple roles
            ->select('DISTINCT e.id, u.id, u.email, u.displayName')
            ->from('User u')
            ->innerJoin('u.Events e')
            ->where('e.id = ?', $event->id)
            ->setHydrationMode(Doctrine::HYDRATE_SCALAR);

        if (isset($extra['recipientList'])) {
            if (empty($extra['recipientList'])) {
                return;
            }
            $eventsQuery->andWhereIn('u.id', $extra['recipientList']);
        } else {
            // If the event belong to "user" category, only send notifications to the user in question
            if ($event->category === 'user') {
                $eventsQuery->andWhere('u.id = ?', $extra['userId']);
            } else {
                // If the object has an ACL dependency on Organization, then extend the query for that condition
                if ($record instanceof Fisma_Zend_Acl_OrganizationDependency) {
                    $eventsQuery->leftJoin('u.UserRole ur')
                                ->leftJoin('ur.Organizations o');
                    if (isset($record->pocId)) {
                        $eventsQuery->andWhere(
                            'o.id = ? OR u.id = ?',
                            array($record->getOrganizationDependencyId(), $record->pocId)
                        );
                    } else {
                        $eventsQuery->andWhere('o.id = ?', $record->getOrganizationDependencyId());
                    }
                }
                // Then, check for privileges
                $eventsQuery->leftJoin('u.Roles r')
                            ->leftJoin('r.Privileges up')
                            ->leftJoin('e.Privilege ep')
                            ->andWhere('up.id = ep.id');
            }
        }

        $userEvents = $eventsQuery->execute();

        $baseUrl = rtrim(Fisma_Url::baseUrl(), '/');
        $urlPath = (isset($extra['url'])) ? $extra['url'] : $event->urlPath;
        $url = ($urlPath) ? ($baseUrl . $urlPath) : null;

        if (!empty($extra['appUrl'])) {
            $url = $baseUrl .  $event->urlPath . $extra['appUrl'];
        } else if (isset($record) && $urlPath) {
            if (strstr($urlPath, 'view')) {
                $url = $baseUrl . $urlPath . $record->id;
            } else {
                $url = $baseUrl . $urlPath;
            }
        }

        if (!empty($extra['log'])) {
            $eventText .= "\n" . $extra['log'];
        }

        $view = new Fisma_Zend_View();
        $view->setScriptPath(Fisma::getPath('application') . '/common-views/mail/')
             ->setEncoding('utf-8');
        $view->event = $event;
        $view->systemName = Fisma::configuration()->getConfig('system_name');
        $view->user = $user;
        $view->url = $url;
        $view->time = Zend_Date::now();
        $view->record = $record;
        if (!empty($extra['modifiedFields'])) {
            $view->modifiedFields = $extra['modifiedFields'];
        }
        if (!empty($extra['completedStep'])) {
            $view->completedStep = $extra['completedStep'];
        }
        $view->detail = Fisma::configuration()->getConfig('email_detail');
        $title = $view->render('title.phtml');
        $content = $view->render('content.phtml');

        $isIn = false;
        $notifications = new Doctrine_Collection('Notification');
        foreach ($userEvents as $userEvent) {
            $notification = new Notification();
            $notification->eventId                  = $userEvent['e_id'];
            $notification->userId                   = $userEvent['u_id'];
            $notification->denormalizedEmail        = $userEvent['u_email'];
            $notification->denormalizedRecipient    = $userEvent['u_displayName'];
            $notification->url                      = $url;
            $notification->eventTitle               = $title;
            $notification->eventText                = $content;
            $notifications[] = $notification;

            if ($user && $userEvent['u_id'] == $user->id) {
                $isIn = true;
            }
        }

        if (! $isIn && $eventName === 'VULNERABILITY_IMPORTED') { //uploader doesn't register for this event
            $notification = new Notification();
            $notification->eventId                  = $event->id;
            $notification->userId                   = $user->id;
            $notification->denormalizedEmail        = $user->email;
            $notification->denormalizedRecipient    = $user->displayName;
            $notification->url                      = $url;
            $notification->eventTitle               = $title;
            $notification->eventText                = $content;
            $notifications[] = $notification;
        }

        /** @todo this does not perform well. to send notifications to 500 users, this would create 500 queries.
         * unfortunately, DQL does not provide a good alternative that I am aware of.
         */
        $notifications->save();

        $fileWriter = new Zend_Log_Writer_Stream(Fisma::getPath('log') . '/notify.log');
        $fileWriter->setFormatter(new Zend_Log_Formatter_Simple("[%timestamp%] %message%\n"));
        $log = new Zend_Log;
        $log->addWriter($fileWriter);

        Fisma_Cli_Notify::sendNotificationEmail($notifications, $log);
    }
}
