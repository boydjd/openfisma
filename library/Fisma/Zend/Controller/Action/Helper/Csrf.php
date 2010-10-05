<?php
/**
 * Copyright (c) 2010 Endeavor Systems, Inc.
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
 * Action helper for protecting against CSRF
 *
 * @author Jani Hartikainen <firstname at codeutopia net>
 * @author Josh Boyd <joshua.boyd@endeavorsystems.com>
 */
class Fisma_Zend_Controller_Action_Helper_Csrf extends Zend_Controller_Action_Helper_Abstract
{
    /**
     * @var Fisma_Zend_Controller_Plugin_CsrfProtect
     */
    protected $_plugin = null;

    /**
     * getPlugin 
     * 
     * @return Fisma_Zend_Controller_Plugin_CsrfProtect 
     */
    public function getPlugin()
    {
        if ($this->_plugin == null) {
            $this->_plugin = Zend_Controller_Front::getInstance()->getPlugin(
                'Fisma_Zend_Controller_Plugin_CsrfProtect'
            );
        }

        return $this->_plugin;
    }

    /**
     * setPlugin 
     * 
     * @param Fisma_Zend_Controller_Plugin_CsrfProtect $plugin 
     * @return void
     */
    public function setPlugin(CU_Controller_Plugin_CsrfProtect $plugin)
    {
        $this->_plugin = $plugin;
    }

    /**
     * getToken 
     * 
     * @return string 
     */
    public function getToken()
    {
        return $this->getPlugin()->getToken();
    }

    /**
     * Checks if value is the valid token for the previous request
     * @param string $value
     * @return bool
     */
    public function isValidToken($value)
    {
        return $this->getPlugin()->isValidToken($value);
    }
}
