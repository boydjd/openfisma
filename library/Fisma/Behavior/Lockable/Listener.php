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
 * Listener for the Lockable behavior. Hooks into preSave() ensuring that locked records aren't modified.
 *
 * @TODO See if we can de-couple this from Fisma_Acl at some point.
 * 
 * @package Fisma_Behavior
 * @subpackage Fisma_Behavior_Lockable
 * @version $Id$
 * @copyright (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @author Josh Boyd <joshua.boyd@endeavorsystems.com> 
 * @license http://www.openfisma.org/content/license GPLv3
 */
class Fisma_Behavior_Lockable_Listener extends Doctrine_Record_Listener
{
    /**
     * Throws an exception if the lock is modified by an unprivileged user, or if the record is modified while it is 
     * locked. 
     * 
     * @param Doctrine_Event $event 
     * @return void
     */
    public function preSave(Doctrine_Event $event)
    {
        $modelName = Doctrine_Inflector::tableize(get_class($event->getInvoker()));
        $modifiedFields = $event->getInvoker()->getModified();
        $hasPrivilege = Fisma_Acl::hasPrivilegeForObject($modelName . '_lock', $event->getInvoker());
        $locked = $event->getInvoker()->isLocked;
        $lockModified = array_key_exists('isLocked', $modifiedFields);
        $numModified = count($modifiedFields);

        /**
         * Record fields haven't been modified, nothing to do here. 
         */
        if (!$event->getInvoker()->isModified())
            return;

        /**
         * The record is not locked, and the lock isn't being changed, nothing to do here. 
         */
        if (!$locked && !$lockModified)
            return;

        /**
         * Only the lock is being modified and the user has the credentials to modify the lock, so there's nothing to
         * do here. 
         */
        if ($lockModified && $numModified == 1 && $hasPrivilege)
            return;

        /**
         * The user does not have the credentials to modify the lock, throw an exception. 
         */
        if ($lockModified && !$hasPrivilege)
            throw new Fisma_Behavior_Lockable_Exception(
                'You do not have the necessary privileges to lock/unlock this record'
            );

        /**
         * The record is locked, throw an exception. 
         */
        if ($locked)
            throw new Fisma_Behavior_Lockable_Exception('The record must be unlocked before it can be modified.');

    }
}
