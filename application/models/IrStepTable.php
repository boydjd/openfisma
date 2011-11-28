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
 * IrStepTable 
 * 
 * @uses Fisma_Doctrine_Table
 * @package Model 
 * @copyright (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @author Josh Boyd <joshua.boyd@endeavorsystems.com> 
 * @license http://www.openfisma.org/content/license GPLv3
 */
class IrStepTable extends Fisma_Doctrine_Table
{
    /**
     * Build the query for _openGap()
     * 
     * @param int $workflowId ID of workflow within which to open the gap
     * @param int $position   Position in workflow where the gap should be created
     * @return Doctrine_Query
     */
    public function openGapQuery($workflowId, $position)
    {
        $openGapQuery = Doctrine_Query::create()->update('IrStep irstep')
                                                ->set('irstep.cardinality', 'irstep.cardinality + 1')
                                                ->where('irstep.workflowId = ?', $workflowId)
                                                ->andWhere('irstep.cardinality >= ?', $position);
        return $openGapQuery;
    }
    
    /**
     * Build the query for _closeGap()
     * 
     * @param int $workflowId ID of workflow in which to perform the operation
     * @param int $position   Position of the gap to be closed.
     * @return Doctrine_Query
     */
    public static function closeGapQuery($workflowId, $position)
    {
        $closeGapQuery = Doctrine_Query::create()->update('IrStep irstep')
                                                 ->set('irstep.cardinality', 'irstep.cardinality - 1')
                                                 ->where('irstep.workflowId = ?', $workflowId)
                                                 ->andWhere('irstep.cardinality >= ?', $position);
        return $closeGapQuery;
    }
}
