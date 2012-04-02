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

require_once(realpath(dirname(__FILE__) . '/../Bootstrap.php'));

/**
 * This is the base class for all unit tests in OpenFISMA. This class bootstraps the runtime for database tests.
 * 
 * @author     Mark E. Haase <mhaase@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2011 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Test
 */
abstract class Test_Case_Database extends PHPUnit_Framework_TestCase
{
    /**
     * The bootstrapper object for tests
     */
    static private $_bootstrap;

    /**
     * Override the parent constructor in order to ensure the bootstrap runs once
     */
    public function __construct($name = NULL, array $data = array(), $dataName = '')
    {
        parent::__construct($name, $data, $dataName);

        if (!self::$_bootstrap) {
            self::$_bootstrap = new Test_Bootstrap;

            self::$_bootstrap->bootstrapDatabaseTest();
        }
    }
}
