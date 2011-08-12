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
 * Application_Service_Acl
 *
 * @Service
 * @package Application_Service 
 * @copyright (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @author Josh Boyd <joshua.boyd@endeavorsystems.com> 
 * @license http://www.openfisma.org/content/license GPLv3
 */
class Application_Service_Acl
{
    /**
     * _acl 
     * 
     * @var Fisma_Zend_Acl 
     */
    protected $_acl = null;

    /**
     * Return an instance of the current user's ACL 
     * 
     * @return User 
     */
    public function get() 
    {
        if (!$this->_acl) {
            $this->_acl = CurrentUser::getInstance()->acl(); 
        }

        return $this->_acl;
    }

    /**
     * Set an ACL, useful for testing purposes 
     * 
     * @param Fisma_Zend_Acl $acl 
     * @return void
     */
    public function set(Fisma_Zend_Acl $acl = null)
    {
        $this->_acl = $acl;
    }
}
