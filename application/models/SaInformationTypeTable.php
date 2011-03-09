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
 * SaInformationTypeTable 
 * 
 * @uses Fisma_Doctrine_Table
 * @copyright (c) Endeavor Systems, Inc. 2010 {@link http://www.endeavorsystems.com}
 * @author Josh Boyd <joshua.boyd@endeavorsystems.com> 
 * @license http://www.openfisma.org/content/license GPLv3
 */
class SaInformationTypeTable extends Fisma_Doctrine_Table implements Fisma_Search_Searchable
{
    /**
     * Return an array of fields (and definitions) which are searchable
     * 
     * @return array
     */
    public function getSearchableFields()
    {
        return array (
            'id' => array(
                'initiallyVisible' => false,
                'label' => 'ID',
                'sortable' => true,
                'type' => 'integer'
            ),
            'name' => array(
                'initiallyVisible' => true,
                'label' => 'Name',
                'sortable' => true,
                'type' => 'text'
            ),
            'description' => array(
                'initiallyVisible' => false,
                'label' => 'Description',
                'sortable' => true,
                'type' => 'text'
            ), 
            'category' => array(
                'initiallyVisible' => true,
                'label' => 'Category',
                'sortable' => true,
                'type' => 'text'
            ),
            'confidentiality' => array(
                'initiallyVisible' => true,
                'label' => 'Confidentiality',
                'sortable' => true,
                'type' => 'text'
            ),
            'integrity' => array(
                'initiallyVisible' => true,
                'label' => 'Integrity',
                'sortable' => true,
                'type' => 'text'
            ),
            'availability' => array(
                'initiallyVisible' => true,
                'label' => 'Availability',
                'sortable' => true,
                'type' => 'text'
            )
        );
    }

    /**
     * Return an array of fields which are used to test access control
     * 
     * Each key is the name of a field and each value is a callback function which provides a list of values to match
     * against that field.
     * 
     * @return array
     */
    public function getAclFields()
    {
        return array();
    }
}
