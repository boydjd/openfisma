<?php
/**
 * Copyright (c) 2013 Endeavor Systems, Inc.
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
 * WorkflowTable
 *
 * @uses Fisma_Doctrine_Table
 * @package Model
 * @copyright (c) Endeavor Systems, Inc. 2013 {@link http://www.endeavorsystems.com}
 * @author Josh Boyd <joshua.boyd@endeavorsystems.com>
 * @license http://www.openfisma.org/content/license GPLv3
 */
class WorkflowTable extends Fisma_Doctrine_Table
{
    /**
     * List all workflows (optionally with a specific module)
     *
     * @param string $module Optional. The module to filter by.
     * @return Doctrine_Collection
     */
    public function listArray($module = null)
    {
        $query = Doctrine_Query::create()->from('Workflow w');
        if ($module) {
            $query->where('w.module = ?', $module);
        }

        return $query->execute();
    }

    /**
     * Get the default workflow for a module
     *
     * @param string $module The module to filter by.
     * @return mixed Workflow object or null of none found.
     */
    public function findDefaultByModule($module)
    {
        if ($module) {
            $query = Doctrine_Query::create()
                ->from('Workflow w')
                ->where('w.module = ?', $module)
                ->andWhere('w.isDefault = ?', true);
            return $query->fetchOne();
        }
        return null;
    }
}
