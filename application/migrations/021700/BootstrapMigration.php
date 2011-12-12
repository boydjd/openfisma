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
 * This migration adds the migration table itself and removes the deprecated doctrine migration table.
 *
 * The migration system relies on a migration table, but that migration table can only be added… using a migration
 * system. To break this cylical dependency, this migration is executed by the migration script first in order
 * to bootstrap the migration system.
 *
 * @author     Mark E. Haase <mhaase@endeavorystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2011 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Migration
 */
class Application_Migration_021700_BootstrapMigration extends Fisma_Migration_Abstract
{
    /**
     * Create the new migration table and drop the old migration table.
     */
    public function migrate()
    {
        $columns = array(
            'id' => "bigint(20) NOT NULL AUTO_INCREMENT",
            'majorversion' => "bigint(20) NOT NULL"
                            . " COMMENT 'The major version associated with the migration. E.g. 2 in 2.17.0.'",
            'minorversion' => "bigint(20) NOT NULL"
                            . " COMMENT 'The minor version associated with the migration. E.g. 17 in 2.17.0.'",
            'tagnumber' => "bigint(20) NOT NULL"
                         . " COMMENT 'The tag number associated with the migration. E.g. 0 in 2.17.0.'",
            'name' => "varchar(255) NOT NULL"
                    . " COMMENT 'The name of the migration.'",
            'startedts' => "datetime DEFAULT NULL"
                         . " COMMENT 'The date and time this migration was started, or null if not started.'",
            'completedts' => "datetime DEFAULT NULL"
                           . " COMMENT 'The date and time this migration was completed, or null if not completed.'",
        );

		echo "Creating migration table…\n";
        $this->_createTable('migration', $columns, 'id');

		if ($this->_tableExists('migration_version')) {
			echo "Dropping doctrine migration_version table…\n";
	        $this->_dropTable('migration_version');
		} else {
			echo "Doctrine migration_version table not found… skipping.\n";
		}
    }
}
