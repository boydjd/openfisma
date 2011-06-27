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
 * This listener creates notification objects in response to CRUD events on some objects
 * 
 * Using this listener on a model will guarantee notifications in response to creation and deletion events,
 * but modification events will only be created if the model declares fields with the extra attribute
 * 'notify: true'
 * 
 * Some objects currently implement some notifications themselves, instead of using the NotificationListener,
 * such as Finding or FindingEvaluation.
 * 
 * @author     Mark E. Haase <mhaase@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Listener
 */
class NotificationListener extends Fisma_Doctrine_Record_Listener
{
    /**
     * Send notifications for object creation
     * 
     * @param Doctrine_Event $event The listened doctrine event to process
     * @return void
     */
    public function postInsert(Doctrine_Event $event)
    {
        if (!self::$_listenerEnabled) {
            return;
        }

        $record = $event->getInvoker();
        $eventName = $this->_classNameToEventName(get_class($record)) . '_CREATED';
        Notification::notify($eventName, $record, CurrentUser::getInstance());
    }
    
    /**
     * Send notifications for object modifications
     * 
     * These notifications are only sent if the model has defined columns with an extra attribute called 
     * 'notify' with a boolean value 'true' AND one of those columns has been modified.
     * 
     * @param Doctrine_Event $event The listened doctrine event to process
     * @return void
     */
    public function postUpdate(Doctrine_Event $event)
    {
        if (!self::$_listenerEnabled) {
            return;
        }

        $record = $event->getInvoker();
        $eventName = $this->_classNameToEventName(get_class($record)) . '_UPDATED';
        
        // Only send the notification if a notifiable field was modified
        $modified = $record->getLastModified();
        $table = $record->getTable();
        foreach ($modified as $name => $value) {
            $columnDef = $table->getColumnDefinition($table->getColumnName($name));
            // Not all columns will define this index, so the suppression operator is used:
            if (@$columnDef['extra']['notify']) {
                Notification::notify($eventName, $record, CurrentUser::getInstance());
                break;
            }
        }
    }
    
    /**
     * Send notifications for object deletions
     * 
     * @param Doctrine_Event $event The listened doctrine event to process
     * @return void
     */
    public function postDelete(Doctrine_Event $event)
    {
        if (!self::$_listenerEnabled) {
            return;
        }

        $record = $event->getInvoker();
        $eventName = $this->_classNameToEventName(get_class($record)) . '_DELETED';
        Notification::notify($eventName, $record, CurrentUser::getInstance());    
    }
    
    /**
     * Convert class name to an event name
     * 
     * e.g. SystemDocument to SYSTEM_DOCUMENT
     * 
     * @param $className
     * @return string
     */
    private function _classNameToEventName($className)
    {
        return strtoupper(Doctrine_Inflector::tableize($className));
    }
}
