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
 * @subpackage Fisma_Behavior_AuditLoggable
 * @version    $Id$
 */
class Fisma_Behavior_AuditLoggable_AuditLog
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
     * @var Fisma_Behavior_AuditLoggable_Generator
     */
    private $_generator;
    
    /**
     * The constructor sets up the two pieces of data needed: the instance and the generator
     */
    public function __construct(Doctrine_Record $instance, Fisma_Behavior_AuditLoggable_Generator $generator)
    {
        $this->_instance = $instance;
        $this->_generator = $generator;
    }
    
    /**
     * Proxy method for writing a log programatically
     * 
     * @param string $message
     */
    public function write($message)
    {
        $this->_generator->write($this->_instance, $message);
    }
    
    /**
     * Proxy method for listing log entries related to an object
     * 
     * @param int $hydrationMode A valid Doctrine hydration mode, e.g. Doctrine::HYDRATE_ARRAY
     * @param int $limit SQL style limit and offset
     * @param int $offset
     * @return mixed Returns a query result. The type depends on which hydration mode you choose.
     */
    public function fetch($hydrationMode, $limit = null, $offset = null)
    {
        return $this->_generator->fetch($this->_instance, $hydrationMode, $limit, $offset);
    }
    
    /**
     * Proxy method for getting an audit log base query relative to an object
     * 
     * @return Doctrine_Query
     */
    public function query()
    {
        return $this->_generator->query($this->_instance);
    }
}
