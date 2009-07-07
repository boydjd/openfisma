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

// Bootstrap the application's CLI mode if it has not already been done
require_once(realpath(dirname(__FILE__) . '/../library/Fisma.php'));
if (Fisma::RUN_MODE_COMMAND_LINE != Fisma::mode()) {
    try {
        Fisma::initialize(Fisma::RUN_MODE_COMMAND_LINE);
        Fisma::connectDb();
        Fisma::setNotificationEnabled(false);
    } catch (Zend_Config_Exception $zce) {
        print "The application is not installed correctly. If you have not run the installer, you should do that now.";
    } catch (Exception $e) {
        print get_class($e) 
            . "\n" 
            . $e->getMessage() 
            . "\n"
            . $e->getTraceAsString()
            . "\n";
    }
}
 
/**
 * This is the base class for all unit tests in OpenFISMA. Currently this class is just a stub. The real reason for
 * including it is to execute the application bootstrap logic contained in this same file.
 *
 * @package Test
 */
abstract class Test_FismaUnitTest extends PHPUnit_Framework_TestCase
{

}
