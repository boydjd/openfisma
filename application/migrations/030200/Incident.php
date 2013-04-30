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
        
        // additional modifications after removal of `ir_step` and `ir_incident_workflow` tables
        $this->getHelper()->addColumn('incident', 'isresolved',"tinyint(1) NOT NULL DEFAULT '0' COMMENT 'The current status.' ", 'closedts');
        $this->getHelper()->query('ALTER TABLE `incident` CHANGE `currentworkflowname` `completedsteps` text ');
        $this->getHelper()->addColumn('incident', 'currentstepid', "bigint(20) DEFAULT NULL COMMENT 'Foreign key to the current workflow step' ", 'currentworkflowname');
        $this->getHelper()->addColumn('incident', 'nextduedate', " date DEFAULT NULL COMMENT 'The deadline date for the next action that needs to be taken on this finding. After this date, the finding is considered to be overdue.' ", 'currentstepid');
        $this->getHelper()->query('ALTER TABLE `incident` ADD KEY `currentstepid_idx` (`currentstepid`)');
        $this->getHelper()->query('ALTER TABLE `incident` ADD CONSTRAINT `incident_currentstepid_workflow_step_id` FOREIGN KEY (`currentstepid`) REFERENCES `workflow_step` (`id`) ');

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
