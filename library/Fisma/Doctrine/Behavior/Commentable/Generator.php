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
 * Generator for the Commentable behavior
 * 
 * @author     Mark E. Haase
 * @copyright  (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Fisma
 * @subpackage Fisma_Doctrine_Behavior_Commentable
 */
class Fisma_Doctrine_Behavior_Commentable_Generator extends Doctrine_Record_Generator
{
    /**
     * Set up the generated class name
     * 
     * @return void
     */
    public function initOptions()
    {
        // This will result in class names like 'FindingAuditLog' and 'SystemAuditLog', etc.
        $this->setOption('className', '%CLASS%Comment');
    }
    
    /**
     * Set up relations
     * 
     * @return void
     */
    public function buildRelation()
    {
        $this->buildForeignRelation('Comment');
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

        // Comment timestamp
        $this->hasColumn(
            'createdTs', 
            'timestamp', 
            null, 
            array('comment' => 'The timestamp when this entry was created')
        );
        
        // The comment message. The length isn't defined which means Doctrine will
        // create the largest delimited string field supported by underlying DBMS
        $this->hasColumn(
            'comment', 
            'string', 
            null, 
            array('comment' => 'The text of the comment')
        );

        // Foreign key to the object which this log relates to
        $this->hasColumn(
            'objectId', 
            'integer', 
            null, 
            array('comment' => 'The parent object to which this comment belongs')
        );
        
        // Foreign key to the user who created this log entry
        $this->hasColumn(
            'userId', 
            'integer', 
            null, 
            array('comment' => 'The user who created comment')
        );
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
     * Add a comment
     * 
     * @param Doctrine_Record $instance The instance to be logged
     * @param string $message The comment to be written
     * @return Doctrine_Record Return the added comment
     */
    public function addComment(Doctrine_Record $instance, $comment)
    {
        // Create a new comment
        $commentClass = $this->_options['className'];
        $instanceClass = $this->getOption('table')->getComponentName();

        $commentEntry = new $commentClass;
        $commentEntry->createdTs = Fisma::now();
        $commentEntry->comment = $comment;
        $commentEntry->userId = CurrentUser::getInstance()->id;
        $commentEntry->objectId = $instance->id;

        $commentEntry->save();
        
        return $commentEntry;
    }
    
    /**
     * List comments for this object, optionally providing a SQL-style limit and offset to get a limited subset of all 
     * the comments
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
              ->select('o.createdTs, o.comment, u.username');
        
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
     * Count the number of comments associated with this object
     * 
     * @param Doctrine_Record $instance
     * @return int
     */
    public function count($instance)
    {
        $query = $this->query($instance);
        
        return $query->count();
    }
        
    /**
     * Get a base query which will return all comments for the current object
     * 
     * @param mixed $instance The object to get logs for
     * @return Doctrine_Query
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
