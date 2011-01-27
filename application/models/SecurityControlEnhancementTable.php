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
 * SecurityControlEnhancementTable 
 * 
 * @uses Fisma_Doctrine_Table
 * @package Model 
 * @copyright (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @author Josh Boyd <joshua.boyd@endeavorsystems.com> 
 * @license http://www.openfisma.org/content/license GPLv3
 */
class SecurityControlEnhancementTable extends Fisma_Doctrine_Table
{
    /**
     * Get enhancements associated with a SecurityAuthorization and SecurityControl.
     *
     * @param integer $said SecurityAuthorization id
     * @param integer $controlId SecurityControl id.
     * @return Doctrine_Query
     */
    public function getSaAndControlQuery($said, $controlId)
    {
        return Doctrine_Query::create()
            ->from('SecurityControlEnhancement sce, sce.SaSecurityControl saSc')
            ->where('saSc.securityAuthorizationId = ?', $said)
            ->andWhere('saSc.securityControlId = ?', $controlId);
    }

    /**
     * Get enhancements of control other than the ones passed in.
     *
     * @param integer $controlId SecurityControl id.
     * @param array $excludeEnhancementIds Ids of SecurityControlEnhancements to be excluded from the results.
     * @return Doctrine_Query
     */
    public function getControlExcludeEnhancementsQuery($controlId, array $excludeEnhancementIds)
    {
        return Doctrine_Query::create()
            ->from('SecurityControlEnhancement sce')
            ->whereNotIn('sce.id', $excludeEnhancementIds)
            ->andWhere('sce.securityControlId = ?', $controlId);
    }
}
