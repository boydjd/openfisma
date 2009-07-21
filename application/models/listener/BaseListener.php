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
 * <http://www.gnu.org/licenses/>.
 *
 * @author    Ryan yang <ryan.yang@users.sourceforge.net>
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/mw/index.php?title=License
 * @version   $Id$
 * @package   Listener
 */
 
/**
 * A special listener which create notifications or update lucene index for each meta-data
 * 
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/license.php
 * @package   Listener
 */
class BaseListener extends Doctrine_Record_Listener 
{
    public function postInsert(Doctrine_Event $event)
    {
        $invoker = $event->getInvoker();
        $type    = $this->_getNotifyType($invoker, 'CREATED');
        Notification::notify($type, $invoker, User::currentUser());
    }

    public function postUpdate(Doctrine_Event $event)
    {
        $invoker = $event->getInvoker();
        $type    = $this->_getNotifyType($invoker, 'MODIFIED');
        Notification::notify($type, $invoker, User::currentUser());
    }

    public function postSave(Doctrine_Event $event)
    {
        $invoker  = $event->getInvoker();
        $modified = $invoker->getModified($old=false, $last=true);
        Fisma_Lucene::updateIndex($invoker->getTable()->getTableName(), $invoker->id, $modified);
    }

    public function postDelete(Doctrine_Event $event)
    {
        $invoker  = $event->getInvoker();
        $type    = $this->_getNotifyType($invoker, 'DELETED');
        Notification::notify($type, $invoker, User::currentUser());

        $modified = $invoker->getModified($old=false, $last=true);
        Fisma_Lucene::deleteIndex($invoker->getTable()->getTableName(), $invoker->id);
    }

    /**
     * Get the notification type
     *
     * @param Doctrine Record $invoker
     * @param string $action
     * @return int notification type
     */
    private function _getNotifyType($invoker, $action)
    {
        $table = strtoupper($invoker->getTable()->getTableName());
        $event = $table . '_' . $action;
        eval ("\$type = Notification::$event;");
        return $type;
    }
}
