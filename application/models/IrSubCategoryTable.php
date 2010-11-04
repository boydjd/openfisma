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
 * IrSubCategoryTable 
 * 
 * @uses Fisma_Doctrine_Table
 * @package Model 
 * @copyright (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @author Josh Boyd <joshua.boyd@endeavorsystems.com> 
 * @license http://www.openfisma.org/content/license GPLv3
 */
class IrSubCategoryTable extends Fisma_Doctrine_Table implements Fisma_Search_Searchable
{
    /**
     * Implement the interface for Searchable
     */
    public function getSearchableFields()
    {
        return array (
            'categoryCode' => array(
                'initiallyVisible' => true,
                'label' => 'Category Code',
                'join' => array(
                    'model' => 'IrCategory',
                    'relation' => 'Category',
                    'field' => 'category'
                ),
                'sortable' => true,
                'type' => 'text'
            ),
            'categoryName' => array(
                'initiallyVisible' => true,
                'label' => 'Category',
                'join' => array(
                    'model' => 'IrCategory',
                    'relation' => 'Category',
                    'field' => 'name'
                ),
                'sortable' => true,
                'type' => 'text'
            ),
            'name' => array(
                'initiallyVisible' => true,
                'label' => 'Subcategory',
                'sortable' => true,
                'type' => 'text'
            ),
            'workflow' => array(
                'initiallyVisible' => true,
                'label' => 'Workflow Name',
                'join' => array(
                    'model' => 'IrWorkflowDef',
                    'relation' => 'Workflow',
                    'field' => 'name'
                ),
                'sortable' => false,
                'type' => 'text'
            )
        );
    }

    /**
     * This model uses default access control (return empty array)
     *
     * @return array
     */
    public function getAclFields()
    {
        return array();
    }
}
