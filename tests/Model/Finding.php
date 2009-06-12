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
     */
    protected function setUp()
    {
        parent::setUp();
        // Loads a finding
        $data = realpath($this->_fixturePath . '/Finding.yml');
        Doctrine::loadData($data);
        $this->_finding = Doctrine::getTable('Finding')->find(1);
        // user id 1 is exist when init database
        $this->_user = Doctrine::getTable('User')->find(1);
    }

    /**
     * Check Finding status in the normal evaluation work flow
     *
     * The work flow of finding status is:
     *     NEW[mitigationType]->
     *     DRAFT[submitMitigation]->
     *     MITIGATION ISSO[deny]->
     *     DRAFT[submitMitigation]->
     *     MITIGATION ISSO[approve]->
     *     MITIGATION IVV[deny]->
     *     DRAFT[submitMitigation]->
     *     MITIGATION ISSO[approve]->
     *     MITIGATION IVV[approve]->
     *     EN[reviseMitigation]->
     *     DRAFT[submitMitigation]->
     *     MITIGATION ISSO[approve]->
     *     MITIGATION IVV[approve]->
     *     EN[uploadEvidence]->
     *     EVIDENCE ISSO[deny]->
     *     EN[uploadEvidence]->
     *     EVIDENCE ISSO[approve]->
     *     EVIDENCE IVV[deny]->
     *     EN[uploadEvidence]->
     *     EVIDENCE ISSO[approve]->
     *     EVIDENCE IVV[approve]->
     *     CLOSED 
     * 
     */
    public function testGetStatus()
    {
        $finding = $this->_finding;
        $this->assertEquals('NEW', $finding->getStatus());

        $finding->type = 'CAP';
        $finding->save();
        $this->assertEquals('DRAFT', $finding->getStatus());
        
        $finding->submitMitigation($this->_user); 
        $this->assertEquals('MITIGATION ISSO', $finding->getStatus());
        
        $finding->deny($this->_user, 'comments');
        $this->assertEquals('DRAFT', $finding->getStatus());
        
        $finding->submitMitigation($this->_user); 
        $this->assertEquals('MITIGATION ISSO', $finding->getStatus());
        
        $finding->approve($this->_user);
        $this->assertEquals('MITIGATION IVV', $finding->getStatus());
 
        $finding->deny($this->_user, 'comments');
        $this->assertEquals('DRAFT', $finding->getStatus());
        
        $finding->submitMitigation($this->_user); 
        $this->assertEquals('MITIGATION ISSO', $finding->getStatus());
        
        $finding->approve($this->_user);
        $this->assertEquals('MITIGATION IVV', $finding->getStatus());

        $finding->approve($this->_user);
        $this->assertEquals('EN', $finding->getStatus());
        
        $finding->reviseMitigation($this->_user);
        $this->assertEquals('DRAFT', $finding->getStatus());
        
        $finding->submitMitigation($this->_user); 
        $this->assertEquals('MITIGATION ISSO', $finding->getStatus());
        
        $finding->approve($this->_user);
        $this->assertEquals('MITIGATION IVV', $finding->getStatus());

        $finding->approve($this->_user);
        $this->assertEquals('EN', $finding->getStatus());

        $finding->uploadEvidence('file name', $this->_user);
        $this->assertEquals('EVIDENCE ISSO', $finding->getStatus());
        
        $finding->deny($this->_user, 'comments');
        $this->assertEquals('EN', $finding->getStatus());

        $finding->uploadEvidence('file name', $this->_user);
        $this->assertEquals('EVIDENCE ISSO', $finding->getStatus());
        
        $finding->approve($this->_user);
        $this->assertEquals('EVIDENCE IVV', $finding->getStatus());

        $finding->deny($this->_user, 'comments');
        $this->assertEquals('EN', $finding->getStatus());
        
        $finding->uploadEvidence('file name', $this->_user);
        $this->assertEquals('EVIDENCE ISSO', $finding->getStatus());
        
        $finding->approve($this->_user);
        $this->assertEquals('EVIDENCE IVV', $finding->getStatus());

        $finding->approve($this->_user);
        $this->assertEquals('CLOSED', $finding->getStatus());
        
    }

    /**
     * Test approve function in finding work flow
     *
     * Check the exception message when finding status is not ready for approving
     *
     */
    public function testApprove()
    {
        $finding = $this->_finding;
        try {
            //Finding status is NEW
            $finding->approve($this->_user);
            $this->fail('An expected exception has not been raised.');
        } catch (Fisma_Exception_General $e) {
            $this->assertEquals("The finding can't be approved",
                $e->getMessage());
        }

        $finding->type = 'CAP';
        $finding->save();
        try {
            //Finding status is DRAFT
            $finding->approve($this->_user);
            $this->fail('An expected exception has not been raised.');
        } catch (Fisma_Exception_General $e) {
            $this->assertEquals("The finding can't be approved",
                $e->getMessage());
        }

        $finding->submitMitigation($this->_user);
        $finding->approve($this->_user);
        $this->assertEquals('APPROVED', $finding->FindingEvaluations->getLast()->decision);
        $finding->approve($this->_user);
        $this->assertEquals('APPROVED', $finding->FindingEvaluations->getLast()->decision);
        try {
            //Finding status is EN
            $finding->approve($this->_user);
            $this->fail('An expected exception has not been raised.');
        } catch (Fisma_Exception_General $e) {
            $this->assertEquals("The finding can't be approved",
                $e->getMessage());
        }

        $finding->UploadEvidence('file name', $this->_user);
        $finding->approve($this->_user);
        $this->assertEquals('APPROVED', $finding->FindingEvaluations->getLast()->decision);
        $finding->approve($this->_user);
        $this->assertEquals('APPROVED', $finding->FindingEvaluations->getLast()->decision);
        try {
            //Finding status is CLOSED
            $finding->approve($this->_user);
            $this->fail('An expected exception has not been raised.');
        } catch (Fisma_Exception_General $e) {
            $this->assertEquals("The finding can't be approved",
                $e->getMessage());
        }

    }

    /**
     * Test deny function in finding work flow
     *
     * Check the exception message when finding status is not ready for denying
     *
     */
    public function testDeny()
    {
        $comment = 'comment test';
        $finding = $this->_finding;
        try {
            //Finding status is NEW
            $finding->deny($this->_user, $comment);
            $this->fail('An expected exception has not been raised.');
        } catch (Fisma_Exception_General $e) {
            $this->assertEquals("The finding can't be denied",
                $e->getMessage());
        }

        $finding->type = 'CAP';
        $finding->save();
        try {
            //Finding status is DRAFT
            $finding->deny($this->_user, $comment);
            $this->fail('An expected exception has not been raised.');
        } catch (Fisma_Exception_General $e) {
            $this->assertEquals("The finding can't be denied",
                $e->getMessage());
        }

        $finding->submitMitigation($this->_user);
        $finding->deny($this->_user, $comment);
        $this->assertEquals('DENIED', $finding->FindingEvaluations->getLast()->decision);
        $this->assertEquals($comment, $finding->FindingEvaluations->getLast()->comment);
        $finding->submitMitigation($this->_user);
        $finding->approve($this->_user);
        $finding->deny($this->_user, $comment);
        $this->assertEquals('DENIED', $finding->FindingEvaluations->getLast()->decision);
        $this->assertEquals($comment, $finding->FindingEvaluations->getLast()->comment);
        $finding->submitMitigation($this->_user);
        $finding->approve($this->_user);
        $finding->approve($this->_user);

        try {
            //Finding status is EN
            $finding->deny($this->_user, $comment);
            $this->fail('An expected exception has not been raised.');
        } catch (Fisma_Exception_General $e) {
            $this->assertEquals("The finding can't be denied",
                $e->getMessage());
        }

        $finding->UploadEvidence('file name', $this->_user);
        $finding->deny($this->_user, $comment);
        $this->assertEquals('DENIED', $finding->FindingEvaluations->getLast()->decision);
        $this->assertEquals($comment, $finding->FindingEvaluations->getLast()->comment);
        $finding->UploadEvidence('file name', $this->_user);
        $finding->approve($this->_user);
        $finding->deny($this->_user, $comment);
        $this->assertEquals('DENIED', $finding->FindingEvaluations->getLast()->decision);
        $this->assertEquals($comment, $finding->FindingEvaluations->getLast()->comment);
        $finding->UploadEvidence('file name', $this->_user);
        $finding->approve($this->_user);
        $finding->approve($this->_user);

        try {
            //Finding status is CLOSED
            $finding->deny($this->_user, $comment);
            $this->fail('An expected exception has not been raised.');
        } catch (Fisma_Exception_General $e) {
            $this->assertEquals("The finding can't be denied",
                $e->getMessage());
        }

    }

    /**
     * Check the finding function submitMitigation in finding work flow
     *
     * Check the exception message when finding status is not ready for
     * mitigation submitting
     */
    public function testSubmitMitigation()
    {
        $finding = $this->_finding;
        try {
            //Finding status is NEW
            $finding->submitMitigation($this->_user);
            $this->fail('An expected exception has not been raised.');
        } catch (Fisma_Exception_General $e) {
            $this->assertEquals("The finding can't be submited mitigation strategy",
                $e->getMessage());
        }
        $finding->type = 'CAP';
        $finding->save();
        $finding->submitMitigation($this->_user);
        try {
            //Finding status is MITIGATION ISSO
            $finding->submitMitigation($this->_user);
            $this->fail('An expected exception has not been raised.');
        } catch (Fisma_Exception_General $e) {
            $this->assertEquals("The finding can't be submited mitigation strategy",
                $e->getMessage());
        }
        $finding->approve($this->_user);
        try {
            //Finding status is MITIGATION IVV
            $finding->submitMitigation($this->_user);
            $this->fail('An expected exception has not been raised.');
        } catch (Fisma_Exception_General $e) {
            $this->assertEquals("The finding can't be submited mitigation strategy",
                $e->getMessage());
        }
        $finding->approve($this->_user);
        try {
            //Finding status is EN
            $finding->submitMitigation($this->_user);
            $this->fail('An expected exception has not been raised.');
        } catch (Fisma_Exception_General $e) {
            $this->assertEquals("The finding can't be submited mitigation strategy",
                $e->getMessage());
        }
        $finding->UploadEvidence('file name', $this->_user);
        try {
            //Finding status is EVIDENCE ISSO
            $finding->submitMitigation($this->_user);
            $this->fail('An expected exception has not been raised.');
        } catch (Fisma_Exception_General $e) {
            $this->assertEquals("The finding can't be submited mitigation strategy",
                $e->getMessage());
        }
        $finding->approve($this->_user);
        try {
            //Finding status is EVIDENCE IVV
            $finding->submitMitigation($this->_user);
            $this->fail('An expected exception has not been raised.');
        } catch (Fisma_Exception_General $e) {
            $this->assertEquals("The finding can't be submited mitigation strategy",
                $e->getMessage());
        }
        $finding->approve($this->_user);
        try {
            //Finding status is CLOSED
            $finding->submitMitigation($this->_user);
            $this->fail('An expected exception has not been raised.');
        } catch (Fisma_Exception_General $e) {
            $this->assertEquals("The finding can't be submited mitigation strategy",
                $e->getMessage());
        }
    }

    /**
     * Check the finding function reviseMitigation in finding work flow
     *
     * Check the exception message when finding status is not ready for revising
     */
    public function testReviseMitigation()
    {
        $finding = $this->_finding;
        try {
            //Finding status is NEW
            $finding->reviseMitigation($this->_user);
            $this->fail('An expected exception has not been raised.');
        } catch (Fisma_Exception_General $e) {
            $this->assertEquals("The finding can't be revised mitigation strategy",
                $e->getMessage());
        }
        $finding->type = 'CAP';
        $finding->save();
        try {
            //Finding status is DRAFT
            $finding->reviseMitigation($this->_user);
            $this->fail('An expected exception has not been raised.');
        } catch (Fisma_Exception_General $e) {
            $this->assertEquals("The finding can't be revised mitigation strategy",
                $e->getMessage());
        }
        $finding->submitMitigation($this->_user);
        try {
            //Finding status is MITIGATION ISSO
            $finding->reviseMitigation($this->_user);
            $this->fail('An expected exception has not been raised.');
        } catch (Fisma_Exception_General $e) {
            $this->assertEquals("The finding can't be revised mitigation strategy",
                $e->getMessage());
        }
        $finding->approve($this->_user);
        try {
            //Finding status is MITIGATION IVV
            $finding->reviseMitigation($this->_user);
            $this->fail('An expected exception has not been raised.');
        } catch (Fisma_Exception_General $e) {
            $this->assertEquals("The finding can't be revised mitigation strategy",
                $e->getMessage());
        }
        $finding->approve($this->_user);
        $finding->reviseMitigation($this->_user);
        $finding->submitMitigation($this->_user);
        $finding->approve($this->_user);
        $finding->approve($this->_user);
        $finding->UploadEvidence('file name', $this->_user);
        try {
            //Finding status is EVIDENCE ISSO
            $finding->reviseMitigation($this->_user);
            $this->fail('An expected exception has not been raised.');
        } catch (Fisma_Exception_General $e) {
            $this->assertEquals("The finding can't be revised mitigation strategy",
                $e->getMessage());
        }
        $finding->approve($this->_user);
        try {
            //Finding status is EVIDENCE IVV
            $finding->reviseMitigation($this->_user);
            $this->fail('An expected exception has not been raised.');
        } catch (Fisma_Exception_General $e) {
            $this->assertEquals("The finding can't be revised mitigation strategy",
                $e->getMessage());
        }
        $finding->approve($this->_user);
        try {
            //Finding status is CLOSED
            $finding->reviseMitigation($this->_user);
            $this->fail('An expected exception has not been raised.');
        } catch (Fisma_Exception_General $e) {
            $this->assertEquals("The finding can't be revised mitigation strategy",
                $e->getMessage());
        }
    }

    /**
     * Check the finding function uploadEvidence in finding work flow
     *
     * Check the exception message when finding status is not ready for
     * uploading
     */
    public function testUploadEvidence()
    {
        $file = 'file name';
        $finding = $this->_finding;
        try {
            //Finding status is NEW
            $finding->uploadEvidence($file, $this->_user);
            $this->fail('An expected exception has not been raised.');
        } catch (Fisma_Exception_General $e) {
            $this->assertEquals("The finding can't be uploaded evidence",
                $e->getMessage());
        }
        $finding->type = 'CAP';
        $finding->save();
        try {
            //Finding status is DRAFT
            $finding->uploadEvidence($file, $this->_user);
            $this->fail('An expected exception has not been raised.');
        } catch (Fisma_Exception_General $e) {
            $this->assertEquals("The finding can't be uploaded evidence",
                $e->getMessage());
        }
        $finding->submitMitigation($this->_user);
        try {
            //Finding status is MITIGATION ISSO
            $finding->uploadEvidence($file, $this->_user);
            $this->fail('An expected exception has not been raised.');
        } catch (Fisma_Exception_General $e) {
            $this->assertEquals("The finding can't be uploaded evidence",
                $e->getMessage());
        }
        $finding->approve($this->_user);
        try {
            //Finding status is MITIGATION IVV
            $finding->uploadEvidence($file, $this->_user);
            $this->fail('An expected exception has not been raised.');
        } catch (Fisma_Exception_General $e) {
            $this->assertEquals("The finding can't be uploaded evidence",
                $e->getMessage());
        }
        $finding->approve($this->_user);
        $finding->uploadEvidence($file, $this->_user);
        $this->assertEquals($file, $finding->Evidence->getLast()->filename);
        try {
            //Finding status is EVIDENCE ISSO
            $finding->uploadEvidence($file, $this->_user);
            $this->fail('An expected exception has not been raised.');
        } catch (Fisma_Exception_General $e) {
            $this->assertEquals("The finding can't be uploaded evidence",
                $e->getMessage());
        }
        $finding->approve($this->_user);
        try {
            //Finding status is EVIDENCE IVV
            $finding->uploadEvidence($file, $this->_user);
            $this->fail('An expected exception has not been raised.');
        } catch (Fisma_Exception_General $e) {
            $this->assertEquals("The finding can't be uploaded evidence",
                $e->getMessage());
        }
        $finding->approve($this->_user);
        try {
            //Finding status is CLOSED
            $finding->uploadEvidence($file, $this->_user);
            $this->fail('An expected exception has not been raised.');
        } catch (Fisma_Exception_General $e) {
            $this->assertEquals("The finding can't be uploaded evidence",
                $e->getMessage());
        }
    }

    /**
     * Check all finding status list
     *
     */
    public function testGetAllStatuses()
    {
        $this->assertEquals(
            array('NEW', 'DRAFT', 'MITIGATION ISSO', 'MITIGATION IVV',
                'EN', 'EVIDENCE ISSO', 'EVIDENCE IVV', 'CLOSED'),
                $this->_finding->getAllStatuses());
    }

    /**
     *  Clear the test data which generated by the test
     *
     */
    public function tearDown()
    {
        $this->_finding->FindingEvaluations->delete();
        $this->_finding->Evidence->delete();
        $this->_finding->AuditLogs->delete();
        $this->_finding->delete();
    }
}
