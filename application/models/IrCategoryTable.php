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
 * IrCategoryTable
 *
 * @uses Fisma_Doctrine_Table
 * @package Model
 * @copyright (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @author Josh Boyd <joshua.boyd@endeavorsystems.com>
 * @license http://www.openfisma.org/content/license GPLv3
 */
class IrCategoryTable extends Fisma_Doctrine_Table
{
    /**
     * Returns all incident categories as a nested array, suitable for inserting into an HTML select
     *
     * The outer array contains categories (CAT0, CAT1, etc.) and the inner array contain subcategories.
     *
     * @return array
     */
    public function getCategoriesForSelect()
    {
        $q = Doctrine_Query::create()
             ->select('c.category, c.name, s.id, s.name')
             ->from('IrCategory c')
             ->innerJoin('c.SubCategories s')
             ->orderBy("c.category, s.name")
             ->setHydrationMode(Doctrine::HYDRATE_SCALAR);
        $categories = $q->execute();

        // The categories need to be reformatted to use in a select menu. Zend Form Select has a weird format
        // for select options
        $selectOptions = array();
        $outerCategory = '';

        foreach ($categories as $category) {
            $categoryLabel = "{$category['c_category']} - {$category['c_name']}";
            $selectOptions[$categoryLabel][$category['s_id']] = $category['s_name'];
        }

        return $selectOptions;
    }
}
