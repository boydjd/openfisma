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
 * Add new configuration for security control 
 * 
 * @uses Doctrine_Migration_Base
 * @package Migrations 
 * @copyright (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @author Josh Boyd <joshua.boyd@endeavorsystems.com> 
 * @license http://www.openfisma.org/content/license GPLv3
 */
class Version66 extends Doctrine_Migration_Base
{
    public function up()
    {
        // Insert the configuration item which indicates the default catalog.
        // Default is NIST SP 800-53 Rev 2. This differs from the fixure (which is Rev3) because we don't want to 
        // force upgrade pre-existing installations.
        $updateSql = "UPDATE configuration SET default_security_control_catalog_id = 3";
        Doctrine_Manager::connection()->exec($updateSql);
    }

    public function down()
    {
        throw new Doctrine_Migration_IrreversibleMigrationException();
    }
}
