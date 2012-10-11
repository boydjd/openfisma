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
 * Add VULNERABILITY_IMPORT event to the event table.
 *
 * @author     Xue-Wei Tang <xue-wei.tang@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2012 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Migration
 */
class Application_Migration_021802_AddEvent extends Fisma_Migration_Abstract
{
    /**
     * Migrate.
     */
    public function migrate()
    {
        $this->message("Replacing VULNERABILITY_CREATE with VULNERABILITY_IMPORT event...");
        $this->getHelper()->exec(
                "UPDATE event
                    SET name = 'VULNERABILITY_IMPORTED'
                       ,description = 'vulnerabilities are imported'
                       ,urlpath = '/vm/vulnerability/list'
                  WHERE name = 'VULNERABILITY_CREATED' "
        );
    }
}

