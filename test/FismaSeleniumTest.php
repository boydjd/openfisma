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

require_once 'PHPUnit/Extensions/SeleniumTestCase.php';
require_once 'Zend/Config/Ini.php';

define('SELENIUM_CONFIG_FILE', CONFIGS . '/selenium.conf');

/**
 * This is the base class for all selenium tests in OpenFISMA. This base class
 * sets up the server information for accessing Selenium RC. The configuration
 * file is stored in /apps/config/selenium.conf
 *
 * @package Test
 * @version $Id:$
 */
abstract class Test_FismaSeleniumTest extends PHPUnit_Extensions_SeleniumTestCase {
    /**
     * Handle for the database connection.
     */
    protected $_db;
    
    const USER_NAME = 'test_user';
    const PASSWORD = 'test_password';

    /**
     * setUp() - Set up access to the Selenium server based on the contents of
     * the selenium.conf configuration file.
     */
    protected function setUp() {
        // Run the application bootstrap in command line mode
        define('COMMAND_LINE', true);
        require_once(dirname(dirname(__FILE__)) . '/apps/bootstrap.php');
        
        // Initialize our DB connection
        $this->_db = Zend_Db::factory(Zend_Registry::get('datasource'));
        Zend_Db_Table::setDefaultAdapter($this->_db);
        
        // Load the selenium configuration and connect to the server
        $seleniumConfig = new Zend_Config_Ini(SELENIUM_CONFIG_FILE, 'selenium');

        $this->setHost($seleniumConfig->host);
        $this->setPort(intval($seleniumConfig->port));
        $this->setBrowser($seleniumConfig->browser);
        $this->setBrowserUrl($seleniumConfig->browserBaseUrl);
    }

    /**
     * truncateTables() - Truncate one or more tables.
     *
     * @param string|array $tables The name[s] of the table[s] to truncate
     */
    protected function truncateTables($tables) {
        if (is_array($tables)) {
            foreach ($tables as $table) {
                $this->truncateTable($table);
            }
        } else {
            $this->truncateTable($tables);
        }
    }
    
    /**
     * truncateTable() - Truncate a single table.
     *
     * This is a helper function to truncateTables() and is private.
     *
     * @param string $table The name of the table to truncate
     */
    private function truncateTable($table) {
        $truncate = $this->_db->prepare("TRUNCATE TABLE $table");
        $truncate->execute();
    }
}