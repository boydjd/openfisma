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
 * Tests for the migration helper.
 *
 * These tests are weak. Although the database connection is mocked out, the tests themselves still rely quite a bit
 * on the respective implementations. E.g. if an implementation uses fetch() but could work equally well
 * with fetchAll(), then changing from fetch to fetchAll would break these tests. I'm not sure how to reasonably
 * avoid this without creating a very complex mock object (one that models the internal state of a database… yikes).
 *
 * Still, having coverage of these things is better than no coverage at all, so this is a jumping off point for
 * getting better coverage of migrations.
 *
 * @author     Mark E. Haase <mhaase@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2011 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Test
 * @subpackage Test_Fisma
 */
class Test_Library_Fisma_Migration_Helper extends Test_Case_Unit
{
    /**
     * Test checking a table for existence.
     */
    public function testTableExists()
    {
        $successfulQueryResult = array(array('name' => 'FooTable'));
        $failedQueryResult = FALSE; // PDOStatement::fetch returns FALSE if there are no more results

        // Mock prepared statement
        $statement = $this->getMock('PDOStatement');
        $statement->expects($this->exactly(2))
                  ->method('fetch')
                  ->will($this->onConsecutiveCalls($successfulQueryResult, $failedQueryResult));

        // Mock DB
        $db = $this->getMock('Mock_Pdo');
        $db->expects($this->exactly(2))
           ->method('prepare')
           ->will($this->returnValue($statement));

        $helper = new Fisma_Migration_Helper($db);

        $this->assertTrue($helper->tableExists('FooTable'));
        $this->assertFalse($helper->tableExists('BarTable'));
    }

    /**
     * Test creating a table.
     */
    public function testCreateTable()
    {
        $db = $this->getMock('Mock_Pdo');
        $db->expects($this->once())
           ->method('exec')
           // PCRE flags (since nobody memorizes these) U=ungreedy, s=skip newlines, i=case insensitive
           ->with($this->matchesRegularExpression('/create\s+table.*foo.*id.*primary\s+key.*id/Usi'));

        $helper = new Fisma_Migration_Helper($db);

        $columns = array(
            'id' => "int NOT NULL AUTO_INCREMENT"
        );

        // The real meaning in this test is in the $db->with() call, so no assertions performed here.
        $helper->createTable('Foo', $columns, 'id');
    }

    /**
     * Test failure when creating a table.
     *
     * @expectedException Fisma_Zend_Exception_Migration
     */
    public function testCreateTableFailure()
    {
        $db = $this->getMock('Mock_Pdo');
        $db->expects($this->once())
           ->method('exec')
           ->will($this->returnValue(FALSE));

        $helper = new Fisma_Migration_Helper($db);
        $columns = array(
            'id' => "int NOT NULL AUTO_INCREMENT"
        );

        $helper->createTable('Foo', $columns, 'id');
    }

    /**
     * Test dropping a table.
     */
    public function testDropTable()
    {
        // Mock DB
        $db = $this->getMock('Mock_Pdo');
        $db->expects($this->once())
           ->method('exec')
           // PCRE flags (since nobody memorizes these) U=ungreedy, s=skip newlines, i=case insensitive
           ->with($this->matchesRegularExpression('/drop\s+table\s+`Foo`/Usi'))
           ->will($this->returnValue(true));

        // The real meaning in this test is in the with() calls, so no assertions performed here.
        $helper = new Fisma_Migration_Helper($db);
        $helper->dropTable('Foo');
    }

    /**
     * Test failure when dropping a table.
     *
     * @expectedException Fisma_Zend_Exception_Migration
     */
    public function testDropTableFailure()
    {
        // Mock DB
        $db = $this->getMock('Mock_Pdo');
        $db->expects($this->once())
           ->method('exec')
           // PCRE flags (since nobody memorizes these) U=ungreedy, s=skip newlines, i=case insensitive
           ->with($this->matchesRegularExpression('/drop\s+table\s+`Foo`/Usi'))
           ->will($this->returnValue(false));

        $helper = new Fisma_Migration_Helper($db);
        $helper->dropTable('Foo');
    }

