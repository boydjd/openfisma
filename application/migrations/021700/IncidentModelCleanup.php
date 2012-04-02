<?php
/**
 * Copyright (c) 2011 Endeavor Systems, Inc.
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
 * This migration supports the sundry changes in OFJ-1662.
 *
 * @author     Mark E. Haase <mhaase@endeavorystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2011 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Migration
 */
class Application_Migration_021700_IncidentModelCleanup extends Fisma_Migration_Abstract
{
    /**
     * See requirements for details
     *
     * @see http://manual.openfisma.org/display/REQCURRENT/OFJ-1622+IRTM+Enhancements
     */
    public function migrate()
    {
        $this->message("Adding POC field to Incident table");

        $this->getHelper()->addColumn('incident', 'pocid', 'bigint(20)', 'organizationid');
        $this->getHelper()->addForeignKey('incident', 'pocid', 'poc', 'id');

        $this->message("Dropping the \"Actions Taken\" column from the Incident table");

        $this->getDb()->exec("UPDATE incident SET additionalInfo = CONCAT(additionalinfo, actionstaken)");
        $this->getHelper()->dropColumn('incident', 'actionstaken');
    }
}
