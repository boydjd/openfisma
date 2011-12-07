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
 * Fisma_Zend_Controller_Action_Helper_PasswordRequirements 
 * 
 * @uses Zend_Controller_Action_Helper_Abstract
 * @package Fisma_Zend_Controller_Action_Helper 
 * @copyright (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @author Josh Boyd <joshua.boyd@endeavorsystems.com> 
 * @license http://www.openfisma.org/content/license GPLv3
 */
class Fisma_Zend_Controller_Action_Helper_PasswordRequirements extends Zend_Controller_Action_Helper_Abstract
{
    /**
     * Get the password complexity requirements
     *
     * @return array The password requirement messages in array
     */
    public function direct()
    {
        $requirements[] = "Length must be at least "
        . Fisma::configuration()->getConfig('pass_min_length')
        . " characters long.";
        if (Fisma::configuration()->getConfig('pass_uppercase') == 1) {
            $requirements[] = "Must contain at least 1 upper case character (A-Z)";
        }
        if (Fisma::configuration()->getConfig('pass_lowercase') == 1) {
            $requirements[] = "Must contain at least 1 lower case character (a-z)";
        }
        if (Fisma::configuration()->getConfig('pass_numerical') == 1) {
            $requirements[] = "Must contain at least 1 numeric digit (0-9)";
        }
        if (Fisma::configuration()->getConfig('pass_special') == 1) {
            $requirements[] = "Must contain at least 1 special character (!@#$%^&*-=+~`_)";
        }
        return $requirements;
    }
}
