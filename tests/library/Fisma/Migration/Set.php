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
 * Tests for Fisma_Migration_Set.
 *
 * @author     Mark E. Haase <mhaase@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2011 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Test
 * @subpackage Test_Fisma
 */
class Test_Library_Fisma_Migration_Set extends Test_Case_Unit
{
    /**
     * Add a migration and then see if the set contains that migration.
     */
    public function testContains()
    {
        $set = new Fisma_Migration_Set;
        $set->add($this->_createMigration("010203", "TestContains"));

        $this->assertTrue($set->contains("010203", "TestContains"));
        $this->assertFalse($set->contains("010203", "FooBar"));
        $this->assertFalse($set->contains("999999", "TestContains"));
    }

    /**
     * Add some migrations to a set and then iterate over them. They should come out in the correct order.
     */
    public function testIterator()
    {
        // Create some dummy migrations and add them into a migration set.
        $migrations = array(
            $this->_createMigration("999999", "TestIterator"),
            $this->_createMigration("010000", "TestIteratorA"),
            $this->_createMigration("040404", "TestIterator"),
            $this->_createMigration("010000", "TestIteratorB"),
        );

        $migrationSet = new Fisma_Migration_Set;

        foreach ($migrations as $migration) {
            $migrationSet->add($migration);
        }

        // Now iterate over the migrations and make sure the versions come out in the correct order.
        $expectedVersions = array("010000", "010000", "040404", "999999");
        $expectedVersionsIndex = 0;

        foreach ($migrationSet as $version => $migration) {
            $this->assertEquals($expectedVersions[$expectedVersionsIndex], $version);

            $expectedVersionsIndex++;
        }

        // Did the loop execute 4 times?
        $this->assertEquals($expectedVersionsIndex, 4);
    }

    /**
     * Test the iterator interface when the set is empty.
     */
    public function testEmptyIterator()
    {
        $set = new Fisma_Migration_Set;
        $count = 0;

        foreach ($set as $version => $migration) {
            $count++;
        }

        $this->assertSame(0, $count);
    }

    /**
     * Test the diff method.
     */
    public function testDiff()
    {
        $migration1 = $this->_createMigration("021700", "TestDiffA");
        $migration2 = $this->_createMigration("021700", "TestDiffB");
        $migration3 = $this->_createMigration("021701", "TestDiffA");
        $migration4 = $this->_createMigration("021701", "TestDiffB");

        $migrationSet1 = new Fisma_Migration_Set;
        $migrationSet1->add($migration1);
        $migrationSet1->add($migration2);
        $migrationSet1->add($migration3);

        $migrationSet2 = new Fisma_Migration_Set;
        $migrationSet2->add($migration1);
        $migrationSet2->add($migration2);
        $migrationSet2->add($migration4);

        $diffSet = $migrationSet1->diff($migrationSet2);

        $this->assertFalse($diffSet->contains("021700", "TestDiffA"));
        $this->assertFalse($diffSet->contains("021700", "TestDiffB"));
        $this->assertTrue($diffSet->contains("021701", "TestDiffA"));
        $this->assertFalse($diffSet->contains("021701", "TestDiffB"));
    }

    /**
     * Test the count method.
     */
    public function testCount()
    {
        $migrationSet = new Fisma_Migration_Set;

        $migrationSet->add($this->_createMigration("021700", "TestCountA"));
        $migrationSet->add($this->_createMigration("021700", "TestCountB"));
        $migrationSet->add($this->_createMigration("021701", "TestCountA"));
        $migrationSet->add($this->_createMigration("021701", "TestCountB"));

        $this->assertSame(4, count($migrationSet));

        $migrationSet->add($this->_createMigration("021701", "TestCountC"));

        $this->assertSame(5, count($migrationSet));
    }

    /**
     * Create a migration with the specified name and version number.
     *
     * This is just a helper method because we do it a lot in these tests.
     *
     * NOTICE that phpunit will create a class name based on the version string and the migration name, so each
     * pair that you pass needs to be unique. To help ensure uniqueness, you should make the migration name based
     * on the test name, e.g. testDiff creates a migration called TestDiff.
     *
     * @param string $versionString A 6 digit padded version string.
     * @param string $migrationName
     * @return
     */
    private function _createMigration($versionString, $migrationName)
    {
        $className = Fisma_Migration_Abstract::CLASS_NAME_PREFIX . "{$versionString}_{$migrationName}";

        return $this->getMockForAbstractClass('Fisma_Migration_Abstract', array(), $className);
    }
}
