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
 * A set of migration references.
 *
 * This set is iterable (e.g. with foreach) and guarantees that migrations are iterated in the proper order, with
 * the lower version numbers always being returned before the higher version numbers.
 *
 * @author     Mark E. Haase <mhaase@endeavorystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2011 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Fisma
 * @subpackage Fisma_Migration
 */
class Fisma_Migration_Set implements Iterator, Countable
{
    /**
     * A list of versions stored in this set.
     *
     * Versions are stored as padded 6-digit strings, e.g. "021700" for easy sorting.
     *
     * @var array
     */
    private $_versions = array();

    /**
     * A nested array of migrations.
     *
     * The outer array contains version numbers (in 6-digit, padded form) as keys. Each version number points to
     * an array of strings that represents the names of migrations within that version.
     *
     * @var array
     */
    private $_migrations = array();

    /**
     * Part of the Iterator implementation. Points to the current version.
     *
     * @var string
     */
    private $_currentVersion;

    /**
     * Part of the Iterator implementation. Points to the current migration within the current version.
     *
     * @var int
     */
    private $_currentMigration = 0;

    /**
     * Add a migration reference to this set.
     *
     * @param Fisma_Migration $migration
     */
    public function add(Fisma_Migration_Abstract $migration)
    {
        $versionString = $migration->getVersion()->getPaddedString();

        if (!in_array($versionString, $this->_versions)) {
            $this->_versions[] = $versionString;
        }

        if (!isset($this->_migrations[$versionString])) {
            $this->_migrations[$versionString] = array();
        }

        $this->_migrations[$versionString][] = $migration;
    }

    /**
     * Return true if this set contains a migration matching the specified version and name.
     *
     * @param string $versionString A 6 digit padded version string, e.g. "021700".
     * @param string $migrationName
     * @return bool
     */
    public function contains($versionString, $migrationName)
    {
        if (isset($this->_migrations[$versionString])) {
            foreach ($this->_migrations[$versionString] as $migration) {
                $className = Fisma_Migration_Abstract::CLASS_NAME_PREFIX . "{$versionString}_{$migrationName}";

                if (get_class($migration) === $className) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Return a new migration set that contains all of the migrations that are in this set but not in the compared set.
     *
     * @param Fisma_Migration_Set $compare
     * @return Fisma_Migration_Set
     */
    public function diff(Fisma_Migration_Set $compare)
    {
        $diffSet = new self();

        foreach ($this as $version => $migration) {
            $versionString = $migration->getVersion()->getPaddedString();
            $migrationName = $migration->getName();

            if (!$compare->contains($versionString, $migrationName)) {
                $diffSet->add($migration);
            }
        }

        return $diffSet;
    }

    /**
     * Count the number of migrations available in this set.
     *
     * @return int
     */
    public function count()
    {
        $count = 0;

        foreach ($this->_migrations as $version) {
            foreach ($version as $migration) {
                $count += count($migration);
            }
        }

        return $count;
    }

    /**
     * Iterator interface.
     *
     * @return string The current migration name.
     */
    public function current()
    {
        $versionString = $this->_versions[$this->_currentVersion];

        return $this->_migrations[$versionString][$this->_currentMigration];
    }

    /**
     * Iterator interface.
     *
     * @return string The current migration version (as a 6-digit padded string).
     */
    public function key()
    {
        return $this->_versions[$this->_currentVersion];
    }

    /**
     * Iterator interface.
     */
    public function next()
    {
        $this->_currentMigration++;

        $versionString = $this->_versions[$this->_currentVersion];

        if (!isset($this->_migrations[$versionString][$this->_currentMigration])) {
            $this->_currentVersion++;
            $this->_currentMigration = 0;
        }
    }

    /**
     * Iterator interface.
     */
    public function rewind()
    {
        sort($this->_versions);

        if (count($this->_versions) > 0) {
            $this->_currentVersion = 0;
            $this->_currentMigration = 0;
        } else {
            $this->_currentVersion = null;
            $this->_currentMigration = null;
        }
    }

    /**
     * Iterator interface.
     *
     * @return bool
     */
    public function valid()
    {
        $valid = isset($this->_versions[$this->_currentVersion]) &&
                 isset($this->_migrations[$this->_versions[$this->_currentVersion]][$this->_currentMigration]);

        return $valid;
    }
}
