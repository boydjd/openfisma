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
 * An encapsulation for audit log that is a proxy for the audit log generator
 * 
 * This abstraction is used to avoid declaring all audit log methods in the namespace of each object which uses the 
 * behavior. The functionality is provided in the generator class itself, but this class provides the glue for
 * connecting a particular *instance* of an auditable object to its corresponding generator.
 * 
 * @author     Mark E. Haase
 * @copyright  (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Fisma
 * @subpackage Fisma_Doctrine_Behavior_AuditLoggable
 */
class Fisma_Doctrine_Behavior_AuditLoggable_AuditLog
{
    /**
     * The instance which this object acts upon
     * 
     * @var Doctrine_Record
     */
    private $_instance;
    
    /**
     * The generator which this object uses for functionality
     * 
     * @var Fisma_Doctrine_Behavior_AuditLoggable_Generator
     */
    private $_generator;
    
    /**
     * The constructor sets up the two pieces of data needed: the instance and the generator
     * 
     * @return void
     */
    public function __construct(Doctrine_Record $instance, Fisma_Doctrine_Behavior_AuditLoggable_Generator $generator)
    {
        $this->_instance = $instance;
        $this->_generator = $generator;
    }
    
    /**
     * Set whether logging is enabled
     * 
     * @param bool $enabled
     */
    public function setEnabled($enabled)
    {
        $this->_generator->setEnabled($enabled);
    }
    
    /**
     * Proxy method for writing a log programatically
     * 
     * @param string $message The specified message to be written
     * @return void
     * @param User $user
     */
    public function write($message, User $user = null)
    {
        $this->_generator->write($this->_instance, $message, $user);
    }
    
    /**
     * Proxy method for listing log entries related to an object
     * 
     * @param int $hydrationMode A valid Doctrine hydration mode, e.g. Doctrine::HYDRATE_ARRAY
     * @param int $limit SQL style limit
     * @param int $offset SQL style offset
     * @return mixed A query result whose type depends on which hydration mode you choose.
     */
    public function fetch($hydrationMode, $limit = null, $offset = null)
    {
        return $this->_generator->fetch($this->_instance, $hydrationMode, $limit, $offset);
    }
    
    /**
     * Proxy method for getting an audit log base query relative to an object
     * 
     * @return Doctrine_Query The audio log base query related to the instance
     */
    public function query()
    {
        return $this->_generator->query($this->_instance);
    }
}
