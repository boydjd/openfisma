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
 * @version   $Id:$
 */

/**
 * give the password validator
 *
 * @package   Form
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/mw/index.php?title=License
 */
class Form_Validator_Password extends Zend_Validate_Abstract
{
    const PASS_MIN = "pass_min";
    const PASS_MAX = "pass_max";
    const PASS_UPPERCASE = "pass_uppercase";
    const PASS_LOWERCASE = "pass_lowercase";
    const PASS_NUMERICAL = "pass_numerical";
    const PASS_SPECIAL   = "pass_special";

        
    public function isValid($pass)
    {
        $this->_messageTemplates = array(
            self::PASS_MIN => 'must be at least '.Config_Fisma::readSysConfig("pass_min").' characters long',
            self::PASS_MAX=>'must not be more than '.Config_Fisma::readSysConfig("pass_max").' characters long',
            self::PASS_UPPERCASE=>'must contain at least 1 uppercase letter (A-Z)',
            self::PASS_LOWERCASE=>'must contain at least 1 lowercase letter (a-z)',
            self::PASS_NUMERICAL=>'must contain at least 1 numeric digit (0-9)',
            self::PASS_SPECIAL  =>'must contain at least 1 special character (!@#$%^&*-=+~`_)'
        );

        $errno = 0;
        $this->_setValue($pass);
        if (strlen($pass) < Config_Fisma::readSysConfig('pass_min')) {
            $errno++;
            $this->_error(self::PASS_MIN);
        }
        if (strlen($pass) > Config_Fisma::readSysConfig('pass_max')) {
            $errno++;
            $this->_error(self::PASS_MAX);
        }
        if (true == Config_Fisma::readSysConfig('pass_uppercase')) {
            if ( false == preg_match("/[A-Z]+/", $pass)) {
                $errno++;
                $this->_error(self::PASS_UPPERCASE);
            }
        }
        if (true == Config_Fisma::readSysConfig('pass_lowercase')) {
            if ( false == preg_match("/[a-z]+/", $pass) ) {
                $errno++;
                $this->_error(self::PASS_LOWERCASE);
            }
        }
        if ( true == Config_Fisma::readSysConfig('pass_numerical')) {
            if ( false == preg_match("/[0-9]+/", $pass) ) {
                $errno++;
                $this->_error(self::PASS_NUMERICAL);
            }
        }
        if ( true == Config_Fisma::readSysConfig('pass_special')) {
            if ( false == preg_match("/[^0-9a-zA-Z]+/", $pass) ) {
                $errno++;
                $this->_error(self::PASS_SPECIAL);
            }
        }
        if ($errno > 0) {
            return false;
        } else {
            return true;
        }
    }
}
