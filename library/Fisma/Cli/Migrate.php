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
 * Run database migrations.
 *
 * @author     Mark E. Haase
 * @copyright  (c) Endeavor Systems, Inc. 2011 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Fisma
 * @subpackage Fisma_Cli
 */
class Fisma_Cli_Migrate extends Fisma_Cli_Abstract
{
    /**
     * This is the migration version that we expect doctrine to have before we switch to the new migration system.
     *
     * If a user does not have this version, he/she needs to update to the latest 2.16.x release before upgrading to
     * 2.17.0.
     *
     * @var int
     */
    const DOCTRINE_MIGRATION_MAX_VERSION = 129;

    /**
     * A PDO object handle for the current database connection.
     *
     * @param PDO
     */
    private $_db;

    /**
     * Indicates whether we're in dry run mode or not.
     *
     * @param bool
     */
    private $_dryRun;

    /**
     * Add arguments for this command line tool.
     *
     * @see http://framework.zend.com/manual/en/zend.console.getopt.rules.html
     *
     * @return array An array containing getopt long syntax
     */
    public function getArgumentsDefinitions()
    {
        return array(
            'dry-run|d' => 'Dry run. (Show what the script would do, but don\'t actually do anything.)',
            'info|i' => 'Show information about the migration system.'
        );
    }

    /**
     * Set up logging
     */
    public function __construct()
    {
        // Log all migration messages to a dedicated migration log.
        $fileWriter = new Zend_Log_Writer_Stream(Fisma::getPath('log') . '/migration.log');
        $fileWriter->setFormatter(new Zend_Log_Formatter_Simple("[%timestamp%] %message%\n"));

        parent::getLog()->addWriter($fileWriter);
    }

    /**
     * Check for migrations that need to be run and execute them in the correct order.
     */
    protected function _run()
    {
        // Process arguments
        $this->_dryRun = $this->getOption('dry-run') === true;

        // Connect to database
        $dbConnection = Doctrine_Manager::getInstance()->getCurrentConnection();
        $this->_db = $dbConnection->getDbh();

        if ($this->getOption('info') === true) {
            $this->_doInfo();
        } else {
            $this->_doMigrations();
        }
    }

    /**
     * Display diagnostic information
     */
    private function _doInfo()
    {
        if (!$this->_migrationTableExists()) {
            $this->getLog()->info("* The migration table DOES NOT exist.");
        } else {
            $this->getLog()->info("* The migration table exists.");

            $availableMigrations = $this->_getAvailableMigrations(Fisma::getPath('migration'));
            $completedMigrations = $this->_getCompletedMigrations();

            $this->getLog()->info(
                 "* There are "
                 . count($availableMigrations)
                 . " migrations available and your system has completed "
                 . count($completedMigrations)
                 . " migrations."
            );

            $completedSql = "SELECT * FROM migration ORDER BY majorversion, minorversion, tagnumber, startedts";
            $completedMigrations = $this->_db->query($completedSql)->fetchAll();

            $this->getLog()->info("* The following migrations have been executed already:");

            $formatString = "%-10s| %-60s| %-20s| %-20s";
            $this->getLog()->info(sprintf($formatString, "VERSION", "NAME", "STARTED", "COMPLETED"));
            $this->getLog()->info(str_repeat('-', 116));

            foreach ($completedMigrations as $migration) {
                $versionString = "{$migration['majorversion']}.{$migration['minorversion']}.{$migration['tagnumber']}";

                $start = isset($migration['startedts']) ?  $migration['startedts'] : 'BUILT IN';
                $stop = isset($migration['completedts'])
                      ? $migration['completedts']
                      : (isset($migration['startedts']) ? 'DID NOT FINISH' : 'BUILT IN');

                $this->getLog()->info(sprintf($formatString, $versionString, $migration['name'], $start, $stop));
            }
        }
    }

    /**
     * Run the migration execution logic.
     */
    private function _doMigrations()
    {
        // If the migrations system has never been run before, then we need to bootstrap it by creating the migration
        // table.
        if (!$this->_migrationTableExists()) {

            if ($this->_dryRun) {
                $message = "Cannot do dry run mode because migrations have not been bootstrapped."
                         . " (Try running without --dry-run or -d.)";

                throw new Fisma_Zend_Exception_User($message);
            }

            $this->_checkLegacyMigrations();
            $this->_bootstrapMigrationTable();
        }

        $this->_checkIncompleteMigrations();

        // Figure out which migrations need to run (if any)
        $availableMigrations = $this->_getAvailableMigrations(Fisma::getPath('migration'));
        $completedMigrations = $this->_getCompletedMigrations();

        $migrationsToRun = $availableMigrations->diff($completedMigrations);

        $numberMigrations = count($migrationsToRun);

        if (count($migrationsToRun) > 0) {
            $this->getLog()->info("Planning to run $numberMigrations migration" . ($numberMigrations > 1 ? 's' : ''));
            $this->_runMigrationSet($migrationsToRun);
        } else {
            $this->getLog()->info("Migrations are up-to-date.");
        }
    }

