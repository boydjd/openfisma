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
 * <http://www.gnu.org/licenses/>.
 */

/**
 * Adapte the authentication to Ldap and return a Doctrine Record object as identity
 * 
 * @author     Jim Chen <xhorse@users.sourceforge.net>
 * @copyright  (c) Endeavor Systems, Inc. 2009 (http://www.endeavorsystems.com)
 * @license    http://www.openfisma.org/content/license
 * @package    Fisma
 * @subpackage Fisma_Auth
 * @version    $Id$
 */
class Fisma_Auth_Adapter_Ldap extends Zend_Auth_Adapter_Ldap
{
    protected $_identity = null;
    /**
     * Constructor
     *
     * @param  array  $options  An array of arrays of Zend_Ldap options
     * @param  Doctrine_Record $identity The identity of the account being authenticated
     * @param  string $password The password of the account being authenticated
     * @return void
     */
    public function __construct(array $options, 
                                Doctrine_Record $identity,
                                $password)
    {
        $this->_identity = $identity;
        parent::__construct($options, $identity->username, $password);
    }

    /**
     * Override the authentication to return a Doctrine_Record instead of string as identity.
     */
    public function authenticate()
    {
        $result = parent::authenticate();
        return new Zend_Auth_Result(
            $result->getCode(),
            $this->_identity ,
            $result->getMessages()
        );
    }

    public function setUsername($username)
    {
        $this->_identity = Doctrine::getTable('User')->findOneByUsername($username);
        $this->_username = (string) $this->_identity->username;
        return $this;
    }
}
