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
 * The index controller implements the default action when no specific request
 * is made.
 *
 * @author     Chris Chen <chriszero@users.sourceforge.net>
 * @copyright  (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Controller
 */
class IndexController extends Fisma_Zend_Controller_Action_Security
{
    /**
     * The default action - show the home page
     * 
     * @return void
     */
    public function indexAction()
    {
        if ($this->_acl->hasArea('dashboard')) {
            $this->_forward('index', 'dashboard');
        } elseif ($this->_acl->hasArea('incident')) {
            $this->_forward('index', 'incident-dashboard');
        } else {
            throw new Fisma_Zend_Exception_User(
                'Your account does not have access to any dashboards. Please contact the'
                . ' administrator to correct your account privileges.'
            );
        }

    }
}
