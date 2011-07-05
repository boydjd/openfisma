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
 * Insert incident privileges
 * 
 * @package Migration
 * @copyright (c) Endeavor Systems, Inc. 2010 {@link http://www.endeavorsystems.com}
 * @author Mark E. Haase <mhaase@endeavorsystems.com>
 * @license http://www.openfisma.org/content/license GPLv3
 */
class Version53 extends Doctrine_Migration_Base
{
    /**
     * Insert privileges
     */
    public function up()
    {
        $privileges = new Doctrine_Collection('Privilege');
        
        $incidentArea = new Privilege();
        $incidentArea->resource = 'area';
        $incidentArea->action = 'incident';
        $incidentArea->description = 'Incident Module';
        $privileges[] = $incidentArea;

        $incidentAdminArea = new Privilege();
        $incidentAdminArea->resource = 'area';
        $incidentAdminArea->action = 'incident_admin';
        $incidentAdminArea->description = 'Incident Module Administration';
        $privileges[] = $incidentAdminArea;

        $incidentReportArea = new Privilege();
        $incidentReportArea->resource = 'area';
        $incidentReportArea->action = 'incident_report';
        $incidentReportArea->description = 'Incident Module Reports';
        $privileges[] = $incidentReportArea;

        $incidentRead = new Privilege();
        $incidentRead->resource = 'incident';
        $incidentRead->action = 'read';
        $incidentRead->description = 'View Incident';
        $privileges[] = $incidentRead;

        $incidentCreate = new Privilege();
        $incidentCreate->resource = 'incident';
        $incidentCreate->action = 'create';
        $incidentCreate->description = 'Create Incident';
        $privileges[] = $incidentCreate;

        $incidentUpdate = new Privilege();
        $incidentUpdate->resource = 'incident';
        $incidentUpdate->action = 'update';
        $incidentUpdate->description = 'Update Incident';
        $privileges[] = $incidentUpdate;

        $incidentReject = new Privilege();
        $incidentReject->resource = 'incident';
        $incidentReject->action = 'reject';
        $incidentReject->description = 'Reject Incident';
        $privileges[] = $incidentReject;

        $incidentClassify = new Privilege();
        $incidentClassify->resource = 'incident';
        $incidentClassify->action = 'classify';
        $incidentClassify->description = 'Classify Incident';
        $privileges[] = $incidentClassify;

        $incidentResolve = new Privilege();
        $incidentResolve->resource = 'incident';
        $incidentResolve->action = 'resolve';
        $incidentResolve->description = 'Resolve Incident';
        $privileges[] = $incidentResolve;

        $incidentClose = new Privilege();
        $incidentClose->resource = 'incident';
        $incidentClose->action = 'close';
        $incidentClose->description = 'Close Incident';
        $privileges[] = $incidentClose;        

        $irWorkflowCreate = new Privilege();
        $irWorkflowCreate->resource = 'ir_workflow_def';
        $irWorkflowCreate->action = 'create';
        $irWorkflowCreate->description = 'Create IR Workflows and IR Workflow Steps';
        $privileges[] = $irWorkflowCreate;

        $irWorkflowRead = new Privilege();
        $irWorkflowRead->resource = 'ir_workflow_def';
        $irWorkflowRead->action = 'read';
        $irWorkflowRead->description = 'View IR Workflows and IR Workflow Steps';
        $privileges[] = $irWorkflowRead;

        $irWorkflowUpdate = new Privilege();
        $irWorkflowUpdate->resource = 'ir_workflow_def';
        $irWorkflowUpdate->action = 'update';
        $irWorkflowUpdate->description = 'Update IR Workflows and IR Workflow Steps';
        $privileges[] = $irWorkflowUpdate;

        $irWorkflowDelete = new Privilege();
        $irWorkflowDelete->resource = 'ir_workflow_def';
        $irWorkflowDelete->action = 'delete';
        $irWorkflowDelete->description = 'Delete IR Workflows and IR Workflow Steps';
        $privileges[] = $irWorkflowDelete;

        $irCategoryCreate = new Privilege();
        $irCategoryCreate->resource = 'ir_category';
        $irCategoryCreate->action = 'create';
        $irCategoryCreate->description = 'Create IR Categories and IR Sub Categories';
        $privileges[] = $irCategoryCreate;

        $irCategoryRead = new Privilege();
        $irCategoryRead->resource = 'ir_category';
        $irCategoryRead->action = 'read';
        $irCategoryRead->description = 'View IR Categories and IR Sub Categories';
        $privileges[] = $irCategoryRead;

        $irCategoryUpdate = new Privilege();
        $irCategoryUpdate->resource = 'ir_category';
        $irCategoryUpdate->action = 'update';
        $irCategoryUpdate->description = 'Update IR Categories and IR Sub Categories';
        $privileges[] = $irCategoryUpdate;

        $irCategoryDelete = new Privilege();
        $irCategoryDelete->resource = 'ir_category';
        $irCategoryDelete->action = 'delete';
        $irCategoryDelete->description = 'Delete IR Categories and IR Sub Categories';
        $privileges[] = $irCategoryDelete;

        $privileges->save();
    }

    /**
     * Remove privileges 
     */
    public function down()
    {
        // Delete area privileges
        $areaPrivileges = Doctrine_Query::create()
                          ->delete('Privilege')
                          ->where('resource = ? AND action LIKE ?', array('area', 'incident%'));
        
        $areaPrivileges->execute();
        
        // Delete object privileges
        $resources = array('incident', 'ir_workflow_def', 'ir_category');
        
        $deletePrivileges = Doctrine_Query::create()
                            ->delete('Privilege')
                            ->whereIn('resource', $resources);
                            
        $deletePrivileges->execute();
    }
}
