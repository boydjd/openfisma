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

require_once(realpath(dirname(__FILE__) . '/../../Case/Unit.php'));

/**
 * Test_Application_Models_IrStep
 * 
 * @uses Test_Case_Unit
 * @package Test 
 * @copyright (c) Endeavor Systems, Inc. 2011 {@link http://www.endeavorsystems.com}
 * @author Josh Boyd <joshua.boyd@endeavorsystems.com> 
 * @license http://www.openfisma.org/content/license GPLv3
 */
class Test_Application_Models_IrStep extends Test_Case_Unit
{
    /**
     * Check for the call of _openGap() with correct parameters
     *
     * @return void
     */
    public function testPreInsert()
    {
        $workflowId = 1;
        $cardinality = 1;
        
        $irStep = $this->getMock('IrStep', array('_openGap'));
        $irStep->expects($this->once())->method('_openGap')->with($workflowId, $cardinality);
        
        $irStep->workflowId = $workflowId;
        $irStep->cardinality = $cardinality;
        
        $irStep->preInsert(null);
    }
    
    /**
     * Check for the call of _closeGap() with correct parameters
     *
     * @return void
     */
    public function testPostDelete()
    {
        $workflowId = 1;
        $cardinality = 1;
        
        $irStep = $this->getMock('IrStep', array('_closeGap'));
        $irStep->expects($this->once())->method('_closeGap')->with($workflowId, $cardinality);
        
        $mockInvoker = new BlankMock();
        $mockInvoker->workflowId = $workflowId;
        $mockInvoker->cardinality = $cardinality;
        
        $mockEvent = $this->getMock('BlankMock', array('getInvoker'));
        $mockEvent->expects($this->once())->method('getInvoker')->will($this->returnValue($mockInvoker));

        $irStep->postDelete($mockEvent);
    }
    
    /**
     * Test the query built for _openGap()
     *
     * @return void
     */
    public function testOpenGapQuery()
    {
        $query = IrStep::openGapQuery(1, 1)->getSql();
        $expectedQuery = 'UPDATE ir_step SET cardinality = cardinality + 1 WHERE workflowid = ? AND cardinality >= ?';
        $this->assertEquals($expectedQuery, $query);
    }

    /**
     * Test the execution in _openGap() via preInsert()
     *
     * @return void
     */
    public function testOpenGap()
    {
        $mockQuery = $this->getMock('BlankMock', array('execute'));
        $mockQuery->expects($this->once())->method('execute');
        
        $irStep = new IrStep();
        $irStep->preInsert($null, $mockQuery);
    }
    
    /**
     * Test the query built for _closeGap()
     *
     * @return void
     */
    public function testCloseGapQuery()
    {
        $query = IrStep::closeGapQuery(1, 1)->getSql();
        $expectedQuery = 'UPDATE ir_step SET cardinality = cardinality - 1 WHERE workflowid = ? AND cardinality >= ?';
        $this->assertEquals($expectedQuery, $query);
    }
    
    /**
     * Test the execution in _closeGap() via postDelete()
     *
     * @return void
     */
    public function testCloseGap()
    {
        $mockQuery = $this->getMock('BlankMock', array('execute'));
        $mockQuery->expects($this->once())->method('execute');
        
        $mockInvoker = new BlankMock();
        $mockInvoker->workflowId = $workflowId;
        $mockInvoker->cardinality = $cardinality;
        
        $mockEvent = $this->getMock('BlankMock', array('getInvoker'));
        $mockEvent->expects($this->once())->method('getInvoker')->will($this->returnValue($mockInvoker));

        $irStep = new IrStep();
        $irStep->postDelete($mockEvent, $mockQuery);
    }
    
    /**
     * Test the room shuffling in preUpdate()
     *
     * @return void
     */
    public function testPreUpdate()
    {
        $oldWorkflowId = 1;
        $oldCardinality = 1;
        
        $workflowId = 2;
        $cardinality = 2;
        
        $irStep = $this->getMock('IrStep', array('getModified', '_openGap', '_closeGap'));
        $irStep->expects($this->exactly(2))->method('getModified')->with(true)
               ->will($this->onConsecutiveCalls(
                   array(),
                   array(
                       'workflowId' => $oldWorkflowId,
                       'cardinality' => $oldCardinality
                   )
               ));
        $irStep->expects($this->once())->method('_closeGap')->with($oldWorkflowId, $oldCardinality);
        $irStep->expects($this->once())->method('_openGap')->with($workflowId, $cardinality);
        
        $irStep->workflowId = $workflowId;
        $irStep->cardinality = $cardinality;
        
        $irStep->preUpdate(null);
        $irStep->preUpdate(null);
    }
}
