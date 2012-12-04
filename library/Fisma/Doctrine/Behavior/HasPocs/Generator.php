<?php
/**
 * Copyright (c) 2012 Endeavor Systems, Inc.
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
 * Generator for the HasPocs behavior
 *
 * @author     Duy K. Bui <duy.bui@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2012 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Fisma
 * @subpackage Fisma_Doctrine_Behavior_HasPocs
 */
class Fisma_Doctrine_Behavior_HasPocs_Generator extends Doctrine_Record_Generator
{
    /**
     * Set up the generated class name
     *
     * @return void
     */
    public function initOptions()
    {
        // This will result in class names like 'FindingPoc' and 'SystemPoc', etc.
        $this->setOption('className', '%CLASS%Poc');
    }

    /**
     * Set up relations
     *
     * @return void
     */
    public function buildRelation()
    {
        $this->buildForeignRelation('Pocs');
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

        $this->hasColumn(
            'pocId',
            'integer',
            null,
            array('comment' => 'The id of the user')
        );

        $this->hasColumn(
            'type',
            'string',
            255,
            array('comment' => 'The position of the user')
        );

        // Foreign key to the object which this poc relates to
        $this->hasColumn(
            'objectId',
            'integer',
            null,
            array('comment' => 'The parent object to which this poc belongs')
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
                'foreign' => 'id',
                'foreignAlias' => 'MultiPocs'
            )
        );

        // Relation for the user class
        $this->hasOne(
            'User',
            array(
                'local' => 'pocId',
                'foreign' => 'id'
            )
        );
    }

    /**
     * Add a poc
     *
     * @param Doctrine_Record $instance The instance to be logged
     * @param int $pocId
     * @param string $type
     * @return Doctrine_Record Return the added poc
     */
    public function addPoc(Doctrine_Record $instance, $pocId, $type)
    {
        // Create a new poc
        $pocClass = $this->_options['className'];
        $instanceClass = $this->getOption('table')->getComponentName();

        $pocEntry = $this->fetchOneByType($instance, $type);
        if (!$pocEntry) {
            $pocEntry = new $pocClass;
        }
        $pocEntry->pocId = $pocId;
        $pocEntry->type = $type;
        $pocEntry->objectId = $instance->id;

        $pocEntry->save();

        return $pocEntry;
    }

    /**
     * Add a poc
     *
     * @param Doctrine_Record $instance The instance to be logged
     * @param string $type
     * @return void
     */
    public function removePoc(Doctrine_Record $instance, $type)
    {
        // Create a new poc
        $pocClass = $this->_options['className'];
        $instanceClass = $this->getOption('table')->getComponentName();

        $pocEntry = $this->fetchOneByType($instance, $type);
        if ($pocEntry) {
            $pocEntry->delete();
        }        
    }

    /**
     * List pocs for this object, optionally providing a SQL-style limit and offset to get a limited subset of all
     * the pocs
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
              ->select('o.type, u.displayname');

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
     * Count the number of pocs associated with this object
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
     * Get a base query which will return all pocs for the current object
     *
     * @param mixed $instance The object to get logs for
     * @return Doctrine_Query
     */
    public function query($instance)
    {
        $query = Doctrine_Query::create()->from("{$this->_options['className']} o")
                                         ->leftJoin('o.User u')
                                         ->where('o.objectId = ?', $instance->id);

        return $query;
    }

    /**
     * Get a poc based on the type
     *
     * @param mixed $instance The object to get the POC for
     * @param string $type The type to specify the POC
     * @return User The POC
     */
    public function fetchOneByType($instance, $type) {
        $query = $this->query($instance);
        $query->andWhere('o.type = ?', $type);

        return $query->fetchOne();
    }
}
