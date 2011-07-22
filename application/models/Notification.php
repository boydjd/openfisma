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
     * @return void
     * @throws Fisma_Zend_Exception if the specified event name is not found
     */
    public static function notify($eventName, $record, $user)
    {
        if (!Fisma::getNotificationEnabled()) {
            return;
        }

        $event = Doctrine::getTable('Event')->findOneByName($eventName);

        if (!$event) {
            throw new Fisma_Zend_Exception("No event named '$eventName' was found");
        }

        $eventText = $event->description;

        // If the model has a "nickname" field, then identify the record by the nickname. Otherwise, identify the record
        // by it's ID, which is a field that all models are expected to have (except for join tables). Some 
        // notifications won't have a nickname or ID (such as notifications about the application's configuration)
        if (isset($record->nickname) && !is_null($record->nickname)) {
            $eventText .= " ($record->nickname)";
        } elseif (isset($record)) {
            $eventText .= " (ID #$record->id)";            
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
            $eventsQuery->innerJoin('u.UserRole ur')
                        ->leftJoin('ur.Organizations o')
                        ->andWhere('o.id = ?', $record->getOrganizationDependencyId());
        }

        $userEvents = $eventsQuery->execute();

        $notifications = new Doctrine_Collection('Notification');
        foreach ($userEvents as $userEvent) {
            $notification = new Notification();
            $notification->eventId   = $userEvent['e_id'];
            $notification->userId    = $userEvent['u_id'];
            $notification->eventText = $eventText;
            $notifications[] = $notification;
        }

        /** @todo this does not perform well. to send notifications to 500 users, this would create 500 queries.
         * unfortunately, DQL does not provide a good alternative that I am aware of. 
         */
        $notifications->save();
    }
}
