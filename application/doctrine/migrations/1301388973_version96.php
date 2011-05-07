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
 * Remove SoftDelete behavior and update soft deleted users to locked status.
 *
 * @package   Migration
 * @copyright (c) Endeavor Systems, Inc. 2011 {@link http://www.endeavorsystems.com}
 * @author    Ben Zheng <ben.zheng@reyosoft.com>
 * @license   http://www.openfisma.org/content/license GPLv3
 */
class Version96 extends Doctrine_Migration_Base
{
    /**
     * Update lock type of soft deleted users to locked status,
     * Remove deleted_at column from user table.
     */
    public function up()
    {
        // Update soft deleted users to locked status.
        $conn = Doctrine_Manager::connection();
        $updateSql = "UPDATE user SET locked = true, lockts = deleted_at, locktype = 'manual' WHERE "
                   . "deleted_at IS NOT NULL";
        $conn->exec($updateSql);

        // Insert a comment into the user_comment table when user is softdeleted.
        $comment = 'Account converted from soft delete to manual lock during migration';
        $createSql = "INSERT INTO user_comment
                      SELECT
                          null,
                          deleted_at,
                          '$comment',
                          id,
                          id
                      FROM user WHERE deleted_at IS NOT NULL";
        $conn->exec($createSql);

        // Remove deleted_at column
        $this->removeColumn('user', 'deleted_at');
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
