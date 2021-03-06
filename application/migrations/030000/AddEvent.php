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
 * Add MITIGATION_REJECTED and EVIDENCE_REJECTED events.
 *
 * @author     Duy K. Bui <duy.bui@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2012 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Migration
 */
class Application_Migration_030000_AddEvent extends Fisma_Migration_Abstract
{
    /**
     * Migrate.
     */
    public function migrate()
    {
        $this->message("Adding finding rejections event...");
        $privilege = $this->getHelper()->query(
            "SELECT id from privilege WHERE resource = ? and action = ? ",
            array('notification', 'finding')
        );
        $this->getHelper()->insert(
            'event',
            array(
                'name' => 'MITIGATION_REJECTED',
                'description' => 'a finding Mitigation Strategy is rejected',
                'privilegeid' => $privilege[0]->id,
                'category' => 'finding',
                'urlPath' => '/finding/remediation/view/id/'
            )
        );
        $this->getHelper()->insert(
            'event',
            array(
                'name' => 'EVIDENCE_REJECTED',
                'description' => 'a finding Evidence Package is rejected',
                'privilegeid' => $privilege[0]->id,
                'category' => 'finding',
                'urlPath' => '/finding/remediation/view/id/'
            )
        );
    }
}

