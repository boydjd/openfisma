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
 * InformationDataTypeTable
 *
 * @uses Fisma_Doctrine_Table
 * @package Model
 * @copyright (c) Endeavor Systems, Inc. 2013 {@link http://www.endeavorsystems.com}
 * @author Duy K. Bui <duy.bui@endeavorsystems.com>
 * @license http://www.openfisma.org/content/license GPLv3
 */
class InformationDataTypeTable extends Fisma_Doctrine_Table implements Fisma_Search_Searchable, Fisma_Search_Facetable
{
    /**
     * Implement the interface for Searchable
     */
    public function getSearchableFields()
    {
        return array (
            'category' => array(
                'initiallyVisible' => true,
                'sortable' => true,
                'type' => 'text'
            ),
            'subcategory' => array(
                'initiallyVisible' => true,
                'sortable' => true,
                'type' => 'text',
                'formatter' => 'Fisma.TableFormat.recordLink',
                'formatterParameters' => array(
                    'prefix' => '/sa/information-data-type/view/id/'
                )
            ),
            'confidentiality' => array(
                'enumValues' => $this->getEnumValues('confidentiality'),
                'initiallyVisible' => true,
                'sortable' => true,
                'type' => 'enum'
            ),
            'defaultConfidentiality' => array(
                'enumValues' => $this->getEnumValues('defaultConfidentiality'),
                'initiallyVisible' => false,
                'sortable' => true,
                'type' => 'enum'
            ),
            'integrity' => array(
                'enumValues' => $this->getEnumValues('integrity'),
                'initiallyVisible' => true,
                'sortable' => true,
                'type' => 'enum'
            ),
            'defaultIntegrity' => array(
                'enumValues' => $this->getEnumValues('defaultIntegrity'),
                'initiallyVisible' => false,
                'sortable' => true,
                'type' => 'enum'
            ),
            'availability' => array(
                'enumValues' => $this->getEnumValues('availability'),
                'initiallyVisible' => true,
                'sortable' => true,
                'type' => 'enum'
            ),
            'defaultAvailability' => array(
                'enumValues' => $this->getEnumValues('defaultAvailability'),
                'initiallyVisible' => false,
                'sortable' => true,
                'type' => 'enum'
            ),
            'description' => array(
                'initiallyVisible' => false,
                'type' => 'text',
                'sortable' => false
            ),
            'catalog' => array(
                'label' => 'Catalog',
                'type' => 'text',
                'sortable' => true,
                'initiallyVisible' => true,
                'join' => array(
                    'model' => 'InformationDataTypeCatalog',
                    'relation' => 'Catalog',
                    'field' => 'name'
                )
            ),
            'published' => array(
                'label' => 'Visible?',
                'type' => 'boolean',
                'initiallyVisible' => true,
                'sortable' => true,
                'join' => array(
                    'model' => 'InformationDataTypeCatalog',
                    'relation' => 'Catalog',
                    'field' => 'published'
                )
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
     * Returns an array of faceted filters
     *
     * @return array
     */
    public function getFacetedFields()
    {
        return array(
            array(
                'label' => 'Visibility',
                'column' => 'published',
                'filters' => array(
                    array(
                        'label' => 'Published',
                        'operator' => 'booleanYes',
                        'operands' => array()
                    ),
                    array(
                        'label' => 'Unpublished',
                        'operator' => 'booleanNo',
                        'operands' => array()
                    )
                )
            )
        );
    }

    public function listAll()
    {
        return Doctrine_Query::create()->from('InformationDataType')->execute();
    }
}
