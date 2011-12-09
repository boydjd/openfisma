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
 * Generator for the attach attachments behavior
 * 
 * @author     Duy K. Bui <duy.bui@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2011 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Fisma
 * @subpackage Fisma_Doctrine_Behavior_AuditLoggable
 */
class Fisma_Doctrine_Behavior_HasAttachments_Generator extends Doctrine_Record_Generator
{
    /**
     * Set up the generated class name
     * 
     * @return void
     */
    public function initOptions()
    {
        // This will result in class names like 'IncidentAttachment'
        $this->setOption('className', '%CLASS%Upload');     
        
        /* Set the Fisma_Doctrine_Behavior_HasAttachments_Attachment model as the base class for these generated classes
        $this->setOption(
            'builderOptions', array('baseClassName' => 'Fisma_Doctrine_Behavior_HasAttachments_Attachment')
        );
         */
    }
    
    /**
     * Set up relations
     * 
     * @return void
     */
    public function buildRelation()
    {
        //$this->buildForeignRelation('Uploads');
        //$this->buildLocalRelation();

   }
     

    /**
     * Table definition
     * 
     * @return void
     */
    public function setTableDefinition()
    {
        /*$this->hasColumn(
            'id',
            'integer',
            null,
            array(
                'primary' => true,
                'autoincrement' => true
            )
        );*/

        // Foreign key to the Upload associated with this Attachment entry
        $this->hasColumn(
            'objectId', 
            'integer', 
            null, 
            array(
                'comment' => 'The uploaded file',
                'primary' => true
            )
        );
        
        // Foreign key to the object which this attachment belongs to
        $this->hasColumn(
            'id', 
            'integer', 
            null, 
            array(
                'comment' => 'The parent object to which the attachment belongs',
                'primary' => true
            )
        );

    }
    
    /**
     * Set up parent object and user relations
     * 
     * @return void
     */
    public function setUp()
    {
        // Relation for the upload class
         $this->hasOne(
            'Upload as Upload',
            array(
                'local' => 'objectId',
                'foreign' => 'id',
                'owningSide' => true
            )
        );
    }
         

    /**
     * Attach an attachment to an object
     * 
     * @param Doctrine_Record $instance The object to which this attachment needs to be attached
     * @param mixed $file The array mapped from FILE_ARRAY by HTTP Request 
     *
    public function attach(Doctrine_Record $instance, $file)
    {
        $upload = new Upload();
        $upload->instantiate($file);

        $instance->Uploads[] = $upload;
    }
     */

    /**
     * Find an attachment by its primary key or FALSE if none found
     * 
     * @param Doctrine_Record $instance The object which owns the attachment
     * @param int $id The primary key of the attachment
     * @return Doctrine_Record|false
     *
    public function find(Doctrine_Record $instance, $id)
    {
        $query = $this->query($instance)->addWhere('id = ?', $id);
        $resultSet = $query->execute();

        if (count($resultSet) > 0) {
            return $resultSet[0];
        } else {
            return false;
        }
    }
     */
    
    /**
     * List attachments for this object, optionally providing an SQL-style limit and offset for pagination
     * 
     * @param Doctrine_Record $instance The object to get attachments for
     * @param int $hydrationMode A valid Doctrine hydration mode, e.g. Doctrine::HYDRATE_ARRAY
     * @param int $limit SQL style limit
     * @param int $offset SQL style offset
     * @return mixed A query result whose type depends on which hydration mode you choose.
     *
    public function fetch($instance, $hydrationMode, $limit = null, $offset = null)
    {
        $query = $this->query($instance)->setHydrationMode($hydrationMode);
        
        if ($limit) {
            $query->limit($limit);
        }
        
        if ($offset) {
            $query->offset($offset);
        }
        
        $results = $query->execute();
        
        return $results;
    }
     */
    
    /**
     * Count the number of attachments attached to this object
     * 
     * @param Doctrine_Record $instance
     * @return int
     *
    public function count($instance)
    {
        $query = $this->query($instance);
        
        return $query->count();
    }
     */
    
    /**
     * Get a base query which will return all attachments for the current object
     * 
     * @param Doctrine_Record $instance The object to get attachments for
     * @return Doctrine_Query The base query for attachments related to the instance
     *
    public function query($instance)
    {
        $query = Doctrine_Query::create()->from("{$this->_options['className']} o")
                                         ->leftJoin('o.User u')
                                         ->where('o.objectId = ?', $instance->id)
                                         ->orderBy('a.createdTs desc');

        return $query;
    }
     */
}
