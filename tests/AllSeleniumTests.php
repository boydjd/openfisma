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
 * This class is the controller which executes all of the Selenium Tests.
 *
 * @package Test
 */
class AllSeleniumTests
{
    /**
     * main() - Test controller main method
     */
    public static function main()
    {
        PHPUnit_TextUI_TestRunner::run(self::suite());
    }

    /**
     * suite() - Creates a phpunit test suite for all Selenium tests in the project.
     * The controller recurses through all subdirectories and loads all of the .php
     * files found.
     *
     * Notice that each test file should be named following the ZF standards in
     * order for this to work.
     *
     * @return PHPUnit_Framework_TestSuite
     */
    public static function suite()
    {
        $suite = new PHPUnit_Framework_TestSuite('phpUnderControl - AllTests');
        
        // Load in all files which are in subdirectories of the Selenium
        // directory
        chdir(TEST);
        self::loadAllTests('.', 'Selenium', $suite);

        return $suite;
    }

    /**
     * loadAllTests() - Load all of the PHP files in the specified directory,
     * and add them to the test suite.
     *
     * @param string $path The parent path containing the directory
     * @param string $directory The name of the directory
     * @param PHPUnit2_Framework_TestSuite $suite Which suite to add these to
     */
    public static function loadAllTests($path, $directory, $suite)
    {
        $directoryHandle = opendir($path . '/' . $directory);
        
        // Loop through all files and subdirectories:
        while (false !== ($file = readdir($directoryHandle))) {
            // Ignore files/directories with a '.' prefix:
            if (preg_match('/^\./', $file) == 0) {
                $fullPath = "$path/$directory/$file";
                // If the directory contains a subdirectory, then recurse into
                // that subdirectory.
                if (is_dir($fullPath)) {
                    self::loadAllTests($path . '/' . $directory, $file, $suite);
                } else {
                    // Figure out the className by using the full path
                    // information: Remove the .php extension and replace the
                    // '.' path with 'Test'
                    $className = str_replace('.php', '', $fullPath);
                    $className = str_replace('.', 'Test', $className);

                    // Explode the path pieces then
                    // implode with '_' in order to form the class name.
                    // Example: ./Admin/ContactInfo.php becomes
                    // 'Test_Admin_ContactInfo'
                    $className = implode('_', explode('/', $className));
                                                   
                    // Now include the file, and check to see if the expected
                    // class name exists. If so, then add that class to the test
                    // suite.
                    require_once($fullPath);
                    if (class_exists($className)) {
                        $suite->addTestSuite($className);
                    } else {
                        throw new Exception("The file $fullPath does not" .
                                            " contain a class called" .
                                            " $className");
                    }
                }
            }
        }
    }
}
