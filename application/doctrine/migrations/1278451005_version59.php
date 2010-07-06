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
 * Save configuration data 
 * 
 * @uses Doctrine_Migration_Base
 * @package Migration 
 * @copyright (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @author Josh Boyd <joshua.boyd@endeavorsystems.com> 
 * @license http://www.openfisma.org/content/license GPLv3
 */
class Version59 extends Doctrine_Migration_Base
{
    public function up()
    {
        $conn = Doctrine_Manager::connection();

        $createBackupTableSql = "CREATE TABLE configuration_old LIKE configuration";

        $conn->exec($createBackupTableSql);

        $loadDataSql = "INSERT configuration_old SELECT * FROM configuration";

        $conn->exec($loadDataSql);
    }

    public function down()
    {
        $this->dropTable('configuration_old');
    }
}
