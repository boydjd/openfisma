<?php
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
 * Migration to add privileges for vulnerabilities
 * 
 * Roles are not updated because as a project policy we should never modify an agency's roles. Roles should be updated
 * manually to be sure that the correct privileges are assigned for the needs of each organization.
 * 
 * @author     Mark E. Haase <mhaase@endeavorystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2010 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Migration
 */
class Version73 extends Doctrine_Migration_Base
{
    /**
     * Add vulnerability privileges
     */
    public function up()
    {
        $conn = Doctrine_Manager::connection();
        
        $conn->beginTransaction();
        
        try {
            // Create new vulnerability privileges
            $privileges = new Doctrine_Collection('Privilege');

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

            $createVulnerability = new Privilege();
            $createVulnerability->resource = 'area';
            $createVulnerability->action = 'vulnerability_report';
            $createVulnerability->description = 'Vulnerability Module Reports';
            $privileges[] = $createVulnerability;

            $createVulnerability = new Privilege();
            $createVulnerability->resource = 'vulnerability';
            $createVulnerability->action = 'create';
            $createVulnerability->description = 'Create Vulnerabilities';
            $privileges[] = $createVulnerability;

            $readVulnerability = new Privilege();
            $readVulnerability->resource = 'vulnerability';
            $readVulnerability->action = 'read';
            $readVulnerability->description = 'View Vulnerabilities';
            $privileges[] = $readVulnerability;

            $updateVulnerability = new Privilege();
            $updateVulnerability->resource = 'vulnerability';
            $updateVulnerability->action = 'update';
            $updateVulnerability->description = 'Edit Vulnerabilities';
            $privileges[] = $updateVulnerability;

            $deleteVulnerability = new Privilege();
            $deleteVulnerability->resource = 'vulnerability';
            $deleteVulnerability->action = 'delete';
            $deleteVulnerability->description = 'Delete Vulnerabilities';
            $privileges[] = $deleteVulnerability;

            $closeVulnerability = new Privilege();
            $closeVulnerability->resource = 'vulnerability';
            $closeVulnerability->action = 'close';
            $closeVulnerability->description = 'Close Vulnerabilities';
            $privileges[] = $closeVulnerability;

            $acceptRiskVulnerability = new Privilege();
            $acceptRiskVulnerability->resource = 'vulnerability';
            $acceptRiskVulnerability->action = 'accept_risk';
            $acceptRiskVulnerability->description = 'Accept Risk On Vulnerabilities';
            $privileges[] = $acceptRiskVulnerability;

            $falsePositiveVulnerability = new Privilege();
            $falsePositiveVulnerability->resource = 'vulnerability';
            $falsePositiveVulnerability->action = 'false_positive';
            $falsePositiveVulnerability->description = 'Set Vulnerabilities To False Positive';
            $privileges[] = $falsePositiveVulnerability;

            $privileges->save();

            $conn->commit();            
        } catch (Doctrine_Exception $e) {
            $conn->rollback();
            
            throw $e;
        }
    }
    
    /**
     * Drop vulnerability privileges
     */
    public function down()
    {
        $conn = Doctrine_Manager::connection();
        
        $conn->beginTransaction();
        
        try {
            $deleteVulnerabilityAreaQuery = Doctrine_Query::create()
                                            ->delete('Privilege')
                                            ->where('resource = ?', 'area')
                                            ->andWhere('action like ?', 'vulnerability%');

            $deleteVulnerabilityAreaQuery->execute();

            $deleteVulnerabilityCrudQuery = Doctrine_Query::create()
                                            ->delete('Privilege')
                                            ->where('resource = ?', 'vulnerability');

            $deleteVulnerabilityCrudQuery->execute();

            $conn->commit();
        } catch (Doctrine_Exception $e) {
            $conn->rollback();
            
            throw $e;
        }
    }
}
