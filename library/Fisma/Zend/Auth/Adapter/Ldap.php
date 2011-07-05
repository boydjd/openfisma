<?php
/**
 * Copyright (c) 2008 Endeavor Systems, Inc.
 *
 * This file is part of OpenFISMA.
 *
 * OpenFISMA is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * OpenFISMA is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with OpenFISMA.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author    Xhorse 
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/mw/index.php?title=License
 * @package   Fisma_Zend_Auth
 */

/**
 * A thin wrapper for the Zend LDAP adapter which returns a User object as its identity
 * 
 * @category   Fisma
 * @copyright  Copyright (c) 2005-2008
 * @license    http://www.openfisma.org/mw/index.php?title=License
 * @package    Fisma_Zend_Auth
 */
class Fisma_Zend_Auth_Adapter_Ldap extends Zend_Auth_Adapter_Ldap
{
    /**
     * Override the authentication to return a Doctrine_Record instead of string as identity.
     */
    public function authenticate()
    {
        $parentResult = parent::authenticate();
        $user = Doctrine::getTable('User')->findOneByUsername($this->getUsername());

        $returnCode = $parentResult->getCode();
        $messages = $parentResult->getMessages();
        
        if ($returnCode == Zend_Auth_Result::FAILURE) {
            $message = 'LDAP Authentication failed with code '
                     . $returnCode
                     . " and messages: \n[LDAP MESSAGE] "
                     . implode("\n[LDAP MESSAGE] ", $messages);

            throw new Fisma_Zend_Exception($message);
        }

        $result = new Zend_Auth_Result(
            $returnCode,
            $user,
            $messages
        );

        return $result;
    }
}
