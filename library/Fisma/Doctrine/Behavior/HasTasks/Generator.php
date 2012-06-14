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
 * Generator for the task behavior
 *
 * @author     Ben Zheng
 * @copyright  (c) Endeavor Systems, Inc. 2012 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Fisma
 * @subpackage Fisma_Doctrine_Behavior_HasTasks
 */
class Fisma_Doctrine_Behavior_HasTasks_Generator extends Doctrine_Record_Generator
{
    /**
     * Set up the generated class name
     *
     * @return void
     */
    public function initOptions()
    {
        // This will result in class names like 'FindingTask' and 'IncidentTask', etc.
        $this->setOption('className', '%CLASS%Task');
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

        // Task timestamp
        $this->hasColumn(
            'createdTs',
            'timestamp',
            null,
            array('comment' => 'The timestamp when this entry was created')
        );

        $this->hasColumn(
            'description',
            'string',
            255,
            array(
                'type' => 'string',
                'extra' => array(
                    'purify' => 'html',
                    'auditLog' => true,
                    'logicalName' => 'Description',
                ),
                'comment' => 'The description for this task',
            )
        );

        $this->hasColumn(
            'status',
            'enum',
            null,
            array(
                'type' => 'enum',
                'values' => array(
                     0 => 'OPEN',
                     1 => 'PENDING',
                     2 => 'CLOSED',
                ),
                'default' => 'OPEN',
                'extra' => array(
                    'auditLog' => true,
                    'logicalName' => 'Status',
                ),
                'comment' => 'The status for this task'
            )
        );

        $this->hasColumn(
            'ecd',
            'timestamp',
            null,
            array(
                'type' => 'timestamp',
                'notblank' => true,
                'notnull' => true,
                'extra' => array(
                    'auditLog' => true,
                    'logicalName' => 'Expected Completion Date',
                ),
                'comment' => 'The expected completion date',
            )
        );

        $this->hasColumn(
            'expectedCost',
            'float',
            null,
            array(
                'type' => 'float',
                'default' => 0,
                'extra' => array(
                    'auditLog' => true,
                    'logicalName' => 'Expected Cost',
                ),
                'comment' => 'The expected cost for this task',
            )
        );

        $this->hasColumn(
            'pocId',
            'integer',
            null,
            array(
                'extra' => array(
                    'auditLog' => true,
                    'logicalName' => 'Point Of Contact',
                ),
                'comment' => 'Foreign key to the point of contact for this task'
            )
        );

        // Foreign key to the object which this task relates to
        $this->hasColumn(
            'objectId',
            'integer',
            null,
            array('comment' => 'The parent object to which this task belongs')
        );

        // Foreign key to the user who created this task entry
        $this->hasColumn(
            'userId',
            'integer',
            null,
            array('comment' => 'The user who created task')
        );
    }

    /**
     * Set up parent object and user relations
     *
     * @return void
     */
    public function setUp()
    {
        // The base class is the class which is using this behavior, such as 'Finding' or 'Incident'
        $baseClass = $this->getOption('table')->getComponentName();

        // Relation for the base class
        $this->hasOne(
            $baseClass,
            array(
                'local' => 'objectId',
                'foreign' => 'id'
            )
        );

        // Relation for the poc class
        $this->hasOne(
            'User',
            array(
                'local' => 'pocId',
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

        $auditloggableBehavior = new Fisma_Doctrine_Behavior_AuditLoggable(
            array(
                'logCreateObject' => true,
                'logUpdateField'  => true,
                'logDeleteObject' => true,
            )
        );

        $commentableBehavior = new Fisma_Doctrine_Behavior_Commentable();

        $softdeleteBehavior = new Doctrine_Template_SoftDelete();

        $this->actAs($auditloggableBehavior);
        $this->actAs($commentableBehavior);
        $this->actAs($softdeleteBehavior);
    }

    /**
     * Add a task
     *
     * @param Doctrine_Record $instance The instance to be logged
     * @param array $task The task to be written
     *     The $task is an array of 5 keys:
     *         'description'  - string (required) The description for this task.
     *         'ecd'          - datetime (required) The expected completion date.
     *         'status'       - string (required) The task status.
     *         'pocId'        - int (optional) The id of point of contact.
     *         'expectedCost' - float (optional) The expected cost for this task.
     *
     * @return Doctrine_Record Return the added task
     */
    public function addTask(Doctrine_Record $instance, $task)
    {
        // Create a new task
        $taskClass = $this->_options['className'];

        $taskEntry = new $taskClass;
        $taskEntry->createdTs = Fisma::now();
        $taskEntry->description = isset($task['description']) ? $task['description'] : null;
        $taskEntry->pocId = isset($task['pocId']) ? $task['pocId'] : null;
        $taskEntry->ecd = isset($task['ecd']) ? $task['ecd'] : null;

        if (isset($task['expectedCost'])) {
            $taskEntry->expectedCost = $task['expectedCost'];
        }

        $status = $this->getTable()->getEnumValues('status');
        if (isset($task['status']) && in_array(strtoupper($task['status']), $status)) {
            $taskEntry->status = strtoupper($task['status']);
        }

        $taskEntry->objectId = $instance->id;
        $taskEntry->userId = CurrentUser::getInstance()->id;

        $taskEntry->save();

        return $taskEntry;
    }

    /**
     * List tasks for this object, optionally providing a SQL-style limit and offset to get a limited subset of all
     * the tasks
     *
     * @param mixed $instance The object to get tasks for
     * @param int $hydrationMode A valid Doctrine hydration mode, e.g. Doctrine::HYDRATE_ARRAY
     * @param int $limit SQL style limit
     * @param int $offset SQL style offset
     * @return mixed A query result whose type depends on which hydration mode you choose.
     */
    public function fetch($instance, $hydrationMode, $limit = null, $offset = null)
    {
        $query = $this->query($instance);
        $query->setHydrationMode($hydrationMode)
              ->select('o.createdTs, o.description, p.username, o.ecd, o.expectedCost, o.status, u.username');

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
     * Count the number of tasks associated with this object
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
     * Get a base query which will return all tasks for the current object
     *
     * @param mixed $instance The object to get tasks for
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
