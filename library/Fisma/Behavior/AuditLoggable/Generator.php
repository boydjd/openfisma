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
 * Generator for the audit log behavior
 * 
 * @author     Mark E. Haase
 * @copyright  (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Fisma
 * @subpackage Fisma_Behavior
 * @version    $Id$
 */
class Fisma_Behavior_AuditLoggable_Generator extends Doctrine_Record_Generator
{
    /**
     * Set up the generated class name
     */
    public function initOptions()
    {
        // This will result in class names like 'FindingAuditLog' and 'SystemAuditLog', etc.
        $this->setOption('className', '%CLASS%AuditLog');
    }
    
    /**
     * Set up relations
     */
    public function buildRelation()
    {
        $this->buildForeignRelation('AuditLog');
        $this->buildLocalRelation();
    }
    
    /**
     * Table definition
     */
    public function setTableDefinition()
    {
        // Primary key
        $this->hasColumn(
            'id', 
            'integer', 
            null, 
            array('primary' => true, 'autoincrement' => true)
        );

        // Log timestamp
        $this->hasColumn(
            'createdTs', 
            'timestamp', 
            null, 
            array('comment' => 'The timestamp when this entry was created')
        );
        
        // The log message -- a textual description of the event. The length isn't defined which means Doctrine will
        // create the largest delimited string field supported by underlying DBMS
        $this->hasColumn(
            'message', 
            'string', 
            null, 
            array('comment' => 'The log message')
        );

        // Foreign key to the object which this log relates to
        $this->hasColumn(
            'objectId', 
            'integer', 
            null, 
            array('comment' => 'The parent object which this log entry refers to')
        );
        
        // Foreign key to the user who created this log entry
        $this->hasColumn(
            'userId', 
            'integer', 
            null, 
            array('comment' => 'The user who created this log entry')
        );
    }
    
    /**
     * Set up parent object and user relations
     */
    public function setUp()
    {
        // The base class is the class which is using this behavior, such as 'Finding' or 'System'
        $baseClass = $this->getOption('table')->getComponentName();
        
        // Relation for the base class
        $this->hasOne(
            $baseClass,
            array(
                'local' => 'objectId',
                'foreign' => 'id'
            )
        );
        
        // Relation for the user class
        $this->hasOne(
            'User',
            array(
                'local' => 'userId',
                'foreign' => 'id'
            )
        );
    }
}
