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
 * Version113 
 * 
 * @uses Doctrine_Migration_Base
 * @package Migration 
 * @copyright (c) Endeavor Systems, Inc. 2011 {@link http://www.endeavorsystems.com}
 * @author Andrew Reeves <andrew.reeves@endeavorsystems.com>
 * @license http://www.openfisma.org/content/license GPLv3
 */
class Version113 extends Doctrine_Migration_Base
{
    /**
     * Update application version.
     */
    public function up()
    {
        $conn = Doctrine_Manager::connection();
        $updateSql = "UPDATE configuration SET app_version = '2.14.2'";
        $conn->exec($updateSql);
    }

    /**
     * Remove configuration 
     */
    public function down()
    {
        $conn = Doctrine_Manager::connection();
        $updateSql = "UPDATE configuration SET app_version = '2.14.1'";
        $conn->exec($updateSql);
    }
}
