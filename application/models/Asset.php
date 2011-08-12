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
 * @version    $Id$
 */
class Asset extends BaseAsset implements Fisma_Zend_Acl_OrganizationDependency
{
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
     * preDelete 
     * 
     * @param Doctrine_Event $event 
     * @access public
     * @return void
     */
    public function preDelete($event)
    {
        if (count($this->Vulnerabilities) > 0) {
            throw new Fisma_Zend_Exception_User(
                'This asset cannot be deleted because it has vulnerabilities against it'
            );
        }

    }
}
