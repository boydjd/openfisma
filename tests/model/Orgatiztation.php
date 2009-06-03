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
 * @author    Ryan <ryan.yang@reyosoft.com>
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/mw/index.php?title=License
 * @version   $Id$
 * @package   Test_Model
 */


/**
 * Test_FismaUnitTest
 */
require_once(realpath(dirname(__FILE__) . '/../FismaUnitTest.php'));

/**
 * Unit tests for the System model
 *
 * @package Test
 */
class Test_Model_Organization extends Test_FismaUnitTest
{
    private $_Organization = null;

    public function setUp()
    {
        parent::setUp('Organization');
        $organization = new Organization();
    }

    public function testTree()
    {
        $orgRoot = new Organization();
        $orgRoot->name = 'root';
        $orgRoot->nickname = 'root node';
        $orgRoot->description = 'i am root node';
        $orgRoot->orgType = 'agency';

        $orgLeft1 = new Organization();
        $orgLeft1->name = 'left_1';
        $orgLeft1->nickname = 'left node 1';
        $orgLeft1->description = 'i am left node 1';
        $orgLeft1->orgType = 'agency';

        $orgRight1 = new Organization();
        $orgRight1->name = 'right_1';
        $orgRight1->nickname = 'right node 1';
        $orgRight1->description = 'i am right node 1';
        $orgRight1->orgType = 'agency';

        $orgLeft1->getNode()->insertAsLastChildOf($orgRoot);
        $orgRight1->getNode()->insertAsLastChildOf($orgRoot);

        $orgRoot->save();

        $organizationTable = Doctrine::getTable('Organization');

        $this->assertEquals($organizationTable->findOneByName('root')->level, NULL);
        $this->assertEquals($organizationTable->findOneByName('left_1')->level, 1);
        $this->assertEquals($organizationTable->findOneByName('right_1')->level, 1);
    }
}
