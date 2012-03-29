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
           ->with($this->matchesRegularExpression('/drop\s+table/Usi'));

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
           ->with($this->matchesRegularExpression('/drop\s+table/Usi'))
           ->will($this->returnValue(FALSE));

        $helper = new Fisma_Migration_Helper($db);
        $helper->dropTable('Foo');
    }

    /**
     * Test the foreign key helper.
     */
    public function testAddForeignKeyWithName()
    {
        $db = $this->getMock('Mock_Pdo');

        $addIndexRegex = '/alter\s+table\s+`footable`\s+add\s+index.*`foocolumn_idx`\s+\(`foocolumn`\)/Usi';
        $addFkRegex = '/alter\s+table\s+`footable`\s+add\s+constraint.*foobar/Usi';

        $db->expects($this->exactly(2))
           ->method('exec')
           // PCRE flags (since nobody memorizes these) U=ungreedy, s=skip newlines, i=case insensitive
           ->with($this->logicalOr(
                $this->matchesRegularExpression($addIndexRegex),
                $this->matchesRegularExpression($addFkRegex)
           ));

        $helper = new Fisma_Migration_Helper($db);
        $helper->addForeignKey('footable', 'foocolumn', 'bartable', 'barcolumn', 'foobar');
    }

    /**
     * Test the foreign key helper's name creation.
     */
    public function testAddForeignKeyWithoutName()
    {
        $db = $this->getMock('Mock_Pdo');

        $addIndexRegex = '/alter\s+table\s+`footable`\s+add\s+index.*`foocolumn_idx`\s+\(`foocolumn`\)/Usi';
        $addFkRegex = '/alter\s+table\s+`footable`\s+add\s+constraint.*footable_foocolumn_bartable_barcolumn/Usi';

        $db->expects($this->exactly(2))
           ->method('exec')
           // PCRE flags (since nobody memorizes these) U=ungreedy, s=skip newlines, i=case insensitive
           ->with($this->logicalOr(
                $this->matchesRegularExpression($addIndexRegex),
                $this->matchesRegularExpression($addFkRegex)
           ));

        $helper = new Fisma_Migration_Helper($db);
        $helper->addForeignKey('footable', 'foocolumn', 'bartable', 'barcolumn');
    }

    /**
     * Test a unique key with one column.
     */
    public function testUniqueKeyWithOneColumn()
    {
        $db = $this->getMock('Mock_Pdo');
        $db->expects($this->once())
           ->method('exec')
           // PCRE flags (since nobody memorizes these) U=ungreedy, s=skip newlines, i=case insensitive
           ->with($this->matchesRegularExpression('/alter\s+table\s+`foo`\s+add\s+unique\s+`bar`\s+\(`bar`\)/Usi'));

        $helper = new Fisma_Migration_Helper($db);
        $helper->addUniqueKey('foo', 'bar');
    }

    /**
     * Test a unique key with multiple columns.
     */
    public function testUniqueKeyWithMultipleColumns()
    {
        $db = $this->getMock('Mock_Pdo');
        $db->expects($this->once())
           ->method('exec')
           // PCRE flags (since nobody memorizes these) U=ungreedy, s=skip newlines, i=case insensitive
           ->with($this->matchesRegularExpression('/`my_idx`\s+\(`apple`,\s*`banana`\)/Usi'));

        $helper = new Fisma_Migration_Helper($db);
        $helper->addUniqueKey('foo', array('apple', 'banana'), 'my_idx');
    }

    /**
     * Negative test: a unique key with multiple columns and no name.
     *
     * @expectedException Fisma_Zend_Exception_Migration
     */
    public function testUniqueKeyWithMultipleColumnsAndNoName()
    {
        $db = $this->getMock('Mock_Pdo');
        $helper = new Fisma_Migration_Helper($db);
        $helper->addUniqueKey('foo', array('apple', 'banana'));
    }

    /**
     * Test adding a column at the end of a table.
     */
    public function testAddColumn()
    {
        $db = $this->getMock('Mock_Pdo');
        $db->expects($this->once())
           ->method('exec')
           // PCRE flags (since nobody memorizes these) U=ungreedy, s=skip newlines, i=case insensitive
           ->with($this->matchesRegularExpression('/alter\s+table\s+`foo`\s+add\s+column\s+`bar`\s+bigint/Usi'));

        $helper = new Fisma_Migration_Helper($db);
        $helper->addColumn('foo', 'bar', 'bigint(20) NOT NULL');
    }

    /**
     * Test adding a column after another column.
     */
    public function testAddColumnAfterColumn()
    {
        $db = $this->getMock('Mock_Pdo');
        $db->expects($this->once())
           ->method('exec')
           // PCRE flags (since nobody memorizes these) U=ungreedy, s=skip newlines, i=case insensitive
           ->with($this->matchesRegularExpression('/add\s+column.*\s+after\s+`unladen_swallow`/Usi'));

        $helper = new Fisma_Migration_Helper($db);
        $helper->addColumn('foo', 'bar', 'bigint(20) NOT NULL', 'unladen_swallow');
    }

    /**
     * Test dropping a column.
     */
    public function testDropColumn()
    {
        $db = $this->getMock('Mock_Pdo');
        $db->expects($this->once())
           ->method('exec')
           // PCRE flags (since nobody memorizes these) U=ungreedy, s=skip newlines, i=case insensitive
           ->with($this->matchesRegularExpression('/alter\s+table\s+`foo`\s+drop\s+column\s+`bar`/Usi'));

        $helper = new Fisma_Migration_Helper($db);
        $helper->dropColumn('foo', 'bar');
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
