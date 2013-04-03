<?php
/**
 * Copyright (c) 2013 Endeavor Systems, Inc.
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
 * @author     Duy K. Bui <duy.bui@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2013 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Migration
 */
class Application_Migration_030300_SystemAsset extends Fisma_Migration_Abstract
{
    /**
     * Migrate.
     */
    public function migrate()
    {
        $helper = $this->getHelper();

        $this->message('Update privileges');
        //Rename asset_create => asset_manage
        $helper->update(
            'privilege',
            array('action' => 'manage', 'description' => 'Manage System Assets'),
            array('resource' => 'asset', 'action' => 'create')
        );

        //Remove asset_read, asset_update, asset_delete
        $helper->exec("DELETE FROM role_privilege WHERE privilegeid IN (" .
            "SELECT id FROM privilege WHERE action IN ('read', 'update', 'delete') AND resource = 'asset'" .
        ");");
        $helper->exec("DELETE FROM privilege WHERE action IN ('read', 'update', 'delete') AND resource = 'asset';");

        //Re-describe asset_unaffiliated => Manage Unassigned Assets
        $helper->update(
            'privilege',
            array('description' => 'Manage Unassigned Assets'),
            array('resource' => 'asset', 'action' => 'unaffiliated')
        );

        //Update Vulnerability => Asset relationship
        $this->message('Update Vulnerability/Asset relationship');
        $helper->dropForeignKeys('vulnerability', 'vulnerability_assetid_asset_id');
        $helper->dropIndexes('vulnerability', 'assetid_idx');
        $helper->addForeignKey('vulnerability', 'assetid', 'asset', 'id', null, 'ON DELETE CASCADE');
    }
}
