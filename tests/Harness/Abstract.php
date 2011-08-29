<?php
/**
 * Copyright (c) 2011 Endeavor Systems, Inc.
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

/**
 * The test harness is responsible for finding and loading tests in a way that php unit can use to run a series of
 * related tests.
 * 
 * The abstract class handles the basic responsibility of finding and loading test classes within the test root
 * directory. Subclasses implement the functionality of picking which test classes to include within a particular suite.
 * 
 * @author     Mark E. Haase <mhaase@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2011 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Test
 * @subpackage TestHarness
 */
abstract class Test_Harness_Abstract
{
    /**
     * Subclasses must implement this method to identify which test classes should be included in this test suite.
     * 
     * @param string $className
     * @return bool
     */
    abstract protected function _includeClassInSuite($className);

    /**
     * A an array of subdirectories (realative to the test root) that will be searched for test classes
     * 
     * @var array
     */
    protected $_testDirectories = array('application', 'library');

    /**
     * Constructor
     * 
     * @param string $name A human readable name for this suite (for reporting purposes)
     */
    public function __construct($name)
    {
        if (empty($name)) {
            throw new Exception("\$name is required for constructing a test harness.");
        }

        $this->_suite = new PHPUnit_Framework_TestSuite;
        $this->_suite->setName($name);
    }

    /**
     * Creates a phpunit test suite for all unit tests in the project. The controller recurses through all directories
     * and loads all of the .php files found.
     *
     * Notice that each test file should be named following the ZF standards in order for this to work.
     *
     * @return PHPUnit_Framework_TestSuite The  assembled test suite
     */
    public function getTestSuite()
    {        
        $testRoot = realpath('.');
        
        // The tests are found by looking in '.'. Notice that Fisma::getPath() isn't used to avoid introducing all of
        // the dependencies of Fisma.php.
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($testRoot));

        foreach ($files as $path => $info) {
            $pathParts = explode(DIRECTORY_SEPARATOR, $files->getSubPathname());

            // Ignore dot files and directories
            if ($files->isDir() || $files->isDot()) {
                continue;
            }

            // Ignore files that are not nested in our test directories
            if (!in_array($pathParts[0], $this->_testDirectories)) {
                continue;
            }

            $className = $this->_loadTestClassFromPath($testRoot, $files->getSubPathname());

            if ($this->_includeClassInSuite($className)) {
                $this->_suite->addTestSuite($className);
            }
        }

        return $this->_suite;
    }

    /**
     * Load a test class from a given path if it meets certain requirements
     * 
     * @param string $path
     */
    private function _loadTestClassFromPath($testRoot, $subPath)
    {
        // Explode the path pieces then implode with '_' in order to form the class name.
        // Example: tests/library/Admin/ContactInfo.php becomes 'Test_Library_Admin_ContactInfo'
        $className = str_replace('.php', '', $subPath);
        $className = 'Test_' . ucfirst(implode('_', explode('/', $className)));

        // Now include the file, and check to see if the expected class name exists. If so, then add that
        // class to the test suite.
        require_once(realpath($testRoot . '/' . $subPath));

        if (!class_exists($className)) {
            throw new Exception("The file $subPath does not contain a class called $className.");
        }
        
        return $className;
    }
}
