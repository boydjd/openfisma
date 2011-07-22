<?php
// @codingStandardsIgnoreFile
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
 * Finish the work started in version 55 by dropping the tables for IrIncidentActor and IrIncidentObserver
 * 
 * @package Migration
 * @copyright (c) Endeavor Systems, Inc. 2010 {@link http://www.endeavorsystems.com}
 * @author Mark E. Haase <mhaase@endeavorsystems.com>
 * @license http://www.openfisma.org/content/license GPLv3
 */
class Version56 extends Doctrine_Migration_Base
{
    /**
     * Drop the old tables
     */
    public function up()
    {
		$this->dropTable('ir_incident_actor');
		$this->dropTable('ir_incident_observer');
    }
    
    /**
     * Recreate the old tables
     */
    public function down()
    {   
		$this->createTable('ir_incident_actor', array(
             'incidentid' => 
             array(
              'type' => 'integer',
              'primary' => true,
              'length' => 8,
             ),
             'userid' => 
             array(
              'type' => 'integer',
              'primary' => true,
              'length' => 8,
             ),
             ), array(
             'indexes' => 
             array(
             ),
             'primary' => 
             array(
              0 => 'incidentid',
              1 => 'userid',
             ),
             ));

 		$this->createTable('ir_incident_observer', array(
              'incidentid' => 
              array(
               'type' => 'integer',
               'primary' => true,
               'length' => 8,
              ),
              'userid' => 
              array(
               'type' => 'integer',
               'primary' => true,
               'length' => 8,
              ),
              ), array(
              'indexes' => 
              array(
              ),
              'primary' => 
              array(
               0 => 'incidentid',
               1 => 'userid',
              ),
              ));
    }
    
    /**
     * Use postDown to migrate data from new table back into old tables.
     */
    public function postDown()
    {
        /*
         * Copy ACTOR records from new table into old actor table, and OBSERVER records from new table into old 
         * observer table
         */
        $conn = Doctrine_Manager::connection();
        $conn->beginTransaction();
        
        try {
            $users = Doctrine::getTable('IrIncidentUser')->findAll();
            
            foreach ($users as $oldUser) {
                if ('ACTOR' == $oldUser->accessType) {
                    $newUser = new IrIncidentActor();
                } elseif ('OBSERVER' == $oldUser->accessType) {
                    $newUser = new IrIncidentObserver();
                }
                
                $newUser->incidentId = $oldUser->incidentId;
                $newUser->userId = $oldUser->userId;
                
                $newUser->save();
            }
            
            $conn->commit();            
        } catch (Doctrine_Exception $e) {
            $conn->rollback();
            
            throw $e;
        }
    }
}
