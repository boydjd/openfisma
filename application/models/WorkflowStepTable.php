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
class WorkflowStepTable extends Fisma_Doctrine_Table
{
    public function listArray($workflow = null)
    {
        $query = Doctrine_Query::create()->from('WorkflowStep ws')->orderBy('ws.cardinality');
        if ($workflow) {
            $query->where('ws.workflowId = ?', (($workflow instanceof Workflow) ? $workflow->id : $workflow));
        }

        return $query->execute();
    }
}
