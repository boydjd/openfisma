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
 * @subpackage Fisma_Doctrine_Behavior_AuditLoggable
 */
class Fisma_Doctrine_Behavior_AuditLoggable_Generator extends Doctrine_Record_Generator
{
    /**
     * Indicates whether logging is enabled
     */
    private $_enabled = true;
    
    /**
     * Set up the generated class name
     * 
     * @return void
     */
    public function initOptions()
    {
        // This will result in class names like 'FindingAuditLog' and 'SystemAuditLog', etc.
        $this->setOption('className', '%CLASS%AuditLog');
    }
    
    /**
     * Set up relations
     * 
     * @return void
     */
    public function buildRelation()
    {
        $this->buildForeignRelation('AuditLog');
        $this->buildLocalRelation();
    }
    
    /**
     * Table definition
     * 
     * @return void
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
        
        // Add the listener for the timestamp field
        $this->addListener(new Fisma_Doctrine_Behavior_AuditLoggable_LogListener);
    }
    
    /**
     * Set up parent object and user relations
     * 
     * @return void
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
    
    /**
     * Set whether logging is enabled for this class
     * 
     * Logging is enabled by default
     * 
     * @param bool $enabled
     */
    public function setEnabled($enabled)
    {
        $this->_enabled = true;
    }
    
    /**
     * Write a log message programmatically
     * 
     * You only need to use this if you want to log something that isn't logged automatically. Create/Update/Delete can
     * all be logged automatically.
     * 
     * @param Doctrine_Record $instance The instance to be logged
     * @param string $message The message to be written
     * @return void
     * @param User $user The user who performed the logged action
     */
    public function write(Doctrine_Record $instance, $message)
    {
        // Normally logs are written by the current user, but current user can be null if the session is 
        // unauthenticated (for example, failed login attempt)
        $user = CurrentUser::getInstance();
        $userId = $user ? $user->id : null;
        
        if ($this->_enabled) {
            // Create a new audit log entry
            $logClass = $this->_options['className'];
            $instanceClass = $this->getOption('table')->getComponentName();

            $auditLogEntry = new $logClass;        
            $auditLogEntry->message = $message;
            $auditLogEntry->userId = $userId;
            $auditLogEntry->objectId = $instance->id;

            // Logs must be saved directly because this is frequently called from a listener, and Doctrine will not be 
            // able to auto-save related records in that context
            $auditLogEntry->save();
        }
    }
    
    /**
     * List log entries for this object, optionally providing a SQL-style limit and offset to get a subset of the 
     * entire log
     * 
     * @param mixed $instance The object to get logs for
     * @param int $hydrationMode A valid Doctrine hydration mode, e.g. Doctrine::HYDRATE_ARRAY
     * @param int $limit SQL style limit
     * @param int $offset SQL style offset
     * @return mixed A query result whose type depends on which hydration mode you choose.
     */
    public function fetch($instance, $hydrationMode, $limit = null, $offset = null)
    {
        $query = $this->query($instance);
        $query->setHydrationMode($hydrationMode)
              ->select('o.createdTs, o.message, u.username');
        
        if ($limit) {
            $query->limit($limit);
        }
        
        if ($offset) {
            $query->offset($offset);
        }
        
        $results = $query->execute();
        
        return $results;
    }
    
    /**
     * Get a base query which will return all logs for the current object
     * 
     * @param mixed $instance The object to get logs for
     * @return Doctrine_Query The audio log base query related to the instance
     */
    public function query($instance)
    {
        $query = Doctrine_Query::create()->from("{$this->_options['className']} o")
                                         ->leftJoin('o.User u')
                                         ->where('o.objectId = ?', $instance->id)
                                         ->orderBy('o.createdTs desc, o.id desc');
        
        return $query;
    }
}
