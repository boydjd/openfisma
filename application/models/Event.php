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
 * @author    ???
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/mw/index.php?title=License
 * @version   $Id$
 * @package   Model
 *
 * @todo Clean up and assign an author
 */

/**
 * A business object which represents an event in OpenFISMA that is able to
 * generate notifications to end users.
 *
 * @package   Model
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/mw/index.php?title=License
 */
class Event extends FismaModel
{
    /**
     * The default table name 
     */
    protected $_name = 'events';
    protected $_primary = 'id';
    
    
    /**
     * Get all events that the specified user can have
     *
     * @param string $uid user id
     * @param string $order the field that is ordered by
     * @return array events
     */
    public function getUserAllEvents($uid, $order='name')
    {
        $sql= $this->select()->setIntegrityCheck(false)
            ->from(array('ur'=>'user_roles'), array())
            ->join(array('rf'=>'role_functions'), 'rf.role_id=ur.role_id',
                    array())
            ->join(array('e'=>'events'), 'e.function_id=rf.function_id',
                    array('id', 'name'))
            ->where('ur.user_id=?', $uid);
        if (!empty($order)) {
            $sql->order($order);
        }
        return $this->_db->fetchPairs($sql);
    }
    
    /**
     * Get my events that the specified user had
     *
     * @param string $uid user id
     * @param string $order the field that is ordered by
     * @return array events
     */
    public function getEnabledEvents($uid, $order='name')
    {
        $sql= $this->select()->setIntegrityCheck(false)
            ->from(array('e'=>'events'), array('id', 'name'))
            ->join(array('ue'=>'user_events'), 'ue.event_id=e.id', array())
            ->where('ue.user_id=?', $uid);
        if (!empty($order)) {
            $sql->order($order);
        }
        return $this->_db->fetchPairs($sql);
    }
    
    /**
     * Reset the enabled events
     *
     * @param numeric $uid
     * @param array   $events
     */
    public function saveEnabledEvents($uid, $events)
    {
        $allEvent = $this->getUserAllEvents($uid);
        $events = array_intersect(array_keys($allEvent), $events);
        $this->_db->delete('user_events', "user_id = $uid");
        if (!empty($events)) {
            foreach ($events as $e) {
                $this->_db->insert('user_events',
                 array('user_id'=>$uid,'event_id'=>$e));
            }
        }
    }
}
