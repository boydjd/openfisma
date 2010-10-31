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
 * Fisma_Zend_Controller_Plugin_Configuration
 * 
 * A controller plugin for making sure that the application is properly configured 
 * 
 * @uses Zend_Controller_Plugin_Abstract
 * @package Fisma_Zend_Controller_Plugin 
 * @copyright (c) Endeavor Systems, Inc. 2010 {@link http://www.endeavorsystems.com}
 * @author Josh Boyd <joshua.boyd@endeavorsystems.com> 
 * @license http://www.openfisma.org/content/license GPLv3
 */
class Fisma_Zend_Controller_Plugin_Configuration extends Zend_Controller_Plugin_Abstract
{
    /**
     * preDispatch 
     * 
     * @param Zend_Controller_Request_Abstract $request 
     * @access public
     * @return void
     */
    public function preDispatch(Zend_Controller_Request_Abstract $request)
    {
        $this->_secureCookieCheck();
    }

    /**
     * Check that secure cookies are enabled if the request is secure 
     * 
     * @access private
     * @return void
     */
    private function _secureCookieCheck()
    {
        if (Zend_Session::getOptions('cookie_secure') && !$this->_request->isSecure()) {
            throw new Zend_Auth_Exception(
                "You must access this application via HTTPS, since secure cookies are enabled."
            );
        }
    }
}
