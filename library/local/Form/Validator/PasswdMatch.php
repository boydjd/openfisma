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
 * @version   $Id$
 * @package   Form
 */

/**
 * Match the provided old password with the one in form
 *
 * @package   Form
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/mw/index.php?title=License
 */
class Form_Validator_PasswdMatch extends Zend_Validate_Abstract
{
    const PASS_MISMATCH = 'mismatch'; 

    protected $_messageTemplates = array(self::PASS_MISMATCH=>"is incorrect");

    /**
     * The user row object
     */
    protected $_userRow = null;

    public function __construct($user)
    {
        assert($user instanceof Zend_Db_Table_Row_Abstract);
        $this->_userRow = $user;
    }

    public function isValid($pass)
    {
        $user = $this->_userRow->getTable();
        $this->_setValue($pass);
        if ($user->digest($pass, $this->_userRow->account) != $this->_userRow->password) {
            $this->_error(self::PASS_MISMATCH);
            return false;
        }
        return true;
    }
}