    /**
     * Check to see if the migration table exists.
     */
    private function _migrationTableExists()
    {
        $table = $this->_db->query("SHOW TABLES LIKE 'migration'")->fetch(PDO::FETCH_ASSOC);

        return ($table !== FALSE);
    }

    /**
     * Determine if its acceptable to create a migration table.
     *
     * The only reason why we wouldn't create a migration table is if the current system has not been upgraded to the
     * latest migration in the 2.16.x series. Because we are switching migration systems between 2.16 and 2.17, we
     * might miss some migrations if a system is upgraded directly to 2.17.0 from some much older version (such as
     * 2.15.0).
     */
    private function _checkLegacyMigrations()
    {
        $table = $this->_db->query("SHOW TABLES LIKE 'migration_version'")->fetch(PDO::FETCH_ASSOC);

        if ($table === FALSE) {
            throw new Fisma_Zend_Exception_User("Not able to find doctrine's migration_version table.");
        }

        $doctrineVersionQuery = $this->_db->query("SELECT MAX(version) AS maxVersion FROM migration_version");

        $result = $doctrineVersionQuery->fetch(PDO::FETCH_ASSOC);
        if ($result === FALSE) {
            throw new Fisma_Zend_Exception_User("No version number found in doctrine's migration_version table.");
        }

        if ($result['maxVersion'] != self::DOCTRINE_MIGRATION_MAX_VERSION) {
            $message = "You must upgrade to the latest 2.16.x release before upgrading to 2.17.0 or higher.";

            throw new Fisma_Zend_Exception_User($message);
        }
    }

    /**
     * Check whether any migrations were started but not completed.
     *
     * This is an invalid condition and will cause the script to abort.
     */
    private function _checkIncompleteMigrations()
    {
        $result = $this->_db->query("SELECT * FROM migration WHERE startedts IS NOT NULL AND completedts IS NULL");

        $incompleteMigrations = $result->fetchAll(PDO::FETCH_ASSOC);

        if (count($incompleteMigrations) > 0) {
            $message = "Some previous migrations did not complete."
                     . " These must be resolved before migrations can be run again.";

            $message .= "VERSION\tNAME\tSTARTED AT\n-------\t----\t----------";

            foreach ($incompleteMigrations as $migration) {
                $message .= "{$migration['majorversion']}.{$migration['minorversion']}.{$migration['tagnumber']}"
                          . "\t{$migration['name']}\t{$migration['startedts']}";
            }

            throw new Fisma_Zend_Exception_User($message);
        }
    }

    /**
     * Create the new migration table and get rid of the old migration table.
     *
     * The migration table is defined in a special bootstrap migration. This is the one migration that is not handled
     * by _runMigration(), because _runMigration() requires the migration table to already be in place.
     */
    private function _bootstrapMigrationTable()
    {
        $this->getLog()->info("BOOTSTRAP:\nAttempting to bootstrap migrations…");

        $bootstrapMigration = new Application_Migration_021700_BootstrapMigration;
        $bootstrapMigration->setDb($this->_db);
        $bootstrapMigration->migrate();

        // If we get here, then the migration ran without any exceptions. Update the new migration table with
        // information about this first migration.
        $updateMigrationTable = $this->_db->exec(
            "INSERT INTO migration VALUES (NULL, 2, 17, 0, 'BootstrapMigration', NOW(), NOW())"
        );

        if ($updateMigrationTable === FALSE) {
            throw new Fisma_Zend_Exception_Migration("An error occurred while inserting the bootstrap migration.");
        }
    }

    /**
     * Handles all of the logic related to executing and tracking the results of a single migration script.
     *
     * @param Fisma_Migration_Abstract $migration
     */
    private function _runMigration(Fisma_Migration_Abstract $migration)
    {
        $version = $migration->getVersion()->getDottedString();
        $name = $migration->getName();

        $this->getLog()->info("Migrate: $version $name");

        if (!$this->_dryRun) {
            $migrationId = $this->_insertMigration($migration);
            $migration->setLog($this->getLog());
            $migration->migrate();
            $this->_completeMigration($migrationId);
        }
    }

