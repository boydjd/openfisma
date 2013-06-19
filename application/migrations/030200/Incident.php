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

/**
 * @author     Andrew Reeves <andrew.reeves@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2013 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Migration
 */
class Application_Migration_030200_Incident extends Fisma_Migration_Abstract
{
    /**
     * Migrate.
     */
    public function migrate()
    {
        $this->getHelper()->addColumn('incident', 'responsestrategies', 'text NULL', 'impact');
        $this->getHelper()->addColumn('incident', 'denormalizedresponsestrategies', 'text NULL', 'responsestrategies');
        $this->getHelper()->addColumn('incident', 'currentworkflowname', 'text NULL', 'currentworkflowstepid');

        $this->getHelper()->exec(
            'UPDATE incident, ir_incident_workflow ' .
            'SET incident.currentworkflowname = ir_incident_workflow.name ' .
            'WHERE incident.id = ir_incident_workflow.incidentid AND ir_incident_workflow.status = ?',
            array('current')
        );

        $this->getHelper()->dropForeignKeys('incident', 'incident_currentworkflowstepid_ir_incident_workflow_id');
        $this->getHelper()->dropColumn('incident', 'currentworkflowstepid');
    }
}
