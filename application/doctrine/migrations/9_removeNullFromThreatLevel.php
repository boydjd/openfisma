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
     * up - Set threat level and countermeasure effectiveness to not null
     * 
     * @access public
     * @return void
     */
    public function up ()
    {
        //disable record listener after backup it.
        $disabled = Doctrine::getTable('Finding')->getRecordListener()->getOption('disabled');
        Doctrine::getTable('Finding')->getRecordListener()->setOption('disabled', true);
        
        $q = Doctrine_Query::create()
          -> update('Finding')
          -> set('threatLevel', '?', '')
          -> where('threatLevel = ? or threatLevel = ?', array('null' , 'NONE'))
          -> execute();
        $this->changeColumn('finding', 'threatLevel', null, 'enum', 
                            array('values' => array('LOW' , 'MODERATE' , 'HIGH') , 'notnull' => true));
        
        $q = Doctrine_Query::create()
          -> update('Finding')
          -> set('countermeasuresEffectiveness', '?', '')
          -> where('countermeasuresEffectiveness = ? or countermeasuresEffectiveness = ?', array('null' , 'NONE'))
          -> execute();
        $this->changeColumn('finding', 'countermeasuresEffectiveness', null, 'enum', 
                            array('values' => array('LOW' , 'MODERATE' , 'HIGH') , 'notnull' => true));
        
        //restore status of record listener.
        Doctrine::getTable('Finding')->getRecordListener()->setOption('disabled', $disabled);
        
        //generate models from yaml right now after yaml upgraded.
        Doctrine::generateModelsFromYaml(Fisma::getPath('schema'), Fisma::getPath('model'));
    }
    /**
     * down - Set threat level and countermeasure effectiveness to null
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
        
        $this->changeColumn('finding', 'threatLevel', null, 'enum', 
                            array('values'  => array('NONE' , 'LOW' , 'MODERATE' , 'HIGH') , 
                                  'default' => 'NONE' , 
                                  'notnull' => false));
        $q = Doctrine_Query::create()
          -> update('Finding')
          -> set('threatLevel', '?', 'NONE')
          -> where('threatLevel = ?', array(''))
          -> execute();
        $this->changeColumn('finding', 'countermeasuresEffectiveness', null, 'enum', 
                            array('values'  => array('NONE' , 'LOW' , 'MODERATE' , 'HIGH') , 
                                  'default' => 'NONE' , 
                                  'notnull' => false));
        $q = Doctrine_Query::create()
          -> update('Finding')
          -> set('countermeasuresEffectiveness', '?', 'NONE')
          -> where('countermeasuresEffectiveness = ?', array(''))
          -> execute();
          
        //restore status of record listener.
        Doctrine::getTable('Finding')->getRecordListener()->setOption('disabled', $disabled);
    }
}
