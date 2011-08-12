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
 * Add an event that represents the deletion of a finding
 *
 * @author     Mark E. Haase <mhaase@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Migration
 */
class Version5 extends Doctrine_Migration_Base
{
    /**
     * Insert new event
     * 
     * @return void
     */
    public function up()
    {
        // Create new event
        $event = new Event;
        $event->name = 'FINDING_DELETED';
        $event->description = 'Finding Deleted';
        
        // Get corresponding privilege
        $privQuery = Doctrine_Query::create()
                     ->from('Privilege p')
                     ->where('p.resource = ? AND p.action = ?', array('notification', 'finding'));
        $privilege = $privQuery->fetchOne();
        $event->Privilege = $privilege;
        
        $event->save();
    }
    
    /**
     * Remove new event
     * 
     * @return void
     */
    public function down()
    {
        Doctrine::getTable('Event')->findOneByName('FINDING_DELETED')->delete();
    }
}
