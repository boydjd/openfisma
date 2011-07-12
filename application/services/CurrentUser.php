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
 * Application_Service_CurrentUser 
 *
 * @Service
 * @package Application_Service 
 * @copyright (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @author Josh Boyd <joshua.boyd@endeavorsystems.com> 
 * @license http://www.openfisma.org/content/license GPLv3
 */
class Application_Service_CurrentUser
{
    protected $_user = null;

    /**
     * Return an instance of the current user 
     * 
     * @return User 
     */
    public function get()
    {
        if (!$this->_user) {
            $this->_user = CurrentUser::getInstance(); 
        }

        return $this->_user;
    }

    /**
     * Set the current user, userful for testing purposes. 
     * 
     * @param User $user 
     * @return void
     */
    public function set(User $user)
    {
        $this->_user = $user;
    }
}
