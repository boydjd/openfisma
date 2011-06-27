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
 * Generator for the attach artifacts behavior
 * 
 * @author     Mark E. Haase
 * @copyright  (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Fisma
 * @subpackage Fisma_Doctrine_Behavior_AuditLoggable
 */
class Fisma_Doctrine_Behavior_AttachArtifacts_Generator extends Doctrine_Record_Generator
{
    /**
     * Extensions which should not be attached
     * 
     * @var array
     */
    private $_extensionsBlackList = array(
        /* CSS        */ 'css',
        /* Executable */ 'app', 'exe', 'com',
        /* HTML       */ 'htm', 'html', 'xhtml',
        /* Java       */ 'class',
        /* Javascript */ 'js',
        /* PHP        */ 'php', 'phtml', 'php3', 'php4', 'php5',
    );
    
    /**
     * MIME types which should not be attached
     * 
     * @var array
     */
     private $_mimeTypeBlackList = array(
         /* CSS        */ 'text/css',
         /* HTML       */ 'text/html', 'application/xhtml+xml',
         /* Javascript */ 'application/x-javascript', 'text/javascript', 'application/ecmascript',
     );
    
    /**
     * Set up the generated class name
     * 
     * @return void
     */
    public function initOptions()
    {
        // This will result in class names like 'IncidentArtifact'
        $this->setOption('className', '%CLASS%Artifact');
        
        // Set the Fisma_Doctrine_Behavior_AttachArtifacts_Artifact model as the base class for these generated classes
        $this->setOption(
            'builderOptions', array('baseClassName' => 'Fisma_Doctrine_Behavior_AttachArtifacts_Artifact')
        );
    }
    
    /**
     * Set up relations
     * 
     * @return void
     */
    public function buildRelation()
    {
        $this->buildForeignRelation('Artifact');
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
            array('comment' => 'The timestamp when this artifact was uploaded')
        );

        // Name of file
        $this->hasColumn(
            'fileName', 
            'string', 
            255, 
            array('comment' => 'The file name for this artifact')
        );
        
        // MIME type
        $this->hasColumn(
            'mimeType', 
            'string', 
            255, 
            array('comment' => 'The MIME type for this artifact')
        );

        // File size
        $this->hasColumn(
            'fileSize', 
            'string', 
            null, 
            array('comment' => 'File size in bytes')
        );

        // Comment associated with this artifact
        $this->hasColumn(
            'comment', 
            'string', 
            null, 
            array('comment' => 'Comment associated with this artifact')
        );

        // Foreign key to the object which this artifact belongs to
        $this->hasColumn(
            'objectId', 
            'integer', 
            null, 
            array('comment' => 'The parent object to which this artifact belongs')
        );
        
        // Foreign key to the user who created this log entry
        $this->hasColumn(
            'userId', 
            'integer', 
            null, 
            array('comment' => 'The user who uploaded this artifact')
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
     * Attach an artifact to an object
     * 
     * @param Doctrine_Record $instance The object to which this artifact needs to be attached
     * @param array $file A $_FILES style array of uploaded file info
     * @param string $comment User's comment associated with this artifact
     */
    public function attach(Doctrine_Record $instance, $file, $comment)
    {
        // Ensure that the file is not on the black list
        $this->checkFileBlackList($file);
        
        // Create a new record to represent this artifact
        $artifactClass = $this->_options['className'];

        $artifact = new $artifactClass;        
        $artifact->createdTs = Fisma::now();
        $artifact->mimeType = $file['type'];
        $artifact->fileSize = $file['size'];
        $artifact->comment = $comment;
        $artifact->userId = CurrentUser::getInstance()->id;
        $artifact->objectId = $instance->id;

        // Insert a timestamp into the file name
        $dateTime = Zend_Date::now()->toString(Fisma_Date::FORMAT_FILENAME_DATETIMESTAMP);
        $fileName = preg_replace('/^(.*)\.(.*)$/', '$1-' . $dateTime . '.$2', $file['name'], 2, $count);

        // Move the file into its correct location
        $destinationPath = $artifact->getStoragePath();
        
        $filePath = "$destinationPath/$fileName";

        $moveResult = move_uploaded_file($file['tmp_name'], $filePath);
        
        if (!$moveResult) {
            throw new Fisma_Zend_Exception("The file could not be stored due to the server's permissions settings.");
        }

        // Persist
        $artifact->fileName = $fileName;
        $artifact->save();
    }
    
    /**
     * Check the specified file against the blacklist to see if it is disallowed
     * 
     * @param array $file File information in array format as specified in the $_FILES superglobal
     * @throw Fisma_Zend_Exception_User If the user has specified a file type which is black listed
     */
    public function checkFileBlackList($file)
    {
        // Check file extension
        $fileExtension = pathinfo($file['name'], PATHINFO_EXTENSION);

        if (in_array($fileExtension, $this->_extensionsBlackList)) {
            throw new Fisma_Zend_Exception_User("This file type (.$fileExtension) is not allowed.");
        }

        // Check mime type
        if (in_array($file['type'], $this->_mimeTypeBlackList)) {
            throw new Fisma_Zend_Exception_User("This file type ({$file['type']}) is not allowed.");
        }
    }
    
    /**
     * Find an artifact by its primary key or FALSE if none found
     * 
     * @param Doctrine_Record $instance The incident object which owns the artifact
     * @param int $id The primary key of the artifact
     * @return Doctrine_Record|false
     */
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
    
    /**
     * List artifacts for this object, optionally providing a SQL-style limit and offset to get a subset of the 
     * artifacts
     * 
     * @param Doctrine_Record $instance The object to get artifacts for
     * @param int $hydrationMode A valid Doctrine hydration mode, e.g. Doctrine::HYDRATE_ARRAY
     * @param int $limit SQL style limit
     * @param int $offset SQL style offset
     * @return mixed A query result whose type depends on which hydration mode you choose.
     */
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
    
    /**
     * Count the number of artifacts attached to this object
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
     * Get a base query which will return all logs for the current object
     * 
     * @param Doctrine_Record $instance The object to get artifacts for
     * @return Doctrine_Query The base query for artifacts related to the instance
     */
    public function query($instance)
    {
        $query = Doctrine_Query::create()->from("{$this->_options['className']} o")
                                         ->leftJoin('o.User u')
                                         ->where('o.objectId = ?', $instance->id)
                                         ->orderBy('o.createdTs desc');

        return $query;
    }
}
