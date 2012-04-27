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
 */
class Application_Migration_021800_FindingWorkflowExtended extends Fisma_Migration_Abstract
{
    /**
     * Add 2 unsigned integer columns
     */
    public function migrate()
    {
        $this->message("Adding finding_new_due & finding_draft_due fields to Configuration table");

        $columnOption = "smallint(5) DEFAULT 30 UNSIGNED NOT NULL";

        $this->getHelper()->addColumn('configuration', 'finding_new_due', $columnOption, 'default_bureau_id');
        $this->getHelper()->addColumn('configuration', 'finding_draft_due', $columnOption, 'finding_new_due');
    }
}
