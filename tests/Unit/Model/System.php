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
 * @author    Woody lee <woody.li@reyosoft.com>
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/mw/index.php?title=License
 * @version   $Id$
 * @package   Test_System
 */

/**
 * Test_FismaUnitTest
 */
require_once(realpath(dirname(__FILE__) . '/../FismaUnitTest.php'));

/**
 * Unit tests for the System model
 *
 * @package Test_model
 */
class Unit_Model_System extends Test_FismaUnitTest
{
    private $_system = null;

    public function setUp()
    {
        $system = new System();
        $system->mapValue('description');
        $system->mapValue('name');
        $system->mapValue('nickname');
        $this->_system = $system;
    }

    /**
     * Test the method of getting security category level
     * 
     * The security category will be the highest level 
     * among the confidentiality, integrity and availability
     * 
     */
    public function testGetSecurityCategory()
    {
        $system = new System();
        
        $system->confidentiality = System::MODERATE_LEVEL;
        $system->integrity = System::MODERATE_LEVEL;
        $system->availability = System::LOW_LEVEL;
        $this->assertEquals($system->getSecurityCategory(), System::MODERATE_LEVEL);
        
        $system->confidentiality = System::HIGH_LEVEL;
        $system->integrity = System::MODERATE_LEVEL;
        $system->availability = System::LOW_LEVEL;
        $this->assertEquals($system->getSecurityCategory(), System::HIGH_LEVEL);
        
        $system->confidentiality = System::LOW_LEVEL;
        $system->integrity = System::LOW_LEVEL;
        $system->availability = System::LOW_LEVEL;
        $this->assertEquals($system->getSecurityCategory(), System::LOW_LEVEL);
        
        $system->confidentiality = System::NA;
        $system->integrity = System::LOW_LEVEL;
        $system->availability = System::LOW_LEVEL;
        $this->assertEquals($system->getSecurityCategory(), null);
        
    }

    public function testSave()
    {
        $this->_system->type = 'gss';
        $this->_system->confidentiality = 'high';
        $this->_system->integrity       = 'moderate';
        $this->_system->availability    = 'low';
        $this->_system->description     = 'description';
        $this->_system->name            = 'name'; 
        $this->_system->nickname        = 'nickname';
        $this->_system->getTable()->addRecordListener(new Listener_System());
        $this->_system->save();

        $ret = Doctrine::getTable('Organization')->findOneByName('name');
        $this->assertEquals('description', $ret->description);
        $this->assertEquals('name', $ret->name);
        $this->assertEquals('nickname', $ret->nickname);
    }

    public function testUpdate()
    {
        $ret = Doctrine::getTable('Organization')->findOneByName('name');
        if (!empty($ret)) {
            $system = $ret->System;
            $system->name = 'newname';
            $system->nickname = 'newnick';
            $system->description = 'new description';
            $system->integrity  = 'high';
            $system->getTable()->addRecordListener(new Listener_System());
            $system->save();
        }

        $this->assertEquals('newname', $ret->name);
        $this->assertEquals('newnick', $ret->nickname);
        $this->assertEquals('new description', $ret->description);
    }

    public function testDelete()
    {
        $ret = Doctrine::getTable('Organization')->findOneByName('newname');
        if (!empty($ret)) {
            $system = $ret->System;
            $system->getTable()->addRecordListener(new Listener_System());
            $system->delete();
        }

        $this->assertEquals(false, Doctrine::getTable('Organization')->find($ret->id));
    }

}