    /**
     * Run a set of migrations.
     *
     * The input to this function is similar to the output of _getAvailableMigrations().
     *
     * @param Fisma_Migration_Set $migrationSet
     */
    private function _runMigrationSet(Fisma_Migration_Set $migrationSet)
    {
        foreach ($migrationSet as $version => $migration) {
            $this->_runMigration($migration);
        }
    }

    /**
     * Insert a migration into the migration table.
     *
     * @param Fisma_Migration_Abstract $migration
     */
    private function _insertMigration(Fisma_Migration_Abstract $migration)
    {
        $version = $migration->getVersion();

        $insertValues = array(
            ':majorVersion' => $version->getMajorVersion(),
            ':minorVersion' => $version->getMinorVersion(),
            ':tagNumber' => $version->getTagNumber(),
            ':name' => $migration->getName(),
        );

        $sql = "INSERT INTO migration (majorversion, minorversion, tagnumber, name, startedts)
                VALUES (:majorVersion, :minorVersion, :tagNumber, :name, NOW())";

        $statement = $this->_db->prepare($sql);
        $result = $statement->execute($insertValues);

        if ($result === FALSE) {
            throw new Fisma_Zend_Exception_Migration('Failed to insert migration (' . get_class($migration) . ')');
        }

        return $this->_db->lastInsertId();
    }

    /**
     * Mark a migration as being completed.
     *
     * @param int $migrationId The ID of the migration which has completed.
     */
    private function _completeMigration($migrationId)
    {
        $sql = "UPDATE migration SET completedts = NOW()";

        $result = $this->_db->exec($sql);

        if ($result === FALSE) {
            throw new Fisma_Zend_Exception_Migration('Not able to complete migration (' . get_class($migration) . ')');
        }
    }

    /**
     * Return all of the migrations found in the specified path.
     *
     * @param string $path
     * @return Fisma_Migration_Set
     */
    private function _getAvailableMigrations($path)
    {
        $migrationSet = new Fisma_Migration_Set;

        // Get the migration versions. (Each version corresponds to a directory.)
        $versionIterator = new FilesystemIterator($path, FilesystemIterator::SKIP_DOTS);

        foreach ($versionIterator as $version) {

            // Validate each migration version
            if (!$version->isDir()) {
                $message = "Unexpected file in migrations directory (" . $version->getPathName() . ")";

                throw new Fisma_Zend_Exception_User($message);
            }

            $versionString = $version->getFilename();

            if (!preg_match('/^\d{6}$/', $versionString)) {
                $message = "Badly named version directory; six numeric digits expected ("
                         . $version->getPathName()
                         . ")";

                throw new Fisma_Zend_Exception_User($message);
            }

            // List migrations for this version
            $migrationIterator = new FilesystemIterator($version->getPathName(), FilesystemIterator::SKIP_DOTS);

            foreach ($migrationIterator as $migration) {
                // Validate migration file name
                if (substr($migration->getFilename(), -4) != ".php") {
                    $message = "Migration is not named with .php extension (" . $migration->getPathName() . ")";

                    throw new Fisma_Zend_Exception_User($message);
                }

                $migrationName = substr($migration->getFilename(), 0, -4);
                $migrationClass = Fisma_Migration_Abstract::CLASS_NAME_PREFIX
                                . "{$versionString}_{$migrationName}";

                $migrationInstance = new $migrationClass();
                $migrationInstance->setDb($this->_db);
                $migrationSet->add($migrationInstance);
            }
        }

        return $migrationSet;
    }

    /**
     * Get a list of all the previously executed migrations.
     *
     * The return value is a list formatted similarly to that of _getAvailableMigrations().
     *
     * @return Fisma_Migration_Set
     */
    private function _getCompletedMigrations()
    {
        // Get migrations data from database.
        $versionSelect = "CONCAT(LPAD(majorversion, 2, '0'), LPAD(minorversion, 2, '0'), LPAD(tagnumber, 2, '0'))";
        $sql = "SELECT $versionSelect AS version, name FROM migration ORDER BY version";

        $result = $this->_db->query($sql);
        $migrations = $result->fetchAll(PDO::FETCH_ASSOC);

        // Package query results as a migration set.
        $migrationSet = new Fisma_Migration_Set;

        foreach ($migrations as $migration) {
            $migrationClass = Fisma_Migration_Abstract::CLASS_NAME_PREFIX
                            . "{$migration['version']}_{$migration['name']}";

            $migrationSet->add(new $migrationClass);
        }

        return $migrationSet;
    }
}
