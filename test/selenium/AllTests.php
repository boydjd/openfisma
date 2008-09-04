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
 * @version   $Id:$
 */

require_once '../paths.php';
set_include_path(get_include_path() .
                 PATH_SEPARATOR . VENDORS .
                 PATH_SEPARATOR . TEST);
require_once 'PHPUnit/Framework/TestSuite.php';
require_once 'PHPUnit/TextUI/TestRunner.php';

/**
 * This class is the controller which executes all of the Unit Test suites. This
 * class is invoked by PhpUnderControl as a part of the continuous integration
 * process.
 *
 * @package Test
 * @version $Id:$
 */
class phpucAllTests
{
    /**
     * main() - Test controller main method
     */
    public static function main() {
        PHPUnit_TextUI_TestRunner::run( self::suite() );
    }

    /**
     * suite() - Creates a phpunit test suite for all unit tests in the project.
     * The controller recurses through all directories and loads all of the .php
     * files found.
     *
     * Notice that each test file should be named following the ZF standards in
     * order for this to work.
     *
     * @return PHPUnit_Framework_TestSuite
     */
    public static function suite() {
        $suite = new PHPUnit_Framework_TestSuite('phpUnderControl - AllTests');
        
        // Load in all files which are in subdirectories of the current
        // directory
        $directory = opendir('.');
        while (false !== ($subdirectory = readdir($directory))) {
            // Ignore directories prefixed with a '.'
            if (preg_match('/^\./', $subdirectory) == 0
                && is_dir($subdirectory)) {
                self::loadAllTests('.', $subdirectory, $suite);
            }
        }

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
    public static function loadAllTests($path, $directory, $suite) {
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

                    // Explode the path pieces and upper case each word, then
                    // implode with '_' in order to form the class name.
                    // Example: ./admin/ContactInfo.php becomes
                    // 'Test_Admin_ContactInfo'
                    $className = implode('_',
                                         array_map('ucfirst',
                                                   explode('/', $className)));
                                                   
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
