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

require_once(realpath(dirname(__FILE__) . '/../../Case/Database.php'));

/**
 * Some tests for doctrine to ensure that their query parsing is not susceptible to SQL injection.
 * 
 * We implicitly trust their parameterized queries (.e.g ->where(foo = ?, "bar")), but other clauses like
 * sort by and order by don't have parameterized versions, so we want to write some malicious tests to see
 * if Doctrine fails gracefully.
 * 
 * @author     Mark E. Haase <mhaase@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2012 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Test
 * @subpackage Test_Library_Doctrine
 */
class Test_Library_Doctrine_Query extends Test_Case_Database
{
    public function setUp()
    {
        # code...
    }

    /**
     * Try using a semicolon to execute two statements at once in orderBy(). 
     * 
     * The MySQL adapter doesn't even support executing two statements separated by semicolons, but as an extreme
     * precaution I want to make sure that Doctrine doesn't even try it.
     *
     * @expectedException Doctrine_Query_Exception
     */
    public function testOrderByExecuteTwoStatements()
    {
        $q = Doctrine_Query::create()
             ->from('User u')
             ->orderBy('u.username ; drop table foo')
             ->setHydrationMode(Doctrine::HYDRATE_SCALAR);

        $q->execute();
    }

    /**
     * Try using a comment to prematurely end a query.
     * 
     * This attack might enable a malicious user to trick MySQL into ignoring subsequent clauses, thereby leaking
     * data.
     *
     * @expectedException Doctrine_Query_Exception
     */
    public function testOrderByHashComment()
    {
        $q = Doctrine_Query::create()
             ->from('User')
             ->orderBy('#')
             ->setHydrationMode(Doctrine::HYDRATE_SCALAR);

        $q->execute();
    }

    /**
     * Try using a comment to prematurely end a query.
     * 
     * This attack might enable a malicious user to trick MySQL into ignoring subsequent clauses, thereby leaking
     * data.
     *
     * @expectedException Doctrine_Query_Exception
     */
    public function testOrderByCStyleComment()
    {
        $q = Doctrine_Query::create()
             ->from('User u')
             ->orderBy('/* foo */ ')
             ->setHydrationMode(Doctrine::HYDRATE_SCALAR);

        $q->execute();
    }
}
