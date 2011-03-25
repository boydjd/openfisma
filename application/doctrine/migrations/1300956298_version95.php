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
class Version95 extends Doctrine_Migration_Base
{
    /**
     * Update lock type of soft deleted users to locked status,
     * Remove deleted_at column.
     */
    public function up()
    {
        $users = $this->_getSoftDeletedUser();
        foreach ($users as $user) {
            $user->locked = true;
            $user->lockTs = $user->deleted_at;
            $user->lockType = 'manual';
            $user->save();
        }

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

    /**
     * Get the soft deleted user to locked status
     * 
     * @return Doctrine_Collection 
     */
    private function _getSoftDeletedUser()
    {
        return Doctrine_Query::create()
               ->from('User u')
               ->where('u.deleted_at IS NOT NULL')
               ->execute();
    }
}
