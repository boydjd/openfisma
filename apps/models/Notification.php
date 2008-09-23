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
 * @author    Chris Chen <chriszero@users.sourceforge.net>
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/mw/index.php?title=License
 * @version   $Id$
 */

/**
 * A business object which represents a notification sent to an end user
 * regarding the occurrence of some event within OpenFISMA.
 *
 * @package   Model
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/mw/index.php?title=License
 */
class Notification extends FismaModel
{
    protected $_name = 'notifications';
    protected $_primary = 'id';
    
    /**
     * Notification event constants
     */
    const FINDING_CREATED = 1;
    const FINDING_IMPORT = 2;
    const FINDING_INJECT = 3;
    
    const ASSET_MODIFIED = 4;
    const ASSET_CREATED = 5;
    const ASSET_DELETED = 6;
    
    const UPDATE_COURSE_OF_ACTION = 7;
    const UPDATE_FINDING_ASSIGNMENT = 8;
    const UPDATE_CONTROL_ASSIGNMENT = 9;
    const UPDATE_COUNTERMEASURES = 10;
    const UPDATE_THREAT = 11;
    const UPDATE_FINDING_RECOMMENDATION = 12;
    const UPDATE_FINDING_RESOURCES = 13;
    const UPDATE_EST_COMPLETION_DATE = 14;
    
    const MITIGATION_STRATEGY_APPROVED = 15;
    const POAM_CLOSED = 16;
    
    const EVIDENCE_UPLOAD = 17;
    const EVIDENCE_APPROVAL_1ST = 18;
    const EVIDENCE_APPROVAL_2ND = 19;
    const EVIDENCE_APPROVAL_3RD = 20;
    
    const ACCOUNT_MODIFIED = 21;
    const ACCOUNT_DELETED = 22;
    const ACCOUNT_CREATED = 23;
    
    const SYSGROUP_DELETED = 24;
    const SYSGROUP_MODIFIED = 25;
    const SYSGROUP_CREATED = 26;
    
    const SYSTEM_DELETED = 27;
    const SYSTEM_MODIFIED = 28;
    const SYSTEM_CREATED = 29;
    
    const PRODUCT_CREATED = 30;
    const PRODUCT_MODIFIED = 31;
    const PRODUCT_DELETED = 32;
    
    const ROLE_CREATED = 33;
    const ROLE_DELETED = 34;
    const ROLE_MODIFIED = 35;
    
    const SOURCE_CREATED = 36;
    const SOURCE_MODIFIED = 37;
    const SOURCE_DELETED = 38;
    
    const NETWORK_MODIFIED = 39;
    const NETWORK_CREATED = 40;
    const NETWORK_DELETED = 41;
    
    const CONFIGURATION_MODIFIED = 42;
    
    const ACCOUNT_LOGIN_SUCCESS = 43;
    const ACCOUNT_LOGIN_FAILURE = 44;
    const ACCOUNT_LOGOUT = 45;
    
    const ECD_EXPIRES_TODAY = 46;
    const ECD_EXPIRES_7_DAYS = 47;
    const ECD_EXPIRES_14_DAYS = 48;
    const ECD_EXPIRES_21_DAYS = 49;

    const ROB_ACCEPT  = 50;

    /**
     * add() - Add notifications for the specified event.
     *
     * @param int $eventType The type of event
     * @param string $userName The name of the user who caused the event
     * @param int|string|array $recordId An ID or description of the object
     *
     * @todo Reconsider the $recordId parameter... seems to be useless
     */
    public function add($eventType, $userName, $recordId)
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
        $ret = $event->find($eventType);
        if (empty($ret)) {
            throw new fisma_Exception('Event name does not exist');
        }
        $eventName = $ret->current()->name;

        // Construct the event text
        $eventText = "$eventName "
                     . (isset($userName) ? "by $userName " : '')
                     . "($record)";

        // Create notification records for all interested users
        $query = "INSERT INTO notifications
                       SELECT null,
                              ue.event_id,
                              ue.user_id,
                              '$eventText',
                              null
                         FROM user_events ue
                        WHERE ue.event_id = $eventType";
        $statement = $this->_db->query($query);
    }
    
    /**
     * getNotifications() - Get all notifications for the specified user.
     *
     * @param integer $userId
     * @return array Array of notification rows
     */
    public function getNotifications($userId) {
        // Query to get notifications for this user
        $notificationsQuery =
             "SELECT n.id,
                     n.event_text,
                     n.timestamp
                FROM notifications n
               WHERE n.user_id=$userId
            ORDER BY n.timestamp";
        $statement = $this->_db->query($notificationsQuery);
        $notifications = $statement->fetchAll();
        
        return $notifications;
    }
}
