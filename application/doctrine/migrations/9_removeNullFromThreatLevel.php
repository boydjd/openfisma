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
 * @author    Jackson Yang <yangjianshan@users.sourceforge.net>
 * @copyright (c) Endeavor Systems, Inc. 2009 (http://www.endeavorsystems.com)
 * @license   http://openfisma.org/content/license
 * @version   $Id$
 * @package   Migration
 */
/**
 * RemoveNullFromThreatLevel
 * 
 * @uses Doctrine
 * @uses _Migration_Base
 * @package Migration
 * @copyright (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com})
 * @author Jackson Yang <yangjianshan@users.sourceforge.net>
 * @license {@link http://www.openfisma.org/content/license}
 */
class RemoveNullFromThreatLevel extends Doctrine_Migration_Base
{
    /**
     * up - Set threat level and countermeasure effectiveness to not null and append those two events for notification.
     * 
     * @access public
     * @return void
     */
    public function up ()
    {
        //disable record listener after backup it.
        $disabled = Doctrine::getTable('Finding')->getRecordListener()->getOption('disabled');
        Doctrine::getTable('Finding')->getRecordListener()->setOption('disabled', true);
        
        //upgrade threat level definition.
        $q = Doctrine_Query::create()
          -> update('Finding')
          -> set('threatLevel', '?', '')
          -> where('threatLevel = ? or threatLevel = ?', array('null' , 'NONE'))
          -> execute();
        $this->changeColumn('finding', 'threatLevel', null, 'enum', 
                            array('values' => array('LOW' , 'MODERATE' , 'HIGH') , 'notnull' => true));
        
        //upgrade countermeasures effectiveness definition.
        $q = Doctrine_Query::create()
          -> update('Finding')
          -> set('countermeasuresEffectiveness', '?', '')
          -> where('countermeasuresEffectiveness = ? or countermeasuresEffectiveness = ?', array('null' , 'NONE'))
          -> execute();
        $this->changeColumn('finding', 'countermeasuresEffectiveness', null, 'enum', 
                            array('values' => array('LOW' , 'MODERATE' , 'HIGH') , 'notnull' => true));
        
        //retrieve related privilege.
        $q = Doctrine_Query::create()
          -> select() 
          -> from('Privilege')
          -> where('resource = ? and action = ?');
        $privilege = $q->fetchOne(array('notification','finding'));
        
        //insert those two events if its related privilege exists.
        if ($privilege != null) {
            //check if the event exists.
            $q = Doctrine_Query::create()
              -> select() 
              -> from('Event')
              -> where('name = ?');
            $event = $q->fetchOne(array('UPDATE_THREAT_LEVEL'));
            
            //insert Threat Level event if it doesn`t exist.
            if( $event == null) {
                $event = new Event();
                $event->name = "UPDATE_THREAT_LEVEL";
                $event->description = "Threat Level For Finding Updated"; 
                $event->Privilege = $privilege;
                $event->save();
            }
            
            //check if the event exists.
            $q = Doctrine_Query::create()
              -> select() 
              -> from('Event')
              -> where('name = ?');
            $event = $q->fetchOne(array('UPDATE_COUNTERMEASURES_EFFECTIVENESS'));
            
            //insert Countermeasures Effectiveness event if it doesn`t exist.
            if ($event == null) {
                $event = new Event();
                $event->name = "UPDATE_COUNTERMEASURES_EFFECTIVENESS";
                $event->description = "Countermeasures Effectiveness For Finding Updated";
                $event->Privilege = $privilege;
                $event->save();
            }
        }
        
        //restore status of record listener.
        Doctrine::getTable('Finding')->getRecordListener()->setOption('disabled', $disabled);
        
        //generate models from yaml right now after yaml upgraded.
        Doctrine::generateModelsFromYaml(Fisma::getPath('schema'), Fisma::getPath('model'));
    }
    /**
     * down - Set threat level and countermeasure effectiveness to null and remove those two events for notification.
     * 
     * @access public
     * @return void
     */
    public function down ()
    {
        //generate models from yaml right now after yaml downgraded.
        Doctrine::generateModelsFromYaml(Fisma::getPath('schema'), Fisma::getPath('model'));
        
        //disable record listener after backup it.
        $disabled = Doctrine::getTable('Finding')->getRecordListener()->getOption('disabled');
        Doctrine::getTable('Finding')->getRecordListener()->setOption('disabled', true);
        
        //downgrade threat level definition.
        $this->changeColumn('finding', 'threatLevel', null, 'enum', 
                            array('values'  => array('NONE' , 'LOW' , 'MODERATE' , 'HIGH') , 
                                  'default' => 'NONE' , 
                                  'notnull' => false));
        $q = Doctrine_Query::create()
          -> update('Finding')
          -> set('threatLevel', '?', 'NONE')
          -> where('threatLevel = ?', array(''))
          -> execute();
        
        //downgrade countermeasures effectiveness definition.
        $this->changeColumn('finding', 'countermeasuresEffectiveness', null, 'enum', 
                            array('values'  => array('NONE' , 'LOW' , 'MODERATE' , 'HIGH') , 
                                  'default' => 'NONE' , 
                                  'notnull' => false));
        $q = Doctrine_Query::create()
          -> update('Finding')
          -> set('countermeasuresEffectiveness', '?', 'NONE')
          -> where('countermeasuresEffectiveness = ?', array(''))
          -> execute();
        
        //remove those two events if possible.
        $events = Doctrine_Query::create()
          -> select()
          -> from('Event')
          -> where('name = ? or name = ?', array('UPDATE_THREAT_LEVEL','UPDATE_COUNTERMEASURES_EFFECTIVENESS'))
          -> execute();
          
        //delete those two events when there are not any external references on defined relations.
        if($events!=null){
            foreach ($events as $event) {
                if (!$event==null && 
                    !$event->hasReference('Users') && 
                    !$event->hasReference('Notifications') && 
                    !$event->hasReference('Evaluations')) {
                        $event->delete();
                   } else {
                        /** @todo is it possible to remove refernced objects on relations without risks by force? */
                        //$event->delete();
                   }
            }
        }
        
        //restore status of record listener.
        Doctrine::getTable('Finding')->getRecordListener()->setOption('disabled', $disabled);
    }
}
