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
 * A proxy for the Commentable generator
 * 
 * This abstraction is used to avoid declaring all Commentable methods in the namespace of each 
 * object which uses the behavior. The functionality is provided in the generator class itself, but this 
 * class provides the glue for connecting a particular object *instance* to its corresponding generator.
 * 
 * @author     Mark E. Haase
 * @copyright  (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Fisma
 * @subpackage Fisma_Doctrine_Behavior_Commentable
 */
class Fisma_Doctrine_Behavior_Commentable_Proxy
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
     * @var Fisma_Doctrine_Behavior_Commentable_Generator
     */
    private $_generator;
    
    /**
     * The constructor sets up the two pieces of data needed: the instance and the generator
     * 
     * @param Doctrine_Record $instance The instance to bind this generator to
     * @param Fisma_Doctrine_Behavior_Commentable_Generator $generator The generator to bind this instance to
     * @return void
     */
    public function __construct(Doctrine_Record $instance, Fisma_Doctrine_Behavior_Commentable_Generator $generator)
    {
        $this->_instance = $instance;
        $this->_generator = $generator;
    }
    
    /**
     * Proxy method for adding a comment
     * 
     * @param string $comment
     * @return Doctrine_Record Return the comment object which was created
     */
    public function addComment($comment)
    {
        return $this->_generator->addComment($this->_instance, $comment);
    }
    
    /**
     * Proxy method for listing comments related to an object
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
     * Proxy method for counting the number of comments attached to an object
     * 
     * @return int
     */
    public function count()
    {
        return $this->_generator->count($this->_instance);
    }
        
    /**
     * Proxy method for getting a comment base query relative to an object
     * 
     * @return Doctrine_Query The comment base query related to the instance
     */
    public function query()
    {
        return $this->_generator->query($this->_instance);
    }
}
