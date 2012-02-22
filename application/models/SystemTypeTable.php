<?php
/**
 * Copyright (c) 2012 Endeavor Systems, Inc.
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
 * The table for system types.
 *
 * @author     Mark E. Haase <mhaase@endeavorystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2012 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Model
 */
class SystemTypeTable extends Fisma_Doctrine_Table implements Fisma_Search_Searchable
{
    public function getSearchableFields()
    {
        return array (
            'name' => array(
                'initiallyVisible' => true,
                'label' => 'Name',
                'sortable' => true,
                'type' => 'text'
            ),
            'nickname' => array(
                'initiallyVisible' => true,
                'label' => 'Nickname',
                'sortable' => true,
                'type' => 'text'
            ),
            'description' => array(
                'initiallyVisible' => true,
                'label' => 'Description',
                'sortable' => false,
                'type' => 'text'
            )
        );
    }

    /**
     * Return a list of fields which are used for access control
     *
     * @return array
     */
    public function getAclFields()
    {
        return array();
    }

    /**
     * Return a dictioanry of types where the keys are the type's primary keys.
     *
     * @return array
     */
    public function getTypeList()
    {
        $types = Doctrine_Query::create()
                 ->select("t.id", "t.name")
                 ->from("SystemType t")
                 ->orderBy("t.name")
                 ->execute()
                 ->toKeyValueArray('id', 'name');

        return $types;
    }
}
