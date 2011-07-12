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
 * Controller plugin to perform forced action enforcement.
 *
 * @uses Zend_Controller_Action_plugin_Abstract
 * @package Fisma_Zend_Controller_Plugin 
 * @copyright (c) Endeavor Systems, Inc. 2011 {@link http://www.endeavorsystems.com}
 * @author mark ma <mark.ma@reyosoft.com>
 * @license http://www.openfisma.org/content/license GPLv3
 */
class Fisma_Zend_Controller_Plugin_ForcedActionHandler extends Zend_Controller_Plugin_Abstract
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
    protected $_namespace = 'OpenFismaForcedAction';

    /**
     * Initialize $_session with a namespace if it is not already initialized
     * @param  string $namespace
     */
    static function setSession($namespace)
    {
        if (!self::$_session) {
            self::$_session = new Zend_Session_Namespace($namespace);
        }
    }

    /**
     * Get all the forced actions stored in a namespace
     * @param  string $id
     * @return $_session->$namespace 
     */
    public function getForcedActions($id)
    {
        $namespace = $this->_namespace . $id;
        self::setSession($namespace);

        // The forced actions are stored as an array in a unique namespace 
        return self::$_session->$namespace;
    }

    /**
     * Add a forced action to an array of namespace of Zend_Session_Namespace
     * @param string $id
     * @param string $forcedAction
     * @param array $forward containing moduleName, controllerName and actionName
     * @return Zend_Controller_Action_Plugin_abstract
     */
    public function registerForcedAction($id, $forcedAction, $forward)
    {
        $namespace = $this->_namespace . $id;
        self::setSession($namespace);

        if (!is_array(self::$_session->$namespace)) {
            self::$_session->$namespace = array();
        }

        self::$_session->{$namespace}[$forcedAction] = $forward;

        return $this;
    }

    /**
     * Remove an forced action from the array of namespace of Zend_Session_Namespace
     * @param string $id
     * @param string $forcedAction
     * @return Zend_Controller_Action_Plugin_abstract
     */
    public function unregisterForcedAction($id, $forcedAction)
    {
        $namespace = $this->_namespace . $id;

        self::setSession($namespace);
        unset(self::$_session->{$namespace}[$forcedAction]);

        return $this;
    }

    /**
     * Check whether an forced action exists in the array of namespace of Zend_Session_Namespace
     * @param string $id
     * @param string $forcedAction
     * @return true if the forced action exists, otherwise false
     */
    public function hasForcedAction($id, $forcedAction)
    {
        $namespace = $this->_namespace . $id;
        self::setSession($namespace);

        if (self::$_session && self::$_session->$namespace) {
            return array_key_exists($forcedAction, self::$_session->$namespace); 
        } 

        return false;
    }

     /**
     * Perform forced action enforcement before dispatching occurs
     * @param Zend_Controller_Request_Abstract $request
     */
    public function preDispatch(Zend_Controller_Request_Abstract $request)
    {  

        $action = $request->getParam('action');
        $currentUser = CurrentUser::getInstance();

        // Does not need to perfrom forced action when action is logout or user has not logined yet
        if ('logout' === $action || is_null($currentUser)) {
            return;
        }
 
        // Get foraced actions array stored at user specific session.
        $forcedActions = $this->getForcedActions($currentUser->id);

        if (!empty($forcedActions)) {
            $forcedAction = array_shift($forcedActions); 

            if ($forcedAction['module'] && $forcedAction['controller'] && $forcedAction['action']) {
                $request->setModuleName($forcedAction['module'])
                        ->setControllerName($forcedAction['controller'])
                        ->setActionName($forcedAction['action']);
                return;
            } else {
                throw new Fisma_Zend_Exception(
                    // @todo Engligh
                    "You miss module name or controller name or action name when you add forced action."
                );
            }
        }
    }
}
