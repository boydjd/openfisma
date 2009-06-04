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
 * @package   Test
 */

/**
 * Run the application bootstrap in command line mode
 */
require_once('../application/init.php');
$plSetting = new Fisma_Controller_Plugin_Setting();
$plSetting->parse();
if (!$plSetting->installed()) {
    die('Please install!');
}

/**
 * Get db contection for Doctrine
 *
 * Which will be used for test data inserting
 *
 */
$datasource = Zend_Registry::get('datasource');
$dsn = $datasource->params->toArray();
Doctrine_Manager::connection('mysql://' . $dsn['username'] . ':' .
    $dsn['password'] . '@' . $dsn['host'] . '/' . $dsn['dbname']);

/**
 * This class is the controller which executes all of the Unit Test suites. This
 * class is invoked by PhpUnderControl as a part of the continuous integration
 * process.
 *
 * @package Test
 */
class AllUnits
{
    public static function suite()
    {
        $suite = new PHPUnit_Framework_TestSuite('PHPUnit Framework');
        $suite->addTestSuite('Unit_Model');
        return $suite;
    }
}
