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
 * Match the provided old password with the one in form
 * 
 * @todo rename this class to a proper name, like Fisma_Zend_Form_Validate_PasswordMatch
 * 
 * @author     Ryan Yang <ryan@users.sourceforge.net>
 * @copyright  (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Fisma
 * @subpackage Fisma_Zend_Form
 */
class Fisma_Zend_Form_Validate_PasswdMatch extends Zend_Validate_Abstract
{
    /**
     * Constant error message key 'mismatch'
     */
    const PASS_MISMATCH = 'mismatch'; 

    /**
     * Error message templates
     * 
     * @var array
     * @todo improve using of the message templates
     */
    protected $_messageTemplates = array(self::PASS_MISMATCH=>"is incorrect");

    /** 
     * Check if the specified password matchs with the password of current user on hash
     * 
     * @param string $pass The specified password to be matched
     * @return boolean True if match, false otherwise
     */
    public function isValid($pass)
    {
        //it seemed that currentUser() is an old user
        $user = Doctrine::getTable('User')->find(CurrentUser::getInstance()->id);
        $this->_setValue($pass);

        if (Fisma_Hash::hash($pass . $user->passwordSalt, $user->hashType) != $user->password) {
            $this->_error(self::PASS_MISMATCH);
            return false;
        }
        return true;
    }
}
