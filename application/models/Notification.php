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

        if (!$event) {
            throw new Fisma_Zend_Exception("No event named '$eventName' was found");
        }

        if (!empty($extra['rowsProcessed'])) {
            $eventText = $extra['rowsProcessed'] . " " . $event->description;
        }
        else {
        	$eventText = $event->description;
        }

        // If the model has a "nickname" field, then identify the record by the nickname. Otherwise, identify the record
        // by it's ID, which is a field that all models are expected to have (except for join tables). Some
        // notifications won't have a nickname or ID (such as notifications about the application's configuration)
        $recordClass = get_class($record);
        if ($recordClass === 'Organization' && $record->systemId != null) {
            $recordClass = 'System';
        }
        if (isset($record->nickname) && !is_null($record->nickname)) {
            $eventText .= " ($recordClass $record->nickname)";
        } elseif (isset($record) && isset($record->id)) {
            $eventText .= " ($recordClass #$record->id)";
        }

        if (!empty($extra['modifiedFields'])) {
            $eventText .= " (" . implode(', ', $extra['modifiedFields']) . ")";
        }

        if (!is_null($user)) {
            $eventText .= " by $user->nameFirst $user->nameLast";
        } else {
            $eventText .= ' by ' . Fisma::configuration()->getConfig('system_name');
        }

        // Figure out which users are listening for this event
        $eventsQuery = Doctrine_Query::create()
            ->select('e.id, u.id')
            ->from('User u')
            ->innerJoin('u.Events e')
            ->where('e.id = ?', $event->id)
            ->setHydrationMode(Doctrine::HYDRATE_SCALAR);

        // If the object has an ACL dependency on Organization, then extend the query for that condition
        if ($record instanceof Fisma_Zend_Acl_OrganizationDependency) {
            $eventsQuery->leftJoin('u.UserRole ur')
                        ->leftJoin('ur.Organizations o')
                        ->andWhere('o.id = ?', $record->getOrganizationDependencyId());
        }

        // If the event belong to "user" category, only send notifications to the user in question
        if ($event->category === 'user') {
            $eventsQuery->andWhere('u.id = ?', $extra['userId']);
        } else { // Otherwise, check for privileges
            $eventsQuery->leftJoin('u.Roles r')
                        ->leftJoin('r.Privileges up')
                        ->leftJoin('e.Privilege ep')
                        ->andWhere('up.id = ep.id');
        }

        $userEvents = $eventsQuery->execute();

        $baseUrl = rtrim(Fisma_Url::baseUrl(), '/');
        $urlPath = (isset($extra['url'])) ? $extra['url'] : $event->urlPath;
        $url = '';

        if (!empty($extra['appUrl'])) {
        	$url = $baseUrl .  $event->urlPath . $extra['appUrl'];
        }
        else if (isset($record) && $urlPath) {
            if (strstr($urlPath, 'view')) {
                $url = $baseUrl . $urlPath . $record->id;
            } else {
                $url = $baseUrl . $urlPath;
            }
        }

        $notifications = new Doctrine_Collection('Notification');
        foreach ($userEvents as $userEvent) {
            $notification = new Notification();
            $notification->eventId   = $userEvent['e_id'];
            $notification->userId    = $userEvent['u_id'];
            $notification->eventText = $eventText;
            $notification->url = $url;
            $notifications[] = $notification;
        }

        /** @todo this does not perform well. to send notifications to 500 users, this would create 500 queries.
         * unfortunately, DQL does not provide a good alternative that I am aware of.
         */
        $notifications->save();
    }
}
