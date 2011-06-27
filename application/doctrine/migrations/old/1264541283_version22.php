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
 * Add event for system document creation
 * 
 * @package Migration
 * @copyright (c) Endeavor Systems, Inc. 2010 {@link http://www.endeavorsystems.com}
 * @author Mark E. Haase <mhaase@endeavorsystems.com>
 * @license http://www.openfisma.org/content/license GPLv3
 */
class Version22 extends Doctrine_Migration_Base
{
    /**
     * Add new events
     */
    public function up()
    {
        Doctrine_Manager::connection()->beginTransaction();

        /*
         * Get the privilege that is associated to this event. We're using the finding notification privilege, even
         * though it doesn't really make sense, because the privileges are organized well anyway. We'll clean them all
         * up later.
         */
        $eventPrivilege = Doctrine::getTable('Privilege')
                          ->findByDql('resource = ? and action = ?', array('notification', 'finding'));
                          
        if (!$eventPrivilege) {
            throw new Fisma_Zend_Exception("Not able to find the finding notification privilege");
        }
        
        // New events
        $createDocumentEvent = new Event();
        $createDocumentEvent->name = 'SYSTEM_DOCUMENT_CREATED';
        $createDocumentEvent->description = 'Initial Version Of System Document';
        $createDocumentEvent->Privilege = $eventPrivilege[0];
        $createDocumentEvent->save();

        $updateDocumentEvent = new Event();
        $updateDocumentEvent->name = 'SYSTEM_DOCUMENT_UPDATED';
        $updateDocumentEvent->description = 'Updated Version Of System Document';
        $updateDocumentEvent->Privilege = $eventPrivilege[0];
        $updateDocumentEvent->save();

        Doctrine_Manager::connection()->commit();
    }

    /**
     * Drop events
     */
    public function down()
    {
        $eventNames = array('SYSTEM_DOCUMENT_CREATED', 'SYSTEM_DOCUMENT_UPDATED');
        $deleteEvents = Doctrine_Query::create()
                        ->delete()
                        ->from('Event')
                        ->whereIn('name', $eventNames);
        $deleteEvents->execute();
    }
}
