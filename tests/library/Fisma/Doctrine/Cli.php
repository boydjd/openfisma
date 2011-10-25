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

require_once(realpath(dirname(__FILE__) . '/../../../Case/Unit.php'));

/**
 * test suite for /library/Fisma/Doctrine/Cli.php
 *
 * @author     Duy K. Bui <duy.bui@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2011 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Test
 * @subpackage Test_Library
 */
class Test_Library_Fisma_Doctrine_Cli extends Test_Case_Unit
{
    /**
     * test getTaskClass
     * @return void
     * @require CliDummy because the only method in source class is protected
     */
    public function testGetTaskClass()
    {
        require_once(realpath(dirname(__FILE__) . '/CliDummy.php'));
        $cli = new Test_Library_Fisma_Doctrine_CliDummy();

        $args = array(1 => 'migrate');
        $this->assertEquals('Fisma_Doctrine_Task_Migrate', $cli->getTaskClassFromArgs($args)); //customized classes are checked manually

        $args[1] = 'Compile';
        $this->assertEquals('Doctrine_Task_Compile', $cli->getTaskClassFromArgs($args));
    }
}

