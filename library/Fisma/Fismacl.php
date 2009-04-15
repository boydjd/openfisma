<?php
/**
 * Copyright (c) 2008 Endeavor Systems, Inc.
 *
 * This file is part of OpenFISMA.
 *
 * OpenFISMA is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * OpenFISMA is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with OpenFISMA.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author    woody
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/mw/index.php?title=License
 * @version   $Id: Fismacl.php -1M 2009-04-15 18:40:38Z (local) $
 * @package    Fisma
 *
 */

/**
 * Fisma_Fismacl
 * get user's role by which to check privilege
 * similar with Zend_Acl
 * 
 * @uses       Zend_Acl
 * @category   local
 * @package    Local
 * @copyright  Copyright (c) 2005-2008
 * @license    http://www.openfisma.org/mw/index.php?title=License
 */
class Fisma_Fismacl extends Zend_Acl
{
    /** 
     * according to the role and decide whether or not to be displayed.
     * @param $resource resources
     * @param $action actions
     * @return bool permit or not
     */
    function isAllowed($resource, $action)
    {
        try {
            $this->requirePrivilege($resource, $action);
            return true;
        } catch(Fisma_Exception_InvalidPrivilege $e) {
            return false;
        }
    }
    
    /**
     * according to the role and decide whether or not to accept the requirement.
     * @param  string $resource
     * @param  string $operation
     */
    public function requirePrivilege($resource, $operation)
    {
        $auth = Zend_Auth::getInstance();
        $me = $auth->getIdentity();
        $roleArray = &$me->roleArray;
        if ( $me->account != "root" ) {
            $acl = Zend_Registry::get('acl');
            foreach ($roleArray as $role) {
                if ( true == parent::isAllowed($role, $resource, $operation) ) {
                    return ;
                }
            }
            throw new Fisma_Exception_InvalidPrivilege("User does not have the privilege for ($resource, $operation)");
        }
    }
}
