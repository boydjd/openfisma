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
 * @author    Mark E. Haase <mhaase@endeavorsystems.com>
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/mw/index.php?title=License
 * @version   $Id$
 * @package   Test
 */

/**
 * @ignore
 * Run the application bootstrap in command line mode
 */
require_once('../application/init.php');
$plSetting = new Fisma_Controller_Plugin_Setting(RootPath::getRootPath());

if (!$plSetting->installed()) {
    die('Please install!');
}

// Load the base class
require_once 'PHPUnit/Framework/TestCase.php';

/**
 * This is the base class for all unit tests in OpenFISMA. Currently this class is just a stub. The real reason for
 * including it is to execute the application bootstrap logic contained in this same file.
 *
 * @package Test
 */
abstract class Test_FismaUnitTest extends PHPUnit_Framework_TestCase
{
    /**
     * Set up access to the database.
     *
     * @todo why isn't this done in the bootstrap?
     */
    protected function setUp()
    {
        // Initialize our DB connection
        $this->_db = Zend_Db::factory(Zend_Registry::get('datasource'));
        Zend_Db_Table::setDefaultAdapter($this->_db);
    }
}
