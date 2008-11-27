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
 * @author    Woody Lee <woody712@users.sourceforge.net>
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/mw/index.php?title=License
 * @version   $Id$
 */

/**
 * @see Zend_Controller_Action_Helper_Abstract
 */
require_once 'Zend/View/Helper/Abstract.php';

/**
 * The helper to verify that if the ACL permit certain of action on resources.
 */
class View_Helper_IsAllow extends Zend_View_Helper_Abstract
{
    /**
     * Verify if the action on the resource is permited.
     * 
     * @param $resource The object to be accessed
     * @param $action   The action to be taken
     * @return boolean
     */
    function isAllow($resource, $action)
    {
        $auth = Zend_Auth::getInstance();
        $me = $auth->getIdentity();
        if ( $me->account == "root" ) {
            return true;
        }
        $roleArray = &$me->roleArray;
        $acl = Zend_Registry::get('acl');
        try{
            foreach ($roleArray as $role) {
                if ( true == $acl->isAllowed($role, $resource, $action) ) {
                    return true;
                }
            }
        } catch(Zend_Acl_Exception $e){
            /// @todo acl log information
        }
        return false;
    }
}
