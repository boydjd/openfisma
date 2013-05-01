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
 * InformationDataTypeCatalogTable
 *
 * @uses Fisma_Doctrine_Table
 * @package Model
 * @copyright (c) Endeavor Systems, Inc. 2013 {@link http://www.endeavorsystems.com}
 * @author Duy K. Bui <duy.bui@endeavorsystems.com>
 * @license http://www.openfisma.org/content/license GPLv3
 */
class InformationDataTypeCatalogTable extends Fisma_Doctrine_Table implements Fisma_Search_Searchable
{
    /**
     * Implement the interface for Searchable
     */
    public function getSearchableFields()
    {
        return array (
            'name' => array(
                'initiallyVisible' => true,
                'sortable' => true,
                'type' => 'text',
                'formatter' => 'Fisma.TableFormat.recordLink',
                'formatterParameters' => array(
                    'prefix' => '/sa/information-data-type-catalog/view/id/'
                )
            ),
            'published' => array(
                'type' => 'boolean',
                'initiallyVisible' => true,
                'sortable' => true
            ),
            'description' => array(
                'initiallyVisible' => true,
                'type' => 'text',
                'sortable' => false
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

    public function listAll()
    {
        return Doctrine_Query::create()->from('InformationDataTypeCatalog')->execute();
    }
}
