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
 * SecurityControlTable
 *
 * @uses Fisma_Doctrine_Table
 * @package Model
 * @copyright (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @author Josh Boyd <joshua.boyd@endeavorsystems.com>
 * @license http://www.openfisma.org/content/license GPLv3
 */
class SecurityControlTable extends Fisma_Doctrine_Table implements Fisma_Search_Searchable
{
    /**
     * Implement the interface for Searchable
     */
    public function getSearchableFields()
    {
        return array (
            'code' => array(
                'initiallyVisible' => true,
                'label' => 'Code',
                'sortable' => true,
                'type' => 'text'
            ),
            'name' => array(
                'initiallyVisible' => true,
                'label' => 'Name',
                'sortable' => false,
                'type' => 'text'
            ),
            'class' => array(
                'enumValues' => $this->getEnumValues('class'),
                'initiallyVisible' => true,
                'label' => 'Class',
                'sortable' => true,
                'type' => 'enum'
            ),
            'family' => array(
                'initiallyVisible' => true,
                'label' => 'Family',
                'sortable' => true,
                'type' => 'text'
            ),
            'control' => array(
                'initiallyVisible' => true,
                'label' => 'Control',
                'sortable' => false,
                'type' => 'text'
            ),
            'supplementalGuidance' => array(
                'initiallyVisible' => false,
                'label' => 'Supplemental Guidance',
                'sortable' => false,
                'type' => 'text'
            ),
            'externalReferences' => array(
                'initiallyVisible' => false,
                'label' => 'External References',
                'sortable' => false,
                'type' => 'text'
            ),
            'priorityCode' => array(
                'enumValues' => $this->getEnumValues('priorityCode'),
                'initiallyVisible' => false,
                'label' => 'Priority Code',
                'sortable' => true,
                'type' => 'enum'
            ),
            'catalog' => array(
                'initiallyVisible' => true,
                'label' => 'Catalog',
                'join' => array(
                    'model' => 'SecurityControlCatalog',
                    'relation' => 'Catalog',
                    'field' => 'name'
                ),
                'sortable' => true,
                'type' => 'text'
            )
        );
    }

    /**
     * Implement required interface, but there is no field-level ACL in this model
     *
     * @return array
     */
    public function getAclFields()
    {
        return array();
    }

    /**
     * Return query for security controls filtered by catalog and impact level.
     *
     * @param integer $catalogId SecurityControlCatalog id.
     * @param string $impact Impact level
     * @return Doctrine_Query
     */
    public function getCatalogIdAndImpactQuery($catalogId, $impact)
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
            ->from('SecurityControl sc')
            ->where('sc.securityControlCatalogId = ?', $catalogId)
            ->andWhereIn('sc.controlLevel', $controlLevels);
    }

    /**
     * Get controls associated with a SecurityAuthorization.
     *
     * @param integer $said SecurityAuthorization id
     * @return Doctrine_Query
     */
    public function getSaQuery($said)
    {
        return Doctrine_Query::create()
            ->from('SecurityControl sc, sc.SaSecurityControls saSc, saSc.SecurityAuthorization sa')
            ->where('sa.id = ?', $said);
    }

    /**
     * Get controls in catalog other than the ones passed in.
     *
     * @param integer $catalogId SecurityControlCatalog id.
     * @param array $excludeControlIds Ids of SecurityControls to be excluded from the results.
     * @return Doctrine_Query
     */
    public function getCatalogExcludeControlsQuery($catalogId, array $excludeControlIds)
    {
        return Doctrine_Query::create()
            ->from('SecurityControl sc')
            ->whereNotIn('sc.id', $excludeControlIds)
            ->andWhere('sc.securityControlCatalogId = ?', $catalogId);
    }
}
