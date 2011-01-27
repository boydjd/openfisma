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
 * SaSecurityControlTable 
 * 
 * @uses Fisma_Doctrine_Table
 * @package Model 
 * @copyright (c) Endeavor Systems, Inc. 2010 {@link http://www.endeavorsystems.com}
 * @author Andrew Reeves <andrew.reeves@endeavorsystems.com> 
 * @license http://www.openfisma.org/content/license GPLv3
 */
class SaSecurityControlTable extends Fisma_Doctrine_Table
{
    /**
     * Queries by SecurityAuthorization id
     *
     * @param integer $said SecurityAuthorization id
     * @return Doctrine_Query
     */
    public function getSecurityAuthorizationQuery($said)
    {
        return Doctrine_Query::create()
            ->from('SaSecurityControl sasc')
            ->where('sasc.securityAuthorizationId = ?', $said);
    }

    /**
     * Queries for enhancements associated with the SaSecurityControls based on impact level.
     *
     * @param integer $said SecurityAuthorization id
     * @param string $impact Impact level
     * @return Doctrine_Query
     */
    public function getEnhancementsForSaAndImpactQuery($said, $impact)
    {
        // HIGH implies MODERATE and MODERATE implies LOW
        $controlLevels = array();
        switch($impact) {
            case 'HIGH':
                $controlLevels[] = 'HIGH';
            case 'MODERATE':
                $controlLevels[] = 'MODERATE';
            default:
                $controlLevels[] = 'LOW';
        }

        return Doctrine_Query::create()
            ->from('SaSecurityControl sasc')
            ->leftJoin('sasc.SecurityControl sc')
            ->leftJoin('sc.Enhancements sce')
            ->where('sasc.securityAuthorizationId = ?', $said)
            ->andWhereIn('sce.level', $controlLevels);
    }

    /**
     * Get SaSecurityControl from SecurityAuthorization and SecurityControl.
     *
     * @param integer $said SecurityAuthorization id.
     * @param integer $scid SecurityControl id.
     * @return Doctrine_Query
     */
    public function getSaAndControlQuery($said, $scid)
    {
        return Doctrine_Query::create()
            ->from('SaSecurityControl saSc')
            ->leftJoin('saSc.SaSecurityControlEnhancement saSce')
            ->where('saSc.securityAuthorizationId = ?', $said)
            ->andWhere('saSc.securityControlId = ?', $scid);
    }
}
