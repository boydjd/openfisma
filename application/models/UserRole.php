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
 * UserRole
 * 
 * @author     Ryan Yang <ryan@users.sourceforge.net>
 * @copyright  (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Model
 */
class UserRole extends BaseUserRole
{
    /**
     * Invalidate ACL of affected user 
     * 
     * This old implementation reflects the relationship UserRole->hasMany(User)
     *
     * @param Doctrine_Event $event 
     *
    public function postSave($event)
    {
        $invoker = $event->getInvoker();
        $affectedUser = Doctrine::getTable('User')->find($invoker->userId);
        $affectedUser->invalidateAcl();
    }
     */

    /**
     * Invalidate ACL of the associated user
     *
     * This new implementation reflects the relationship UserRole->hasOne(User)
     * 
     * @param mixed $event 
     * 
     * @return void
     */
    public function postSave($event)
    {
        $this->User->invalidateAcl();
    }
}
