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
 * SaSecuirityControlEnhancementTable 
 * 
 * @uses Fisma_Doctrine_Table
 * @package Model 
 * @copyright (c) Endeavor Systems, Inc. 2010 {@link http://www.endeavorsystems.com}
 * Andrew Reeves <andrew.reeves@endeavorsystems.com> 
 * @license http://www.openfisma.org/content/license GPLv3
 */
class SaSecurityControlEnhancementTable extends Fisma_Doctrine_Table
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
            ->from('SaSecurityControlEnhancement sasce, sasce.SaSecurityControl sasc')
            ->where('sasc.securityAuthorizationId = ?', $said);
    }

    /**
     * Get SaSecurityControlEnhancement from SecurityAuthorization and SecurityControlEnhancement.
     *
     * @param integer $said SecurityAuthorization id.
     * @param integer $sceid SecurityControlEnhancement id.
     * @return Doctrine_Query
     */
    public function getSaAndEnhancementQuery($said, $sceid)
    {
        return Doctrine_Query::create()
            ->from('SaSecurityControlEnhancement saSce')
            ->innerJoin('saSce.SaSecurityControl saSc')
            ->where('saSc.securityAuthorizationId = ?', $said)
            ->andWhere('saSce.securityControlEnhancementId = ?', $sceid);
    }
}
