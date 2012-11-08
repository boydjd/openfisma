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
 * Add Asset Service Tags as defined in OFJ-1960
 *
 * @author     Duy K. Bui <duy.bui@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2012 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Migration
 */
class Application_Migration_030000_AssetServiceTags extends Fisma_Migration_Abstract
{
    /**
     * Migrate.
     */
    public function migrate()
    {
        $this->message("Adding Asset service tags...");
        $this->getHelper()->addColumn(
            'configuration',
            'asset_service_tags',
            "text",
            'user_editable_profiles'
        );
        $this->getHelper()->exec(
            "UPDATE configuration SET `asset_service_tags` = 'development,testing,pre-production,staging,production';"
        );
        $this->getHelper()->addColumn(
            'asset',
            'servicetag',
            'varchar(255)',
            'addressport'
        );
    }
}

