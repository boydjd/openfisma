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
 * @author    Ryan yang<ryanyang@reyosoft.com>
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/mw/index.php?title=License
 * @version   $Id: PasswdMatch.php -1M 2009-04-15 18:05:32Z (local) $
 * @package   Form
 */

/**
 * Match the provided old password with the one in form
 *
 * @package   Form
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/mw/index.php?title=License
 */
class Fisma_Form_Validator_PasswdMatch extends Zend_Validate_Abstract
{
    const PASS_MISMATCH = 'mismatch'; 

    protected $_messageTemplates = array(self::PASS_MISMATCH=>"is incorrect");

    /** 
     * Validate the password
     * @param string $pass password
     * @return true|false
     */
    public function isValid($pass)
    {
        $user = User::currentUser();
        $this->_setValue($pass);
        if ($user->hash($pass) != $user->password) {
            $this->_error(self::PASS_MISMATCH);
            return false;
        }
        return true;
    }
}
