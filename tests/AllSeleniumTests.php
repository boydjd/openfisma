<?php
/**
 * Copyright (c) 2008 Endeavor Systems, Inc.
 *
 * This file is part of OpenFISMA.
 *
 * OpenFISMA is free software: you can redistribute it and/or modify it under the terms of the GNU General Public 
 * License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later
 * version.
 *
 * OpenFISMA is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied 
 * warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for more 
 * details.
 *
 * You should have received a copy of the GNU General Public License along with OpenFISMA.  If not, see 
 * {@link http://www.gnu.org/licenses/}.
 */

// Bootstrap the application's CLI mode if it has not already been done
require_once(realpath(dirname(__FILE__) . '/../library/Fisma.php'));
if (Fisma::RUN_MODE_COMMAND_LINE != Fisma::mode()) {
    try {
        Fisma::initialize(Fisma::RUN_MODE_COMMAND_LINE);
        Fisma::connectDb();
        Fisma::setNotificationEnabled(false);
    } catch (Zend_Config_Exception $zce) {
        print "The application is not installed correctly." .
            " If you have not run the installer, you should do that now.";
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
 * @author     Mark E. Haase <mhaase@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Test
 * @version    $Id$
 */
class AllSeleniumTests
{
    /**
     * Test controller main method
     * 
     * @return void
     */
    public static function main()
    {
        PHPUnit_TextUI_TestRunner::run(self::suite());
    }

    /**
     * Creates a phpunit test suite for all Selenium tests in the project.
     * The controller recurses through all subdirectories and loads all of
     * the .php * files found.
     *
     * Notice that each test file should be named following the ZF standards in
     * order for this to work.
     *
     * @return PHPUnit_Framework_TestSuite The assembled test suite
     */
    public static function suite()
    {
        $suite = new PHPUnit_Framework_TestSuite('phpUnderControl - AllTests');
        
        // Load in all files which are in subdirectories of the Selenium
        // directory
        //$suite->addTestSuite('Test_Selenium_NetworkTest');
        $directory = opendir(Fisma::getPath('test'));
        while (false !== ($subdirectory = readdir($directory))) {
            // Ignore directories prefixed with a '.'
            if (preg_match('/^\./', $subdirectory) == 0
                && is_dir($subdirectory)
                && 'selenium' != $subdirectory
                && 'Model' != $subdirectory
                && 'fixtures' != $subdirectory) {
                self::loadAllTests('.', $subdirectory, $suite);
            }
        }

        return $suite;
    }

    /**
     * Load all of the PHP files in the specified directory,
     * and add them to the test suite.
     *
     * @param string $path The parent path containing the directory
     * @param string $directory The name of the directory
     * @param PHPUnit_Framework_TestSuite The test suite to assemble test case
     * @return void
     * @throws Exception if the class is not found in the file
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