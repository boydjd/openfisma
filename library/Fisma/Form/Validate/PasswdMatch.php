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
 * Match the provided old password with the one in form
 * 
 * @author     Ryan Yang <ryan@users.sourceforge.net>
 * @copyright  (c) Endeavor Systems, Inc. 2009 (http://www.endeavorsystems.com)
 * @license    http://www.openfisma.org/content/license
 * @package    Fisma
 * @subpackage Fisma_Form
 * @version    $Id$
 */
class Fisma_Form_Validate_PasswdMatch extends Zend_Validate_Abstract
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
        //it seemed that currentUser() is an old user
        $user = Doctrine::getTable('User')->find(User::currentUser()->id);
        $this->_setValue($pass);

        if ($user->hash($pass) != $user->password) {
            $this->_error(self::PASS_MISMATCH);
            return false;
        }
        return true;
    }
}
