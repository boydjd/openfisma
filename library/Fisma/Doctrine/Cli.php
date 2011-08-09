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
        if ('migrate' == $taskName) {
            $taskClass = 'Fisma_Doctrine_Task_' . Doctrine_Inflector::classify($taskName);
        } else {
            $taskClass = 'Doctrine_Task_' . Doctrine_Inflector::classify($taskName);
        }

        return $taskClass;
    }
}
