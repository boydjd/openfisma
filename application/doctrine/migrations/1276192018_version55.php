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
 * Consolidate the IrIncidentActor and IrIncidentObserver into a single model called IrIncidentUser
 * 
 * 1) Create a table for the IrIncidentUser
 * 2) Copy existing actors into the new table, setting the accessType to ACTOR
 * 3) Copy existing observers into the new table, setting the accessType to OBSERVER
 * 
 * It isn't possible to drop the old tables in this migration because the drop tables will run BEFORE we copy the
 * data over in the postUp() method. (We could store the contents of the tables in a class variable, but that seems
 * risky.) So tables are dropped in version 56.
 * 
 * @package Migration
 * @copyright (c) Endeavor Systems, Inc. 2010 {@link http://www.endeavorsystems.com}
 * @author Mark E. Haase <mhaase@endeavorsystems.com>
 * @license http://www.openfisma.org/content/license GPLv3
 */
class Version55 extends Doctrine_Migration_Base
{
    /**
     * Create the new table and drop the old tables
     */
    public function up()
    {
        // Create the new table
		$this->createTable('ir_incident_user', array(
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
             'accesstype' => 
             array(
              'type' => 'enum',
              'values' => 
              array(
              0 => 'ACTOR',
              1 => 'OBSERVER',
              ),
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
        return;
    }

    /**
     * Use postUp to migrate data from old tables to new table.
     */
    public function postUp()
    {
        // Regenerate models so that we can instantiate new IrIncidentUser objects
        $task = new Doctrine_Task_GenerateModelsYaml();
        
        $task->setArguments(Zend_Registry::get('doctrine_config'));

        $task->execute();
        
        // Copy records from each old table into the new table, wrapped in a transaction
        $conn = Doctrine_Manager::connection();
        $conn->beginTransaction();
        
        try {
            $actors = Doctrine::getTable('IrIncidentActor')->findAll();
            
            foreach ($actors as $actor) {
                $incidentUser = new IrIncidentUser();
                
                $incidentUser->incidentId = $actor->incidentId;
                $incidentUser->userId = $actor->userId;
                $incidentUser->accessType = 'ACTOR';
                
                $incidentUser->save();
            }

            $observers = Doctrine::getTable('IrIncidentObserver')->findAll();
            
            foreach ($observers as $observer) {
                $incidentUser = new IrIncidentUser();
                
                $incidentUser->incidentId = $observer->incidentId;
                $incidentUser->userId = $observer->userId;
                $incidentUser->accessType = 'OBSERVER';
                
                $incidentUser->save();
            }
            
            $conn->commit();            
        } catch (Doctrine_Exception $e) {
            $conn->rollback();
            
            throw $e;
        }
    }

    /**
     * Drop the new table
     */
    public function down()
    {   
        // Drop new table
		$this->dropTable('ir_incident_user');
    }
}
