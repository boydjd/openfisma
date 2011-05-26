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
 * Action helper for performing forced action enforcement 
 *
 * @uses       Zend_Controller_Action_Helper_Abstract
 * @package    Fisma_Zend_Controller_Action_Helper
 * @copyright  (c) Endeavor Systems, Inc. 2011 {@link http://www.endeavorsystems.com}
 * @author     Mark Ma <mark.ma@reyosoft.com>
 * @license    http://www.openfisma.org/content/license GPLv3
 */
class Fisma_Zend_Controller_Action_Helper_ForcedAction extends Zend_Controller_Action_Helper_Abstract
{
    /**
     * @var Fisma_Zend_Controller_Plugin_ForcedActionHandler
     */
    static protected $_plugin = null;

    /**
     * getPlugin 
     * 
     * @return Fisma_Zend_Controller_Plugin_ForcedActionHandler 
     */
    public function getPlugin()
    {
        if (self::$_plugin == null) {
            self::$_plugin = Zend_Controller_Front::getInstance()->getPlugin(
                'Fisma_Zend_Controller_Plugin_ForcedActionHandler'
            );
        }

        return self::$_plugin;
    }

    /**
     * Add a forced action to a user's session
     * 
     * @param string id
     * @param string name of forced action
     * @param array contains module name, controller name, action action for the forced action
     * @return void
     */
    public function registerForcedAction($id, $forcedAction, $forward)
    {
       $this->getPlugin()->registerForcedAction($id, $forcedAction, $forward);
    }

    /**
     * Remove a forced action from a user's session
     * 
     * @param string id
     * @param string name of forced action
     * @return void
     */
    public function unregisterForcedAction($id, $forcedAction)
    {
       $this->getPlugin()->unregisterForcedAction($id, $forcedAction);
    }

    /**
     * Check whether an accesss control exists in a user's session
     * 
     * @param string id
     * @param string name of forced action
     * @return true if exists, otherwise false
     */
    public function hasForcedAction($id, $forcedAction)
    {
        return $this->getPlugin()->hasForcedAction($id, $forcedAction);
    }
}
