<?php
/**
 * Copyright (c) 2012 Endeavor Systems, Inc.
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
 * Migration for allowing IPv6 addresses.
 *
 * @uses Fisma_Migration_Abstract
 * @package Migration
 * @copyright (c) Endeavor Systems, Inc. 2012 {@link http://www.endeavorsystems.com}
 * @author Andrew Reeves <andrew.reeves@endeavorsystems.com>
 * @license http://www.openfisma.org/content/license GPLv3
 */
class Application_Migration_021700_Ipv6 extends Fisma_Migration_Abstract
{
    /**
     * Main migration function called by migration script.
     *
     * @return void
     */
    public function migrate()
    {
        $this->getHelper()->exec(
            "ALTER TABLE `asset` MODIFY COLUMN `addressip` varchar(39) NULL AFTER `source`;"
        );
        $this->getHelper()->exec(
            "ALTER TABLE `poc` MODIFY COLUMN `lastloginip` varchar(39) NULL AFTER `failurecount`;"
        );
    }
}
