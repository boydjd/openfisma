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
 * PocTable
 *
 * @uses Fisma_Doctrine_Table
 * @package Model
 * @copyright (c) Endeavor Systems, Inc. 2012 {@link http://www.endeavorsystems.com}
 * @author Andrew Reeves <andrew.reeves@endeavorsystems.com>
 * @license http://www.openfisma.org/content/license GPLv3
 */
class PocTable extends Fisma_Doctrine_Table implements Fisma_Search_Searchable, Fisma_Search_Facetable
{
    /**
     * Implement the interface for Searchable
     */
    public function getSearchableFields()
    {
        return array (
            'username' => array(
                'initiallyVisible' => false,
                'label' => 'Contact Name',
                'sortable' => true,
                'type' => 'text'
            ),
            'title' => array(
                'initiallyVisible' => false,
                'label' => 'Title',
                'sortable' => true,
                'type' => 'text'
            ),
            'nameFirst' => array(
                'initiallyVisible' => true,
                'label' => 'First Name',
                'sortable' => true,
                'type' => 'text'
            ),
            'nameLast' => array(
                'initiallyVisible' => true,
                'label' => 'Last Name',
                'sortable' => true,
                'type' => 'text'
            ),
            'email' => array(
                'initiallyVisible' => true,
                'label' => 'E-Mail Address',
                'sortable' => true,
                'type' => 'text'
            ),
            'phoneOffice' => array(
                'initiallyVisible' => true,
                'label' => 'Office Phone',
                'sortable' => true,
                'type' => 'text'
            ),
            'phoneMobile' => array(
                'initiallyVisible' => false,
                'label' => 'Mobile Phone',
                'sortable' => true,
                'type' => 'text'
            ),
            'createdTs' => array(
                'initiallyVisible' => false,
                'label' => 'Created Date',
                'sortable' => true,
                'type' => 'datetime'
            ),
            'modifiedTs' => array(
                'initiallyVisible' => false,
                'label' => 'Modified Date',
                'sortable' => true,
                'type' => 'datetime'
            ),
            'reportingOrganizationId' => array(
                'initiallyVisible' => true,
                'label' => 'Reporting Organization',
                'join' => array(
                    'model' => 'Organization',
                    'relation' => 'ReportingOrganization',
                    'field' => 'nickname'
                ),
                'sortable' => true,
                'type' => 'text'
            ),
            'lockType' => array(
                'enumValues' => $this->getEnumValues('lockType'),
                'initiallyVisible' => false,
                'label' => 'Account Lock',
                'sortable' => true,
                'type' => 'enum'
            ),
            'accountType' => array(
                'enumValues' => $this->getEnumValues('accountType'),
                'initiallyVisible' => true,
                'label' => 'Account Type',
                'sortable' => true,
                'type' => 'enum'
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
                'label' => 'Account Type',
                'column' => 'accountType',
                'filters' => array(
                    array(
                        'label' => 'Contacts',
                        'operator' => 'enumIs',
                        'operands' => array('Contact')
                    ),
                    array(
                        'label' => 'Users',
                        'operator' => 'enumIs',
                        'operands' => array('User')
                    )
                )
            ),
            array(
                'label' => 'Account Status',
                'column' => 'lockType',
                'filters' => array(
                    array(
                        'label' => 'Active',
                        'operator' => 'enumNotIn',
                        'operands' => $this->getEnumValues('lockType')
                    ),
                    array(
                        'label' => 'Disabled',
                        'operator' => 'enumIs',
                        'operands' => array('manual')
                    ),
                    array(
                        'label' => 'Locked',
                        'operator' => 'enumIn',
                        'operands' => array('expired', 'inactive', 'password')
                    )
                )
            ),
            array(
                'label' => 'Lock Reason',
                'column' => 'lockType',
                'filters' => array(
                    array(
                        'label' => 'Inactivity',
                        'operator' => 'enumIs',
                        'operands' => array('inactive')
                    ),
                    array(
                        'label' => 'Password Expiration',
                        'operator' => 'enumIs',
                        'operands' => array('expired')
                    ),
                    array(
                        'label' => 'Invalid Login Attempts',
                        'operator' => 'enumIs',
                        'operands' => array('password')
                    )
                )
            )
        );
    }
}
