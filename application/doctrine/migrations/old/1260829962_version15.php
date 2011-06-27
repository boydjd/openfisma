<?php
/**
 * Copyright (c) 2008 Endeavor Systems, Inc.
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
 * Migrate user audit logs from old table to new table
 * 
 * This has to be done separately from #13 because you can't create a table and populate it in the same migration.
 * 
 * @author     Mark E. Haase
 * @copyright  (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Migration
 */
class Version15 extends Doctrine_Migration_Base
{
    /**
     * Migrate from old table to new table and drop old table
     */
    public function up()
    {
        // Copy data into new table -- this is MySQL proprietary b/c at this point in time we only support MySQL and
        // a portable approach would be more complicated
        $mysql = Doctrine_Manager::getInstance()->getCurrentConnection()->getDbh();
        $query = "INSERT INTO user_audit_log 
                  SELECT
                      null,
                      createdTs,
                      message,
                      userId,
                      userId
                  FROM account_log";
        $mysql->query($query);

        // Drop old table
        $this->dropTable('account_log');
    }
    
    /**
     * This migration can't be reversed because some data is discarded in the up() method
     */
    public function down()
    {
        throw new Doctrine_Migration_IrreversibleMigrationException('Cannot reverse migration #14');
    }
}
