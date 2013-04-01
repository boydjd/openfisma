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

class Application_Migration_030300_Asset extends Fisma_Migration_Abstract
{
    /**
     * Migrate.
     */
    public function migrate()
    {
        $this->getHelper()->createTable(
            'asset_service',
            array(
                'id'          => 'bigint(20) NOT NULL AUTO_INCREMENT',
                'addressport' => 'bigint(20) unsigned DEFAULT NULL',
                'protocol'    => 'text COLLATE utf8_unicode_ci',
                'service'     => 'text COLLATE utf8_unicode_ci',
                'assetid'     => 'bigint(20) NULL',
                'productid'   => 'bigint(20) NULL'
            ),
            'id'
        );
        $this->getHelper()->addColumn('asset', 'addressmac', 'text NULL', 'addressip');
        $this->getHelper()->addForeignKey('asset_service', 'assetid', 'asset', 'id');
        $this->getHelper()->addForeignKey('asset_service', 'productid', 'product', 'id');

        $this->getHelper()->exec(
            'INSERT INTO asset_service (addressport, assetid, productid) ' .
            'SELECT addressport, id, productid ' .
            'FROM asset ' .
            'WHERE addressport IS NOT NULL'
        );

        $this->getHelper()->dropColumn('asset', 'addressport');
    }
}
