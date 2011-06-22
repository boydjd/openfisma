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
 * Create area privileges for finding module and system inventory module
 * 
 * This is doing a bunch of things:
 * 1) Create areas for the finding module and system_inventory module, plus report and admin areas for each module 
 *    (6 new areas total)
 * 2) Assign the new finding area to any role which has the 'read finding' privilege
 * 3) Assign the new system inventory area to any role which has the 'read organization' privilege
 * 4) Remove the deprecated report area and assign the finding_report and system_inventory_report areas to any role
 *    which previously had the deprecated report area.
 * 5) Assign the finding_admin and system_inventory_admin areas to any role which currently has the admin area role
 * 6) Remove the deprecated configuration area
 * 
 * @package Migration
 * @copyright (c) Endeavor Systems, Inc. 2010 {@link http://www.endeavorsystems.com}
 * @author Mark E. Haase <mhaase@endeavorsystems.com>
 * @license http://www.openfisma.org/content/license GPLv3
 */
class Version54 extends Doctrine_Migration_Base
{
    /**
     * Insert area privileges for finding and system inventory modules
     */
    public function up()
    {
        $conn = Doctrine_Manager::connection();
        
        $conn->beginTransaction();
        
        try {
            // Create new area privileges
            $privileges = new Doctrine_Collection('Privilege');
        
            $findingArea = new Privilege();
            $findingArea->resource = 'area';
            $findingArea->action = 'finding';
            $findingArea->description = 'Finding Module';
            $privileges[] = $findingArea;

            $findingAreaAdmin = new Privilege();
            $findingAreaAdmin->resource = 'area';
            $findingAreaAdmin->action = 'finding_admin';
            $findingAreaAdmin->description = 'Finding Module Administration';
            $privileges[] = $findingAreaAdmin;

            $findingAreaReports = new Privilege();
            $findingAreaReports->resource = 'area';
            $findingAreaReports->action = 'finding_report';
            $findingAreaReports->description = 'Finding Module Reports';
            $privileges[] = $findingAreaReports;

            $systemInventoryArea = new Privilege();
            $systemInventoryArea->resource = 'area';
            $systemInventoryArea->action = 'system_inventory';
            $systemInventoryArea->description = 'System Inventory Module';
            $privileges[] = $systemInventoryArea;

            $systemInventoryAreaAdmin = new Privilege();
            $systemInventoryAreaAdmin->resource = 'area';
            $systemInventoryAreaAdmin->action = 'system_inventory_admin';
            $systemInventoryAreaAdmin->description = 'System Inventory Module Administration';
            $privileges[] = $systemInventoryAreaAdmin;

            $systemInventoryAreaReports = new Privilege();
            $systemInventoryAreaReports->resource = 'area';
            $systemInventoryAreaReports->action = 'system_inventory_report';
            $systemInventoryAreaReports->description = 'System Inventory Module Reports';
            $privileges[] = $systemInventoryAreaReports;

            $privileges->save();
            
            // Assign finding area privileges to any role which has the read findings privilege
            $readFindingRolesQuery = Doctrine_Query::create()
                                     ->from('Role r')
                                     ->innerJoin('r.Privileges p')
                                     ->where('p.resource = ? AND p.action = ?', array('finding', 'read'));

            $readFindingRoles = $readFindingRolesQuery->execute();
            
            foreach ($readFindingRoles as $readFindingRole) {
                $readFindingRole->link('Privileges', array($findingArea->id));
            }
            
            $readFindingRoles->save();
            
            // Assign system inventory area privileges to any role which has the read organization privilege
            $readOrganizationRolesQuery = Doctrine_Query::create()
                                          ->from('Role r')
                                          ->innerJoin('r.Privileges p')
                                          ->where('p.resource = ? AND p.action = ?', array('organization', 'read'));

            $readOrganizationRoles = $readOrganizationRolesQuery->execute();
            
            foreach ($readOrganizationRoles as $readOrganizationRole) {
                $readOrganizationRole->link('Privileges', array($systemInventoryArea->id));
            }
            
            $readOrganizationRoles->save();
            
            // Convert existing report area to finding_report and system_inventory_report areas
            $reportAreaPrivilegeQuery = Doctrine_Query::create()
                                        ->from('Privilege')
                                        ->where('resource = ? AND action = ?', array('area', 'report'))
                                        ->limit(1);

            $reportAreaPrivilege = $reportAreaPrivilegeQuery->execute();
            $reportAreaPrivilege = $reportAreaPrivilege[0];
            
            $reportAreaRolesQuery = Doctrine_Query::create()
                                    ->from('Role r')
                                    ->innerJoin('r.Privileges p')
                                    ->where('p.resource = ? AND p.action = ?', array('organization', 'read'));

            $reportAreaRoles = $reportAreaRolesQuery->execute();
            
            foreach ($reportAreaRoles as $reportAreaRole) {
                $reportAreaRole->unlink('Privileges', array($reportAreaPrivilege->id));

                $reportAreaRole->link(
                    'Privileges', 
                    array(
                        $findingAreaReports->id,
                        $systemInventoryAreaReports->id
                    )
                );
            }
            
            $reportAreaRoles->save();            

            $reportAreaPrivilege->delete();
            
            // Add finding_admin and system_inventory_admin areas to roles which already have the admin area
            $adminAreaRolesQuery = Doctrine_Query::create()
                                     ->from('Role r')
                                     ->innerJoin('r.Privileges p')
                                     ->where('p.resource = ? AND p.action = ?', array('area', 'admin'));

            $adminAreaRoles = $adminAreaRolesQuery->execute();
            
            foreach ($adminAreaRoles as $adminAreaRole) {
                $adminAreaRole->link(
                    'Privileges', 
                    array(
                        $findingAreaAdmin->id,
                        $systemInventoryAreaAdmin->id
                    )
                );
            }
            
            $adminAreaRoles->save();

            // Remove app configuration privilege
            $appConfigPrivilegeQuery = Doctrine_Query::create()
                                       ->from('Privilege')
                                       ->where('resource = ? AND action = ?', array('area', 'configuration'));
                                        
            $appConfigPrivilege = $appConfigPrivilegeQuery->execute();
            
            $removeReportAreaQuery = Doctrine_Query::create()
                                     ->delete('RolePrivilege')
                                     ->where('privilegeId = ?', array($appConfigPrivilege[0]->id));

            $removeReportAreaQuery->execute();

            $appConfigPrivilege->delete();

            $conn->commit();            
        } catch (Doctrine_Exception $e) {
            $conn->rollback();
            
            throw $e;
        }
    }

    /**
     * Remove area privileges for finding and system inventory modules 
     */
    public function down()
    {
        $conn = Doctrine_Manager::connection();
        
        $conn->beginTransaction();
        
        try {
            // Re-add report area privilege
            $reportArea = new Privilege();
            $reportArea->resource = 'area';
            $reportArea->action = 'report';
            $reportArea->description = 'View Reports';
            $reportArea->save();

            // Re-add configuration area privilege
            $configurationArea = new Privilege();
            $configurationArea->resource = 'area';
            $configurationArea->action = 'configuration';
            $configurationArea->description = 'Configure Application';
            $configurationArea->save();
            
            // Find the area privileges that we want to remove
            $areaPrivilegesParameters = array('area', 'finding%', 'system_inventory%');
            
            $areaPrivilegesQuery = Doctrine_Query::create()
                              ->from('Privilege')
                              ->where('resource = ? AND (action LIKE ? OR action LIKE ?)', $areaPrivilegesParameters);

            $areaPrivileges = $areaPrivilegesQuery->execute();
            
            // Delete any associations those privileges have to roles
            $deleteRolePrivilegesQuery = Doctrine_Query::create()
                                         ->delete('RolePrivilege')
                                         ->whereIn('privilegeid', $areaPrivileges->getPrimaryKeys());

            $deleteRolePrivilegesQuery->execute();

            // Delete the privileges themselves
            $areaPrivileges->delete();
            
            $conn->commit();
        } catch (Doctrine_Exception $e) {
            $conn->rollback();
            
            throw $e;
        }
    }
}
