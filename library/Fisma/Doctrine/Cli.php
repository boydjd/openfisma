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
 * Contain Cli functions
 * 
 * @uses Doctrine_Cli
 * @package Fisma
 * @subpackage Fisma_Doctrine_Cli
 * @copyright (c) Endeavor Systems, Inc. 2011 {@link http://www.endeavorsystems.com}
 * @author Mark Ma <mark.ma@reyosoft.com> 
 * @license http://www.openfisma.org/content/license GPLv3
 */
class Fisma_Doctrine_Cli extends Doctrine_Cli
{
   /**
     * Get the name of the task class based on the first argument
     * which is always the task name. Do some inflection to determine the class name
     *
     * @param  array $args       Array of arguments from the cli
     * @return string $taskClass Task class name
     */
    protected function _getTaskClassFromArgs($args)
    {
        $taskName = str_replace('-', '_', $args[1]);
        $taskNames = array('build_all_reload', 'rebuild_db', 'drop_db');
        if (in_array($taskName, $taskNames)) {
            $taskClass = 'Fisma_Doctrine_Task_' . Doctrine_Inflector::classify($taskName);
        } else {
            $taskClass = 'Doctrine_Task_' . Doctrine_Inflector::classify($taskName);
        }

        return $taskClass;
    }
    
    /**
     * Get array of all the Doctrine_Task child classes that are loaded
     *
     * @return array $tasks
     */
    public function getLoadedTasks()
    {
        $parent = new ReflectionClass('Doctrine_Task');
        
        $classes = get_declared_classes();
        
        $tasks = array();
        
        foreach ($classes as $className) {
            $class = new ReflectionClass($className);
        
            if ($class->isSubClassOf($parent)) {
                $task = str_replace('Doctrine_Task_', '', $className);
                $tasks[$task] = $task;
            }
        }

        // Make sure migrate action does not exist in the tasks
        if (isset($tasks['Migrate'])) {
            unset($tasks['Migrate']);
        }

        if (isset($this->_tasks['Migrate'])) {
            unset($this->_tasks['Migrate']);
        }

        return array_merge($this->_tasks, $tasks);
    }
}
