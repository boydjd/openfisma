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
 * This interface indicates that an object has an access control dependency on an organization object
 * 
 * In other words, an object which implements this interface should not be modified unless the user performing the 
 * modification has the privilege to modify objects associated with this object's associated organization.
 * 
 * Example: The Asset class implements Fisma_Zend_Acl_OrganizationDependency by providing a 
 * getOrganizationDependencyId() method. When a user modifies an instance of Asset, such as $asset, the ACL layer
 * verifies that the user has the "edit asset" privilege for $asset's owning organization.
 * 
 * @author     Mark E. Haase
 * @copyright  (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Fisma
 * @subpackage Fisma_Zend_Acl
 */
interface Fisma_Zend_Acl_OrganizationDependency
{
    /**
     * Implementers should return the ID of the associated organization ID object
     * 
     * @return int
     */
    public function getOrganizationDependencyId();    
}
