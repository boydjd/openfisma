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

// Bootstrap the application's CLI mode if it has not already been done
require_once(realpath(dirname(__FILE__) . '/../library/Fisma.php'));
require_once 'bootstrap.php';

/**
 * This is the base class for all unit tests in OpenFISMA. 
 * 
 * It contains some magic for bootstrapping test classes. See the comment on __construct() for details.
 * 
 * @author     Mark E. Haase <mhaase@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2011 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Test
 */
abstract class Test_Case extends PHPUnit_Framework_TestCase
{
    private static $_boostrapped = false;

    /**
     * Override the constructor as a hacky way of running a bootstrap routine.
     * 
     * This is brittle since it can be broken by changes to phpunit's public interface for test cases, but the 
     * benefit is that it is easy to bootstrap test classes whether they are run individually or as part of a suite.
     */
    public function __construct()
    {
        parent::__construct();

        if (!self::$_bootstrapped) {
            
            self::$_bootstrapped = true;
        }
    }
}
