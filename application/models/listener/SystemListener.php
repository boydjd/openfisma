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
 * @author    Ryan Yang <ryan@users.sourceforge.net>
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
Class SystemListener extends Doctrine_Record_Listener
{
    public function preInsert(Doctrine_Event $event)
    {
        Notification::notify(Notification::SYSTEM_CREATED, $this, User::currentUser());
    }
    
    public function preUpdate(Doctrine_Event $event)
    {
        Notification::notify(Notification::SYSTEM_MODIFIED, $this, User::currentUser());
    }
    
    /**
     * Begin a transaction
     */
    public function preSave(Doctrine_Event $event)
    {
        $system = $event->getInvoker();
        $modified = $system->getModified();

        // Update FIPS 199
        if (isset($modified['confidentiality'])
            || isset($modified['integrity'])
            || isset($modified['availability'])) {
            $system->fipsCategory = $system->fipsSecurityCategory();
        }
        
        // Format the Exhibit 53 UPI like: xxx-xx-xx-xx-xx-xxxx-xx
        if (isset($modified['uniqueProjectId'])) {
            $tempUpi = str_replace('-', '', $modified['uniqueProjectId']);
            $tempUpi = str_pad($tempUpi, 17, '0');
            $system->uniqueProjectId = substr($tempUpi, 0, 3) . '-'
                                     . substr($tempUpi, 3, 2) . '-'
                                     . substr($tempUpi, 5, 2) . '-'
                                     . substr($tempUpi, 7, 2) . '-'
                                     . substr($tempUpi, 9, 2) . '-'
                                     . substr($tempUpi, 11, 4) . '-'
                                     . substr($tempUpi, 15, 2);
        }
    }

    public function postSave(Doctrine_Event $event) 
    {
        $system = $event->getInvoker();
        $org = $system->Organization[0];

        $modified = $system->getModified($old=false, $last=true);
        Fisma_Lucene::updateIndex('system', $system->id, $modified);
    }

    /**
     * Delete the Organization which is related with the system.
     *
     * @param Doctrine_Event $event
     * @return void
     */
    public function preDelete(Doctrine_Event $event)
    {
        $system = $event->getInvoker();
        $ret = Doctrine::getTable('Organization')->findOneBySystemId($system->id);
        Doctrine_Query::create()->delete()
                                ->from('Organization o')
                                ->where('o.id = ' . $ret->id)
                                ->execute();

    }

    public function postDelete(Doctrine_Event $event)
    {
        $system = $event->getInvoker();
        Fisma_Lucene::deleteIndex('system', $system->id);
    }
}
