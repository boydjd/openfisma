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
 * @copyright  (c) Endeavor Systems, Inc. 2011 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Test
 * @subpackage Test_Library_Doctrine
 */
class Test_Library_Doctrine_Query extends Test_Case_Database
{
    /**
     * This is a word that can be used in different injection scenarios just to see if injection is possible.
     * 
     * If the danger word can be injected into a query in an unexpected place, then the query may be vulnerable.
     * 
     * @var string
     */
    private $_dangerWord = "DANGER";
    
    /**
     * Test the helper methods of this class
     * 
     * There are some helper methods in this class for creating, checking, and dropping a dummy table, 
     * and this test makes sure that those helper methods work right.
     */
    public function testCreateDummyTable()
    {
        // Create a dummy table
        $this->_createFooTable();
        $this->assertTrue($this->_checkFooTableExists());
        
        // Drop the dummy table
        $this->_dropFooTable();
        $this->assertFalse($this->_checkFooTableExists());
    }

    /**
     * Try injecting a semicolon into various clauses
     * 
     * @dataProvider provideSemicolonClauses
     * 
     * @param string $clauseName The name of the DQL method to call (e.g. orderBy)
     * @param string $clauseValue The value to pass to the clause
     */
    public function testInjectSemicolon($clauseName, $clauseValue)
    {
        // Use raw handle to create a temporary table
        $this->_createFooTable();
        
        $q = Doctrine_Query::create()
             ->from('User u')
             ->select('u.username')
             ->$clauseName($clauseValue);
        $q->execute();
        
        $this->assertTrue($this->_checkFooTableExists());
        
        $this->_dropFooTable();
    }

    /**
     * Return some different SQL clauses that contain semicolons to see if multiple statements can be 
     * injected into a single query.
     * 
     * @return array
     */
    public function provideSemicolonClauses()
    {
        return array(
            array("select", "u.username ; drop table foo ; select u.username"),
            array("groupBy", "u.username ; drop table foo"),
            array("orderBy", "u.username ; drop table foo"),
            array("leftJoin", "u.Roles r ; drop table foo"),
            array("innerJoin", "u.Roles r ; drop table foo"),
            array("limit", "1337 ; drop table foo"),
            array("offset", "1337 ; drop table foo")
        );
    }

    /**
     * Try to insert a comment into a query using an order by clause
     * 
     * @dataProvider provideCommentClauses
     * 
     * @param string $clauseName The name of the DQL method to call (e.g. orderBy)
     * @param string $clauseValue The value to pass to the clause
     */
    public function testInjectComment($clauseName, $clauseValue)
    {
        $q = Doctrine_Query::create()
             ->from('User u')
             ->select('u.username')
             ->$clauseName($clauseValue);

        $this->assertThat($q->getSql(), $this->logicalNot($this->stringContains($this->_dangerWord)));
    }

    /**
     * Return some different styles of comments for use in query injection tests
     * 
     * We return different comments that contain a danger word... if this word shows up in a query then
     * we know that comment injection is possible. Comment injection can be dangerous because it may be possible
     * to comment out or mask important parts of a query, such as the parts that enforce ACL.
     * 
     * @return array
     */
    public function provideCommentClauses()
    {
        return array(
            array("select", "u.username # $this->_dangerWord "),
            array("groupBy", "u.username # $this->_dangerWord "),
            array("orderBy", "u.username # $this->_dangerWord "),
            array("leftJoin", "u.Roles r # $this->_dangerWord "),
            array("innerJoin", "u.Roles r # $this->_dangerWord "),
            array("limit", "1337 # $this->_dangerWord "),
            array("offset", "1337 # $this->_dangerWord "),

            array("select", "u.username -- $this->_dangerWord "),
            array("groupBy", "u.username -- $this->_dangerWord "),
            array("orderBy", "u.username -- $this->_dangerWord "),
            array("leftJoin", "u.Roles r -- $this->_dangerWord "),
            array("innerJoin", "u.Roles r -- $this->_dangerWord "),
            array("limit", "1337 -- $this->_dangerWord "),
            array("offset", "1337 -- $this->_dangerWord "),

            array("select", "u.username /* $this->_dangerWord */ "),
            array("groupBy", "u.username /* $this->_dangerWord */ "),
            array("orderBy", "u.username /* $this->_dangerWord */ "),
            array("leftJoin", "u.Roles r /* $this->_dangerWord */ "),
            array("innerJoin", "u.Roles r /* $this->_dangerWord */ "),
            array("limit", "1337 /* $this->_dangerWord */ "),
            array("offset", "1337 /* $this->_dangerWord */ ")
        );
    }

    /**
     * Try to inject one kind of clause into a different kind of clause. 
     * 
     * For example ->orderBy(u.username GROUP BY u.username) should not add a GROUP BY clause to the query.
     *
     * @dataProvider provideMixedClauses
     * 
     * @param string $clauseName The name of the DQL method to call (e.g. orderBy)
     * @param string $clauseValue The value to pass to the clause
     * @param string $forbiddenText Text that should not be present in the resulting query
     */
    public function testInjectOneClauseIntoOtherClause($clauseName, $clauseValue, $forbiddenText)
    {
        $q = Doctrine_Query::create()
             ->from('User u')
             ->select('u.username')
             ->$clauseName($clauseValue);

        $this->assertThat($q->getSql(), $this->logicalNot($this->stringContains($forbiddenText)));
    }

    /**
     * Return some mixed clauses, for example, a groupBy() that contains an ORDER BY.
     * 
     * @return array
     */
    public function provideMixedClauses()
    {
        return array(
            array("groupBy", "u.username ORDER BY u.username ", "ORDER BY"),
            array("orderBy", "u.username GROUP BY u.username ", "GROUP BY")
        );
    }

    /**
     * Create a dummy table named "foo"
     */
    protected function _createFooTable()
    {
        // Create the foo table only if it doesn't already exist
        if (!$this->_checkFooTableExists()) {
            $mysql = Doctrine_Manager::getInstance()->getCurrentConnection()->getDbh();

            if (FALSE === $mysql->exec("CREATE TABLE foo (foo INT)")) {
                throw new Exception("Unable to create dummy foo table: " . print_r($mysql->errorInfo(), true));
            }            
        }
    }

    /**
     * Check if the foo table exists
     * 
     * Notice that a failed test case might leave you in a situation where a foo table from a previous test
     * exists at the beginning of your new test, SO DON'T ASSUME THAT FOO DOESN'T EXIST when you write your tests.
     */
    protected function _checkFooTableExists()
    {
        $schema = Fisma::$appConf['db']['schema'];

        $checkTableQuery = "SELECT COUNT(*) AS CNT FROM information_schema.tables"
                         . " WHERE table_schema LIKE ? AND table_name LIKE ?";

        $mysql = Doctrine_Manager::getInstance()->getCurrentConnection()->getDbh();
        $statement = $mysql->prepare($checkTableQuery);

        if (!$statement->execute(array($schema, "foo"))) {
            throw new Exception(
                "Failed to execute query while checking if table $name exists: " 
                . print_r($statement->errorInfo(), true)
            );
        }
        
        $result = $statement->fetch();

        return (1 == $result['CNT']);
    }

    /**
     * Drop the dummy table
     */
    protected function _dropFooTable()
    {
        if ($this->_checkFooTableExists()) {
            $mysql = Doctrine_Manager::getInstance()->getCurrentConnection()->getDbh();

            if (FALSE === $mysql->exec("DROP TABLE foo")) {
                throw new Exception(
                    "Can't drop foo table: " . print_r($mysql->errorInfo(), true)
                );
            }
        }
    }
}
