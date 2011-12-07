<?php
/**
 * Copyright (c) 2011 Endeavor Systems, Inc.
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
 * A proxy for the attachment class generator
 * 
 * This abstraction is used to avoid declaring all HasAttachments methods in the namespace of each 
 * object which uses the behavior. The functionality is provided in the generator class itself, but this 
 * class provides the glue for connecting a particular object *instance* to its corresponding generator.
 * 
 * @author     Duy K. Bui <duy.bui@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2011 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Fisma
 * @subpackage Fisma_Doctrine_Behavior_HasAttachments
 */
class Fisma_Doctrine_Behavior_HasAttachments_Proxy
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
     * @var Fisma_Doctrine_Behavior_HasAttachments_Generator
     */
    private $_generator;
    
    /**
     * The constructor sets up the two pieces of data needed: the instance and the generator
     * 
     * @return void
     */
    public function __construct(Doctrine_Record $instance, Fisma_Doctrine_Behavior_HasAttachments_Generator $generator)
    {
        $this->_instance = $instance;
        $this->_generator = $generator;
    }
    
    /**
     * Proxy method for attaching an attachment
     * 
     * @param int $typeId The type of attachment, such as DocumentType 
     */
    public function attach($file)
    {
        $this->_generator->attach($this->_instance, $file);
    }
    
    /**
     * Proxy method for finding an attachment by ID
     * 
     * @param int $attachmentId The primary key of the attachment to find
     * @return Doctrine_Record
     *
    public function find($attachmentId)
    {
        return $this->_generator->find($this->_instance, $attachmentId);
    }
     */
    
    /**
     * Proxy method for listing attachments related to an object
     * 
     * @param int $hydrationMode A valid Doctrine hydration mode, e.g. Doctrine::HYDRATE_ARRAY
     * @param int $limit SQL style limit
     * @param int $offset SQL style offset
     * @return mixed A query result whose type depends on which hydration mode you choose.
     *
    public function fetch($hydrationMode, $limit = null, $offset = null)
    {
        return $this->_generator->fetch($this->_instance, $hydrationMode, $limit, $offset);
    }
     */
    
    /**
     * Proxy method for counting the number of attachments attached to an object
     *
    public function count()
    {
        return $this->_generator->count($this->_instance);
    }
     */
        
    /**
     * Proxy method for getting an attachment base query relative to an object
     * 
     * @return Doctrine_Query The attachment base query related to the instance
     *
    public function query()
    {
        return $this->_generator->query($this->_instance);
    }
     */
}