    /**
     * Test adding a column.
     *
     * The test uses addColumns instead of addColumn just to kill 2 birds with one stone.
     */
    public function testAddColumn()
    {
        $regex = '/alter\s+table\s+`Foo`\s+add\s+column\s+`bar`.*default.*auto_increment.*primary.*comment/Usi';

        // Mock DB
        $db = $this->getMock('Mock_Pdo');
        $db->expects($this->once())
           ->method('exec')
           // PCRE flags (since nobody memorizes these) U=ungreedy, s=skip newlines, i=case insensitive
           ->with($this->matchesRegularExpression($regex));

        $columns = array(
            'bar' => array(
                'type' => 'INTEGER',
                'autoIncrement' => true,
                'default' => '1',
                'primary' => true,
                'comment' => 'Best column evar!'
            )
        );

        // The real meaning in this test is in the with() calls, so no assertions performed here.
        $helper = new Fisma_Migration_Helper($db);
        $helper->addColumns('Foo', $columns);
    }

    /**
     * Test adding a column without specifiying a data type.
     *
     * @expectedException Fisma_Zend_Exception_Migration
     */
    public function testAddColumnWithoutDataType()
    {
        $db = $this->getMock('Mock_Pdo');

        // The 'bar' column is missing the required 'type' field.
        $columns = array(
            'bar' => array(
                'autoIncrement' => true,
                'default' => '1',
                'primary' => true,
                'comment' => 'Best column evar!'
            )
        );

        // The real meaning in this test is in the with() calls, so no assertions performed here.
        $helper = new Fisma_Migration_Helper($db);
        $helper->addColumns('Foo', $columns);
    }

    /**
     * Test adding a column that has both a primary key and a unique key.
     *
     * @expectedException Fisma_Zend_Exception_Migration
     */
    public function testAddColumnWithPrimaryAndUniqueKeys()
    {
        $db = $this->getMock('Mock_Pdo');

        // Can't use 'primary' and 'unique' at same time:
        $columns = array(
            'bar' => array(
                'type' => 'INTEGER',
                'autoIncrement' => true,
                'default' => '1',
                'primary' => true,
                'unique' => true,
                'comment' => 'Best column evar!'
            )
        );

        // The real meaning in this test is in the with() calls, so no assertions performed here.
        $helper = new Fisma_Migration_Helper($db);
        $helper->addColumns('Foo', $columns);
    }

    /**
     * Test dropping a column.
     *
     * The test uses dropColumns instead of dropColumn just to kill 2 birds with one stone.
     */
    public function testDropColumn()
    {
        $db = $this->getMock('Mock_Pdo');
        $db->expects($this->once())
           ->method('exec')
           // PCRE flags (since nobody memorizes these) U=ungreedy, s=skip newlines, i=case insensitive
           ->with($this->matchesRegularExpression('/alter\s+table\s+`Foo`\s+drop\s+column\s+`bar`/Usi'));

        // The real meaning in this test is in the with() calls, so no assertions performed here.
        $helper = new Fisma_Migration_Helper($db);
        $helper->dropColumns('Foo', array('bar'));
    }

    /**
     * Test adding a foreign key.
     */
    public function testAddForeignKeyWithName()
    {
        $regex = '/alter\s+table\s+`Foo`\s+add\s+constraint\s+`foo_constraint`\s+foreign\s+key'
               . '\s+`foo_constraint`\s+\(`barid`\)\s+references\s+`bar`\s+\(`id`\)/Usi';

        $db = $this->getMock('Mock_Pdo');
        $db->expects($this->once())
           ->method('exec')
           // PCRE flags (since nobody memorizes these) U=ungreedy, s=skip newlines, i=case insensitive
           ->with($this->matchesRegularExpression($regex));

        // The real meaning in this test is in the with() calls, so no assertions performed here.
        $helper = new Fisma_Migration_Helper($db);
        $helper->addForeignKey('Foo', 'foo_constraint', 'barid', 'bar', 'id');
    }

    /**
     * Test adding a foreign key where the name is inferred from the other parameters.
     */
    public function testAddForeignKeyWithoutName()
    {
        $regex = '/alter\s+table\s+`Foo`\s+add\s+constraint\s+`Foo_barid_bar_id`\s+foreign\s+key'
               . '\s+`Foo_barid_bar_id`\s+\(`barid`\)\s+references\s+`bar`\s+\(`id`\)/Usi';

        $db = $this->getMock('Mock_Pdo');
        $db->expects($this->once())
           ->method('exec')
           // PCRE flags (since nobody memorizes these) U=ungreedy, s=skip newlines, i=case insensitive
           ->with($this->matchesRegularExpression($regex));

        // The real meaning in this test is in the with() calls, so no assertions performed here.
        $helper = new Fisma_Migration_Helper($db);
        $helper->addForeignKey('Foo', null, 'barid', 'bar', 'id');
    }

