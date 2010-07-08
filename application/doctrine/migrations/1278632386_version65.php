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
 * Copy old configuration data into new schema 
 * 
 * @uses Doctrine_Migration_Base
 * @package Migration 
 * @copyright (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @author Josh Boyd <joshua.boyd@endeavorsystems.com> 
 * @license http://www.openfisma.org/content/license GPLv3
 */
class Version65 extends Doctrine_Migration_Base
{
    /**
     * Copy old configuration data into new schema 
     * 
     * @return void
     */
    public function up()
    {
        $pdo = Doctrine_Manager::connection()->getDbh();
        $conn = Doctrine_Manager::connection();

        $createSql = "INSERT INTO configuration (id) VALUE (1)";
        $conn->exec($createSql);

        $oldConfigSql = "SELECT name, value FROM configuration_old";
        $stmt = $pdo->query($oldConfigSql);

        foreach ($stmt as $row) {
            $updateSql = sprintf(
                "UPDATE configuration SET '%s' = '%s'",
                $row['name'], 
                mysql_real_escape_string($row['value'])
            );

            $conn->exec($updateSql); 
        }

        $conn->exec('DROP TABLE configuration_old');
    }

    /**
     * Irreversible 
     * 
     * @return void
     */
    public function down()
    {
        throw new Doctrine_Migration_IrreversibleMigrationException();  
    }
}
