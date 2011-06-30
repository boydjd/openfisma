<?php
// @codingStandardsIgnoreFile
/**
 * Copyright (c) 2010 Endeavor Systems, Inc.
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
 * Adds Privileges, Event, Module, and VulnerabilityResolution fixture data.  
 * Admin role is updated with the new privileges.
 *
 * @package Migration
 * @copyright (c) Endeavor Systems, Inc. 2010 {@link http://www.endeavorsystems.com}
 * @author Christian Smith <christian.smith@endeavorsystems.com>
 * @license http://www.openfisma.org/content/license GPLv3
 */
class Version88 extends Doctrine_Migration_Base
{
    /**
     * Add configuration
     */

    public function up()
    {
        //Update Module table
        $module = new Module();
        $module->name = 'Vulnerability Management';
        $module->canBeDisabled = true;
        $module->enabled = false;
        $module->save();

        //Retrieve the ADMIN role ID from the Role table to assign privileges
        $adminRole = Doctrine::getTable('Role')->findOneByNickname('ADMIN');

        $privileges = new Doctrine_Collection('Privilege');

        //Vulnerability areas
        $vulnerabilityArea = new Privilege();
        $vulnerabilityArea->resource = 'area';
        $vulnerabilityArea->action = 'vulnerability';
        $vulnerabilityArea->description = 'Vulnerability Module';  
        $privileges[] = $vulnerabilityArea;

        $vulnerabilityAdminArea = new Privilege();
        $vulnerabilityAdminArea->resource = 'area';
        $vulnerabilityAdminArea->action = 'vulnerability_admin';
        $vulnerabilityAdminArea->description = 'Vulnerability Module Administration';
        $privileges[] = $vulnerabilityAdminArea;

        $vulnerabilityReportArea = new Privilege();
        $vulnerabilityReportArea->resource = 'area';
        $vulnerabilityReportArea->action = 'vulnerability_report';
        $vulnerabilityReportArea->description = 'Vulnerability Module Reports';
        $privileges[] = $vulnerabilityReportArea;

        //Vulnerability CRUD
        $vulnerabilityCreate = new Privilege();
        $vulnerabilityCreate->resource = 'vulnerability';
        $vulnerabilityCreate->action = 'create';
        $vulnerabilityCreate->description = 'Create Vulnerabilities';  
        $privileges[] = $vulnerabilityCreate;

        $vulnerabilityRead = new Privilege();
        $vulnerabilityRead->resource = 'vulnerability';
        $vulnerabilityRead->action = 'read';
        $vulnerabilityRead->description = 'View Vulnerabilities';
        $privileges[] = $vulnerabilityRead;

        $vulnerabilityUpdate = new Privilege();
        $vulnerabilityUpdate->resource = 'vulnerability';
        $vulnerabilityUpdate->action = 'update';
        $vulnerabilityUpdate->description = 'Edit Vulnerabilities';
        $privileges[] = $vulnerabilityUpdate;

        $vulnerabilityDelete = new Privilege();
        $vulnerabilityDelete->resource = 'vulnerability';
        $vulnerabilityDelete->action = 'delete';
        $vulnerabilityDelete->description = 'Delete Vulnerabilities';
        $privileges[] = $vulnerabilityDelete;

        //Vulnerability Comment
        $vulnerabilityComment = new Privilege();
        $vulnerabilityComment->resource = 'vulnerability';
        $vulnerabilityComment->action = 'comment';
        $vulnerabilityComment->description = 'Comment on Vulnerability';
        $vulnerabilityComment->save();
        $privileges[] = $vulnerabilityComment; 
        
        //Asset Unaffiliated 
        $assetUnaffiliated = new Privilege();
        $assetUnaffiliated->resource = 'asset';
        $assetUnaffiliated->action = 'unaffiliated';
        $assetUnaffiliated->description = 'Unaffiliated Assets';
        $assetUnaffiliated->save();
        $privileges[] = $assetUnaffiliated;
        
        //Vulnerability Resolution CRUD
        $vulnerabilityResolutionCreate = new Privilege();
        $vulnerabilityResolutionCreate->resource = 'vulnerability_resolution';
        $vulnerabilityResolutionCreate->action = 'create';
        $vulnerabilityResolutionCreate->description = 'Create Resolution';
        $privileges[] = $vulnerabilityResolutionCreate;
        
        $vulnerabilityResolutionRead = new Privilege();
        $vulnerabilityResolutionRead->resource = 'vulnerability_resolution';
        $vulnerabilityResolutionRead->action = 'read';
        $vulnerabilityResolutionRead->description = 'View Resolution';
        $privileges[] = $vulnerabilityResolutionRead;
        
        $vulnerabilityResolutionUpdate = new Privilege();
        $vulnerabilityResolutionUpdate->resource = 'vulnerability_resolution';
        $vulnerabilityResolutionUpdate->action = 'update';
        $vulnerabilityResolutionUpdate->description = 'Edit Resolution';
        $privileges[] = $vulnerabilityResolutionUpdate;
        
        $vulnerabilityResolutionDelete = new Privilege();
        $vulnerabilityResolutionDelete->resource = 'vulnerability_resolution';
        $vulnerabilityResolutionDelete->action = 'delete';
        $vulnerabilityResolutionDelete->description = 'Delete Resolution';
        $privileges[] = $vulnerabilityResolutionDelete;
        
        $vulnerabilityNotification = new Privilege();
        $vulnerabilityNotification->resource = 'notification';
        $vulnerabilityNotification->action = 'vulnerability';
        $vulnerabilityNotification->description = 'Vulnerability Notifications';
        $privileges[] = $vulnerabilityNotification;

        $privileges->save();

        //Add new privileges to the admin role
        $adminRole->Privileges->merge($privileges);
        $adminRole->save();

        //Update Event Table
        $vulnerabilitiesInjected = new Event();
        $vulnerabilitiesInjected->name = 'VULNERABILITIES_INJECTED';
        $vulnerabilitiesInjected->description = 'Security Scanner Vulnerability Injected';
        $vulnerabilitiesInjected->Privilege = $vulnerabilityNotification;
        $vulnerabilitiesInjected->save();

        $vulnerabilitiesCreated = new Event();
        $vulnerabilitiesCreated->name = 'VULNERABILITIES_CREATED';
        $vulnerabilitiesCreated->description = 'Vulnerability Created';
        $vulnerabilitiesCreated->Privilege = $vulnerabilityNotification;
        $vulnerabilitiesCreated->save();

        //Modify IRCategory CRUD renaming each resource from ir_category to ir_sub_category
        $irCategories = Doctrine::getTable('Privilege')->findByResource('ir_category');
        foreach ($irCategories as $irCategory) {
            $irCategory->resource = 'ir_sub_category';
        }
        $irCategories->save();

        //Add fixture data to VulnerabilityResolutions
        $conn = Doctrine_Manager::connection();
        $createSql = "INSERT INTO vulnerability_resolution (name, description) VALUES ('Technically Infeasable',"
            . "'Technical limitations prevent remediation of the weakness.  Existing security controls mitigate"
            . " the risk to an acceptable level.')";
        $conn->exec($createSql);

        $createSql = "INSERT INTO vulnerability_resolution (name, description) VALUES ('Cost Prohibitive',"
            . "'The system cannot be cost-justified, based on tangible measures of actual dollar costs to implement"
            . " an effective mitigation strategy.  Existing security controls mitigate the risk of an acceptable level.')";
        $conn->exec($createSql);

        $createSql = "INSERT INTO vulnerability_resolution (name, description) VALUES ('Breaks System',"
            . "'The implementation of a remediation would cause a loss or limitation of system functionality. Existing"
            . " security controls mitigate the risk to an acceptable level.')";
        $conn->exec($createSql);
    }

    public function down()
    {
        throw new Doctrine_Migration_IrreversibleMigrationException();
    }
}
