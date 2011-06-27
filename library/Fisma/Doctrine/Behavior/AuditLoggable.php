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
 * A behavior which provides automatic audit logging for models
 * 
 * @author     Mark E. Haase
 * @copyright  (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Fisma
 * @subpackage Fisma_Doctrine_Behavior_AuditLoggable
 */
class Fisma_Doctrine_Behavior_AuditLoggable extends Doctrine_Template
{
    /**
     * Overload constructor to plug in the record generator
     * 
     * @param array $options The template options
     * @return void
     */
    public function __construct(array $options = array())
    {
        parent::__construct($options);
        
        $this->_plugin = new Fisma_Doctrine_Behavior_AuditLoggable_Generator();
    }
    
    /**
     * Define fields and listeners that are added to the class which uses this behavior
     * 
     * @return void
     */
    public function setTableDefinition()
    {
        // This listener creates log entries automatically when objects are created, updated, or deleted
        $this->addListener(new Fisma_Doctrine_Behavior_AuditLoggable_ObjectListener($this->_options));
    }
    
    /**
     * Define a relation to the generated audit log class
     * 
     * @return void
     */
    public function setUp()
    {
        // The "component name" is the name of the class which is applying this behavior, so this will result in a 
        // class name like 'FindingAuditLog' or 'SystemAuditLog'
        $foreignClassName = $this->getTable()->getComponentName() . 'AuditLog';

        $this->hasMany(
            $foreignClassName, 
            array(
                'local' => 'id',
                'foreign' => 'objectId'
            )
        );
        
        $this->_plugin->initialize($this->getTable());
    }
    
    /**
     * Return a log instance
     * 
     * The instance acts as glue between the instance itself and the generator which needs to act on it
     * 
     * @return Fisma_Doctrine_Behavior_AuditLoggable_AuditLog The newly instantiated auditlog instance
     */
    public function getAuditLog()
    {
        return new Fisma_Doctrine_Behavior_AuditLoggable_AuditLog($this->getInvoker(), $this->_plugin);
    }
}
