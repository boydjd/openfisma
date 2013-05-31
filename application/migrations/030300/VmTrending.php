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

class Application_Migration_030300_VmTrending extends Fisma_Migration_Abstract
{
    /**
     * Migrate.
     */
    public function migrate()
    {
        $this->getHelper()->createTable(
            'vulnerability_trending',
            array(
                'id'                => 'bigint(20) NOT NULL AUTO_INCREMENT',
                'period'            => 'date DEFAULT NULL',
                'organizationid'    => 'bigint(20) NULL',
                'open'              => 'bigint(20) NULL',
                'closed'            => 'bigint(20) NULL',
                'opencvss'          => 'double DEFAULT NULL'
            ),
            'id'
        );
        $this->getHelper()->addForeignKey('vulnerability_trending', 'organizationid', 'organization', 'id');
    }
}
