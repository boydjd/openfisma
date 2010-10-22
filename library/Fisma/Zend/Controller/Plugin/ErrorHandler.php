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
 * Extend the default ErrorHandler plugin so that exceptions are caught in preDispatch() too. 
 * 
 * @uses Zend_Controller_Plugin_ErrorHandler
 * @package Fisma_Zend_Controller_Plugin 
 * @copyright (c) Endeavor Systems, Inc. 2010 {@link http://www.endeavorsystems.com}
 * @author Josh Boyd <joshua.boyd@endeavorsystems.com> 
 * @license http://www.openfisma.org/content/license GPLv3
 */
class Fisma_Zend_Controller_Plugin_ErrorHandler extends Zend_Controller_Plugin_ErrorHandler
{
    /**
     * Unregister default error handler 
     * 
     * @access public
     * @return void
     */
    public function routeStartup()
    {
        Zend_Controller_Front::getInstance()->unregisterPlugin('Zend_Controller_Plugin_ErrorHandler');
    }

    /**
     * Update $request object prior to entering dispatch loop 
     * 
     * @param Zend_Controller_Request_Abstract $request 
     * @access public
     * @return parent::postDispatch 
     */
    public function preDispatch(Zend_Controller_Request_Abstract $request) 
    {
        return $this->postDispatch($request);
    }

    /**
     * Forward to error controller if not previously done 
     * 
     * @param Zend_Controller_Request_Abstract $request 
     * @access public
     * @return void
     */
    public function postDispatch(Zend_Controller_Request_Abstract $request)
    {
        if ($request->getControllerName() != 'error') {
            parent::postDispatch($request);
        }
    }
}
