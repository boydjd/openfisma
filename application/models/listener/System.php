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
 * @author    Ryan yang <ryan.yang@reyosoft.com>
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/mw/index.php?title=License
 * @version   $Id:$
 * @package   Listener
 */

/**
 * be called by System CURD
 *
 * @package   Listener
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/mw/index.php?title=License
 */

Class Listener_System extends Doctrine_Record_Listener
{
    
    /**
     * Insert the Organization with the name, nickname and description which is 
     * belong to the system.
     *
     * @param Doctrine_Event $event
     * @return void
     */
    public function postInsert(Doctrine_Event $event)
    {
         $invoker = $event->getInvoker();
         $organization = new Organization();
         $organization->systemId    = $invoker->id;
         $organization->name        = $invoker->name;
         $organization->nickname    = $invoker->nickname;
         $organization->description = $invoker->description;
         $organization->orgType     = 'system';
         $organization->save();
    }

    /**
     * Update the Organization with the name, nickname and description which is 
     * belong to the system.
     *
     * @param Doctrine_Event $event
     * @return void
     */
    public function postUpdate(Doctrine_Event $event)
    {
        $invoker = $event->getInvoker();
        $organization = Doctrine::getTable('Organization')->findOneBySystemId($invoker->id);
        $organization->name = $invoker->name;
        $organization->nickname = $invoker->nickname;
        $organization->description = $invoker->description;
        $organization->save();
    }

    /**
     * Delete the Organization which is related with the system.
     *
     * @param Doctrine_Event $event
     * @return void
     */
    public function preDelete(Doctrine_Event $event)
    {
        $invoker = $event->getInvoker();
        $ret = Doctrine::getTable('Organization')->findOneBySystemId($invoker->id);
        Doctrine_Query::create()->delete()
                                ->from('Organization o')
                                ->where('o.id = '.$ret->id)
                                ->execute();
    }
}
