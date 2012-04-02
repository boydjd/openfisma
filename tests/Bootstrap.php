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
 * Bootstrap class for test cases 
 * 
 * @author     Mark E. Haase <mhaase@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2011 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Test
 */
class Test_Bootstrap
{
    /**
     * A reference to the application.
     * 
     * @var Zend_Application
     */
    private $_application;

    /**
     * Tracks if the common bootstrap has run or not
     */
    private $_bootstrapCommon = false;

    /**
     * Tracks if the unit test bootstrap has run or not
     */
    private $_bootstrapUnitTest = false;

    /**
     * Tracks if the database test bootstrap has run or not
     */
    private $_bootstrapDatabaseTest = false;

    /**
     * Execute all common test case bootstrap functions
     */
    private function _bootstrapCommon()
    {
        if (!$this->_bootstrapCommon) {
            defined('APPLICATION_ENV')
                || define(
                    'APPLICATION_ENV',
                    (getenv('APPLICATION_ENV') ? getenv('APPLICATION_ENV') : 'development')
                );
            defined('APPLICATION_PATH') || define(
                'APPLICATION_PATH',
                realpath(dirname(__FILE__) . '/../application')
            );

            set_include_path(
                APPLICATION_PATH . '/../library/Symfony/Components' . PATH_SEPARATOR .
                APPLICATION_PATH . '/../library' .  PATH_SEPARATOR .
                APPLICATION_PATH . '/../tests' . PATH_SEPARATOR .
                get_include_path()
            );

            require_once 'Fisma.php';
            require_once 'Zend/Application.php';

            $this->_application = new Zend_Application(
                APPLICATION_ENV,
                APPLICATION_PATH . '/config/application.ini'
            );

            Fisma::setAppConfig($this->_application->getOptions());
            Fisma::initialize(Fisma::RUN_MODE_TEST);
            
            Doctrine_Manager::getInstance()->setAttribute(Doctrine::ATTR_AUTOLOAD_TABLE_CLASSES, true);

            $frontController = Zend_Controller_Front::getInstance();
            $frontController->setControllerDirectory(Fisma::getPath('controller'));

            error_reporting(E_ALL & ~E_NOTICE);
            
            $this->_bootstrapCommon = true;
        }
    }

    /**
     * Return the application object
     */
    public function getApplication()
    {
        return $this->_application;
    }

    /**
     * Bootstraps unit test cases
     */
    public function bootstrapUnitTest()
    {
        if (!$this->_bootstrapUnitTest) {
            $this->_bootstrapCommon();

            Doctrine_Manager::connection(new PDO('sqlite::memory:'));

            $this->_bootstrapUnitTest = true;
        }
    }
    
    /**
     * Bootstraps database test cases
     */
    public function bootstrapDatabaseTest()
    {
        if (!$this->_bootstrapDatabaseTest) {
            $this->_bootstrapCommon();

            $db = Fisma::$appConf['db'];
            $connectString = $db['adapter'] 
                           . '://' 
                           . $db['username'] 
                           . ':' 
                           . $db['password'] 
                           . '@' 
                           . $db['host'] 
                           . ($db['port'] ? ':' . $db['port'] : '') 
                           . '/' 
                           . $db['schema'];

            Doctrine_Manager::connection($connectString);

            $this->_bootstrapDatabaseTest = true;
        }
    }
}
