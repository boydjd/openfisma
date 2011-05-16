<?php
/**
 * Copyright (c) 2011 Endeavor Systems, Inc.
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
 * Action helper for performing access control enforcement
 *
 * @author     Mark Ma <mark.ma@reyosoft.com>
 * @copyright  (c) Endeavor Systems, Inc. 2010 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Fisma
 * @subpackage Fisma_Zend_Controller
 * @version    $Id $
 */
class Fisma_Zend_Controller_Action_Helper_AccessControl extends Zend_Controller_Action_Helper_Abstract
{
    /**
     * @var Fisma_Zend_Controller_Plugin_AccessControl
     */
    static protected $_plugin = null;

    /**
     * getPlugin 
     * 
     * @return Fisma_Zend_Controller_Plugin_AccessControl 
     */
    public function getPlugin()
    {
        if (self::$_plugin == null) {
            self::$_plugin = Zend_Controller_Front::getInstance()->getPlugin(
                'Fisma_Zend_Controller_Plugin_AccessControlHandler'
            );
        }

        return self::$_plugin;
    }

    /**
     * Add an access control to a user's session
     * 
     * @param int user id
     * @param string name of access control
     * @param array contains module name, controller name, action action for the access control
     * @return void
     */
    public function registerAccessControl($id, $accessControl,$forward)
    {
        // combine userid with nameSpace in the plugin to form a unique session name space for a user.
        $nameSpace = $this->getPlugin()->getNameSpace() . $id; 
        $this->getPlugin()->registerAccessControl($nameSpace, $accessControl, $forward);
    }

    /**
     * Remove an access control from a user's session
     * 
     * @param int user id
     * @param string name of access control
     * @return void
     */
    public function unRegisterAccessControl($id, $accessControl)
    {
        // combine userid with nameSpace in the plugin to form a unique session name space for a user.
        $nameSpace = $this->getPlugin()->getNameSpace() . $id; 
        $this->getPlugin()->unRegistAccessControl($nameSpace, $accessControl);
    }

    /**
     * Check whether an accesss control exists in a user's session
     * 
     * @param int user id
     * @param string name of access control
     * @return true if exists, otherwise false
     */
    public function hasAccessControl($id, $accessControl)
    {
        // combine userid with nameSpace in the plugin to form a unique session name space for a user.
        $nameSpace = $this->getPlugin()->getNameSpace() . $id; 

        return $this->getPlugin()->hasAccessControl($nameSpace, $accessControl);
    }
}
