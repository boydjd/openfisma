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
 * A proxy for the HasPocs generator
 *
 * This abstraction is used to avoid declaring all HasPocs methods in the namespace of each
 * object which uses the behavior. The functionality is provided in the generator class itself, but this
 * class provides the glue for connecting a particular object *instance* to its corresponding generator.
 *
 * @author     Duy K. Bui <duy.bui@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2012 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Fisma
 * @subpackage Fisma_Doctrine_Behavior_HasPocs
 */
class Fisma_Doctrine_Behavior_HasPocs_Proxy
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
     * @var Fisma_Doctrine_Behavior_HasPocs_Generator
     */
    private $_generator;

    /**
     * The constructor sets up the two pieces of data needed: the instance and the generator
     *
     * @param Doctrine_Record $instance The instance to bind this generator to
     * @param Fisma_Doctrine_Behavior_HasPocs_Generator $generator The generator to bind this instance to
     * @return void
     */
    public function __construct(Doctrine_Record $instance, Fisma_Doctrine_Behavior_HasPocs_Generator $generator)
    {
        $this->_instance = $instance;
        $this->_generator = $generator;
    }

    /**
     * Proxy method for adding a poc
     *
     * @param int $pocId
     * @param string $type
     * @return Doctrine_Record Return the poc object which was created
     */
    public function addPoc($pocId, $type)
    {
        return $this->_generator->addPoc($this->_instance, $pocId, $type);
    }

    /**
     * Proxy method for removing a poc
     *
     * @param int $pocId
     * @param string $type
     * @return void
     */
    public function removePoc($pocId, $type)
    {
        return $this->_generator->removePoc($this->_instance, $pocId, $type);
    }

    /**
     * Proxy method for listing pocs related to an object
     *
     * @param int $hydrationMode A valid Doctrine hydration mode, e.g. Doctrine::HYDRATE_ARRAY
     * @param int $limit SQL style limit
     * @param int $offset SQL style offset
     * @return mixed A query result whose type depends on which hydration mode you choose.
     */
    public function fetch($hydrationMode = Doctrine::HYDRATE_RECORD, $limit = null, $offset = null)
    {
        return $this->_generator->fetch($this->_instance, $hydrationMode, $limit, $offset);
    }

    /**
     * Proxy method for counting the number of pocs attached to an object
     *
     * @return int
     */
    public function count()
    {
        return $this->_generator->count($this->_instance);
    }

    /**
     * Proxy method for getting a poc base query relative to an object
     *
     * @return Doctrine_Query The poc base query related to the instance
     */
    public function query()
    {
        return $this->_generator->query($this->_instance);
    }

    /**
     * Proxy method for getting a poc based on the type
     *
     * @return User The POC
     */
    public function fetchOneByType($pocId, $type)
    {
        return $this->_generator->fetchOneByType($this->_instance, $type, $pocId);
    }

    /**
     * Proxy method for getting pocs based on the type
     *
     * @return User The POC
     */
    public function fetchAllByType($type)
    {
        return $this->_generator->fetchAllByType($this->_instance, $type);
    }

    /**
     * Proxy method for getting positions of a poc
     *
     * @return array The list of positions
     */
    public function fetchAllPositions($pocId)
    {
        return $this->_generator->fetchAllPositions($this->_instance, $pocId);
    }
}
