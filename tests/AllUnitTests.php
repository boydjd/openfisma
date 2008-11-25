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
 * @author    Woody.Li <chris.chen@reysoft.com>
 * @copyright (c) 
 * @license   http://www.
 * @version   $Id$
 */

/** Run the test suit in command line mode
 *  Install PHPUnit3 at first. more
 *  info: http://www.phpunit.de/manual/3.3/en/index.html
 *  Usage: phpunit Alltest.php
 */ 
require_once dirname(dirname(__FILE__)) .  '/library/local/Config/Fisma.php';
$fisma = Config_Fisma::getInstance();

/**
 * This is testsuit
 *
 * @package Test
 */
class Framework_AllTests
{
    public static function suite()
    {
        $suite = new PHPUnit_Framework_TestSuite('PHPUnit Framework');
        $suite->addTestSuite('Unit_LoginTest');
        return $suite;
    }
}
?>
