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
 * Assets are IT hardware, software, and documentation components that comprise information systems
 *
 * @author     Mark E. Haase <mhaase@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Model
 */
class Asset extends BaseAsset implements Fisma_Zend_Acl_OrganizationDependency
{
    /**
     * This model uses a combined "manage" privilege in place of usual CRUD
     *
     * @var bool
     */
    const IS_MANAGED = true;

    /**
     * Set custom mutators
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();

        $this->hasMutator('orgSystemId', 'setOrgSystemId');
    }

    /**
     * Implement the required method for Fisma_Zend_Acl_OrganizationDependency
     *
     * @return int
     */
    public function getOrganizationDependencyId()
    {
        return $this->orgSystemId;
    }

    /**
     * setOrgSystemId
     *
     * @param mixed $value
     * @param mixed $load
     * @return void
     */
    public function setOrgSystemId($value, $load = true)
    {
        $this->_set('orgSystemId', $value);

        // if $load is false, early out to avoid creating worthless objects
        if (!$load) {
            return;
        }

        // now deal with the parent organization
        $parentOrganizationId = null;
        if (!empty($value)) {
            $org = Doctrine::getTable('Organization')->find($value);
            $parent = $org->getNode()->getParent();
            while (!empty($parent) && !empty($parent->systemId)) {
                $parent = $parent->getNode()->getParent();
            }
            if (!empty($parent)) {
                $parentOrganizationId = $parent->id;
            }
        }
        $this->_set('denormalizedParentOrganizationId', $parentOrganizationId);
    }
}
