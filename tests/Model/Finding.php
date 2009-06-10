<?php
/**
 * Copyright (c) 2008 Endeavor Systems, Inc.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 * @author    Chris.chen <chris.chen@reyosoft.com>
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/mw/index.php?title=License
 * @version   $Id$
 * @package   
 */

require_once(realpath(dirname(__FILE__) . '/../FismaUnitTest.php'));

/**
 * Unit Model tests for Finding model
 *
 * The test must be run under the init database
 * Table finding must be empty and finding status depend on table evaluation
 * 
 */
class Test_Model_Finding extends Test_FismaUnitTest
{
    private $_finding = null;
    private $_user    = null;

    /**
     * Load fixture data
     * 
     * User record id 1 must be exist in User table
     *
     */
    protected function setUp()
    {
        parent::setUp();
        $data = realpath($this->_fixturePath . '/Finding.yml');
        Doctrine::loadData($data);
        $this->_finding = new Finding();
        $this->_user = Doctrine::getTable('User')->find(1);
    }

    /**
     * Test Finding status in the normal evaluation work flow
     *
     * The work flow of finding status is:
     *      NEW->DRAFT->MSA(MITIGATION ISSO)->MSA(MITIGATION IVV)->
     *          EN->EA(EVIDENCE ISSO)->EA(EVIDENCE IVV)->CLOSED
     * 
     * Test fixture finding data id 1
     */
    public function testGetStatus()
    {
        $finding = $this->_finding->getTable()->find(1);
        $this->assertEquals('NEW', $finding->getStatus());

        $finding->status = 'DRAFT';
        $finding->save();
        $this->assertEquals('DRAFT', $finding->getStatus());
        
        $finding->status = 'MSA';
        $finding->CurrentEvaluation = Doctrine::getTable('Evaluation')->find(1);
        $finding->save();
        $this->assertEquals('MITIGATION ISSO', $finding->getStatus());
        
        $fe_1 = new FindingEvaluation();
        $fe_1->findingId = $finding->id;
        $fe_1->evaluationId = $finding->currentEvaluationId;
        $fe_1->save();
        $finding->CurrentEvaluation = Doctrine::getTable('Evaluation')->find(2);
        $finding->save();
        $this->assertEquals('MITIGATION IVV', $finding->getStatus());
 
        $finding->status = 'EN';
        $finding->CurrentEvaluation = NULL;
        $finding->save();
        $this->assertEquals('EN', $finding->getStatus());

        $finding->status = 'EA';
        $fe_2 = new FindingEvaluation();
        $fe_2->findingId = $finding->id;
        $fe_2->evaluationId = 2;
        $fe_2->save();
        $finding->CurrentEvaluation = Doctrine::getTable('Evaluation')->find(3);
        $finding->save();
        $this->assertEquals('EVIDENCE ISSO', $finding->getStatus());
        
        $fe_3 = new FindingEvaluation();
        $fe_3->findingId = $finding->id;
        $fe_3->evaluationId = 3;
        $fe_3->save();
        $finding->CurrentEvaluation = Doctrine::getTable('Evaluation')->find(4);
        $finding->save();
        $this->assertEquals('EVIDENCE IVV', $finding->getStatus());

        $finding->status = 'CLOSED';
        $finding->CurrentEvaluation = NULL;
        $this->assertEquals('CLOSED', $finding->getStatus());
    }
    
    /**
     * Test approved function
     *
     * The function will insert a record on table finding_evaluation,
     * Assert the value of field decision is APPROVED  
     *
     * Test fixture finding data id 2
     */

    public function testApprove()
    {
        $finding = $this->_finding->getTable()->find(2);
        $finding->approve($this->_user);
        $this->assertEquals('APPROVED', $finding->FindingEvaluations->getLast()->decision);
    }

    /**
     * Test approved function
     *
     * The function will insert a record on table finding_evaluation,
     * Assert the value of field decision is DENIED  
     *
     * Test fixture finding data id 2
     */
    public function testDeny()
    {
        $finding = $this->_finding->getTable()->find(2);
        $comment = 'comment test';
        $finding->deny($this->_user, $comment);
        $this->assertEquals('DENIED', $finding->FindingEvaluations->getLast()->decision);
        $this->assertEquals($comment, $finding->FindingEvaluations->getLast()->comment);
    }

    /**
     *  Clear the test data which generated by the test
     *
     */
    public function tearDown()
    {
        $fe = new FindingEvaluation();
        $fe->getTable()->findByFindingId('1')->delete();
        $fe->getTable()->findByFindingId('2')->delete();
        $finding = Doctrine::getTable('finding')->find(1)->delete();
        $finding = Doctrine::getTable('finding')->find(2)->delete();
    }
}
