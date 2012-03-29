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
 * Migration for the Finding Workflow Administrative phase 1
 *
 * @uses Fisma_Migration_Abstract
 * @package Migration
 * @copyright (c) Endeavor Systems, Inc. 2012 {@link http://www.endeavorsystems.com}
 * @author Duy K. Bui <duy.bui@endeavorsystems.com>
 * @license http://www.openfisma.org/content/license GPLv3
 */
class Application_Migration_021700_FindingWorkflow extends Fisma_Migration_Abstract
{
    /**
     * Turn on "SoftDelete" behavior and add a "description" column to Evaluation model
     *
     * @return void
     */
    public function migrate()
    {
        $this->getHelper()->exec(
            'ALTER TABLE `evaluation` '
            . 'ADD COLUMN `description` text NULL AFTER `nickname`, '
            . 'ADD COLUMN `deleted_at` datetime NULL AFTER `daysuntildue`;'
        );
        $this->getHelper()->exec(
            'ALTER TABLE `event` '
            . 'ADD COLUMN `deleted_at` datetime NULL AFTER `daysuntildue`;'
        );
        $this->getHelper()->exec(
            'ALTER TABLE `privilege` '
            . 'ADD COLUMN `deleted_at` datetime NULL AFTER `daysuntildue`;'
        );
    }
}

