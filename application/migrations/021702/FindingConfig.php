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
 * Make Finding's on-time settings for NEW & DRAFT statuses part of the Configuration table
 *
 * @author     Duy K. Bui <duy.bui@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2012 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Migration
 * @tickets    OFJ-1779
 */
class Application_Migration_021702_FindingConfig extends Fisma_Migration_Abstract
{
    /**
     * Add 2 unsigned integer columns
     */
    public function migrate()
    {
        $this->message("Adding threat_type & use_legacy_finding_key fields to Configuration table");

        $threatOption = "enum('threat_level','residual_risk') DEFAULT 'threat_level' NOT NULL";
        $legacyOption = "tinyint(1) DEFAULT 1 NOT NULL";

        $this->getHelper()->addColumn('configuration', 'threat_type', $threatOption, 'host_url');
        $this->getHelper()->addColumn('configuration', 'use_legacy_finding_key', $legacyOption, 'threat_type');
    }
}