    /**
     * Test dropping a foreign key.
     */
    public function testDropForeignKey()
    {
        $db = $this->getMock('Mock_Pdo');
        $db->expects($this->once())
           ->method('exec')
           // PCRE flags (since nobody memorizes these) U=ungreedy, s=skip newlines, i=case insensitive
           ->with($this->matchesRegularExpression('/alter\s+table\s+`Foo`\s+drop\s+foreign\s+key\s+`bar`/Usi'));

        // The real meaning in this test is in the with() calls, so no assertions performed here.
        $helper = new Fisma_Migration_Helper($db);
        $helper->dropForeignKeys('Foo', array('bar'));
    }

    /**
     * Test adding an index with one column.
     */
    public function testAddIndexWithOneColumn()
    {
        $regex = '/alter\s+table\s+`Foo`\s+add\s+index\s+`bar`\s+\(`alpha`\)/Usi';

        $db = $this->getMock('Mock_Pdo');
        $db->expects($this->once())
           ->method('exec')
           // PCRE flags (since nobody memorizes these) U=ungreedy, s=skip newlines, i=case insensitive
           ->with($this->matchesRegularExpression($regex));

        // The real meaning in this test is in the with() calls, so no assertions performed here.
        $helper = new Fisma_Migration_Helper($db);
        $helper->addIndex('Foo', 'alpha', 'bar');
    }

    /**
     * Test adding an index with multiple columns.
     */
    public function testAddIndexWithMultipleColumns()
    {
        $regex = '/alter\s+table\s+`Foo`\s+add\s+index\s+`bar`\s+\(`alpha`,\s*`beta`\)/Usi';

        $db = $this->getMock('Mock_Pdo');
        $db->expects($this->once())
           ->method('exec')
           // PCRE flags (since nobody memorizes these) U=ungreedy, s=skip newlines, i=case insensitive
           ->with($this->matchesRegularExpression($regex));

        // The real meaning in this test is in the with() calls, so no assertions performed here.
        $helper = new Fisma_Migration_Helper($db);
        $helper->addIndex('Foo', array('alpha', 'beta'), 'bar');
    }

    /**
     * Test adding an index where the index name is inferred from the single column in the index.
     */
    public function testAddIndexWithoutNameAndOneColumn()
    {
        $regex = '/alter\s+table\s+`Foo`\s+add\s+index\s+`alpha_idx`\s+\(`alpha`\)/Usi';

        $db = $this->getMock('Mock_Pdo');
        $db->expects($this->once())
           ->method('exec')
           // PCRE flags (since nobody memorizes these) U=ungreedy, s=skip newlines, i=case insensitive
           ->with($this->matchesRegularExpression($regex));

        // The real meaning in this test is in the with() calls, so no assertions performed here.
        $helper = new Fisma_Migration_Helper($db);
        $helper->addIndex('Foo', 'alpha');
    }

    /**
     * Test adding an index where the index name cannot be inferred because there is more than 1 column.
     *
     * @expectedException Fisma_Zend_Exception_Migration
     */
    public function testAddIndexWithoutNameAndMultipleColumns()
    {
        $regex = '/alter\s+table\s+`Foo`\s+add\s+index\s+`alpha_idx`\s+\(`alpha`\)/Usi';

        $db = $this->getMock('Mock_Pdo');

        // The real meaning in this test is in the with() calls, so no assertions performed here.
        $helper = new Fisma_Migration_Helper($db);
        $helper->addIndex('Foo', array('alpha', 'beta'));
    }

    /**
     * Test dropping an index.
     */
    public function testDropIndex()
    {
        $db = $this->getMock('Mock_Pdo');
        $db->expects($this->once())
           ->method('exec')
           // PCRE flags (since nobody memorizes these) U=ungreedy, s=skip newlines, i=case insensitive
           ->with($this->matchesRegularExpression('/alter\s+table\s+`Foo`\s+drop\s+index\s+`bar`/Usi'));

        // The real meaning in this test is in the with() calls, so no assertions performed here.
        $helper = new Fisma_Migration_Helper($db);
        $helper->dropIndexes('Foo', array('bar'));
    }
}
