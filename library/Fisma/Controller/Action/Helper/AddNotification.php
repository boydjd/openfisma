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
 * @author    Woody lee <woody.li@reyosoft.com>
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/mw/index.php?title=License
 * @version   $Id$
 * @package   Helper
 *
 */
require_once 'Zend/Controller/Action/Helper/Abstract.php';

/**
 * Add notifications for the specified event.
 * 
 */
class Fisma_Controller_Action_Helper_AddNotification extends Zend_Controller_Action_Helper_Abstract
{
    
    /**
     * addNotification() - Add notifications for the specified event.
     *
     * @param int $eventType The type of event
     * @param string $userName The name of the user who caused the event
     * @param int|string|array $recordId An ID or description of the object
     * @param int $systemId The ID of the associated system, if applicable
     *
     * @todo Reconsider the $recordId parameter... seems to be useless
     */
    public static function addNotification($eventType, $userName, $recordId, $systemId=null)
    {
        // Format the $recordId for inclusion in the event text.
        // Notice: this value is currently not used
        if (is_array($recordId)) {
            $record = implode(",", $recordId);
        } else {
            $record = $recordId;
        }

        // Create a new event object with the specified type
        $event = new Event();
        $event = $event->getTable()->find($eventType);
        if (empty($event)) {
            throw new Fisma_Exception_General('Event name does not exist');
        }

        // Construct the event text
        $eventText = "$event->name "
                     . (isset($userName) ? "by $userName " : '')
                     . "($record)";

        // Create notification records for all interested users
        if ($systemId == null) {
            $userEvents = new UserEvent();
            $userEvents = $userEvents->getTable('UserEvent')->findByEventId($eventType);
        } else {
            $q = Doctrine_Query::create()
                 ->select('ue.eventId, ue.userId')
                 ->from('UserEvent ue, UserOrganization uo')
                 ->Where('ue.eventId = ?', $eventType)
                 ->andWhere('uo.organizationId = (SELECT o.id FROM Organization o WHERE o.systemId = '.$systemId.')');
            $userEvents = $q->execute();
        }
        if (!$userEvents = $userEvents->toArray()) {
            return false;
        }
        foreach ($userEvents as $userEvent) {
            $notification = new Notification();
            $notification->eventId = $userEvent['eventId'];
            $notification->userId = $userEvent['userId'];
            $notification->eventText = $eventText;
            $notification->save();
        }
    }
    
    /**
     * Perform helper when called as $this->_helper->addNotification() from an action controller
     * 
     * @param int $eventType The type of event
     * @param string $userName The name of the user who caused the event
     * @param int|string|array $recordId An ID or description of the object
     * @param int $systemId The ID of the associated system, if applicable
     */
    public function direct($eventType, $userName, $recordId, $systemId = null)
    {
        $this->addNotification($eventType, $userName, $recordId, $systemId);
    }
}