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
 * Singleton class representing the current, authenticated user 
 * 
 * @package Model 
 * @copyright (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @author Josh Boyd <joshua.boyd@endeavorsystems.com> 
 * @license http://www.openfisma.org/content/license GPLv3
 */
class CurrentUser
{
    /**
     * Holds an instance of the class
     * @var User
     */
    private static $_instance = null;

    /**
     * Private constructor prevents direct instantiation of the class 
     * 
     * @return void
     */
    private function __construct()
    {
    }

    /**
     * Prevent cloning of the singleton instance 
     * 
     * @return void
     */
    private final function __clone()
    {
    }

    /**
     * Returns an object which represents the current, authenticated user
     * 
     * In certain contexts there is no current user, such as before login or when running from a command line. In those
     * cases, this method returns null.
     * 
     * @return User The current authenticated user or null if none exists
     */
    public static function getInstance()
    {
        if (!isset(self::$_instance)) {
            if (Fisma::RUN_MODE_COMMAND_LINE != Fisma::mode() && Fisma::RUN_MODE_TEST != Fisma::mode()) {
                $auth = Zend_Auth::getInstance();
                $auth->setStorage(new Fisma_Zend_Auth_Storage_Session());
                self::$_instance = $auth->getIdentity();
            }
        }

        return self::$_instance;
    }

    /**
     * Set the instance of CurrentUser, used primarily for testing only
     * 
     * @param mixed $user expects null (reset) or a User-like object 
     * 
     * @return void
     */
    public static function setInstance($user)
    {
        self::$_instance = $user;
    }
}
