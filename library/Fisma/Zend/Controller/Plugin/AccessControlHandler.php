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
 * Controller plugin to perform access control enforcement.
 *
 * @uses Zend_Controller_Action_Helper_Abstract
 * @package Fisma_Zend_Controller_Plugin 
 * @copyright (c) Endeavor Systems, Inc. 2011 {@link http://www.endeavorsystems.com}
 * @author mark ma <mark.ma@reyosoft.com>
 * @license http://www.openfisma.org/content/license GPLv3
 */
class Fisma_Zend_Controller_Plugin_AccessControlHandler extends Zend_Controller_Plugin_Abstract
{
    /**
     * Session storage
     * @var Zend_Session_Namespace
     */
    static protected $_session = null;

    /**
     * The name space of Zend_Session_Namespace  
     * @var string
     */
    protected $_nameSpace = 'OpenfismaAccessControl';

    /**
     * Return the nameSpace
     * @return string
     */
    public function getNameSpace()
    {
        return $this->_nameSpace;
    }

    /**
     * Initialize $_session with a nameSpace if it is not already initialized
     * @param  string $nameSpace
     */
    static function setSession($nameSpace)
    {
        if (!self::$_session) {
            self::$_session = new Zend_Session_Namespace($nameSpace);
        }
    }

    /**
     * Get all the accessControls stored in a nameSpace
     * @param  string $nameSpace
     * @return $_session->{$nameSpace} 
     */
    public function getAccessControls($nameSpace)
    {
        self::setSession($nameSpace);
        return self::$_session->{$nameSpace};
    }

    /**
     * Add a access control to an array of nameSpace of Zend_Session_Namespace
     * @param string $nameSpace
     * @param string $accessControl
     * @param array $forward containing moduleName, controllerName and actionName
     * @return Zend_Controller_Action_Plugin_abstract
     */
    public function registerAccessControl($nameSpace, $accessControl, $forward)
    {
        self::setSession($nameSpace);

        if (!is_array(self::$_session->{$nameSpace})) {
            self::$_session->{$nameSpace} = array();
        }

        self::$_session->{$nameSpace}[$accessControl] = $forward;

        return $this;
    }

    /**
     * Remove an access control from the array of nameSpace of Zend_Session_Namespace
     * @param string $nameSpace
     * @param string $accessControl
     * @return Zend_Controller_Action_Plugin_abstract
     */
    public function unRegistAccessControl($nameSpace, $accessControl)
    {
        self::setSession($nameSpace);
        unset(self::$_session->{$nameSpace}[$accessControl]);

        return $this;
    }

    /**
     * Check whether an access control exists in the array of nameSpace of Zend_Session_Namespace
     * @param string $nameSpace
     * @param string $accessControl
     * @return true if the access control exists, otherwise false
     */
    public function hasAccessControl($nameSpace, $accessControl)
    {
        self::setSession($nameSpace);
        if (self::$_session && self::$_session->{$nameSpace}) {
            return array_key_exists($accessControl, self::$_session->{$nameSpace}); 
        } 

        return false;
    }

     /**
     * Perform access control enforcement before dispatching occurs
     * @param Zend_Controller_Request_Abstract $request
     */
    public function preDispatch(Zend_Controller_Request_Abstract $request)
    {  

        $action = $request->getParam('action');
        $currentUser = CurrentUser::getInstance();

        // does not need to perfrom access control when action is logout or user has not logined yet
        if ('logout' === $action || is_null($currentUser)) {
            return;
        }
 
        $nameSpace = $this->_nameSpace . $currentUser->id; 
        $accessControls = $this->getAccessControls($nameSpace);

        if (!empty($accessControls)) {
            $accessControl = array_shift($accessControls); 

            if ($accessControl['module'] && $accessControl['controller'] && $accessControl['action']) {
                $request->setModuleName($accessControl['module'])
                        ->setControllerName($accessControl['controller'])
                        ->setActionName($accessControl['action']);
                return;
            }
        }
    }
}
