<?php
/**
 * Copyright (c) 2010 Endeavor Systems, Inc.
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
 * Fix typo
 *
 * @package   Migration
 * @copyright (c) Endeavor Systems, Inc. 2010 {@link http://www.endeavorsystems.com}
 * @author    Ben Zheng <ben.zheng@reyosoft.com>
 * @license   http://www.openfisma.org/content/license GPLv3
 */
class Version90 extends Doctrine_Migration_Base
{
    /**
     * Fix typo 'Mitiation'
     */
    public function up()
    {
        $pdo = Doctrine_Manager::connection()->getDbh();
        $conn = Doctrine_Manager::connection();

        $updateSql = "UPDATE event SET description = 'Mitigation Strategy Revised' where name = 'MITIGATION_REVISE'";
        $conn->exec($updateSql);
    }

    /**
     * Remove the fixing of typo
     */
    public function down()
    {
    }
}
